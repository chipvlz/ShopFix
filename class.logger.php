<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR.'/class.dbmanager.php';
	require_once ROOT_DIR.'/class.crypto.php';
	require ROOT_DIR.'/admin/admin_config.php';

	/**
	* Logger
	*/
	class Logger {

		static private $instance = null;

		static public function i() {
			if (is_null(static::$instance)) {
				static::$instance = new self();
			}
			return static::$instance;
		}

		public function getLogs($filter='all') {
			$logs = array();
			$find = DbManager::i()->select("sf_logs",array("message","mode"));
			if ($find !== false && !is_array($find)) {
				$find = array($find);
			}
			foreach ($find as $log) {
				$log->message = Crypto::DecryptString(base64_decode(base64_decode(ADMIN_KEY)),base64_decode(base64_decode(ADMIN_IV)),base64_decode(base64_decode($log->message)));
				$log->message = stripslashes(filter_var(htmlentities($log->message)));
				if ($filter == 'all') {
					array_push($logs, $log);
				} else if ($filter == $log->mode) {
					array_push($logs, $log);
				}
			}
			return $logs;
		}

		public function writeLog($message,$mode='all') {
			$time = date("F j, Y, g:i a");
			$ip = $_SERVER['REMOTE_ADDR'];
			$message = basename($_SERVER['SCRIPT_FILENAME'])." [$ip] ($time) : ".$message;
			$msg = base64_encode(base64_encode(Crypto::EncryptString(base64_decode(base64_decode(ADMIN_KEY)),base64_decode(base64_decode(ADMIN_IV)),$message)));
			DbManager::i()->insert("sf_logs",array("message","mode"),array($msg,$mode));
		}
		
		function __construct() {
			unset($this->instance);
		}
	}
?>