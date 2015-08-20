<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__)."/..");
	require_once ROOT_DIR . '/class.logger.php'; //requires class.dbmanager
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.submission.php';

	if (!SessionManager::i()->isAdminLoggedIn()) {
		Logger::i()->writeLog("Admin not logged in",'dev');
		die(Submission::createResult("Admin is not logged in"));
	}

	if (!SessionManager::i()->validateToken("UpdateProductToken","token")) {
		Logger::i()->writeLog("Token to update product is missing",'dev');
		die(Submission::createResult("Token mismatch"));
	}

	if ($field = Submission::checkFields("action","product",$_POST)) {
		die(Submission::createResult(ucfirst($field)." is missing or invalid"));
	}

	$product = (array)json_decode(base64_decode($_POST['product']));

	switch ($_POST['action']) {
		case 'soldOut':
			if (!DbManager::i()->update("sf_products",array("soldOut" => intval($product['soldOut'])),array("productid" => intval($product['productid'])))) {
				Logger::i()->writeLog("Marking product as soldOut failed, error = ".DbManager::i()->error,'dev');
				die(Submission::createResult("Failed to mark product as soldOut"));
			}
			break;

		case 'delete':
			if (!DbManager::i()->delete("sf_products",array("productid" => intval($product['productid'])))) {
				Logger::i()->writeLog("Deleting product failed, error = ".DbManager::i()->error,'dev');
				die(Submission::createResult("Failed to delete product"));
			}
			break;

		case 'product':
			if (!DbManager::i()->update("sf_products",$product,array("productid" => intval($product['productid'])))) {
				Logger::i()->writeLog("Update Product failed, error = ".DbManager::i()->error,'dev');
				die(Submission::createResult("Failed to update product"));
			}
			break;
		
		default:
			break;
	}

?>