<?php

namespace Cundd\Rest\Authentication;
use Cundd\Rest\Authentication\Exception\InvalidConfigurationException;

/**
 * The class expects an existing valid authenticated Frontend User or credentials passed through the request.
 *
 * Example URL: logintype=login&pid=PIDwhereTheFEUsersAreStored&user=MyUserName&pass=MyPassword
 *
 * @package Cundd\Rest\Authentication
 */
class ConfigurationBasedAuthenticationProvider extends AbstractAuthenticationProvider {
	/**
	 * Keyword to allow access for the given access method
	 */
	const ACCESS_ALLOW = 'allow';

	/**
	 * Keyword to deny access for the given access method
	 */
	const ACCESS_DENY = 'deny';

	/**
	 * The request want's to read data
	 */
	const ACCESS_METHOD_READ = 'read';

	/**
	 * The request want's to write data
	 */
	const ACCESS_METHOD_WRITE = 'write';

	/**
	 * Specifies if the request wants to write data
	 * @var boolean
	 */
	protected $write = -1;

	/**
	 * @var \Cundd\Rest\Configuration\TypoScriptConfigurationProvider
	 */
	protected $configurationProvider;

	/**
	 * Inject the configuration provider
	 * @param \Cundd\Rest\Configuration\TypoScriptConfigurationProvider $configurationProvider
	 */
	public function injectConfigurationProvider(\Cundd\Rest\Configuration\TypoScriptConfigurationProvider $configurationProvider) {
		$this->configurationProvider = $configurationProvider;
	}

	/**
	 * Tries to authenticate the current request
	 * @return bool Returns if the authentication was successful
	 * @throws Exception\InvalidConfigurationException if the current configuration ('read' or 'write') is not set
	 */
	public function authenticate() {
		$configurationKey = 'read';
		$configuration = $this->getConfigurationForCurrentPath();
		if ($this->isWrite()) {
			$configurationKey = 'write';
		}

		// Throw an exception if the configuration is not complete
		if (!isset($configuration[$configurationKey])) {
			throw new InvalidConfigurationException($configurationKey . ' configuration not set', 1376826223);
		}

		$access = $configuration[$configurationKey];
		if ($access === self::ACCESS_ALLOW) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns if the given request needs authentication
	 * @return bool
	 */
	public function requestNeedsAuthentication() {
		return TRUE;
	}

	/**
	 * Returns if the request wants to write data
	 * @return bool
	 */
	public function isWrite() {
		if ($this->write === -1) {
			$this->write = !in_array(strtoupper($this->request->method()), array('GET', 'HEAD'));
//			$this->write = in_array(strtoupper($this->request->method()), array('POST', 'PUT', 'DELETE', 'PATCH'));
		}
		return $this->write;
	}

	/**
	 * Returns the configuration matching the current request's path
	 * @return string
	 * @throws \UnexpectedValueException if the request is not set
	 */
	public function getConfigurationForCurrentPath() {
		if (!$this->request) {
			throw new \UnexpectedValueException('The request isn\'t set', 1376816053);
		}
		return $this->getConfigurationForPath($this->request->path());
	}

	/**
	 * Returns the configuration matching the given request path
	 * @param string $path
	 * @return string
	 */
	public function getConfigurationForPath($path) {
		$configuredPaths = $this->getConfiguredPaths();
		$matchingConfiguration = array();

		foreach ($configuredPaths as $configuration) {
			$currentPath = $configuration['path'];

			$currentPathPattern = str_replace('*', '\w*', str_replace('?', '\w', $currentPath));
			$currentPathPattern = "!$currentPathPattern!";
			if ($currentPath === 'all' && !$matchingConfiguration) {
				$matchingConfiguration = $configuration;
			} else if (preg_match($currentPathPattern, $path)) {
				$matchingConfiguration = $configuration;
			}
		}
		return $matchingConfiguration;
	}

	/**
	 * Returns the paths configured in the settings
	 * @return array
	 */
	public function getConfiguredPaths() {
		$settings = $this->configurationProvider->getSettings();
		if (isset($settings['paths']) && is_array($settings['paths'])) {
			return $settings['paths'];
		}
		return isset($settings['paths.']) ? $settings['paths.'] : array();
	}
}