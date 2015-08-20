<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__)."/..");
	require_once ROOT_DIR.'/class.logger.php'; //requires class.dbmanager
	require_once ROOT_DIR.'/class.sessionmanager.php';
	require_once ROOT_DIR.'/class.submission.php';
	require ROOT_DIR.'/admin/admin_config.php';
	
	if (!SessionManager::i()->isAdminLoggedIn()) {
		Logger::i()->writeLog("Admin is not logged in",'dev');
		die(Submission::createResult("Permission denied"));
	}

	$request_method = $_SERVER['REQUEST_METHOD'];

	if ($request_method == "GET") {
		unset($request_method);
		if (!SessionManager::i()->validateToken("SettingsToken","csrf","GET")) {
			Logger::i()->writeLog("Token to get settings is missing",'dev');
			die(Submission::createResult("Permission denied"));
		}
		header("Content-Type: application/json; charset=UTF-8");
		$settings = DbManager::i()->select("sf_settings",array("settings"));
		if ($settings !== false && !is_array($settings)) {
			$prefs = Crypto::DecryptString(base64_decode(base64_decode(ADMIN_KEY)),base64_decode(base64_decode(ADMIN_IV)),base64_decode(base64_decode($settings->settings)));
			echo json_encode(array("settings" => json_decode(base64_decode($prefs))));
			unset($prefs);
		} else {
			Logger::i()->writeLog("Could not load settings, error = ".DbManager::i()->error,'dev');
			die(Submission::createResult("Could not load Settings"));
		}
	} else if ($request_method == "POST") {
		unset($request_method);
		if (!SessionManager::i()->validateToken("SettingsToken","token")) {
			Logger::i()->writeLog("Token to set settings is missing",'dev');
			die(Submission::createResult("Permission denied"));
		}
		if (isset($_POST['settings'])) {
			$settings = (array)json_decode(base64_decode($_POST['settings']));
			if (isset($settings['paypal']) && count((array)$settings['paypal']) > 0) {
				if ($field = Submission::checkFields(array("username","password","signature"),(array)$settings['paypal'])) {
					die(Submission::createResult(ucfirst($field)." is missing or invalid"));
				}
			} else if (isset($settings['btc']) && count((array)$settings['btc']) > 0) {
				if ($field = Submission::checkFields(array("api_key","api_pin"),(array)$settings['btc'])) {
					die(Submission::createResult(ucfirst($field)." is missing or invalid"));
				}
			} else if (isset($settings['cms_settings']) && count((array)$settings['cms_settings']) > 0) {
				if ($field = Submission::checkFields(array("title"),(array)$settings['cms_settings'])) {
					die(Submission::createResult(ucfirst($field)." is missing or invalid"));
				}
			} else {
				die(Submission::createResult("Invalid Settings"));
			}
			$settings = base64_encode(base64_encode(Crypto::EncryptString(base64_decode(base64_decode(ADMIN_KEY)),base64_decode(base64_decode(ADMIN_IV)),$_POST['settings'])));
			$find = DbManager::i()->select("sf_settings",array("settings"));
			if ($find !== false && !is_array($find)) { //settings already exists
				$update = DbManager::i()->update("sf_settings",array("settings" => $settings));
				if (!$update) {
					Logger::i()->writeLog("Could not update settings, error = ".DbManager::i()->error,'dev');
					die();
				}
			} else {
				$insert = DbManager::i()->insert("sf_settings",array("settings"),array($settings));
				if (!$insert) {
					Logger::i()->writeLog("Could not insert settings, error = ".DbManager::i()->error,'dev');
					die();
				}
			}
			Logger::i()->writeLog("Settings updated");
			unset($find);
			unset($settings);
			die(Submission::createResult("Settings updated successfully",true));
		}
	}
?>