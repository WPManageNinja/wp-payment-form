<?php

namespace WPPayForm\Framework\Exception;

class ExceptionHandler
{
  const APPEND_TO_LOG_FILE = 3;

  /**
   * framework\App\Application
   * @var Object
   */
  protected $app = null;

  public function __construct($app)
  {
    $this->app = $app;
    // $this->registerHandlers();
  }

	public function registerHandlers()
	{
		error_reporting(-1);
    set_error_handler([$this, 'handleError']);
    set_exception_handler([$this, 'handleException']);
    register_shutdown_function([$this, 'handleShutdown']);
	}

	public function handleError($severity, $message, $file = '', $line = 0)
  {
    try {
      if (error_reporting() & $severity) {
          throw new \ErrorException($message, 0, $severity, $file, $line);
      }
    } catch(\Exception $e) {
      $this->handleException($e);
    }
  }

  public function handleException($e)
  {
    try {
      if ($this->app->getEnv() != 'production') {
        $this->report($e);
        $this->render($e);
      }
    } catch (\Exception $e) {
        wp_die(
          '<pre>'
          . $e->getMessage().' : '.$e->getFile().' ('.$e->getLine().')' .
          '</pre>'
        );
    }
  }

    public function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
					$this->handleException(new \ErrorException(
						$error['message'], 0, $error['type'], $error['file'], $error['line']
					));
        }
    }

    public function report($e)
    {
        $logDir = $this->app->storagePath('logs');

        if (!is_readable($logDir)) {
          mkdir($logDir, 0777);
        }

        //Log in: wp-content/debug.log
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
          error_log((string) $e);
        }

        //Log in: plugin-root-dir/storage/logs/error.log
        error_log(
					'['.current_time('mysql').'] ' . (string) $e,
					self::APPEND_TO_LOG_FILE,
					$logDir.'/error.log'
        );
    }

    public function render($e)
    {
      echo get_class($e) .' : '. $e->getMessage() . ' in ' . $e->getFile() . ' (' . $e->getLine() . ')';
      echo '<br><pre>' . str_replace("\n", '<br>', $e->getTraceAsString()) . '</pre>';
    }

    protected function isFatal($type)
    {
      return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }
}
