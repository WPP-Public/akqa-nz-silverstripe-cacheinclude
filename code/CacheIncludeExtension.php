<?php
/*
 *
 * Copyright (c) 2009 Heyday
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) license
 *
 */

/**
 *
 * This class provides the ability to cache includes in templates
 * This can be very useful in areas like footers and headers where the content requires a lot of database calls.
 *
 * @name     cache-include
 * @version  0.1
 * @author   Cam Spiers (cameron [at] heyday [dot] co [dot] nz)
 *
 */
class CacheIncludeExtension extends Extension
{

	private static $_context_class = 'CacheIncludeContext';

	private static $_context_instance = false;

	private static $_controller = false;

	private static $_auto_clear = false;

	private static $_enabled = true;

	private static $_config = array();

	private static $_directory = '../heyday-cacheinclude/cache';

	private static $_run = array();

	private static $_default_config = array(
		'context' => 0,
		'member' => false,
		'expires' => false
	);

	private static $_is_admin_checked = false;

	private static $_is_admin = false;

	private static $_delayed_processing = false;

	private static $_extra_memory = false;

	private static $_extra_memory_given = false;

	public static function get_instance($owner)
	{

		$instance = new self;

		$instance->owner = $owner;

		return $instance;

	}

	public static function set_context_class($class)
	{

		if (ClassInfo::classImplements($class, 'CacheIncludeContextInterface')) {

			self::$_context_class = $class;

		} else {

			user_error($class . ' must implement CacheIncludeContextInterface', E_USER_ERROR);

		}

	}

	public static function set_auto_clear($value)
	{

		self::$_auto_clear = $value;

	}

	public static function set_delayed_processing($enabled)
	{
		self::$_delayed_processing = (boolean) $enabled;
	}

	/**
	 * Turn off an on caching
	 * @param boolean $enabled
	 */
	public static function set_enabled($enabled)
	{
		self::$_enabled = (boolean) $enabled;
	}
	/**
	 * Set config
	 * @param array $config
	 */
	public static function set_config($config)
	{

		self::$_config = array_merge(self::$_config, $config);

	}
	/**
	 * Takes a yaml file and loads it into the config
	 * @param string $file
	 */
	public static function load_config($file)
	{

		$cachefile = $file . '.cache';

		if (file_exists($cachefile) && !isset($_GET['flush'])) {

			$yaml = unserialize(file_get_contents($cachefile));

		}

		if (!isset($yaml) || !is_array($yaml)) {

			require_once 'thirdparty/spyc/spyc.php';

			$yaml = Spyc::YAMLLoad($file);

			file_put_contents($cachefile, serialize($yaml));

		}

		self::set_config($yaml);

	}
	/**
	 * Add a config array based on the template name
	 * @param string $template
	 * @param array $config
	 */
	public static function add_config($template, $config)
	{
		//Add config for template
		self::$_config[$template] = $config;

	}
	/**
	 * Set default config
	 * @param string $template
	 * @param array $expires
	 */
	public static function set_default_config($config)
	{

		self::$_default_config = $config;

	}
	/**
	 * Set the directory to save the cache into
	 * @param string $directory
	 */
	public static function set_directory($directory)
	{

		self::$_directory = $directory;

	}

	public static function get_extra_memory()
	{

		return self::$_extra_memory;

	}

	public static function set_extra_memory($extra_memory)
	{

		self::$_extra_memory = $extra_memory;

	}

	public static function ensure_extra_memory()
	{

		increase_memory_limit_to(self::$_extra_memory);

	}
	/**
	 * Determines in the path has expired
	 * @param string $path
	 * @param int $expires
	 * @return boolean
	 */
	protected static function expired($path, $expires = false)
	{

		//If the file doesn't exist or flush is called the the file needs to be written.
		if (!file_exists($path) || isset($_GET['flush'])) {

			return true;

		}

		//If the file does not have an expiry time then it should never expire
		if (!$expires) {

			return false;

		}
		//If the file is older the the expiry time the it needs to be written
		return (date('U') - filemtime($path)) >= $expires;

	}

