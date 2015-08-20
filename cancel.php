<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.logger.php';
	if (isset($_GET['token'])) {
		Logger::i()->writeLog("Payment cancelled with token = ".$_GET['token'],'payment');
	} else {
		Logger::i()->writeLog("Payment cancelled with no token",'payment');
	}
	header("Location: index.php");
?>