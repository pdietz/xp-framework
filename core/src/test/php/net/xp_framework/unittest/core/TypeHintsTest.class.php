<?php
/* This class is part of the XP framework
 *
 * $Id$
 */

  uses('unittest.TestCase');
  
  /**
   * Test type hints. Uses a simple inline declared class with the 
   * following methods:
   *
   * <code>
   *   passObject(Generic $o)
   *   passNullable(Generic $o= NULL)
   * </code>
   *
   * Both of these static methods simply return the value passed to 
   * them.
   */
  class TypeHintsTest extends TestCase {
  
    /**
     * Defines fixture
     */
    #[@beforeClass]
    public static function defineTypeHintedClass() {
      ClassLoader::defineClass('net.xp_framework.unittest.core.TypeHintedClass', 'lang.Object', array(), '{
        public static function passObject(Generic $o) { return $o; }
        public static function passNullable(Generic $o= NULL) { return $o; }
      }');
    }

    /**
     * Tests passing an object to passObject()
     *
     */
    #[@test]
    public function passObject() {
      $o= new Object();
      $this->assertEquals($o, TypeHintedClass::passObject($o));
    }

    /**
     * Tests passing a primitive to passObject() raises an exception.
     *
     */
    #[@test, @expect('lang.IllegalArgumentException')]
    public function passPrimitive() {
      TypeHintedClass::passObject(1);
    }

    /**
     * Tests passing NULL to passObject() raises an exception.
     *
     */
    #[@test, @expect('lang.IllegalArgumentException')]
    public function passNull() {
      TypeHintedClass::passObject(NULL);
    }

    /**
     * Tests passing an object to passNullable()
     *
     */
    #[@test]
    public function passObjectNullable() {
      $o= new Object();
      $this->assertEquals($o, TypeHintedClass::passNullable($o));
    }

    /**
     * Tests passing a primitive to passNullable() raises an exception.
     *
     */
    #[@test, @expect('lang.IllegalArgumentException')]
    public function passPrimitiveNullable() {
      TypeHintedClass::passNullable(1);
    }

    /**
     * Tests passing NULL to passNullable() does not raise an exception
     *
     */
    #[@test]
    public function passNullNullable() {
      $this->assertEquals(NULL, TypeHintedClass::passNullable(NULL));
    }
  }
?>
