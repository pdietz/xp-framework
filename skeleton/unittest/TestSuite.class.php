<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses(
    'util.profiling.Timer',
    'unittest.TestCase',
    'unittest.TestResult',
    'unittest.TestListener',
    'util.NoSuchElementException',
    'lang.MethodNotImplementedException'
  );

  /**
   * Test suite
   *
   * Example:
   * <code>
   *   uses(
   *     'unittest.TestSuite', 
   *     'net.xp_framework.unittest.rdbms.DBTest'
   *   );
   *   
   *   $suite= new TestSuite();
   *   $suite->addTest(new DBTest('testConnect'));
   *   $suite->addTest(new DBTest('testSelect'));
   *   
   *   echo $suite->run()->toString();
   * </code>
   *
   * @test     xp://net.xp_framework.unittest.tests.SuiteTest
   * @see      http://junit.sourceforge.net/doc/testinfected/testing.htm
   * @purpose  Testcase container
   */
  class TestSuite extends Object {
    public
      $tests     = array();

    protected
      $listeners = array();

    /**
     * Add a test
     *
     * @param   unittest.TestCase test
     * @return  unittest.TestCase
     * @throws  lang.IllegalArgumentException in case given argument is not a testcase
     */
    public function addTest(TestCase $test) {
      $this->tests[]= $test;
      return $test;
    }

    /**
     * Add a test class
     *
     * @param   lang.XPClass<unittest.TestCase> class
     * @param   mixed[] arguments default [] arguments to pass to test case constructor
     * @return  lang.reflect.Method[] ignored test methods
     * @throws  lang.IllegalArgumentException in case given argument is not a testcase class
     * @throws  util.NoSuchElementException in case given testcase class does not contain any tests
     */
    public function addTestClass($class, $arguments= array()) {
      if (!$class->isSubclassOf('unittest.TestCase')) {
        throw new IllegalArgumentException('Given argument is not a TestCase class ('.xp::stringOf($class).')');
      }

      $ignored= array();
      $numBefore= $this->numTests();
      foreach ($class->getMethods() as $m) {
        if (!$m->hasAnnotation('test')) continue;
        if ($m->hasAnnotation('ignore')) $ignored[]= $m;

        // Add test method
        $this->addTest(call_user_func_array(array($class, 'newInstance'), array_merge(
          (array)$m->getName(TRUE),
          $arguments
        )));
      }

      if ($numBefore === $this->numTests()) {
        throw new NoSuchElementException('No tests found in '.$class->getName());
      }

      return $ignored;
    }
    
    /**
     * Returns number of tests in this suite
     *
     * @return  int
     */
    public function numTests() {
      return sizeof($this->tests);
    }
    
    /**
     * Remove all tests
     *
     */
    public function clearTests() {
      $this->tests= array();
    }
    
    /**
     * Returns test at a given position
     *
     * @param   int pos
     * @return  unittest.TestCase or NULL if none was found
     */
    public function testAt($pos) {
      if (isset($this->tests[$pos])) return $this->tests[$pos]; else return NULL;
    }
    
    /**
     * Adds a listener
     *
     * @param   unittest.TestListener l
     * @return  unittest.TestListener the added listener
     */
    public function addListener(TestListener $l) {
      $this->listeners[]= $l;
      return $l;
    }

    /**
     * Removes a listener
     *
     * @param   unittest.TestListener l
     * @return  bool TRUE if the listener was removed, FALSE if not.
     */
    public function removeListener(TestListener $l) {
      for ($i= 0, $s= sizeof($this->listeners); $i < $s; $i++) {
        if ($this->listeners[$i] !== $l) continue;

        // Found the listener, remove it and re-index the listeners array
        unset($this->listeners[$i]);
        $this->listeners= array_values($this->listeners);
        return TRUE;
      }
      return FALSE;
    }

    /**
     * Run a test case.
     *
     * @param   unittest.TestCase test
     * @param   unittest.TestResult result
     * @throws  lang.MethodNotImplementedException
     */
    protected function runInternal($test, $result) {
      if (!($method= $test->getClass()->getMethod($test->name))) {
        throw new MethodNotImplementedException('Method does not exist', $test->name);
      }

      $this->notifyListeners('testStarted', array($test));
      
      // Check for @ignore
      if ($method->hasAnnotation('ignore')) {
        $this->notifyListeners('testSkipped', array(
          $result->setSkipped($test, $method->getAnnotation('ignore'), 0.0)
        ));
        return;
      }

      // Check for @expect
      $expected= NULL;
      if ($method->hasAnnotation('expect')) {
        $expected= XPClass::forName($method->getAnnotation('expect'));
      }
      
      // Check for @limit
      $eta= 0;
      if ($method->hasAnnotation('limit')) {
        $eta= $method->getAnnotation('limit', 'time');
      }

      $timer= new Timer();
      $timer->start();

      // Setup test
      try {
        $test->setUp();
      } catch (PrerequisitesNotMetError $e) {
        $timer->stop();
        $this->notifyListeners('testSkipped', array(
          $result->setSkipped($test, $e, $timer->elapsedTime())
        ));
        return;
      } catch (AssertionFailedError $e) {
        $timer->stop();
        $this->notifyListeners('testFailed', array(
          $result->setFailed($test, $e, $timer->elapsedTime())
        ));
        return;
      }

      // Run test
      try {
        $method->invoke($test, NULL);
      } catch (TargetInvocationException $t) {
        $timer->stop();
        $e= $t->getCause();

        // Was that an expected exception?
        if ($expected && $expected->isInstance($e)) {
          $test->tearDown();
          if ($eta && $timer->elapsedTime() > $eta) {
            $this->notifyListeners('testFailed', array(
              $result->setFailed($test, new AssertionFailedError('Timeout', sprintf('%.3f', $timer->elapsedTime()), sprintf('%.3f', $eta)), $timer->elapsedTime())
            ));
          } else {
            $this->notifyListeners('testSucceeded', array(
              $result->setSucceeded($test, $timer->elapsedTime())
            ));
          }
          xp::gc();
          return;
        }

        $this->notifyListeners('testFailed', array(
          $result->setFailed($test, $e, $timer->elapsedTime())
        ));
        $test->tearDown();
        return;
      }

      $timer->stop();
      $test->tearDown();

      // Check expected exception
      if ($expected) {
        $e= new AssertionFailedError(
          'Expected exception not caught',
          (isset($e) && $e instanceof XPException ? $e->getClassName() : NULL),
          $method->getAnnotation('expect')
        );
        $this->notifyListeners('testFailed', array(
          $result->setFailed($test, $e, $timer->elapsedTime())
        ));
        return;
      }
      
      if (sizeof(xp::registry('errors')) > 0) {
        $this->notifyListeners('testFailed', array(
          $result->setFailed($test, new AssertionFailedError('Errors', '<Non-clean error stack>', '<no errors>'), $timer->elapsedTime())
        ));
      } else if ($eta && $timer->elapsedTime() > $eta) {
        $this->notifyListeners('testFailed', array(
          $result->setFailed($test, new AssertionFailedError('Timeout', sprintf('%.3f', $timer->elapsedTime()), sprintf('%.3f', $eta)), $timer->elapsedTime())
        ));
      } else {
        $this->notifyListeners('testSucceeded', array(
          $result->setSucceeded($test, $timer->elapsedTime())
        ));
      }
      xp::gc();
    }
    
    /**
     * Notify listeners
     *
     * @param   string method
     * @param   mixed[] args
     */
    protected function notifyListeners($method, $args) {
      foreach ($this->listeners as $l) {
        call_user_func_array(array($l, $method), $args);
      }
    }
    
    /**
     * Run a single test
     *
     * @param   unittest.TestCase test
     * @return  unittest.TestResult
     */
    public function runTest(TestCase $test) {
      $this->notifyListeners('testRunStarted', array($this));
      
      // Run the single test case
      $result= new TestResult();
      $this->runInternal($test, $result);

      $this->notifyListeners('testRunFinished', array($this, $result));
      return $result;
    }
    
    /**
     * Run this test suite
     *
     * @return  unittest.TestResult
     */
    public function run() {
      $this->notifyListeners('testRunStarted', array($this));

      $result= new TestResult();
      for ($i= 0, $s= sizeof($this->tests); $i < $s; $i++) {
        $this->runInternal($this->tests[$i], $result);
      }

      $this->notifyListeners('testRunFinished', array($this, $result));
      return $result;
    }
  }
?>
