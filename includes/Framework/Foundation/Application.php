<?php

/**
 * This file is part of the Glue WordPress plugin framework.
 *
 * @package   Glue
 * @link      https://github.com/wpglue
 * @author    Sheikh Heera <heera.sheikh77@gmail.com>
 * @license   https://github.com/wpglue/framework/blob/master/LICENSE
 */

namespace WPPayForm\Framework\Foundation;

use WPPayForm\Classes\Framework\Exception\ExceptionHandler;

class Application extends Container
{
	use PathsAndUrlsTrait, SetGetAttributesTrait, FacadeLoaderTrait, HelpersTrait;

	/**
	 * Framework Version
	 */
	const VERSION = '1.0.0';

	/**
	 * $baseFile root plugin file path
	 * @var string
	 */
	protected $baseFile = null;

	/**
	 * The app config (/config/app.php)
	 * @var array
	 */
	protected $appConfig = null;

	/**
	 * Callbacks for framework's booted event
	 * @var array
	 */
	protected $booted = array();

	/**
	 * Callbacks for framework's ready event
	 * @var array
	 */
	protected $ready = array();

	/**
	 * Get application version
	 * @return string
	 */
	public function version()
	{
		return self::VERSION;
	}

	/**
	 * Static interface to initiate the application
	 * @param string $baseFile (root plugin file path)
	 * @param array $appConfig (/config/app.php)
	 * @return $this
	 */
	public static function run($baseFile, $appConfig)
	{
		return new static($baseFile, $appConfig);
	}

	/**
	 * Init the application
	 * @param string $baseFile (root plugin file path)
	 * @param array $appConfig (/config/app.php)
	 */
	public function __construct($baseFile, $appConfig)
	{
		$this->baseFile = $baseFile;
		$this->appConfig = $appConfig;
		$this->bootstrapApplication();
	}

	/**
	 * Bootup the application
	 * @param string $baseFile (root plugin file path)
	 * @param array $appConfig (/config/app.php)
	 * @return void
	 */
	protected function bootstrapApplication()
	{
		$this->setAppBaseBindings();
		$this->setExceptionHandler();
		$this->loadApplicationTextDomain();
		$this->bootStrapApplicationProviders();
	}

	/**
	 * Register application base bindings
	 * @return  void
	 */
	protected function setAppBaseBindings()
	{
		$this->bindAppInstance();
		$this->registerAppPaths();
		$this->registerAppUrls();
	}

	/**
	 * Bind application instance
	 * @return  void
	 */
	protected function bindAppInstance()
	{
		AppFacade::setApplication($this);
	}

	/**
	 * Set Application paths
	 * @return void
	 */
	protected function registerAppPaths()
	{
		$path = plugin_dir_path($this->baseFile);
		$this->bindInstance('path', $path);
		$this->bindInstance('path.app', $path.'app/');
		$this->bindInstance('path.config', $path.'config/');
		$this->bindInstance('path.public', $path.'public/');
		$this->bindInstance('path.framework', $path.'framework/');
		$this->bindInstance('path.resource', $path.'resources/');
		$this->bindInstance('path.storage', $path.'storage/');
		$this->bindInstance('path.asset', $path.'resources/assets/');
		$this->bindInstance('path.language', $path.'resources/languages/');
		$this->bindInstance('path.view', $path.'resources/views/');
	}

	/**
	 * Set Application urls
	 * @return void
	 */
	protected function registerAppUrls()
	{
		$url = plugin_dir_url($this->baseFile);
		$this->bindInstance('url', $url);
		$this->bindInstance('url.public', $url.'public/');
		$this->bindInstance('url.resource', $url.'resources/');
		$this->bindInstance('url.asset', $url.'resources/assets/');
	}

	/**
	 * Set Application Exception Handler
	 * @return void
	 */
	protected function setExceptionHandler()
	{
		if (defined('WP_DEBUG') && WP_DEBUG) {
			return new ExceptionHandler($this);
		}
	}

	/**
	 * load languages path for i18n pot files
	 * @return bool
	 */
	protected function loadApplicationTextDomain()
	{
		return load_plugin_textdomain(
			$this->getTextDomain(), false, $this->languagePath()
		);
	}

	/**
	 * Bootstrap all service providers
	 * @return void
	 */
	protected function bootStrapApplicationProviders()
	{
		$this->bootstrapWith(array_merge(
			$this->getEngineProviders(),
			$this->getPluginProviders(),
			$this->getCommonProviders()
		));

		$this->fireCallbacks($this->ready);
	}

	/**
	 * Boot application with providers
	 * @param  array $providers
	 * @return void
	 */
	public function bootstrapWith(array $providers)
	{
		$instances = [];

		foreach ($providers as $provider) {
			$instances[] = $instance = new $provider($this);
			$instance->booting();
		}

		$this->registerAppFacadeLoader();

		$this->fireCallbacks($this->booted);
		
		foreach ($instances as $provider) {
			$provider->booted();
		}
	}

	/**
	 * Get engine/core providers
	 * @return array
	 */
	public function getEngineProviders()
	{
		return $this->getProviders('core');
	}

	/**
	 * Get plugin providers (Common)
	 * @return array
	 */
	public function getCommonProviders()
	{
		return $this->getProviders('plugin')['common'];
	}

	/**
	 * Get plugin providers (Backend|Frontend)
	 * @return array
	 */
	public function getPluginProviders()
	{
        if ($this->isUserOnAdminArea()) {
            return $this->getProviders('plugin')['backend'];
        } else {
            return $this->getProviders('plugin')['frontend'];
        }
	}

    /**
     * Register booted events
     * @param  mixed $callback
     * @return void
     */
    public function booted($callback)
    {
    	$this->booted[] = $this->parseHandler($callback);
    }

    /**
     * Register ready events
     * @param  mixed $callback
     * @return void
     */
    public function ready($callback)
    {
    	$this->ready[] = $this->parseHandler($callback);
    }

    /**
     * Fire application event's handlers
     * @param  array  $callbacks
     * @return void
     */
    public function fireCallbacks(array $callbacks)
    {
      foreach ($callbacks as $callback) {
        call_user_func_array($callback, [$this]);
      }
    }
}
