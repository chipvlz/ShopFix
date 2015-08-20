<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.logger.php';
	require_once ROOT_DIR . '/class.settings.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.submission.php';
	require_once ROOT_DIR . '/recaptchalib.php';

	if (!SessionManager::i()->validateToken("RegisterToken","token")) {
		Logger::i()->writeLog("Token to register is missing",'dev');
		echo Submission::createResult("Please refresh the page and try again");
		die();
	}

	if (isset($_POST['registration'])) {
		$registration = (array)json_decode(base64_decode($_POST['registration']));
		if ($field = Submission::checkFields(array("username","email","password","repeat_password"),$registration)) {
			die(Submission::createResult(ucfirst($field). " is missing or invalid"));
		} else if (!Submission::checkEquality($registration['password'],$registration['repeat_password'])) {
			die(Submission::createResult("Passwords do not match"));
		}
		if (!is_null(Settings::i()->captcha_private)) {
			if (!isset($registration['captcha_response'])) {
				die(Submission::createResult("Please validate the captcha"));
			}
			$reCaptcha = new ReCaptcha(Settings::i()->captcha_private);
			$resp = $reCaptcha->verifyResponse($_SERVER["REMOTE_ADDR"],$registration['captcha_response']);
			if (!$resp->success) {
				die(Submission::createResult("Please validate the Captcha"));
			}
		}
		$u = $registration['username'];
		$iv = Crypto::GenerateIV();
		$key = Crypto::GenerateKey($u);
		$username = base64_encode(base64_encode(Crypto::EncryptString($key,$iv,$u)));

		$find = DbManager::i()->select("sf_members",array("userid"),array("key" => base64_encode(base64_encode($key))));
		if ($find && count($find) > 0) {
			die(Submission::createResult("Username is already taken"));
		}
		$pw = base64_encode(base64_encode(Crypto::EncryptString($key,$iv,$registration['password'])));
		$email = base64_encode(base64_encode(Crypto::EncryptString($key,$iv,$registration['email'])));
		$ip = base64_encode(base64_encode(Crypto::EncryptString($key,$iv,$_SERVER['REMOTE_ADDR'])));
		$key = base64_encode(base64_encode($key));
		$iv = base64_encode(base64_encode($iv));
		$reg_date = date("Y-m-d");
		$insert = DbManager::i()->insert("sf_members",array("username","email","password","key","iv","register_date","ip"),array($username,$email,$pw,$key,$iv,$reg_date,$ip));
		if ($insert) {
			Logger::i()->writeLog("Account created with username: $u");
			die(Submission::createResult("Your account has been created successfully",true));
		} else {
			Logger::i()->writeLog("Could not register user, error = ".DbManager::i()->error,'dev');
			die(Submission::createResult("Could not register account. Please try again later"));
		}
	} else {
		die(Submission::createResult("Please fill in all information"));
	}
?>