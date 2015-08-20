<?php
	/**
	* Submission
	*/
	class Submission {
		public static function checkFields($fields = array(),$check = array()) {
			$missingField = null;
			foreach ($fields as $field) {
				if (!isset($check[$field])) {
					if (is_string($check[$field]) && strlen($field) <= 0) {
						$missingField = $field;
						break;
					} 
					$missingField = $field;
					break;
				}
			}
			unset($fields);
			return $missingField;
		}
		public static function checkEquality($one,$two) {
			if (is_string($one) && is_string($two)) {
				return (strcmp($one, $two) == 0);
			}
			return $one == $two;
		}
		public static function createResult($msg = "Something went wrong, please try again later",$success = false) {
			if ($success) {
				return json_encode(array("result" => "success","successMessage" => $msg));
			}
			return json_encode(array("result" => "failure","errorMessage" => $msg));
		}
	}
?>