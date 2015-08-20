<?php

	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.blockio.php';
	require_once ROOT_DIR . '/class.payments.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.settings.php';
	require_once ROOT_DIR . '/admin/admin_config.php';

 	define('BTC_API_VERSION', 2);

	function createURL() {
		$url = "";
		$scheme = (isset($_SERVER['HTTPS']) ? "https://" : "http://");
		$url .= $scheme . $_SERVER['SERVER_NAME'] . str_replace($_SERVER['DOCUMENT_ROOT'],"",getcwd()."/process.php");
		return $url;
	}

	/*
	array(2) {
  ["status"]=>
  string(7) "success"
  ["data"]=>
  object(stdClass)#12 (4) {
    ["network"]=>
    string(7) "BTCTEST"
    ["available_balance"]=>
    string(10) "0.00080000"
    ["pending_received_balance"]=>
    string(10) "0.00000000"
    ["balances"]=>
    array(1) {
      [0]=>
      object(stdClass)#13 (5) {
        ["user_id"]=>
        int(11)
        ["label"]=>
        string(23) "user12BTCCheckout_28576"
        ["address"]=>
        string(35) "2N8zsEPb9XCBq1zVNdVdjGqKRyjP5w9gaba"
        ["available_balance"]=>
        string(10) "0.00080000"
        ["pending_received_balance"]=>
        string(10) "0.00000000"
      }
    }
  }
}


	*/
	/**
	* BTC
	*/
	class BTC {

		private $api_key = null;
		private $api_pin = null;
		private $token = null;
		private $block_io = null;

		public static function exchangeCurrencyToBTC($amount,$c='EUR') {
			$url = "https://blockchain.info/tobtc?currency=".$c."&value=".$amount;
			$result = file_get_contents($url);
			return floatval($result);
		}

		public function checkPaymentStatus() {
			$payments = (array)json_decode(Payments::i()->getPayments());
			$pending = (array)$payments['pending'];
			foreach ($pending as $payment) {
				$response = (array)$this->block_io->get_address_balance(array('addresses' => base64_decode($payment->token)));
				if ($response['status'] == "success") {
					$data = (array)$response['data'];
					foreach ((array)$data['balances'] as $balance) {
						if ($balance->address == base64_decode($payment->token) && $balance->label == $payment->payerid) {
							if (round($balance->available_balance,8) >= round($payment->amount,8)) {
								$update = DBManager::i()->update("sf_purchases",array("pending" => 0),array("token" => $payment->token));
								if ($update) {
									$_SESSION['shopping-cart'] = base64_encode("{}");
									DbManager::i()->update("sf_carts",array("cart" => $_SESSION['shopping-cart']),array("userid" => $_SESSION['userid']));
									return array("result" => "success","resultMessage" => "Payment received! Refreshing your payments...");
								} else {
									throw new Exception("Could not update Purchase. Please try again later");
								}
							} else {
								throw new Exception("Paid amount is not enough. Need ".round(($payment->amount - $balance->available_balance),8)." more Bitcoins",1212);
							}
							break;
						} else {
							throw new Exception("Balance Address: ".$balance->address." NOT EQUAL TO payment address: ".$payment->address." and balance label: ".$balance->label." NOT EQUAL to payment label".$payment->payerid);
						}
					}
				} else {
					throw new Exception("Could not get address balance for address");
				}
			}
		}

		public function doPayment($total) {
			$total = $this->exchangeCurrencyToBTC($total);
			$label = "user".$_SESSION['userid']."BTCCheckout_".rand(0,100000);
			$response = (array)$this->block_io->get_new_address(array("label" => $label));
			if ($response['status'] != "success") {
				throw new Exception("Failed to create new Bitcoin Address");
			}
			$responseData = (array)$response['data'];
			$address = $responseData['address'];
			$insert = DbManager::i()->insert("sf_purchases",array("token","payerid","type","userid","cart","date","ip","amount","pending"),array(base64_encode($address),base64_encode($label),base64_encode("Bitcoin"),intval($_SESSION['userid']),base64_encode($_SESSION['shopping-cart']),time(),base64_encode($_SERVER['REMOTE_ADDR']),floatval($total),1));
			if ($insert !== false) {
				return json_encode(array("btcamount" => $total,"btcaddress" => ($insert ? $address : "error")));
			} else {
				throw new Exception("Could not insert pending purchase");
			}
		}

		function __construct() {
			$this->api_key = Settings::i()->btc_api_key;
			$this->api_pin = Settings::i()->btc_api_pin;
			if (!is_null($this->api_key) && !is_null($this->api_pin)) {
				try {
					$this->block_io = new BlockIo($this->api_key, $this->api_pin, BTC_API_VERSION);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			} else {
				throw new Exception("API Key and API Pin are missing in settings");
			}
			$this->token = SessionManager::GenerateToken();
		}

		function __destruct() {
			unset($this->token);
			unset($this->api_key);
			unset($this->api_pin);
			unset($this->block_io);
		}
	}
?>