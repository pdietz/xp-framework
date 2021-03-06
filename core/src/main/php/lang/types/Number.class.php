<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  /**
   * The abstract class Number is the superclass of classes representing
   * numbers
   *
   * @test  xp://net.xp_framework.unittest.core.types.NumberTest
   */
  abstract class Number extends Object {
    public $value = '';
    
    /**
     * Constructor
     *
     * @param   string value
     */
    public function __construct($value) {
      $this->value= (string)$value;
    }

    /**
     * ValueOf factory
     *
     * NOTE: We don't use a base-class version with "new static" here because
     * in PHP 5.3.3 there is a bug with the class resolution when this method
     * is invoked via reflection.
     *
     * @see     https://github.com/xp-framework/xp-framework/issues/293
     * @param   string $value
     * @return  self
     * @throws  lang.IllegalArgumentException
     */
    public static function valueOf($value) {
      raise('lang.MethodNotImplementedException', 'Abstract base class', __METHOD__);
    }

    /**
     * Returns the value of this number as an int.
     *
     * @return  int
     */
    public function intValue() {
      return $this->value + 0;
    }

    /**
     * Returns the value of this number as a double.
     *
     * @deprecated Inconsistent with XP type system - use doubleValue() instead
     * @return  double
     */
    public function floatValue() {
      return $this->doubleValue();
    }

    /**
     * Returns the value of this number as a float.
     *
     * @return  double
     */
    public function doubleValue() {
      return $this->value + 0.0;
    }
    
    /**
     * Returns a hashcode for this number
     *
     * @return  string
     */
    public function hashCode() {
      return $this->value;
    }

    /**
     * Returns a string representation of this number object
     *
     * @return  string
     */
    public function toString() {
      return $this->getClassName().'('.$this->value.')';
    }
    
    /**
     * Indicates whether some other object is "equal to" this one.
     *
     * @param   lang.Object cmp
     * @return  bool TRUE if the compared object is equal to this object
     */
    public function equals($cmp) {
      return $cmp instanceof $this && $this->value === $cmp->value;
    }
  }
?>
