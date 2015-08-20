<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR.'/class.btc.php';
	require_once ROOT_DIR.'/class.logger.php';
	require_once ROOT_DIR.'/class.submission.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	
	if (!SessionManager::i()->isLoggedIn()) {
		Logger::i()->writeLog("User not logged in",'access');
		die(Submission::createResult("Permission denied"));
	}

	if (!SessionManager::i()->validateToken("PaymentStatusToken","token")) {
		Logger::i()->writeLog("Token to get payment status is missing",'access');
		die(Submission::createResult("Permission denied"));
	}
	try {
		$btc = new BTC();
		$info = (array)$btc->checkPaymentStatus();
		if ($info['result'] == "success") {
			die(Submission::createResult($info['resultMessage'],true));
		}
	} catch (Exception $e) {
		Logger::i()->writeLog("Caught Exception: ".$e->getMessage(),'dev');
	}
?>