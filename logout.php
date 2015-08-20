<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.logger.php';

	if (!SessionManager::i()->validateToken("LogoutToken","token")) {
		Logger::i()->writeLog("Logout failed for UserID = ".$_SESSION['userid']);
		header("Location: index.php");
		die();
	}
	SessionManager::i()->destroySession();
?>