<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.logger.php';
	/**
	* 
	*/
	class Payments {

		static private $instance = null;

		static public function i() {
			if (is_null(static::$instance)) {
				static::$instance = new self();
			}
			return static::$instance;
		}
			
		public function getPayments() {
			$payments = DbManager::i()->select("sf_purchases",array("token","payerid","type","cart","date","amount","pending"),array("userid" => intval($_SESSION['userid'])));
			if ($payments !== false) {
				$purchases = array();
				$pending = array();
				if (!is_array($payments)) {
					$payments = array($payments);
				}
				foreach ($payments as $payment) {
					$payment->type = base64_decode($payment->type);
					$payment->payerid = base64_decode($payment->payerid);
					$payment->cart = (array)json_decode(base64_decode(base64_decode($payment->cart)));
					if ($payment->pending == 1) {
						array_push($pending, $payment);
					} else {
						array_push($purchases, $payment);
					}
		 		}
		 		$ret = json_encode(array("payments" => $purchases,"pending" => $pending));
				unset($purchases);
				unset($pending);
				unset($payments);
				return $ret;
			}
			Logger::i()->writeLog("Could not get payments, error = ".DbManager::i()->error,'dev');
			return null;
		}	

		function __construct() {
			
		}
	}
?>