	protected static function is_admin()
	{

		if (!self::$_is_admin_checked) {

			self::$_is_admin_checked = true;

			self::$_is_admin = Member::currentUserID() && Member::currentUser()->isAdmin();

		}

		return self::$_is_admin;

	}
	/**
	 * Writes the content to the path
	 * @param string $path
	 * @param string $content
	 * @return string
	 */
	protected static function write($path, $content)
	{

		//check member, we don't want to write the cache with an admin logged in.

		if (self::is_admin()) {

			return $content;

		}

		if (!is_dir(dirname($path))) {

			mkdir(dirname($path), 0777, true);

		}

		//Write the file to the disk
		file_put_contents($path, $content);

		//Return the contents
		return $content;

	}

	/**
	 * Reads the cache
	 * @param string $path
	 * @return string
	 */
	protected static function read($path)
	{
		//Read file from disk

		return file_get_contents($path);

	}
	/**
	 * based on the key returns the path to the file
	 * @param string $key
	 * @return string
	 */
	protected static function path($key)
	{
		//Return the path to the html file
		return realpath(self::$_directory) . '/' . $key . '.cache';

	}
	/**
	 * Deletes all cache files
	 */
	public static function clearAll()
	{

		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$_directory)) as $file) {
			unlink($file);
		}

	}
	/**
	 * Clears all cache files for a particular template
	 * @param string $template
	 */
	public static function clearTemplate($template, $force = false)
	{

		if (self::$_delayed_processing && !$force) {

			CacheIncludeQueueItem::add($template);

		} else {

			foreach (glob(self::path('*' . $template)) as $file) {

				unlink($file);

			}

		}

	}

	public static function clearMember($memberId)
	{

		foreach (glob(self::$_directory . '/Members_' . $memberId . '*.cache') as $file) {

			unlink($file);

		}

	}

	public static function clearFolder($folder = '')
	{

		if (is_dir(self::$directory . '/' . $folder)) {

			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$_directory . '/' . $folder)) as $path => $file) {
				unlink($file);
			}

		}

	}

	public static function get_controller()
	{

		if (!self::$_controller) {

			self::$_controller = Controller::curr();

		}

		return self::$_controller;

	}

	public static function get_context_instance()
	{

		if (!self::$_context_instance) {

			self::$_context_instance = new self::$_context_class;

		}

		return self::$_context_instance;

	}
	/**
	 * Called from templates to display the incude. Receives config from the self::$config rules at the top.
	 * @param string $template
	 * @return string
	 */
	public function CacheInclude($template, $function = false)
	{

		if (!self::$_enabled || self::is_admin()) {

			if ($function && $this->owner->hasMethod($template)) {

				$result = $this->owner->$template();

				if ($result instanceof ViewableData) {

					return $result->forTemplate();

				} else {

					return $result;

				}

			}

			return $this->owner->renderWith($template);

		}

		//Get the config for this template
		$config = isset(self::$_config[$template])
			?
			array_merge(self::$_default_config, self::$_config[$template])
			:
			self::$_default_config;

		$keyParts = self::get_context_instance()->context($template, self::get_controller(), $config);

		//Get path to file

		$key = implode('_', (array) $keyParts);
		$key = strlen($key) > 100 ? md5($key) : $key;
		$path = self::path($key . '_' . $template);

		//If the file is expired
		if (self::expired($path, $config['expires'])) {
			//Write a new file with the rendered template

			if (self::$_extra_memory && !self::$_extra_memory_given) {

				self::ensure_extra_memory();

			}

			if ($function && $this->owner->hasMethod($template)) {

				$result = $this->owner->$template();

				if ($result instanceof ViewableData) {

					return self::write($path, $result->forTemplate());

				} else {

					return self::write($path, $result);

				}



			}

			return self::write($path, $this->owner->renderWith($template));

		} else {
			//Read the file off disk
			return self::read($path);

		}

	}

	public function onAfterWrite()
	{

		$this->onChange();

	}

	public function onAfterDelete()
	{

		$this->onChange();

	}

	public function onChange()
	{

		if (self::$_auto_clear) {

			self::clearAll();

			return;

		}

		if (!isset(self::$_run[$this->owner->ClassName])) {

			self::$_run[$this->owner->ClassName] = true;

			$templates = array();

			foreach (self::$_config as $template => $config) {

				if (isset($config['contains']) && is_array($config['contains'])) {

					foreach ($config['contains'] as $class) {

						if ($this->owner instanceof $class) {

							$templates[] = $template;

							break;

						}
					}

				}

			}

			if (count($templates) > 0) {

				foreach ($templates as $template) {

					self::clearTemplate($template);

				}

			}

		}

	}

	public function extraStatics()
	{

	}

}