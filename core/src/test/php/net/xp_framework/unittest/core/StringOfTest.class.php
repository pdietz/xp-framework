<?php
/* This class is part of the XP framework
 *
 * $Id$
 */

  uses('unittest.TestCase');

  /**
   * Tests the xp::stringOf() core utility
   *
   * @purpose  Testcase
   */
  class StringOfTest extends TestCase {

    /**
     * Returns a class with a toString() method that always returns the following:
     * <pre>
     *   TestString(6) { String }
     * </pre>
     *
     * @return  lang.Object
     */
    protected function testStringInstance() {
      return newinstance('lang.Object', array(), '{
        function toString() {
          return "TestString(6) { String }";
        }
      }');
    }

    /**
     * Tests string argument
     *
     */
    #[@test]
    public function stringArgument() {
      $this->assertEquals('"Hello"', xp::stringOf('Hello'));
    }

    /**
     * Tests boolean argument
     *
     */
    #[@test]
    public function booleanArgument() {
      $this->assertEquals('true', xp::stringOf(TRUE));
      $this->assertEquals('false', xp::stringOf(FALSE));
    }

    /**
     * Tests null argument
     *
     */
    #[@test]
    public function nullArgument() {
      $this->assertEquals('null', xp::stringOf(NULL));
    }

    /**
     * Tests xp::null() argument
     *
     * @see     xp://net.xp_framework.unittest.core.NullTest
     */
    #[@test]
    public function xpNullArgument() {
      $this->assertEquals('<null>', xp::stringOf(xp::null()));
    }

    /**
     * Tests numbers
     *
     * @see     xp://net.xp_framework.unittest.core.NullTest
     */
    #[@test]
    public function numericArgument() {
      $this->assertEquals('1', xp::stringOf(1));
      $this->assertEquals('-1', xp::stringOf(-1));
      $this->assertEquals('1.5', xp::stringOf(1.5));
      $this->assertEquals('-1.5', xp::stringOf(-1.5));
    }

    /**
     * Tests an object argument
     *
     */
    #[@test]
    public function objectArgument() {
      $this->assertEquals('TestString(6) { String }', xp::stringOf($this->testStringInstance()));
    }

    /**
     * Tests simple array
     *
     */
    #[@test]
    public function simpleArrayArgument() {
      $this->assertEquals(
        "[\n  0 => 1\n  1 => 2\n  2 => 3\n]", 
        xp::stringOf(array(1, 2, 3))
      );
    }

    /**
     * Tests array of arrays
     *
     */
    #[@test]
    public function arrayOfArraysArgument() {
      $this->assertEquals(
        "[\n  0 => [\n    0 => 1\n    1 => 2\n    2 => 3\n  ]\n]", 
        xp::stringOf(array(array(1, 2, 3)))
      );
    }

    /**
     * Tests simple array
     *
     */
    #[@test]
    public function hashmapArgument() {
      $this->assertEquals(
        "[\n  foo => \"bar\"\n  bar => 2\n  baz => TestString(6) { String }\n]", 
        xp::stringOf(array(
          'foo' => 'bar', 
          'bar' => 2, 
          'baz' => $this->testStringInstance()
        ))
      );
    }

    /**
     * Tests stdClass and Directory builtin classes in PHP
     *
     */
    #[@test]
    public function builtinObjectsArgument() {
      $this->assertEquals("php.stdClass {\n}", xp::stringOf(new stdClass()));
      $this->assertEquals("php.Directory {\n}", xp::stringOf(new Directory('.')));
    }

    /**
     * Tests resource
     *
     */
    #[@test]
    public function resourceArgument() {
      $fd= fopen('php://stdin', 'r');
      $this->assertTrue((bool)preg_match('/resource\(type= stream, id= [0-9]+\)/', xp::stringOf($fd)));
      fclose($fd);
    }

    /**
     * Tests recursion within an array
     *
     */
    #[@test]
    public function arrayRecursion() {
      $a= array();
      $a[0]= 'Outer array';
      $a[1]= array();
      $a[1][0]= 'Inner array';
      $a[1][1]= &$a;
      $this->assertEquals('[
  0 => "Outer array"
  1 => [
    0 => "Inner array"
    1 => ->{:recursion:}
  ]
]', 
      xp::stringOf($a));
    }

    /**
     * Tests recursion within an array
     *
     */
    #[@test]
    public function objectRecursion() {
      $o= new stdClass();
      $o->child= new stdClass();
      $o->child->parent= $o;
      $this->assertEquals('php.stdClass {
  child => php.stdClass {
    parent => ->{:recursion:}
  }
}',
      xp::stringOf($o));
    }

    /**
     * Tests a situation where no recursion exists
     *
     */
    #[@test]
    public function noRecursion() {
      $test= newinstance('lang.Object', array(), '{
        public function toString() {
          return "Test";
        }
      }');
      $this->assertEquals(
        "[\n  a => Test\n  b => Test\n]", 
        xp::stringOf(array(
          'a' => $test,
          'b' => $test
        ))
      );
    }
    
    /**
     * Tests objects with very large hashcodes don't produce problems
     * in the recursion detection algorithm.
     *
     */
    #[@test]
    public function noRecursionWithLargeNumbers() {
      $test= newinstance('lang.Object', array(), '{
        public function hashCode() {
          return 9E100;
        }
        
        public function toString() {
          return "Test";
        }
      }');
      $this->assertEquals(
        "[\n  a => Test\n  b => Test\n]", 
        xp::stringOf(array(
          'a' => $test,
          'b' => $test
        ))
      );
    }

    /**
     * Tests toString() isn't invoked recursively by sourcecode such as:
     * <code>
     *   class MaliciousRecursionGenerator extends Object {
     *     function toString() {
     *       return xp::stringOf($this);
     *     }
     *   }
     *
     *   echo xp::stringOf(new MaliciousRecursionGenerator());
     * </code>
     *
     */
    #[@test]
    public function toStringRecursion() {
      $test= newinstance('lang.Object', array(), '{
        public function toString() {
          return xp::stringOf($this);
        }
      }');
      $this->assertEquals(
        $test->getClassName()." {\n  __id => \"".$test->hashCode()."\"\n}",
        xp::stringOf($test)
      );
    }
    
    /**
     * Test repeated xp::stringOf invokations on the same object
     *
     */
    #[@test]
    public function repeatedCalls() {
      $object= new Object();
      $stringRep= $object->toString();
      
      $this->assertEquals($stringRep, xp::stringOf($object), 'first');
      $this->assertEquals($stringRep, xp::stringOf($object), 'second');
    }

    /**
     * Test indentations
     *
     */
    #[@test]
    public function indenting() {
      $cl= ClassLoader::defineClass('net.xp_framework.unittest.core.StringOfTest_IndentingFixture', 'lang.Object', array(), '{
        protected $inner= NULL;
        public function __construct($inner) {
          $this->inner= $inner;
        }
        public function toString() {
          return "object {\n  ".xp::stringOf($this->inner, "  ")."\n}";
        }
      }');
      $this->assertEquals(
        "object {\n  object {\n    null\n  }\n}",
        $cl->newInstance($cl->newInstance(NULL))->toString()
      );
    }
  }
?>
