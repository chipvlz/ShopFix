<?php
		
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.crypto.php';
	
	/**
	* SessionManager
	*/
	class SessionManager { 

		static private $instance = null;

		static public function i() {
			if (is_null(static::$instance)) {
				static::$instance = new self();
			}
			if (session_status() == PHP_SESSION_NONE) {
    			session_start();
			}
			session_regenerate_id();
			return static::$instance;
		}

		public function isAdminLoggedIn() {
			return (isset($_SESSION['admin_login']) && $_SESSION['admin_login'] == 1 && isset($_SESSION['admin_answer']));
		}

		public function isLoggedIn() {
			return (isset($_SESSION['login']) && $_SESSION['login'] == 1 && isset($_SESSION['userid']));
		}

		public function validateToken($sess_tok,$token,$method = "POST") {
			$array = null;
			if (strcmp($method, "POST") == 0) {
				$array = $_POST;
			} else if (strcmp($method, "GET") == 0) {
				$array = $_GET;
			} else {
				return false;
			}
			if (isset($_SESSION[$sess_tok]) && isset($array[$token])) {
				if (is_array($array[$token])) {
					return false;
				}
				return (strcmp($_SESSION[$sess_tok],str_replace(" ", "+", $array[$token])) == 0);
			}
			return false;
		}

		public static function GenerateToken() {
			return base64_encode(Crypto::XorString(base64_encode(md5(uniqid(mt_rand(), true)))));
		}

		public function destroySession($refresh=false,$loc="index.php") {
			$_SESSION = array();
			session_destroy();
			if ($refresh) {
				header("Location: ".$loc);
			}
		}
	}
?>