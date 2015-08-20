<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.btc.php';
	require_once ROOT_DIR . '/class.logger.php';
	require_once ROOT_DIR . '/class.paypal.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.settings.php';

	error_log("[ShopFix PROHEX] CALLBACK CALLED ".print_r($_GET,true));

	function generateMessage($first_name,$cart,$transid) {
		$message = 'Hallo '.$first_name.",\n\nWe received your Payment with the Transaction ID: ".$transid."\n\n";
		$message .= "You purchased:\n\n";
		foreach ($cart as $productid => $product) {
			$message .= "- " . $product->name . " (" . $product->description . ")" . "\n";
		}
		$message .= "\nTo download the Products you have purchased, please visit the 'Payment History' section when logged in\n\n";
		$message .= "- Your " . Settings::i()->title . " Team";
		return $message;
	}


	if (isset($_GET['token'])) {
		Logger::i()->writeLog("Processing PayPal Payment");
		if (!SessionManager::i()->isLoggedIn()) {
			Logger::i()->writeLog("User not logged in",'dev');
			die();
		}
		try {
			$paypal = new PayPal(true);
		} catch (Exception $e) {
			Logger::i()->writeLog("Caught Exception: ".$e->getMessage(),'dev');
			die();
		}

		$response = $paypal->doRequest("GetExpressCheckoutDetails",array("TOKEN" => $_GET['token']));
		$first_name = $response['FIRSTNAME'];

		if (!$response) {
			Logger::i()->writeLog("Could not get express checkout details, error = ".$paypal->error,'dev');
			header("Location: index.php");
			die();
		} 

		$response = $paypal->doRequest("DoExpressCheckoutPayment",array(
						"TOKEN" => $response['TOKEN'],
						"PAYERID" => $response['PAYERID'],
						"PAYMENTACTION" => "Sale",
						"PAYMENTREQUEST_0_AMT" => $response['PAYMENTREQUEST_0_AMT'],
						"PAYMENTREQUEST_0_CURRENCYCODE" => $response['PAYMENTREQUEST_0_CURRENCYCODE']
					));
		if ($response) {
			if ($response['PAYMENTINFO_0_PAYMENTSTATUS'] == "Completed" && $response['ACK'] == "Success" && $response['PAYMENTINFO_0_ACK'] == "Success") { //did pay
				$userid = intval($_SESSION['userid']);
				if (isset($_SESSION['shopping-cart'])) {
					$token = base64_encode(DbManager::i()->escapeString($_GET['token']));
					$payerid = base64_encode(DbManager::i()->escapeString($_GET['PayerID']));
					$cart = DbManager::i()->escapeString($_SESSION['shopping-cart']);
					$amount = floatval($response['PAYMENTINFO_0_AMT']);
					DbManager::i()->insert("sf_purchases",array("token","payerid","type","userid","cart","date","ip","amount","pending"),array(base64_encode($response['PAYMENTINFO_0_TRANSACTIONID']),$payerid,base64_encode("PayPal"),$userid,$cart,time(),base64_encode($_SERVER['REMOTE_ADDR']),$amount,0));
					$_SESSION['shopping-cart'] = base64_encode("{}");
					DbManager::i()->update("sf_carts",array("cart" => $_SESSION['shopping-cart']),array("userid" => $userid));
					$find = DbManager::i()->select("sf_members",array("email","key","iv"),array("userid" => $_SESSION['userid']));
					if ($find !== false && !is_array($find)) {
						$recipient = Crypto::DecryptString(base64_decode(base64_decode($find->key)),base64_decode(base64_decode($find->iv)),base64_decode(base64_decode($find->email)));
						$subject = Settings::i()->title.' Payment received';
						$message = generateMessage($first_name,(array)json_decode(base64_decode($cart)),$response['PAYMENTINFO_0_TRANSACTIONID']);
						$header = 'From: shopfix@'.$_SERVER['SERVER_NAME'] . "\r\n" .
						    'Reply-To: shopfix@'.$_SERVER['SERVER_NAME'] . "\r\n" .
						    'X-Mailer: PHP/' . phpversion();
						mail($recipient, $subject, $message, $header);
						Logger::i()->writeLog("PayPal Transaction registered: ".$response['PAYMENTINFO_0_TRANSACTIONID']);
					}
					header("Location: index.php");
					die();
				} else {
					header("Location: index.php");
				}
			} else {
				header("Location: index.php");
				die();
			}
		} else {
			Logger::i()->writeLog("Could not do express checkout, error = ".$paypal->error,'dev');
		}
	} else {
		header("Location: index.php");
		die();
	}
?>