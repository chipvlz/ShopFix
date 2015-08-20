<?php
	defined('ADMIN_USER') ?: define('ADMIN_USER', 'ADMIN_USER');
	defined('ADMIN_PW') ?: define('ADMIN_PW', 'YOUR_PW');
	defined('ADMIN_ANSWER') ?: define('ADMIN_ANSWER', 'YOUR_ANSWER'); //second password so to speak
	defined('ADMIN_KEY') ?: define('ADMIN_KEY', 'CREATE_A_STRONG_KEY_HERE'); //use generate key method of class.crypto, base64 encode it twice then or so
	defined('ADMIN_IV') ?: define('ADMIN_IV', 'CREATE_A_STRONG_IV_HERE'); //use generate iv method of class.crypto, base64 encode it twice or so
?>