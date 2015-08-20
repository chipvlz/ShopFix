<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.logger.php';
	require_once ROOT_DIR . '/class.payments.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';

	if (!SessionManager::i()->isLoggedIn()) {
		Logger::i()->writeLog("User is not logged in",'dev');
		SessionManager::i()->destroySession(true,"index.php");
		die();
	}

	if (!SessionManager::i()->validateToken("DownloadToken","token","GET")) {
		Logger::i()->writeLog("Token to download is missing",'dev');
		SessionManager::i()->destroySession(true,"index.php");
		die();
	}

	if (!isset($_GET['productid']) || !isset($_GET['transaction_id'])) {
		header("Location: index.php");
		die();
	}

	$payments = (array)json_decode(Payments::i()->getPayments());
	$payments = (array)$payments['payments'];
	$payments = array_filter($payments,function($payment) {
		return ($payment->token == $_GET['transaction_id']);
	});
	if (count($payments) == 1) {
		$payment = $payments[0];
		$has_purchased = false;
		foreach ($payment->cart as $key => $value) {
			if ($key == $_GET['productid']) {
				$has_purchased = true;
				break;
			}
		}
		if ($has_purchased) { //purchased
			$find = DbManager::i()->select("sf_products",array("file"),array("productid" => intval($_GET['productid'])));
			if ($find !== false && !is_array($find)) {
				$file_path = $_SERVER['DOCUMENT_ROOT'].$find->file;
				if (file_exists($file_path)) {
					header("Content-type: application/force-download");
					header("Content-Disposition: attachment; filename=\"".str_replace(" ", "_", basename($file_path))."\"");
					echo file_get_contents($file_path);
					Logger::i()->writeLog("User ".$_SESSION['userid']." downloaded ".basename($file_path));
				} else {
					Logger::i()->writeLog("Failed to download file ".basename($file_path)." - it does not exist",'dev');
					header("Location: index.php");
				}
			}
		} else {
			Logger::i()->writeLog("User ". $_SESSION['userid'] ." has not purchased the product he/she is trying to download");
			header("Location: index.php");
			die();
		}
	} else {
		Logger::i()->writeLog("Could not get purchase for transaction_id = ".$_GET['transaction_id'].", error = ".DbManager::i()->error,'dev');
		header("Location: index.php");
		die();
	}
?>