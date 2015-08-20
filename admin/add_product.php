<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__)."/..");
	require_once ROOT_DIR . '/class.imageresizer.php';
	require_once ROOT_DIR . '/class.logger.php'; //requires class.dbmanager
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.submission.php';

	function processImages($image,&$imagePath,&$bigImagePath) {
		$result = null;
		if ($_FILES[$image]["error"] == UPLOAD_ERR_OK) {
			$tmp_name = $_FILES[$image]["tmp_name"];
			$name = $_FILES[$image]["name"];
			$target_file = dirname(__DIR__)."/images/".$name;
			$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
			if (file_exists($target_file)) {
				$bigImagePath = str_replace($_SERVER['DOCUMENT_ROOT'], "", $target_file);
				$imagePath = ImageResizer::i()->resizeImage($target_file,200,200);
				if ($imagePath == null) {
					$result = "Resizing failed";
				} 
			} else if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
				$result = "Invalid File Type. Only JPG, PNG and GIF are allowed";
			} else {
				if (move_uploaded_file($tmp_name,$target_file)) {
					$bigImagePath = str_replace($_SERVER['DOCUMENT_ROOT'], "", $target_file);
					$imagePath = ImageResizer::i()->resizeImage($target_file,200,200);
					if ($imagePath == null) {
						$result = "Resizing failed";
					}
				} else {
					$result = "Moving failed";
				}
			}
		} else {
			$result = "Upload failed";
		}
		return $result;
	}

	function processFile($file,&$filePath) {
		$result = null;
		if ($_FILES[$file]["error"] == UPLOAD_ERR_OK) {
			$tmp_name = $_FILES[$file]["tmp_name"];
			$name = $_FILES[$file]["name"];
			$target_file = dirname(__DIR__)."/uploads/".$name;

			if (file_exists($target_file)) {
				$result = "File exists already";
			} else {
				if (move_uploaded_file($tmp_name,$target_file)) {
					$filePath = str_replace($_SERVER['DOCUMENT_ROOT'], "", dirname(__DIR__)."/uploads/".$name);
				} else {
					$result = "Moving failed";
				}
			}
		}
		return $result;
	}

	if (!SessionManager::i()->isAdminLoggedIn()) {
		Logger::i()->writeLog("Admin is not logged in",'access');
		die(Submission::createResult("Permission denied"));
	}
	if (!SessionManager::i()->validateToken("AddProductToken","token")) {
		Logger::i()->writeLog("Token to add product is missing",'access');
		die(Submission::createResult("Please refresh the page and try again"));
	}
	if (isset($_POST['product'])) {
		$product = (array)json_decode(base64_decode($_POST['product']));
		if ($field = Submission::checkFields(array("name","price","description","available"),$product)) {
			die(Submission::createResult(ucfirst($field). " is missing or invalid"));
		} else if (!isset($_FILES) || $field = Submission::checkFields(array("bigimage","productfile"),$_FILES)) {
			die(Submission::createResult(ucfirst($field). " is missing or invalid"));
		}
		$imagePath = null;
		$bigImagePath = null;
		$productPath = null;
		if (($res = processImages("bigimage",$imagePath,$bigImagePath)) || is_null($imagePath) || is_null($bigImagePath)) {
			die(Submission::createResult("Failed to process image -> ".$res));
		}
		if (($res = processFile("productfile",$productPath)) || is_null($productPath)) {
			die(Submission::createResult("Failed to process Product File -> ".$res));
		}
		if (floatval($product['price']) == 0) {
			die(Submission::createResult("Price can not be 0"));
		}
		$soldOut = (intval($product['available']) == 0) ? 1: 0;
		$insert = DbManager::i()->insert("sf_products",array("name","price","description","available","image","bigimage","file","soldOut"),array($product['name'],floatval($product['price']),$product['description'],intval($product['available']),$imagePath,$bigImagePath,$productPath,$soldOut));
		if ($insert) {
			Logger::i()->writeLog("Added Product successfully");
			echo Submission::createResult("Product added successfully",true);
		} else {
			Logger::i()->writeLog("Could not add product. error = ".DbManager::i()->error,'dev');
			echo Submission::createResult("Could not add product");
		}
		unset($product);
		unset($imagePath);
		unset($bigImagePath);
		unset($productPath);
	} else {
		Logger::i()->writeLog("Tried to access script without post parameters",'dev');
		echo Submission::createResult("Bad request");
	}
?>