<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */
 
  uses('security.checksum.Checksum', 'io.FileUtil');
  
  /**
   * CRC16 checksum [CRC-16 (Modbus)]
   *
   * @see      xp://security.checksum.Checksum
   * @purpose  Provide an API to check CRC16 checksums
   * @see      http://en.wikipedia.org/wiki/Cyclic_redundancy_check
   */
  class CRC16 extends Checksum {

    /**
     * Constructor
     *
     * @param   var value
     */
    public function __construct($value) {
      if (is_int($value)) {
        parent::__construct(sprintf('%04x', $value)); 
      } else {
        parent::__construct($value);
      }
    }
  
    /**
     * Create a new checksum from a string
     *
     * @param   string str
     * @return  security.checksum.CRC16
     */
    public static function fromString($str) {
      $sum= 0xFFFF;
      for ($x= 0, $s= strlen($str); $x < $s; $x++) {
        $sum= $sum ^ ord($str{$x});
        for ($i= 0; $i < 8; $i++) {
          $sum= (1 === ($sum & 1) ? ($sum >> 1) ^ 0xA001 : $sum >> 1);
        }
      }
      return new CRC16($sum);
    }

    /**
     * Returns message digest
     *
     * @return  security.checksum.MessageDigestImpl
     */
    public static function digest() {
      return MessageDigest::newInstance('crc16');
    }

    /**
     * Create a new checksum from a file object
     *
     * @param   io.File file
     * @return  security.checksum.CRC16
     */
    public static function fromFile($file) {
      return CRC16::fromString(FileUtil::getContents($file));
    }
  }
?>
