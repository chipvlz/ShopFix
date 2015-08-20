<?php
	
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__) . "/..");
	require ROOT_DIR.'/class.logger.php';
	require ROOT_DIR.'/class.sessionmanager.php';

	if (isset($_GET['csrf'])) {
		$_GET['csrf'] = str_replace(" ", "+", $_GET["csrf"]);
	}

	if (SessionManager::i()->isAdminLoggedIn() && (!isset($_GET["csrf"]) || !isset($_SESSION['LogoutToken']))) {
		header("Location: admincp.php");
		Logger::i()->writeLog("Tried to logout but failed. Either not logged in or tokens are missing",'dev');
	} else if (SessionManager::i()->validateToken("LogoutToken","csrf","GET")) {
		Logger::i()->writeLog("Tried to logout but failed. GET Token = ".$_GET['csrf'].", Session Token = ".$_SESSION['LogoutToken'],'dev');
		SessionManager::i()->destroySession(true,"index.php");
	} else {
		header("Location: admincp.php");
	}
?>