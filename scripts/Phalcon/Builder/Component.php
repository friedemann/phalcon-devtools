<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2013 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

namespace Phalcon\Builder;

use Phalcon\Script\Color,
	Phalcon\Builder\BuilderException;

/**
 * \Phalcon\Builder\Component
 *
 * Base class for builder components
 *
 * @category 	Phalcon
 * @package 	Builder
 * @subpackage  Component
 * @copyright   Copyright (c) 2011-2013 Phalcon Team (team@phalconphp.com)
 * @license 	New BSD License
 */
abstract class Component
{

	protected $_options = array();

	public function __construct($options)
	{
		$this->_options = $options;
	}

	/**
	 * Tries to find the current configuration in the application
	 *
	 * @param string $path
	 * @return Phalcon\Config
	 */
	protected function _getConfig($path)
	{
        // use configuration from script parameters
        // merge all configs together
        $forcedConfig = $this->_options["config"];
        if (NULL !== $forcedConfig) {
            $forcedConfig = explode(",", $forcedConfig);

            $mergedConfig = new \Phalcon\Config();
            foreach ($forcedConfig as $config) {
                // use "INI" configs
                if (preg_match("#\.ini$#i", $config)) {
                    $mergedConfig->merge(new \Phalcon\Config\Adapter\Ini($config));
                }

                // use "PHP" configs
                else if (preg_match("#\.php$#i", $config)){
                    $mergedConfig->merge(include($config));
                }


            }

            return $mergedConfig;
        }

		foreach (array('app/config/', 'config/') as $configPath) {
			if (file_exists($path . $configPath . "config.ini")) {
				return new \Phalcon\Config\Adapter\Ini($path . $configPath . "/config.ini");
			} else {
				if (file_exists($path . $configPath. "/config.php")) {
					$config = include($path . $configPath . "/config.php");
					return $config;
				}
			}
		}

		$directory = new \RecursiveDirectoryIterator('.');
		$iterator = new \RecursiveIteratorIterator($directory);
		foreach ($iterator as $f) {
			if (preg_match('/config\.php$/', $f->getPathName())) {
				$config = include $f->getPathName();
				return $config;
			} else {
				if (preg_match('/config\.ini$/', $f->getPathName())) {
					return new \Phalcon\Config\Adapter\Ini($f->getPathName());
				}
			}
		}
		throw new BuilderException('Builder can\'t locate the configuration file');
	}

	/**
	 * Check if a path is absolute
	 *
	 * @return boolean
	 */
	public function isAbsolutePath($path)
	{
		if (PHP_OS == "WINNT") {
			if (preg_match('/^[A-Z]:\\\\/', $path)) {
				return true;
			}
		} else {
			if (substr($path, 0, 1) == DIRECTORY_SEPARATOR) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the script is running on Console mode
	 *
	 * @return boolean
	 */
	public function isConsole()
	{
		return !isset($_SERVER['SERVER_SOFTWARE']);
	}

	/**
	 * Check if the current adapter is supported by Phalcon
	 *
	 * @param string $adapter
	 * @throws BuilderException
	 */
	public function isSupportedAdapter($adapter)
	{
		if (!class_exists('\Phalcon\Db\Adapter\Pdo\\' . $adapter)) {
			throw new BuilderException("Adapter $adapter is not supported");
		}
	}

	/**
	 * Shows a success notification
	 *
	 * @param string $message
	 */
	protected function _notifySuccess($message)
	{
		print Color::success($message);
	}

	abstract public function build();

}
