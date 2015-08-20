<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__)."/..");
	require_once ROOT_DIR . '/class.logger.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.submission.php';

	if (!SessionManager::i()->isAdminLoggedIn()) {
		Logger::i()->writeLog("Admin is not logged in",'access');
		die(Submission::createResult("Admin is not logged in"));
	}

	if (!SessionManager::i()->validateToken("UpdateCustomersToken","token")) {
		Logger::i()->writeLog("Token to update customer is missing",'access');
		die(Submission::createResult("Token mismatch"));
	}

	if ($field = Submission::checkFields(array("customerid","action"),$_POST)) {
		die(Submission::createResult(ucfirst($field)." is missing or invalid"));
	}

	function renewPassword($c) {
		$plain = Crypto::generateRandomPassword(15);
		$info = DbManager::i()->select("sf_members",array("key","iv"),array("userid" => intval($c)));
		if ($info !== false && !is_array($info)) {
			$key = base64_decode(base64_decode($info->key));
			$iv = base64_decode(base64_decode($info->iv));

			$password = base64_encode(base64_encode(Crypto::EncryptString($key,$iv,$plain)));
			if (DbManager::i()->update("sf_members",array("password" => $password),array("userid" => intval($c)))) {
				unset($password);
				unset($key);
				unset($iv);
				unset($info);
				Logger::i()->writeLog("Password renewed for UserID: $c, password = $plain");
				return Submission::createResult($plain,true);
			} 
		}
		Logger::i()->writeLog("Renew password failed, error = ".DbManager::i()->error,'dev');
		return Submission::createResult("Could not renew password");
	}

	function deleteCustomer($c) {
		$delete = DbManager::i()->delete("sf_members",array("userid" => intval($c)));
		if (!$delete) {
			Logger::i()->writeLog("Deleting customer $c failed, error = ".DbManager::i()->error,'dev');
			return Submission::createResult("Could not delete customer");
		}
		return Submission::createResult("Customer deleted",true);
	}

	switch ($_POST['action']) {
		case 'renew':
			echo renewPassword($_POST['customerid']);
			break;
		
		case 'delete':
			echo deleteCustomer($_POST['customerid']);
			break;

		default:
			break;
	}
?>