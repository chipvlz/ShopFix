<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.logger.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.submission.php';

	if (!SessionManager::i()->validateToken("LoadProductsToken","token")) {
		Logger::i()->writeLog("Token to load products is missing",'dev');
		die(Submission::createResult("Permission denied"));
	}

	header("Content-Type: application/json; charset=UTF-8");

	$products = DbManager::i()->select("sf_products",array("productid","name","price","description","available","image","bigimage","soldOut"));
	if ($products !== false) {
		$prods = array();
		if (!is_array($products)) {
			$products = array($products);
		} 
		foreach ($products as $product) {
			array_push($prods, array(
				"productid" => $product->productid,
				"name" => $product->name,
				"price" => $product->price,
				"description" => $product->description,
				"available" => intval($product->available),
				"image" => $product->image,
				"bigimage" => $product->bigimage,
				"soldOut" => intval($product->soldOut)
			));
		}
		echo json_encode(array("products" => $prods));
		unset($prods);
		unset($products);
	} else {
		Logger::i()->writeLog("Could not get products, error = ".DbManager::i()->error,'dev');
		die(Submission::createResult("Could not get products"));
	}
?>