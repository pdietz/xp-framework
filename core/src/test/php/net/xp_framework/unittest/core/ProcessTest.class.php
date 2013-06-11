<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses(
    'unittest.TestCase',
    'lang.Runtime',
    'lang.System',
    'lang.Process',
    'io.streams.Streams',
    'io.streams.MemoryOutputStream'
  );

  /**
   * TestCase for Process class
   *
   * @see   xp://lang.Process
   */
  class ProcessTest extends TestCase {

    /**
     * Skips tests if process execution has been disabled.
     */
    #[@beforeClass]
    public static function verifyProcessExecutionEnabled() {
      if (Process::$DISABLED) {
        throw new PrerequisitesNotMetError('Process execution disabled', NULL, array('enabled'));
      }
    }
  
    /**
     * Return executable name
     *
     * @return  string
     */
    protected function executable() {
      return Runtime::getInstance()->getExecutable()->getFilename();
    }
  
    /**
     * Test process status information methods
     *
     * @see      xp://lang.Process#getProcessId
     * @see      xp://lang.Process#getFilename
     * @see      xp://lang.Process#getCommandLine
     * @see      xp://lang.Process#exitValue
     */
    #[@test]
    public function information() {
      $p= new Process($this->executable(), array('-v'));
      try {
        $this->assertEquals(-1, $p->exitValue(), 'Process should not have exited yet');
        $this->assertNotEquals(0, $p->getProcessId());
        $this->assertNotEquals('', $p->getFilename());
        $this->assertTrue(create(new String($p->getCommandLine()))->contains('-v'));
        $p->close();
      } catch (AssertionFailedError $e) {
        $p->close();    // Ensure process is closed
        throw $e;
      }
    }

    /**
     * Tests Process::newInstance()
     *
     */
    #[@test]
    public function newInstance() {
      $p= Runtime::getInstance()->getExecutable()->newInstance(array('-v'));
      $version= 'PHP '.phpversion();
      $this->assertEquals($version, $p->out->read(strlen($version)));
      $p->close();
    }

    /**
     * Test exit value
     *
     */
    #[@test]
    public function exitValueReturnedFromClose() {
      $p= new Process($this->executable(), array('-r', 'exit(0);'));
      $this->assertEquals(0, $p->close());
    }

    /**
     * Test non-zero exit value
     *
     */
    #[@test]
    public function nonZeroExitValueReturnedFromClose() {
      $p= new Process($this->executable(), array('-r', 'exit(2);'));
      $this->assertEquals(2, $p->close());
    }

    /**
     * Test exit value
     *
     */
    #[@test]
    public function exitValue() {
      $p= new Process($this->executable(), array('-r', 'exit(0);'));
      $p->close();
      $this->assertEquals(0, $p->exitValue());
    }

    /**
     * Test non-zero exit value
     *
     */
    #[@test]
    public function nonZeroExitValue() {
      $p= new Process($this->executable(), array('-r', 'exit(2);'));
      $p->close();
      $this->assertEquals(2, $p->exitValue());
    }

    /**
     * Test standard input
     *
     */
    #[@test]
    public function stdIn() {
      $p= new Process($this->executable(), array('-r', 'fprintf(STDOUT, fread(STDIN, 0xFF));'));
      $p->in->write('IN');
      $p->in->close();
      $out= $p->out->read();
      $p->close();
      $this->assertEquals('IN', $out);
    }

    /**
     * Test standard output
     *
     */
    #[@test]
    public function stdOut() {
      $p= new Process($this->executable(), array('-r', 'fprintf(STDOUT, "OUT");'));
      $out= $p->out->read();
      $p->close();
      $this->assertEquals('OUT', $out);
    }

    /**
     * Test standard error
     *
     */
    #[@test]
    public function stdErr() {
      $p= new Process($this->executable(), array('-r', 'fprintf(STDERR, "ERR");'));
      $err= $p->err->read();
      $p->close();
      $this->assertEquals('ERR', $err);
    }

    /**
     * Test running a non-existant file
     *
     */
    #[@test, @expect('io.IOException')]
    public function runningNonExistantFile() {
      new Process(':FILE_DOES_NOT_EXIST:');
    }

    /**
     * Test running a directory (System::tempDir() used as argument)
     *
     */
    #[@test, @expect('io.IOException')]
    public function runningDirectory() {
      new Process(System::tempDir());
    }

    /**
     * Test running an empty command ("")
     *
     */
    #[@test, @expect('io.IOException')]
    public function runningEmpty() {
      new Process('');
    }

    /**
     * Test getProcessById() method
     *
     */
    #[@test, @expect('lang.IllegalStateException')]
    public function nonExistantProcessId() {
      Process::getProcessById(-1);
    }

    /**
     * Test getProcessById() method
     *
     */
    #[@test]
    public function getByProcessId() {
      $pid= getmypid();
      $p= Process::getProcessById($pid);
      $this->assertInstanceOf('lang.Process', $p);
      $this->assertEquals($pid, $p->getProcessId());
    }

    /**
     * Test invoking close() method twice
     *
     */
    #[@test]
    public function doubleClose() {
      $p= new Process($this->executable(), array('-r', 'exit(222);'));
      $this->assertEquals(222, $p->close());
      $this->assertEquals(222, $p->close());
    }

    /**
     * Test close() method on a process retrieved by getProcessById()
     *
     */
    #[@test, @expect(class= 'lang.IllegalStateException', withMessage= '/Cannot close not-owned/')]
    public function closingProcessByProcessId() {
      Process::getProcessById(getmypid())->close();
    }

    /**
     * Test huge STDOUT output
     *
     */
    #[@test]
    public function hugeStdout() {
      $p= new Process($this->executable(), array('-r', 'fputs(STDOUT, str_repeat("*", 65536));'));
      $out= '';
      while (!$p->out->eof()) {
        $out.= $p->out->read();
      }
      $p->close();
      $this->assertEquals(65536, strlen($out));
    }

    /**
     * Test huge STDERR output
     *
     */
    #[@test]
    public function hugeStderr() {
      $p= new Process($this->executable(), array('-r', 'fputs(STDERR, str_repeat("*", 65536));'));
      $err= '';
      while (!$p->err->eof()) {
        $err.= $p->err->read();
      }
      $p->close();
      $this->assertEquals(65536, strlen($err));
    }
  }
?>
