<?php

namespace WPPayForm\Classes;

trait PathAndUrlsTrait
{
	/**
	 * Get plugin's main file path
	 * @return string [plugin file path]
	 */
	public function baseFile()
	{
		return $this->baseFile;
	}

	/**
	 * Get plugin's root path
	 * @param  string $path
	 * @return string
	 */
	public function path($path = '')
	{
		return $this['path'].ltrim($path, '/');
	}

	/**
	 * Get plugin's /app path
	 * @param  string $path
	 * @return string
	 */
	public function appPath($path = '')
	{
		return $this['path.app'].ltrim($path, '/');
	}

	/**
	 * Get plugin's /config path
	 * @param  string $path
	 * @return string
	 */
	public function configPath($path = '')
	{
		return $this['path.config'].ltrim($path, '/');
	}

	/**
	 * Get plugin's /framework path
	 * @param  string $path
	 * @return string
	 */
	public function frameworkPath($path = '')
	{
		return $this['path.framework'].ltrim($path, '/');
	}

	/**
	 * Get plugin's /resources path
	 * @param  string $path
	 * @return string
	 */
	public function resourcePath($path = '')
	{
		return $this['path.resource'].ltrim($path, '/');
	}

	/**
	 * Get plugin's /resources/languages path
	 * @param  string $path
	 * @return string
	 */
	public function languagePath($path = '')
	{
		return $this['path.language'].ltrim($path, '/');
	}

	/**
	 * Get plugin's /storage path
	 * @param  string $path
	 * @return string
	 */
	public function storagePath($path = '')
	{
		return $this['path.storage'].ltrim($path, '/');
	}

	/**
	 * Get plugin's /resources/views path
	 * @param  string $path
	 * @return string
	 */
	public function viewPath($path = '')
	{
		return $this['path.view'].ltrim($path, '/');
	}

	/**
	 * Get plugin's /resources/assets path
	 * @param  string $path
	 * @return string
	 */
	public function assetPath($path = '')
	{
		return $this['path.asset'].ltrim($path, '/');
	}

	/**
	 * Get plugin's /public path
	 * @param  string $path
	 * @return string
	 */
	public function publicPath($path = '')
	{
		return $this['path.assets'].ltrim($path, '/');
	}

	/**
	 * Get plugin's root url
	 * @param  string $url
	 * @return string
	 */
	public function url($url = '')
	{
		return $this['url'].ltrim($url, '/');
	}

	/**
	 * Get plugin's /public url
	 * @param  string $url
	 * @return string
	 */
	public function publicUrl($url = '')
	{
		return $this['url.public'].ltrim($url, '/');
	}

	/**
	 * Get plugin's /resources url
	 * @param  string $url
	 * @return string
	 */
	public function resourceUrl($url = '')
	{
		return $this['url.resource'].ltrim($url, '/');
	}

	/**
	 * Get plugin's /resources/assets url
	 * @param  string $url
	 * @return string
	 */
	public function assetUrl($url = '')
	{
		return $this['url.asset'].ltrim($url, '/');
	}
}