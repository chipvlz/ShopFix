<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.logger.php';
	require_once ROOT_DIR . '/class.submission.php';
	require_once ROOT_DIR . '/class.settings.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/recaptchalib.php';

	if (!SessionManager::i()->validateToken("LoginToken","token")) {
		Logger::i()->writeLog("Token to login is missing",'dev');
		die(Submission::createResult("Please refresh the page and try again"));
	}

	if (isset($_POST['login'])) {
		$login = (array)json_decode(base64_decode($_POST['login']));
		if ($field = Submission::checkFields(array("username","password"),$login)) {
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

		$key = Crypto::GenerateKey($login['username']);
		$find = DbManager::i()->select("sf_members",array("iv","userid"),array("key" => base64_encode(base64_encode($key))));
		if ($find !== false) {
			if (!is_array($find)) {
				$iv = base64_decode(base64_decode($find->iv));
				$password = base64_encode(base64_encode(Crypto::EncryptString($key,$iv,$login['password'])));
				$find = DbManager::i()->select("sf_members",array("userid"),array("password" => $password));
				if ($find !== false && !is_array($find)) {
					echo Submission::createResult("login successful",true);
					$_SESSION['login'] = 1;
					$_SESSION['userid'] = $find->userid;
					$find = DbManager::i()->select("sf_carts",array("cart"),array("userid" => $find->userid));
					if ($find !== false && !is_array($find)) { //cart already exists for user
						if ($find->cart != "e30=" && strlen($find->cart) != 4) { //not empty cart - overwrite with saved one from DB
							$_SESSION['shopping-cart'] = $find->cart;
						} else { //empty cart, use session cart
							if (isset($_SESSION['shopping-cart'])) {
								DbManager::i()->update("sf_carts",array("cart" => $_SESSION['shopping-cart']),array("userid" => intval($_SESSION['userid'])));
							}
						}
					} else {
						if (isset($_SESSION['shopping-cart'])) {
							DbManager::i()->insert("sf_carts",array("cart","userid"),array($_SESSION['shopping-cart'],intval($_SESSION['userid'])));
						}
					}					
				} else {
					Logger::i()->writeLog("Login is incorrect (".$login['username'].":".$login['password'].")");
					echo Submission::createResult("Username or Password are incorrect");
				}
			} else {
				Logger::i()->writeLog("User does not exist: ".$login['username']);
				echo Submission::createResult("No user found with this username");
			}
		} else {
			Logger::i()->writeLog("Could not get check for login, error = ".DbManager::i()->error,'dev');
			echo Submission::createResult("Username or Password are incorrect");
		}
	} else {
		echo Submission::createResult("Please fill in all information");
	}
?>