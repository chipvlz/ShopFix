<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__) . "/..");
	require_once ROOT_DIR.'/class.logger.php';
	require_once ROOT_DIR.'/class.sessionmanager.php';
	require_once ROOT_DIR.'/class.settings.php';
	require_once ROOT_DIR.'/class.submission.php';
	require_once ROOT_DIR.'/recaptchalib.php';
	require_once ROOT_DIR.'/admin/admin_config.php';

	if (!SessionManager::i()->validateToken("LoginToken","token")) {
		Logger::i()->writeLog("Token to login is invalid",'access');
		die(Submission::createResult("Please refresh the page and try again"));
	}

	if (isset($_POST['login'])) {
		$login = (array)json_decode(base64_decode($_POST['login']));
		if ($field = Submission::checkFields(array("username","password","answer"),$login)) {
			die(Submission::createResult(ucfirst($field). " is missing or invalid"));
		}
		if (Settings::i()->captcha_private) {
			if (!isset($login['captcha_response'])) {
				die(Submission::createResult("Please validate the captcha"));
			}
			$reCaptcha = new ReCaptcha(Settings::i()->captcha_private);
			$resp = $reCaptcha->verifyResponse($_SERVER["REMOTE_ADDR"],$login['captcha_response']);
			if (!$resp->success) {
				die(Submission::createResult("Please validate the Captcha"));
			}
		}
		if ($login['username'] == ADMIN_USER && $login['password'] == ADMIN_PW && $login['answer'] == ADMIN_ANSWER) {
			$_SESSION['admin_login'] = 1;
			$_SESSION['admin_answer'] = ADMIN_ANSWER;
			Logger::i()->writeLog("Login successful");
			die(Submission::createResult("Admin Login successful",true));
		} else {
			Logger::i()->writeLog("Username: ".$login['username']." or Password: ".$login['password']." are invalid");
			die(Submission::createResult("Username or Password are incorrect"));
		}
	} else {
		die(Submission::createResult("Please fill in all information"));
	}
?>