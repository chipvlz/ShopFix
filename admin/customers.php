<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__)."/..");
	require_once ROOT_DIR . '/class.dbmanager.php';
	require_once ROOT_DIR . '/class.logger.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.submission.php';

	if (!SessionManager::i()->isAdminLoggedIn()) {
		Logger::i()->writeLog("Tried to access this script without permissions. Was that you?",'access');
		die(Submission::createResult("Permission denied"));
	}

	if (!SessionManager::i()->validateToken("GetCustomersToken","token")) {
		Logger::i()->writeLog("Token to access customers is missing",'access');
		die(Submission::createResult("Token mismatch"));
	}

	header("Content-Type: application/json; charset=UTF-8");

	$customers = DbManager::i()->select("sf_members",array("userid","username","email","register_date","ip","key","iv"));
	if ($customers !== false) {
		$members = array();
		if (!is_array($customers)) {
			$customers = array($customers);
		}
		foreach ($customers as $customer) {
			$key = base64_decode(base64_decode($customer->key));
			$iv = base64_decode(base64_decode($customer->iv));
			array_push($members, array(
				"customerid" => $customer->userid,
				"name" => Crypto::DecryptString($key,$iv,base64_decode(base64_decode($customer->username))),
				"email" => Crypto::DecryptString($key,$iv,base64_decode(base64_decode($customer->email))),
				"date" => strtotime($customer->register_date) * 1000,
				"ip" => Crypto::DecryptString($key,$iv,base64_decode(base64_decode($customer->ip)))
			));
		}
		echo json_encode(array("customers" => $members));
		unset($members);
		unset($customers);
	} else {
		Logger::i()->writeLog("Could not get customers, error = ".DbManager::i()->error,'dev');
		die(Submission::createResult("Could not load customers"));
	}
?>