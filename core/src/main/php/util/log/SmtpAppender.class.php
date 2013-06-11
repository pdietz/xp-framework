<?php
/* This class is part of the XP framework
 *
 * $Id$
 */

  uses('util.log.Appender');

  /**
   * Appender which sends log to an email address
   *
   * @see   xp://util.log.Appender
   * @test  xp://net.xp_framework.unittest.logging.SmtpAppenderTest
   */  
  class SmtpAppender extends Appender {
    public 
      $email    = '',
      $prefix   = '',
      $sync     = TRUE;
      
    public
      $_data    = array();
    
    /**
     * Constructor
     *
     * @param   string email the email address to send log entries to
     * @param   string prefix
     * @param   bool sync default TRUE
     */
    public function __construct($email= NULL, $prefix= '', $sync= TRUE) {
      $this->email= $email;
      $this->prefix= $prefix;
      $this->sync= $sync;
    }
    
    /**
     * Destructor
     *
     */
    public function __destruct() {
      $this->finalize();
    }

    /**
     * Sends email
     *
     * @param  string $prefix
     * @param  string $content
     */
    protected function send($prefix, $content) {
      mail($this->email, $prefix, $content);
    }
    
    /**
     * Append data
     *
     * @param   util.log.LoggingEvent event
     */ 
    public function append(LoggingEvent $event) {
      $body= $this->layout->format($event);
      if ($this->sync) {
        $this->send($this->prefix, $body);
      } else {
        $this->_data[]= $body;
      }
    }
    
    /**
     * Finalize this appender - is called when the logger shuts down
     * at the end of the request.
     *
     */
    public function finalize() {
      if ($this->sync || 0 == sizeof($this->_data)) return;
      
      $body= '';
      foreach ($this->_data as $line) {
        $body.= $line."\n";
      }

      $this->send($this->prefix.' ['.(sizeof($this->_data)).' entries]', $body);
    }
  }
?>
