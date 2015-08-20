<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__) . "/..");
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.settings.php';
	require_once ROOT_DIR . '/admin/admin_config.php';

	if (SessionManager::i()->isAdminLoggedIn()) {
		header("Location: admincp.php");
		die();
	}
	$_SESSION['LoginToken'] = SessionManager::GenerateToken();
?>
<!DOCTYPE html>
<html lang="en" ng-app="ShopFixAdminApp">
	<head>
		<title><?php echo Settings::i()->title; ?> - AdminCP</title>

		<!-- Meta information -->
		<meta charset="UTF-8">
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="description" content="ShopFix is a simple but useful shop CMS" />
		<meta name="keywords" content="shopping, shopfix, cms, purchases" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
		<meta name='expires' content='0'>
		<meta content='no-cache'>

		<!-- jQuery -->
		<script src="../js/jquery-1.11.2.min.js"></script>

		<!-- Angular JS -->
		<script type="text/javascript" src= "../js/angular.min.js"></script>
		<script type="text/javascript" src= "./js/shopfix.js"></script>

		<!-- Bootstrap -->
		<script type="text/javascript" src="../js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="../css/bootstrap.min.css">

		<script type="text/javascript">
			var loginResponse = null;
			var loginContainer = null;
		    var loadCaptcha = function() {
		      loginContainer = grecaptcha.render('login_container', {
		        'sitekey' : '<?php if (!is_null(Settings::i()->captcha_public)) { echo Settings::i()->captcha_public; } ?>',
		        'callback' : function(response) {
		          loginResponse = response;
		        }
		      });
		    };
		</script>

	</head>
	<body>
		<div class="container">
			<div class="row">
				<div class="panel panel-default">
					<div class="panel-heading"><h3>Login</h3></div>
					<div class="panel-body">
						<div id="loginPanel">
							<div id="error" class="col-md-4 col-md-offset-4"></div>
							<div class="col-md-4 col-md-offset-4">
								<form class="form" id="loginForm" method="POST" ng-controller="LoginController" ng-init="login.csrftoken = '<?php echo $_SESSION["LoginToken"]; ?>';">
									<div class="form-group">
										<label for="username" class="sr-only">Username</label>
										<input type="text" name="username" ng-model="login.username" id="username" class="form-control" placeholder="Username" required autofocus>
									</div>
									<div class="form-group">
										<label for="password" class="sr-only">Password</label>
										<input type="password" name="password" ng-model="login.password" id="password" class="form-control" placeholder="Password" autocomplete="off" required>	
									</div>
									<div class="form-group">
										<label for="answer" class="sr-only">Answer</label>
										<input type="text" name="answer" ng-model="login.answer" id="answer" class="form-control" placeholder="You know it" required>
									</div>
									<div class="form-group">
										<div id="login_container"></div>
									</div>
									<div class="form-group">
										<button ng-click="doLogin(login);" class="btn btn-primary center-block" type="submit">Sign in</button>
									</div>
								</form>
								<script src="https://www.google.com/recaptcha/api.js?onload=loadCaptcha&render=explicit" async defer></script>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>