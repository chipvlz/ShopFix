<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.dbmanager.php';
	require_once ROOT_DIR . '/admin/admin_config.php';

	/**
	* Settings
	*/
	class Settings {

		public $title = "ShopFix";
		public $captcha_public = null;
		public $captcha_private = null;
		public $paypal_email = null;
		public $paypal_api_pass = null;
		public $paypal_api_signature = null;
		public $btc_api_key = null;
		public $btc_api_pin = null;

		static private $instance = null;

		static public function i() {
			if (is_null(static::$instance)) {
				static::$instance = new self();
			}
			return static::$instance;
		}

		function __construct() {
			$settings = DbManager::i()->select("sf_settings",array("settings"));
			if ($settings !== false && !is_array($settings)) {
				$prefs = Crypto::DecryptString(base64_decode(base64_decode(ADMIN_KEY)),base64_decode(base64_decode(ADMIN_IV)),base64_decode(base64_decode($settings->settings)));
				$prefs = (array)json_decode(base64_decode($prefs));
				if (isset($prefs['cms_settings'])) {
					$settings = (array)$prefs['cms_settings'];
					if (isset($settings['title']) && strlen($settings['title']) > 0) {
						$this->title = stripslashes(filter_var(htmlspecialchars($settings['title'])));
					}
					if (isset($settings['captcha_public']) && strlen($settings['captcha_public']) > 0) {
						$this->captcha_public = stripslashes(filter_var(htmlspecialchars($settings['captcha_public'])));
					}
					if (isset($settings['captcha_secret']) && strlen($settings['captcha_private']) > 0) {
						$this->captcha_private = stripslashes(filter_var(htmlspecialchars($settings['captcha_private'])));
					}
				}
				if (isset($prefs['paypal'])) {
					$settings = (array)$prefs['paypal'];
					if (isset($settings['username']) && strlen($settings['username']) > 0) {
						$this->paypal_email = stripslashes(filter_var(htmlspecialchars($settings['username'])));
					}
					if (isset($settings['password']) && strlen($settings['password']) > 0) {
						$this->paypal_api_pass = stripslashes(filter_var(htmlspecialchars($settings['password'])));
					}
					if (isset($settings['signature']) && strlen($settings['signature']) > 0) {
						$this->paypal_api_signature = stripslashes(filter_var(htmlspecialchars($settings['signature'])));
					}
				}
				if (isset($prefs['btc'])) {
					$settings = (array)$prefs['btc'];
					if (isset($settings['api_key']) && strlen($settings['api_key']) > 0) {
						$this->btc_api_key = stripslashes(filter_var(htmlspecialchars($settings['api_key'])));
					}
					if (isset($settings['api_pin']) && strlen($settings['api_pin']) > 0) {
						$this->btc_api_pin = stripslashes(filter_var(htmlspecialchars($settings['api_pin'])));
					}
				}
				unset($prefs);
			}
		}

		function __destruct() {
			unset($this->title);
			unset($this->captcha_public);
			unset($this->captcha_private);
			unset($this->paypal_email);
			unset($this->paypal_api_pass);
			unset($this->paypal_api_signature);
			unset($this->btc_api_key);
			unset($this->btc_api_pin);
		}
	}
?>