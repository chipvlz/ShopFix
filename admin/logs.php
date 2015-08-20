<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__)."/..");
	require_once ROOT_DIR.'/class.logger.php'; //requires class.dbmanager
	require_once ROOT_DIR.'/class.sessionmanager.php';
	require_once ROOT_DIR.'/class.submission.php';

	if (!SessionManager::i()->isAdminLoggedIn()) {
		Logger::i()->writeLog("Admin is not logged in",'dev');
		die(Submission::createResult("Permission denied"));
	}

	if (!SessionManager::i()->validateToken("LoadLogsToken","csrf","GET")) {
		Logger::i()->writeLog("Token to load logs is missing",'dev');
		die(Submission::createResult("Permission denied"));
	}

	$all_logs = Logger::i()->getLogs();
	$dev_logs = array();
	$access_logs = array();
	foreach ($all_logs as $log) {
		if ($log->mode == "dev") {
			array_push($dev_logs, $log);
		} else if($log->mode == "access") {
			array_push($access_logs, $log);
		}
	}
	echo json_encode(array("all_logs" => $all_logs,"dev_logs" => $dev_logs,"access_logs" => $access_logs));
?>