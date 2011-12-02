<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  /**
   * Abstract base class for TDS records
   *
   */
  abstract class TdsRecord extends Object {
    protected static $precision;

    static function __static() {
      self::$precision= ini_get('precision');
    }

    /**
     * Convert lo and hi values to money value
     *
     * @param   int lo
     * @param   int hi
     * @return  string
     */
    protected function toMoney($lo, $hi) {
      if ($hi < 0) {
        $hi= ~$hi;
        $lo= ~($lo - 1);
        $div= -10000;
      } else {
        $div= 10000;
      }
      return bcdiv(bcadd(bcmul($hi, '4294967296'), $lo), $div, 5);
    }

    /**
     * Convert to number
     *
     * @param   string n
     * @param   int scale
     * @param   int prec
     * @return  var
     */
    protected function toNumber($n, $scale, $prec) {
      if (0 === $scale) {
        return $n > LONG_MAX || $n < LONG_MIN ? $n : (int)$n;
      } else {
        $n= bcdiv($n, pow(10, $scale, $prec));
        return strlen($n) > self::$precision ? $n : (double)$n;
      }
    }
    
    /**
     * Unmarshal from a given stream
     *
     * @param   rdbms.tds.TdsDataStream stream
     * @param   [:var] field
     * @return  var
     */
    public abstract function unmarshal($stream, $field);
  }
?>
