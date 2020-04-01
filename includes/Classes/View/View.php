<?php

namespace WPPayForm\Classes\View;


class View
{

	/**
	 * The Application (Framework)
	 * @var FluentForm\Classes\Framework\Foundation\Application
	 */
	protected $app;

	/**
	 * Resolved path of view
	 * @var string
	 */
	protected $path;


	/**
	 * Passed data for the view
	 * @var array
	 */
	protected $data = [];

		/**
	 * Shared data for the view
	 * @var array
	 */
	protected static $sharedData = [];

	/**
	 * Registered composer callbacks for the view
	 * @var array
	 */
	protected static $composerCallbacks = [];

	/**
	 * Make an instance of the the class
	 * @param FluentForm\Framework\Foundation\Application $app
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

		/**
	 * Generate and echo/print a view file
	 * @param  string $path
	 * @param  array  $data
	 * @return void
	 */
	public function render($path, $data = [])
	{
		echo $this->make($path, $data);
	}

	/**
	 * Generate a view file
	 * @param  string $path
	 * @param  array  $data
	 * @return string [generated html]
	 * @throws InvalidArgumentException
	 */
	public function make($path, $data = [])
	{
		if (file_exists($this->path = $this->resolveFilePath($path))) {
			$this->data = $data;
			return $this;
		}

		// throw new InvalidArgumentException("The view file [{$this->path}] doesn't exists!");
	}

		/**
	 * Resolve the view file path
	 * @param  string $path
	 * @return string
	 */
	protected function resolveFilePath($path)
	{
    $path = str_replace('.', DIRECTORY_SEPARATOR, $path);

		if (strpos($path, '::') !== false) {
      list($namespace, $path) = explode('::', $path);
			$viewName = $this->app['path.view.extras'][$namespace].DIRECTORY_SEPARATOR.$path;
		} else {
			$viewName = $this->app->viewPath($path);
		}

    return $viewName.'.php';
	}


}