 <?php
 
  // A standard method that can be used for logging messages

  if ( defined('ISDEBUGLOGGING') && !ISDEBUGLOGGING ) return; // Set this contstant to control logging

  if (!is_dir($this->_logDir)) {
    mkdir($this->_logDir);
  }
  $token = ( isset($_SESSION['securityToken']) ? $_SESSION['securityToken'] : zen_create_random_value(6) . time() );
  $file = $this->_logDir . '/' . date('Y-m-d') . ' '. $this->code . '_' . $token . '.log';
  $backtrace = debug_backtrace();

  if ( is_array($message) ) $message = print_r($message,true);
  if ( is_object($message) ) $message = var_export($message,true);

  $fp = @fopen($file, 'a');
  @fwrite($fp, date('Y-m-d H:i:s') . ' Stage: ' . $stage . ' From: ' . $backtrace[0]['file'] . ' Line: ' . $backtrace[0]['line']  . ' Time: ' . time() . PHP_EOL . '    ' . $message . PHP_EOL . PHP_EOL);

  if ( $doBackTrace ) {
    @fwrite($fp, '    BACKTRACE - ' . $stage . PHP_EOL);
    foreach ( $backtrace as $call )  {
      @fwrite($fp, '     ' . $call['file'] . ' Line: ' . $call['line'] . PHP_EOL);
    }
    @fwrite($fp, PHP_EOL);

    @fclose($fp);
  }
