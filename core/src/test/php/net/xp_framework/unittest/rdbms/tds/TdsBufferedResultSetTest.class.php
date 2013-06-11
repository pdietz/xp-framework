<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses(
    'unittest.TestCase',
    'rdbms.tds.TdsBufferedResultSet',
    'rdbms.tds.TdsProtocol'
  );

  /**
   * TestCase
   *
   * @see   xp://rdbms.tds.TdsBufferedResultSet
   */
  class TdsBufferedResultSetTest extends TestCase {
    protected static $proto;
  
    /**
     * Defines the mock socket class necessary for these tests
     *
     */
    #[@beforeClass]
    public static function mockSocket() {
      self::$proto= ClassLoader::defineClass('net.xp_framework.unittest.rdbms.tds.MockTdsProtocol', 'lang.Object', array(), '{
        protected $records= array();

        public function __construct($records) {
          $this->records= $records;
        }

        public function fetch($fields) {
          return array_shift($this->records);
        }
      }');
    }

    /**
     * Creates a new result set fixture
     *
     * @param  [:var][] $result [description]
     * @return rdbms.tds.TdsBufferedResultSet
     */
    protected function newResultSet($result) {
      $records= array();
      if ($result) {
        foreach ($result[0] as  $name => $value) {
          $fields[]= array(
            'name'  => $name, 
            'type'  => is_int($value) ? TdsProtocol::T_INT4 : TdsProtocol::T_VARCHAR
          );
        }
        foreach ($result as $hash) {
          $records[]= array_values($hash);
        }
      } else {
        $fields= array();
      }

      return new TdsBufferedResultSet(self::$proto->newInstance($records), $fields);
    }
  
    /**
     * Test constructor
     */
    #[@test]
    public function can_create_with_empty() { 
      $this->newResultSet(array());
    }

    /**
     * Test constructor
     */
    #[@test]
    public function can_create() { 
      $this->newResultSet(array(
        array(
          'id'   => 6100,
          'name' => 'Binford'
        )
      ));
    }

    /**
     * Test next()
     */
    #[@test]
    public function next() { 
      $records= array(
      );
      $fixture= $this->newResultSet($records);
      $this->assertFalse($fixture->next());
    }

    /**
     * Test next()
     */
    #[@test]
    public function next_once() { 
      $records= array(
        array(
          'id'   => 6100,
          'name' => 'Binford'
        )
      );
      $fixture= $this->newResultSet($records);
      $this->assertEquals($records[0], $fixture->next());
    }

    /**
     * Test next()
     */
    #[@test]
    public function next_twice() { 
      $records= array(
        array(
          'id'   => 6100,
          'name' => 'Binford Lawnmower'
        ),
        array(
          'id'   => 61000,
          'name' => 'Binford Moonrocket'
        )
      );
      $fixture= $this->newResultSet($records);
      $this->assertEquals($records[0], $fixture->next());
      $this->assertEquals($records[1], $fixture->next());
    }

    /**
     * Test next()
     */
    #[@test]
    public function next_returns_false_at_end() { 
      $records= array(
        array(
          'id'   => 6100,
          'name' => 'Binford Lawnmower'
        ),
      );
      $fixture= $this->newResultSet($records);
      $fixture->next();
      $this->assertFalse($fixture->next());
    }

    /**
     * Test seek()
     */
    #[@test]
    public function seek_to_0_before_start() {
      $records= array(
        array(
          'id'   => 6100,
          'name' => 'Binford Lawnmower'
        )
      );
      $fixture= $this->newResultSet($records);
      $fixture->seek(0);
      $this->assertEquals($records[0], $fixture->next());
    }

    /**
     * Test seek()
     */
    #[@test]
    public function seek_to_0_after_start() {
      $records= array(
        array(
          'id'   => 6100,
          'name' => 'Binford Lawnmower'
        )
      );
      $fixture= $this->newResultSet($records);
      $fixture->next();
      $fixture->seek(0);
      $this->assertEquals($records[0], $fixture->next());
    }

    /**
     * Test seek()
     */
    #[@test]
    public function seek_to_1() {
      $records= array(
        array(
          'id'   => 6100,
          'name' => 'Binford Lawnmower'
        ),
        array(
          'id'   => 61000,
          'name' => 'Binford Moonrocket'
        )
      );
      $fixture= $this->newResultSet($records);
      $fixture->seek(1);
      $this->assertEquals($records[1], $fixture->next());
    }

    /**
     * Test seek()
     */
    #[@test, @expect(class= 'rdbms.SQLException', withMessage= 'Cannot seek to offset 1, out of bounds')]
    public function seek_to_offset_exceeding_length() {
      $fixture= $this->newResultSet(array())->seek(1);
    }

    /**
     * Test seek()
     */
    #[@test, @expect(class= 'rdbms.SQLException', withMessage= 'Cannot seek to offset -1, out of bounds')]
    public function seek_to_negative_offset() {
      $fixture= $this->newResultSet(array())->seek(-1);
    }

    /**
     * Test seek()
     */
    #[@test, @expect(class= 'rdbms.SQLException', withMessage= 'Cannot seek to offset 0, out of bounds')]
    public function seek_to_zero_offset_on_empty() {
      $fixture= $this->newResultSet(array())->seek(0);
    }
  }
?>
