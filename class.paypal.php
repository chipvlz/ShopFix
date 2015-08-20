<?php
	
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.dbmanager.php';
	require_once ROOT_DIR . '/class.settings.php';
	require_once ROOT_DIR . '/admin/admin_config.php';

	define('API_VERSION', 99.0);

	/**
	* PayPal
	*/
	class PayPal {

		private $method;
		private $params;
		private $curl;
		public $error;
		private $api_url = "https://api-3t.sandbox.paypal.com/nvp";
		private $testing = false;
		private $api_user;
		private $api_password;
		private $api_signature;

		function __construct($testing = false) {
			$this->testing = $testing;
			if (!$testing) {
				$this->api_url = str_replace("sandbox.", "", $this->api_url);
			}
			$this->api_user = Settings::i()->paypal_email;
			$this->api_password = Settings::i()->paypal_api_pass;
			$this->api_signature = Settings::i()->paypal_api_signature;
			if (is_null($this->api_user) || is_null($this->api_password) || is_null($this->api_signature)) {
				throw new Exception("PayPal API Credentials missing");
			}
		}

		public function generateURL($token) {
			$url = "https://sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=".$token;
			if (!$this->testing) {
				$url = str_replace("sandbox.", "", $url);
			}
			return $url;
		}

		public function doRequest($method = false,$params = false) {
			if ($params) {
				$final = array_merge($params,array(
					'METHOD' => $method,
					'VERSION' => API_VERSION,
					'USER' => $this->api_user,
					'SIGNATURE' => $this->api_signature, 
					'PWD' => $this->api_password
				));
				$this->params = http_build_query($final);
			}
			$this->curl = curl_init();
			curl_setopt_array($this->curl, array(
						CURLOPT_URL => $this->api_url,
						CURLOPT_POST => 1,
						CURLOPT_POSTFIELDS => $this->params,
						CURLOPT_RETURNTRANSFER => 1,
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_SSL_VERIFYHOST => false,
						CURLOPT_VERBOSE => 0
			));
			$response = curl_exec($this->curl);
			$responseArray = array();
			parse_str($response,$responseArray);
			if (curl_errno($this->curl)) {
				$this->error = curl_error($this->curl);
				curl_close($this->curl);
				return false;
			} else {
				if ($responseArray['ACK'] == "Success") {
					curl_close($this->curl);
					return $responseArray;
				} else {
					$this->error = print_r($responseArray,true);
					curl_close($this->curl);
					return false;
				}
			}
		}

		function __destruct() {
			unset($this->method);
			unset($this->params);
			unset($this->curl);
			unset($this->api_url);
			unset($this->testing);
			unset($this->api_user);
			unset($this->api_password);
			unset($this->api_signature);
		}
	}
?>