<?php
	
	define('CRYPTO_METHOD', MCRYPT_BLOWFISH);
	define('CRYPTO_MODE', MCRYPT_MODE_CBC);

	/**
	* Crypto
	*/
	class Crypto {
		
		public static function EncryptString($key,$iv,$enc) {
			if (is_null($key) || is_null($iv) || is_null($enc)) {
				return $enc;
			}
			return mcrypt_encrypt(CRYPTO_METHOD, $key, base64_encode($enc), CRYPTO_MODE, $iv);
		}

		public static function DecryptString($key,$iv,$dec) {
			if (is_null($key) || is_null($iv) || is_null($dec)) {
				return $dec;
			}
			return base64_decode(mcrypt_decrypt(CRYPTO_METHOD, $key, $dec, CRYPTO_MODE, $iv));
		}

		public static function XorString($str,$xor_key=9) {
			$array = str_split($str);
			$result = "";
			for($i = 0; $i < count($array); $i++) {
				$charCode = ord($array[$i]);
				$result .= chr($xor_key^$charCode);
			}
			unset($array);
			return $result;
		}

		public static function generateRandomPassword($length) {
		    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		    $pass = array(); //remember to declare $pass as an array
		    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		    for ($i = 0; $i < $length; $i++) {
		        $n = rand(0, $alphaLength);
		        $pass[] = $alphabet[$n];
		    }
		    $pw = implode($pass); //turn the array into a string
		    unset($pass);
		    return $pw;
		}

		public static function GenerateKey($password) {
			$key = pack('H*', hash_hmac('ripemd128', 'some random string that no one cares about', $password));
			return $key;
		}
		
		public static function GenerateIV() {
			$iv_size = mcrypt_get_iv_size(CRYPTO_METHOD, CRYPTO_MODE);
	    	return mcrypt_create_iv($iv_size, MCRYPT_RAND);
		}
	}
?>