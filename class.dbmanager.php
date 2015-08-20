<?php
	
	defined("DB_NAME") ?: define('DB_NAME', 'shopfix');
	defined("DB_USER") ?: define('DB_USER', 'DB_USER');
	defined("DB_PW") ?: define('DB_PW', 'DB_PASS');
	defined("DB_HOST") ?: define('DB_HOST', 'DB_HOST');
	defined("DB_CHARSET") ?: define('DB_CHARSET', 'utf8');

	/**
	* DbManager
	*/
	class DbManager {
		static private $instance = null;
		protected $connection = null;
		public $error = null;

		function __construct() {
			$this->connection = mysqli_connect(DB_HOST,DB_USER,DB_PW,DB_NAME);
			mysqli_set_charset($this->connection, DB_CHARSET);
			$this->error = "None";
		}

		function __destruct() {
			mysqli_close($this->connection);
			unset($this->connection);
			unset($this->error);
		}

		static public function i() {
			if (is_null(static::$instance)) {
				static::$instance = new self();
			}
			return static::$instance;
		}

		private function generateTypes($values,$s = null) {
			$types = (is_null($s) ? "" : $s);
			foreach ($values as $value) {
				switch (gettype($value)) {
					case 'integer':
						$types .= "i";
						break;

					case 'double':
						$types .= "d";
						break;

					case 'string':
						$types .= "s";
						break;
					
					default:
						$types .= "b";
						break;
				}
			}
			return $types;
		}

		private function refValues($arr) { 
        	$refs = array();
			foreach ($arr as $key => $value) {
            	$refs[$key] = &$arr[$key]; 
        	}
			return $refs; 
		}

		public function escapeString($string) {
			return mysqli_real_escape_string($this->connection,$string);
		}


		private function generateValues($values) {
			$string = "(";
			for ($i = 0; $i < count($values); $i++) {
				$string .= "?,";
			}
			$string = rtrim($string, ",") . ")";
			return $string;
		}

		/**
		* 	@param tbl string | table name
		* 	@param columns string|array comma separated string or array
		* 	@param values string|array comma separared string or array
		*	@return boolean
		*/
		public function insert($tbl,$columns,$values) {
			if (!is_array($columns) || !is_array($values)) {
				$this->error = "DbManager::i()->insert parameters must be type array";
				return false;
			}
			$query = "INSERT INTO " . $this->escapeString("`" . DB_NAME . "`.".$tbl) . (is_null($columns) ? "" : " (`" . implode("`,`", $columns) . "`)") . " VALUES " . $this->generateValues($values);
			$query = $this->escapeString($query);
			if ($prep = mysqli_prepare($this->connection,$query)) {
				array_unshift($values, $this->generateTypes($values));
				array_unshift($values, $prep);
				if (call_user_func_array("mysqli_stmt_bind_param", $this->refValues($values))) {
					mysqli_stmt_execute($prep);
					mysqli_stmt_close($prep);
					unset($prep);
					unset($query);
					return true;
				}
			}
			$this->error = "DbManager::i()->insert could not prepare query";
			unset($query);
			return false;
		}

		/**
		*	@param tbl | table name
		*	@param cond | which row to delete
		*/

		public function delete($tbl,$cond=null) {
			if ($cond !== null && !is_array($cond)) {
				$this->error = "DbManager::i()->delete parameter must either be null or an array";
				return false;
			}
			$query = "DELETE FROM " . $this->escapeString("`" . DB_NAME . "`.".$tbl) . (is_null($cond) ? "" : " WHERE " . $this->generateConditions($cond));
			$query = $this->escapeString($query);
			if ($prep = mysqli_prepare($this->connection,$query)) {
				$types = "";
				if (!is_null($cond)) {
					$types = $this->generateTypes($cond);
				}
				$params = array();
				array_push($params, $prep);
				array_push($params, $types);
				if (!is_null($cond)) {
					foreach ($cond as $key => $value) {
						array_push($params, $value);
					}
				}
				if (!call_user_func_array("mysqli_stmt_bind_param", $this->refValues($params))) {
					$this->error = "DbManager::i()->delete call to mysqli_stmt_bind_param failed";
					unset($query);
					unset($params);
					return false;
				}
				mysqli_stmt_execute($prep);
				mysqli_stmt_close($prep);
				unset($prep);
				unset($query);
				unset($params);
				return true;
			}
			$this->error = "DbManager::i()->delete could not prepare query";
			return false;
		}

		private function generateSets($set) {
			if (is_array($set)) { //array
				$string = "";
				foreach ($set as $key => $value) {
					$string .= $key . " = ?,";
				}
				$string = rtrim($string, ",") . " ";
				return $string;
			} else { //string
				return $set;
			}
		}

		private function generateConditions($cond) {
			if (is_array($cond)) {
				$string = "";
				foreach ($cond as $key => $value) {
					$string .= '`'.$key.'`' . " = ? AND ";
				}
				$string = rtrim($string, "AND ");
				return $string;
			} else {
				return $cond;
			}
		}

		/**
		*	@param tbl string | table name
		*	@param set array |  key => value
		* 	@param cond array | key => value
		*	@return boolean
		*/
		public function update($tbl,$set,$cond = null) {
			if (!is_array($set) || ($cond !== null && !is_array($cond))) {
				$this->error = "DbManager::i()->update invalid parameters";
				return false;
			}
			$query = "UPDATE " . $this->escapeString("`" . DB_NAME . "`.".$tbl) . " SET " . $this->generateSets($set) . (is_null($cond) ? "" : "WHERE " . $this->generateConditions($cond));
			$query = $this->escapeString($query);
			if ($prep = mysqli_prepare($this->connection,$query)) {
				$types = $this->generateTypes($set);
				if (!is_null($cond)) {
					$types = $this->generateTypes($cond,$types); //append to first types
				}
				$params = array();
				array_push($params, $prep);
				array_push($params, $types);
				foreach ($set as $key => $value) {
					array_push($params, $value);
				}
				if (!is_null($cond)) {
					foreach ($cond as $key => $value) {
						array_push($params, $value);
					}
				}
				if (!call_user_func_array("mysqli_stmt_bind_param", $this->refValues($params))) {
					$this->error = "DbManager::i()->update call to mysqli_stmt_bind_param failed";
					unset($query);
					unset($params);
					return false;
				}
				mysqli_stmt_execute($prep);
				mysqli_stmt_close($prep);
				unset($prep);
				unset($query);
				unset($params);
				return true;
			}
			$this->error = "DbManager::i()->update could not prepare query";
			return false;
		}

		/**
		*	@param tbl string | table name
		*	@param info array | columns to select
		*	@param cond array | key => value
		*	@return array on success | false on failure
		*/
		public function select($tbl,$info,$cond = null) {
			if (!is_array($info) || ($cond !== null && !is_array($cond))) {
				$this->error = "DbManager::i()->select invalid parameters";
				return false;
			}
			$query = "SELECT `" . implode("`,`", $info) . "` FROM " . $this->escapeString("`" . DB_NAME . "`.".$tbl) . (is_null($cond) ? "" : " WHERE " . $this->generateConditions($cond));
			$query = $this->escapeString($query);
			if ($prep = mysqli_prepare($this->connection,$query)) {
				$types = "";
				if (!is_null($cond)) {
					$types = $this->generateTypes($cond,$types);
				}
				$params = array();
				array_push($params, $prep);
				array_push($params, $types);
				if (!is_null($cond)) {
					foreach ($cond as $key => $value) {
						array_push($params, $value);
					}
				}
				if (!is_null($cond)) {
					if (!call_user_func_array("mysqli_stmt_bind_param", $this->refValues($params))) {
						$this->error = "DbManager::i()->select call to mysqli_stmt_bind_param failed";
						unset($query);
						unset($params);
						return false;
					}
				}
				mysqli_stmt_execute($prep);
				$results = array();
				$results[] = $prep;
				$row = new stdClass();
				foreach ($info as $key => $value) {
					$results[] = &$row->$value;
				}
				if (call_user_func_array("mysqli_stmt_bind_result", $results)) {
					$resultArray = array();
					while (mysqli_stmt_fetch($prep)) {
						$obj = new stdClass();
						foreach ($info as $key => $value) {
							$obj->$value = $row->$value;
						}
						array_push($resultArray, $obj);
					}
					mysqli_stmt_close($prep);
					unset($prep);
					unset($query);
					unset($params);
					return (count($resultArray) == 1) ? $resultArray[0] : (array)$resultArray;
				}
				$this->error = "DbManager::i()->select call to mysqli_stmt_bind_result failed";
				mysqli_stmt_close($prep);
				unset($prep);
				unset($query);
				unset($params);
				return false;
			}
			$this->error = "DbManager::i()->select could not prepare query";
			return false;
		}
	}
?>