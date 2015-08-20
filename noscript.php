<?php 
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.settings.php';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo Settings::i()->title; ?></title>
	<meta charset="UTF-8">
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="description" content="ShopFix is a simple but useful shop CMS" />
	<meta name="keywords" content="shopping, shopfix, cms, purchases" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<meta name='expires' content='0'>
	<meta content='no-cache'>

	<link rel="stylesheet" href="./css/bootstrap.min.css">
</head>
<body>
	<div class="container">
		<div class="jumbotron">
		  <h1>Hi there!</h1>
		  <p>It seems you do not have JavaScript enabled for this Website, however <?php echo Settings::i()->title; ?> requires you to have JavaScript enabled.</p>
		  <p>Please enable JavaScript and then you can get back to <a href="index.php">the actual <?php echo Settings::i()->title; ?> website</a></p>
		</div>
	</div>
</body>
</html>