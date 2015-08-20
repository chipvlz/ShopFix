<?php

	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.payments.php';
	require_once ROOT_DIR . '/class.submission.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	
	if (!SessionManager::i()->isLoggedIn()) {
		Logger::i()->writeLog("User not logged in",'access');
		die(Submission::createResult("Permission denied"));
	}

	if (!SessionManager::i()->validateToken("GetPaymentsToken","token")) {
		Logger::i()->writeLog("Token to get payments is missing",'access');
		die(Submission::createResult("Permission denied"));
	}

	header("Content-Type: application/json; charset=UTF-8");

	$payments = Payments::i()->getPayments();
	if (!is_null($payments) && strlen($payments) > 0) {
		echo $payments;
	} else {
		echo Submission::createResult("Could not get payments");
	}
?>