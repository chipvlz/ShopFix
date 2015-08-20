<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.logger.php'; //requires class.dbmanager
	require_once ROOT_DIR . '/class.submission.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';

	if (SessionManager::i()->isLoggedIn()) {
		$request_method = $_SERVER['REQUEST_METHOD'];
		$userid = intval($_SESSION['userid']);
		$userinfo = DbManager::i()->select("sf_members",array("key","iv","username","email","password"),array("userid" => $userid));
		if ($request_method == "GET") {
			unset($request_method);
			if (!SessionManager::i()->validateToken("AccountSettingsToken","token","GET")) {
				Logger::i()->writeLog("Token to access account settings is missing",'access');
				die(Submission::createResult("Permission denied"));
			}
			if ($userinfo !== false && !is_array($userinfo)) {
				$username = Crypto::DecryptString(base64_decode(base64_decode($userinfo->key)),base64_decode(base64_decode($userinfo->iv)),base64_decode(base64_decode($userinfo->username)));
				$email = Crypto::DecryptString(base64_decode(base64_decode($userinfo->key)),base64_decode(base64_decode($userinfo->iv)),base64_decode(base64_decode($userinfo->email)));
				$password = Crypto::DecryptString(base64_decode(base64_decode($userinfo->key)),base64_decode(base64_decode($userinfo->iv)),base64_decode(base64_decode($userinfo->password)));
				echo json_encode(array("username" => $username,"email" => $email,"password" => $password));
				unset($username);
				unset($email);
				unset($password);
				unset($userinfo);
			} else {
				Logger::i()->writeLog("No user found in the database for UserID = $userid, error = ".DbManager::i()->error,'dev');
				die(Submission::createResult("Could not find user"));
			}
		} else if ($request_method == "POST") {
			unset($request_method);
			if (!SessionManager::i()->validateToken("UpdateAccountSettingsToken","token")) {
				Logger::i()->writeLog("Token to update account settings is missing",'access');
				die(Submission::createResult("Permission denied"));
			}
			if ($userinfo !== false && !is_array($userinfo)) {
				if (isset($_POST['pw'])) {
					$pw = base64_decode($_POST['pw']);
					$pw = base64_encode(base64_encode(Crypto::EncryptString(base64_decode(base64_decode($userinfo->key)),base64_decode(base64_decode($userinfo->iv)),$pw)));
					$update = DbManager::i()->update("sf_members",array("password" => $pw),array("userid" => $userid));
					if ($update) {
						Logger::i()->writeLog("User password updated, UserID = $userid");
						echo Submission::createResult("Password updated successfully",true);
					} else {
						Logger::i()->writeLog("User password could not be updated, error = ".DbManager::i()->error);
						echo Submission::createResult("Could not update password. Please try again later.");
					}
					unset($pw);
				} else if (isset($_POST['email'])) {
					$email = base64_decode($_POST['email']);
					$email = base64_encode(base64_encode(Crypto::EncryptString(base64_decode(base64_decode($userinfo->key)),base64_decode(base64_decode($userinfo->iv)),$email)));
					$update = DbManager::i()->update("sf_members",array("email" => $email),array("userid" => $userid));
					if ($update) {
						Logger::i()->writeLog("User Email updated, UserID = $userid");
						echo Submission::createResult("Email updated successfully",true);
					} else {
						Logger::i()->writeLog("User Email could not be updated, reason = ".DbManager::i()->error);
						echo Submission::createResult("Could not update email. Please try again later.");
					}
					unset($email);
				} else {
					echo Submission::createResult("Invalid POST Parameter");
				}
				unset($userinfo);
			} else {
				die(Submission::createResult("Could not find user"));
			}
		} else {
			die(Submission::createResult("Invalid request method"));
		}
	} else {
		die(Submission::createResult("User is not logged in"));
	}

?>