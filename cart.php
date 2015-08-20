<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.logger.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.submission.php';

	$request_method = $_SERVER['REQUEST_METHOD'];

	if ($request_method == "GET") {
		if (!SessionManager::i()->validateToken("CartToken","csrf","GET")) {
			Logger::i()->writeLog("Token to get cart is missing",'dev');
			die(Submission::createResult("Permission denied"));
		}
		header("Content-Type: application/json; charset=UTF-8");
		if (isset($_SESSION['shopping-cart']) && strlen($_SESSION['shopping-cart']) > 0) {
			echo json_encode(base64_decode($_SESSION['shopping-cart']));
			die();
		}
		echo Submission::createResult("Missing Shopping Cart");
	} else if ($request_method == "POST") {
		if (!SessionManager::i()->validateToken("CartToken","token")) {
			Logger::i()->writeLog("Token to set cart is missing",'dev');
			die(Submission::createResult("Permission denied"));
		}
		if (isset($_POST['cart'])) {
			$_SESSION['shopping-cart'] = $_POST['cart'];
			if (SessionManager::i()->isLoggedIn()) {
				$find = DbManager::i()->select("sf_carts",array("cart"),array("userid" => intval($_SESSION['userid'])));
				if ($find !== false && !is_array($find)) { //cart already exists for user
					$update = DbManager::i()->update("sf_carts",array("cart" => $_SESSION['shopping-cart']),array("userid" => intval($_SESSION['userid'])));
					if (!$update) {
						Logger::i()->writeLog("Updating cart failed, error = ".DbManager::i()->error,'dev');
						die(Submission::createResult("Failed to update cart"));
					}
				} else {
					$insert = DbManager::i()->insert("sf_carts",array("cart","userid"),array($_SESSION['shopping-cart'],intval($_SESSION['userid'])));
					if ($insert) {
						Logger::i()->writeLog("Inserting cart failed, error = ".DbManager::i()->error,'dev');
						die(Submission::createResult("Failed to insert cart"));
					}
				}
				unset($find);
			}
		}
	}
?>