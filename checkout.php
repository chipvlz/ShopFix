<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR.'/class.btc.php';
	require_once ROOT_DIR.'/class.logger.php';
	require_once ROOT_DIR.'/class.paypal.php';
	require_once ROOT_DIR.'/class.sessionmanager.php';
	require_once ROOT_DIR.'/class.submission.php';

	if (!SessionManager::i()->isLoggedIn()) {
		Logger::i()->writeLog("User not logged in",'access');
		die(Submission::createResult("Permission denied"));
	}

	if (!SessionManager::i()->validateToken("CheckoutToken","token")) {
		Logger::i()->writeLog("Token to checkout is missing",'access');
		die(Submission::createResult("Permission denied"));
	}

	function createURLForScript($script) {
		$url = "";
		$scheme = (isset($_SERVER['HTTPS']) ? "https://" : "http://");
		$url .= $scheme . $_SERVER['SERVER_NAME'] . str_replace($_SERVER['DOCUMENT_ROOT'],"",getcwd()."/$script");
		return $url;
	}

	function checkoutWithPaypal($total,$cart) {
		try {
			$paypal = new PayPal(true);
		} catch (Exception $e) {
			Logger::i()->writeLog("Caught Exception: ".$e->getMessage(),'dev');
			die();
		}
		$params = array(
				'RETURNURL' => createURLForScript("process.php"),
				'CANCELURL' => createURLForScript("cancel.php"),
				'PAYMENTREQUEST_0_AMT' => floatval(number_format(floatval($total),2)),
				'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR'
				);
		$params['SOLUTIONTYPE'] = "Sole";
	 	$params['LANDINGPAGE'] = "Billing";
		
		$k = 0;
		foreach ($cart as $key => $value) {
	 		$info = (array)$value;
	 		$params['L_PAYMENTREQUEST_0_NAME'.$k] = $info["name"];
	 		$params['L_PAYMENTREQUEST_0_DESCR'.$k] = $info["description"];
	 		$params['L_PAYMENTREQUEST_0_AMT'.$k] = floatval(number_format(floatval($info['price']),2));
	 		$params['L_PAYMENTREQUEST_0_QTY'.$k] = intval($info['quantity']);
	 		$k++;
		}

		$response = $paypal->doRequest("SetExpressCheckout",$params);
	
		if ($response) {
			Logger::i()->writeLog("Starting PayPal checkout");
			return $paypal->generateURL($response['TOKEN']);
		} else {
			Logger::i()->writeLog("Could not get token, error = ".$paypal->error,'dev');
			die(Submission::createResult("Can not checkout at the moment. Please try again later."));
		}
	}

	function checkoutWithBTC($total,$cart) {
		$response = null;
		try {
			$btc = new BTC();
			$response = $btc->doPayment($total);
		} catch (Exception $e) {
			Logger::i()->writeLog("Caught Exception: ".$e->getMessage(),'dev');
			die();
		}
		Logger::i()->writeLog("Start Bitcoin Checkout with address = ".$response['btcaddress']);
		return $response;
	}

	if (!isset($_POST['mode'])) {
		die(Submission::createResult("Mode is missing"));
	}

	$totalttc = 42;
	$cart = null;

	if (isset($_POST['total']) && isset($_POST['cart'])) {
		$totalttc = round(floatval($_POST['total']),2);
		$cart = (array)json_decode(base64_decode($_POST['cart'])); //maybe make a function out of it
		$calculated_total = 0;
		foreach ($cart as $productid => $product) {
			$calculated_total += $product->price;
		}
		if ($totalttc < $calculated_total) {
			Logger::i()->writeLog("Possible security issue. Calculated price $calculated_total is bigger than received total of $totalttc",'dev');
			die(Submission::createResult("Price edited :("));
		}
	} else {
		die(Submission::createResult("Missing information"));
	}
	switch ($_POST['mode']) {
		case 'paypal':
			echo checkoutWithPaypal($totalttc,$cart);
			break;

		case 'btc':
			echo checkoutWithBTC($totalttc,$cart);
			break;
		
		default:
			break;
	}
	
?>