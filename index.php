<?php 
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__));
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.settings.php';

	$loggedIn = SessionManager::i()->isLoggedIn();

	$_SESSION['RegisterToken'] = SessionManager::GenerateToken();
	$_SESSION['LoginToken'] = SessionManager::GenerateToken();
	$_SESSION['LogoutToken'] = SessionManager::GenerateToken();
	$_SESSION['GetPaymentsToken'] = SessionManager::GenerateToken();
	$_SESSION['CartToken'] = SessionManager::GenerateToken();
	$_SESSION['LoadProductsToken'] = SessionManager::GenerateToken();
	$_SESSION['UpdateAccountSettingsToken'] = SessionManager::GenerateToken();
	$_SESSION['AccountSettingsToken'] = SessionManager::GenerateToken();
	$_SESSION['CheckoutToken'] = SessionManager::GenerateToken();
	$_SESSION['DownloadToken'] = SessionManager::GenerateToken();
	$_SESSION['PaymentStatusToken'] = SessionManager::GenerateToken();

?>
<!DOCTYPE html>
<html lang="en" ng-app="ShopFixApp">
	<head>
		<title><?php echo Settings::i()->title; ?></title>

		<!-- Handle NoScript -->
		<noscript>
			<meta http-equiv="refresh" content="0;url=noscript.php">
		</noscript>

		<!-- Meta information -->
		<meta charset="UTF-8">
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="description" content="ShopFix is a simple but useful shop CMS" />
		<meta name="keywords" content="shopping, shopfix, cms, purchases" />
		<meta name="author" content="Websec GmbH" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
		<meta name='expires' content='0'>
		<meta content='no-cache'>

		<!-- jQuery -->
		<script src="./js/jquery-1.11.2.min.js"></script>

		<!-- Angular JS -->
		<script type="text/javascript" src= "./js/angular.min.js"></script>
		<script type="text/javascript" src= "./js/shopfix.js"></script>

		<!-- Bootstrap -->
		<script type="text/javascript" src="./js/bootstrap.min.js"></script>
		<script type="text/javascript" src="./js/modal.js"></script>
		<link rel="stylesheet" href="./css/bootstrap.min.css">

		<script type="text/javascript" src="./js/bootstrap-notify.min.js"></script>

		<!-- Lightbox -->
		<script type="text/javascript" src="./js/lightbox.min.js"></script>
		<link rel="stylesheet" href="./css/lightbox.css">

		<!-- Custom Stylesheets -->
		<link rel="stylesheet" href="./css/shopfix.css">
		<link rel="stylesheet" href="./css/animate.min.css">

		<script type="text/javascript">
			var loginResponse = null;
			var registrationResponse = null;
			var loginContainer = null;
			var registrationContainer = null;
		    var loadCaptcha = function() {
		      loginContainer = grecaptcha.render('login_container', {
		        'sitekey' : '<?php if (!is_null(Settings::i()->captcha_public)) { echo Settings::i()->captcha_public; } ?>',
		        'callback' : function(response) {
		          loginResponse = response;
		        }
		      });
		      registrationContainer = grecaptcha.render('registration_container', {
		        'sitekey' : '<?php if (!is_null(Settings::i()->captcha_public)) { echo Settings::i()->captcha_public; } ?>',
		        'callback' : function(response) {
		          registrationResponse = response;
		        }
		      });
		    };
		</script>

	</head>
	<body ng-controller="PageController as pager" ng-init="pager.loggedIn = '<?php echo $loggedIn; ?>'">
		<nav class="navbar navbar-default">
			<div class="container" style="width:100%;">
				<div class="col-md-12">
					<div id="cartToken" csrf="<?php echo $_SESSION['CartToken']; ?>"></div>
					<div class="navbar-header">
						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#menu-toggle">
							<span class="sr-only">Toggle navigation</span>
					        <span class="icon-bar"></span>
					        <span class="icon-bar"></span>
					        <span class="icon-bar"></span>
					    </button>
					    <a class="navbar-brand" href="index.php"><?php echo Settings::i()->title; ?></a>
					</div>
					<div class="collapse navbar-collapse" id="menu-toggle">
						<form class="navbar-form navbar-right" role="search" ng-show="pager.isPage('main')">
	        				<div class="form-group">
	        					<div class="input-group">
	        						<div class="input-group-addon"><span class="glyphicon glyphicon-search" aria-hidden="true"></span></div>
	          						<input ng-model="query" type="text" class="form-control" placeholder="Search">
	          					</div>
	        				</div>
	      				</form>
	      				<div ng-controller="ShopController">
							<ul class="nav navbar-nav navbar-right" ng-show="Shop.hasItem()">
		      					<li class="dropdown">
		          					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
		          						<span class="glyphicon glyphicon-shopping-cart" aria-hidden="true"></span> 
		          						<b style="color:red">{{Shop.totalPrice() | currency:'€' }}</b>
		          						<span class="caret"></span>
		          					</a>
		          					<ul class="dropdown-menu" role="menu">
		            					<li><a ng-click="pager.setPage('checkout')"><span class="glyphicon glyphicon-check" aria-hidden="true"></span> Checkout</a></li>
		            					<li class="divider"></li>
		            					<li><a ng-click="pager.setPage('cart')"><span class="glyphicon glyphicon-th-list" aria-hidden="true"></span> View Cart</a></li>
		            					<li class="divider"></li>
		            					<li><a ng-click="emptyCart();pager.setPage('main');"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Empty Cart</a></li>
		          					</ul>
		        				</li>
		      				</ul>
		      			</div>
		      			<ul class="nav navbar-nav navbar-right" ng-controller="LoginController">
		      				<li class="dropdown">
		          				<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
		          					<span class="glyphicon glyphicon-user" aria-hidden="true"></span> 
		          					<b style="color:green">Account</b>
		          					<span class="caret"></span>
		          				</a>
		          				<ul class="dropdown-menu" role="menu">
		            				<li ng-show="pager.loggedIn"><a ng-click="pager.setPage('history')"><span class="glyphicon glyphicon-tag" aria-hidden="true"></span> Purchase History</a></li>
		            				<li ng-show="pager.loggedIn" class="divider"></li>
		            				<li ng-show="pager.loggedIn"><a ng-click="pager.setPage('account_settings')"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Account Settings</a></li>
		            				<li ng-show="pager.loggedIn" class="divider"></li>
		            				<li ng-show="pager.loggedIn"><a ng-click="doLogout('<?php echo $_SESSION['LogoutToken']; ?>');"><span class="glyphicon glyphicon-console" aria-hidden="true"></span> Logout</a></li>
		            				<li ng-hide="pager.loggedIn"><a ng-click="pager.setPage('checkout');pager.setSubPage('login');"><span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> Login</a></li>
		            				<li ng-hide="pager.loggedIn"><a ng-click="pager.setPage('checkout');pager.setSubPage('registration');"><span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> Register</a></li>
		          				</ul>
		        			</li>
		      			</ul>
					</div>
				</div>
			</div>
		</nav>
		<div class="container" style="width:100%;">
			<div ng-controller="ProductsController" ng-init="loadProducts('<?php echo $_SESSION["LoadProductsToken"]; ?>');" ng-show="pager.isPage('main')">
				<div class="row">
					<div class="col-md-6" ng-hide="prod.length">
						<h2 class="text-center" ng-show="query.length">No Results for "{{query}}"</h2>
						<h2 class="text-center" ng-hide="query.length">No Products available</h2>
					</div>
					<div ng-repeat="product in prod=(products | filter:{name:query} | orderBy:'name')" class="col-md-4">
						<div class="panel panel-default" style="height:510px!important;">
		  					<div class="panel-body">
		    					<h2>{{ product.name }} <i ng-hide="product.soldOut" class="pull-right">{{ product.price | currency:'€' }}</i><i ng-show="product.soldOut" class="pull-right" style="color:red">Sold Out</i></h2>
		    					<a href="{{ product.bigimage }}" data-lightbox="{{ product.name }}" data-title="{{ product.name }}">
		    						<img ng-src="{{ product.image }}" alt="image" class="img-thumbnail img-responsive center-block" style="max-width:200px;max-height:200px;" />
		    					</a>
		    					<br>
		    					<div ng-controller="TabController as tabCtrl">
			    					<ul class="nav nav-pills">
			  							<li role="presentation" ng-class="{active: tabCtrl.isTabHighlighted('description')}"><a ng-click="tabCtrl.highlightTab('description')">Description</a></li>
									</ul>
									<br>
									<textarea class="form-control" readonly="readonly" readonly>{{ product.description }}</textarea>
								</div>
								<br>
								<button class="btn btn-primary center-block" type="submit" ng-click="addProductToCart(product)" ng-class="{disabled:product.soldOut}">Add to Cart</button>
		  					</div>
						</div>
					</div>
				</div>
			</div>
			<div ng-controller="ShopController">
				<div ng-show="pager.isPage('cart')">
					<div class="row">
						<h2 class="text-center" ng-hide="Shop.hasItem()">It seems your cart is empty</h2>
						<h2 class="text-center" ng-show="Shop.hasItem()">Your Cart</h2>
						<table class="table table-hover" ng-show="Shop.hasItem()">
							<thead>
								<tr>
									<th>Name</th>
									<th>Price per item</th>
									<th>Quantity</th>
									<th>Total Price</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<tr ng-repeat="product in Shop.getCart() track by $index">
									<td>{{ product.name }}</td>
									<td>{{ product.price | currency:'€' }}</td>
									<td>
										<select ng-options="n for n in q=([] | range:1:product.available)" ng-model="product.quantity" ng-change="updateShop();">
											<option value="{{n}}">{{n}}</option>
										</select>
									</td>
									<td>{{ (product.price * product.quantity) | currency:'€' }}</td>
									<td><button type="button" class="btn btn-danger" ng-click="removeItem(product)"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="row">
						<div class="col-md-2 col-md-offset-4">
							<button type="button" class="btn btn-primary center-block" ng-click="pager.setPage('main')">Continue Shopping</button>
						</div>
						<div class="col-md-2">
							<button type="button" class="btn btn-success center-block" ng-show="Shop.hasItem()" ng-click="pager.setPage('checkout')">Proceed to checkout</button>
						</div>
					</div>
					<div class="row">
						<h2 ng-show="Shop.hasItem()" class="pull-right">Total: {{ Shop.totalPrice() | currency:'€' }}</h2>
					</div>
				</div>
				<div ng-show="pager.isPage('checkout')">
					<div ng-show="pager.loggedIn">
						<h2 class="text-center">Checkout</h2>
						<div class="row">
							<div class="panel panel-default">
			  					<div class="panel-body">
			  						<?php if (strlen(Settings::i()->paypal_email) > 0) { ?>
			  						<div class="col-md-6">
										<h3 class="text-center">Checkout with Paypal</h3>
			    						<a><img ng-click="checkoutWithPaypal('<?php echo $_SESSION['CheckoutToken']; ?>');" class="center-block" src="https://www.paypalobjects.com/webstatic/en_US/developer/docs/ec/EC-button.gif" alt="Paypal"/></a>
									</div>
									<?php } if (strlen(Settings::i()->btc_api_key) > 0) { ?>
									<div class="col-md-6">
										<h3 class="text-center">Checkout with Bitcoin</h3>
										<a><img ng-click="checkoutWithBTC('<?php echo $_SESSION['CheckoutToken']; ?>');" class="center-block" src="images/bitcoin.png" alt="BTC"/></a>
									</div>
									<?php } if (strlen(Settings::i()->paypal_email) <= 0 && strlen(Settings::i()->btc_api_key) <= 0) { ?>
										<p class="text-center">Checkout not possible at the moment. Please contact an administrator</p>
									<?php } ?>
			  					</div>
							</div>
							<div id="btcResult" ng-show="btcaddress.length">
								<div class="panel panel-default">
									<div class="panel-body">
										<h3 class="text-center"><span class="glyphicon glyphicon-btc" aria-hidden="true"></span> Please send {{btcamount | currency:'':8}} Bitcoins to: {{btcaddress}}</h3>
										<p class="text-center">After paying please visit 'Payment History' to check for the status. Thanks :)</p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div ng-hide="pager.loggedIn" ng-init="pager.setSubPage('login')">
						<div class="row">
							<div class="col-md-6 col-md-offset-3">
								<div class="panel panel-default">
									<div class="panel-heading"><a ng-show="pager.isSubPage('registration')" ng-click="pager.setSubPage('login')">Login</a><a ng-show="pager.isSubPage('login')" ng-click="pager.setSubPage('registration')">Register</a></div>
									<div class="panel-body">
										<div id="loginPanel">
											<h4 ng-show="pager.isSubPage('login')" class="text-center">Login</h4>
											<h4 ng-show="pager.isSubPage('registration')" class="text-center">Register an account</h4>
											<div id="error" class="col-md-8 col-md-offset-2"></div>
											<div class="col-md-8 col-md-offset-2">
												<form id="loginForm" method="POST" ng-show="pager.isSubPage('login')" ng-controller="LoginController" ng-init="login.csrftoken = '<?php echo $_SESSION["LoginToken"]; ?>';">
													<div class="form-group">
														<label for="username" class="sr-only">Username</label>
														<input type="text" name="username" id="username" ng-model="login.username" class="form-control" placeholder="Username" required autofocus>
													</div>
													<div class="form-group">
														<label for="password" class="sr-only">Password</label>
														<input type="password" name="password" id="password" ng-model="login.password" class="form-control" placeholder="Password" autocomplete="off" required>
													</div>
													<div class="form-group">
														<div id="login_container"></div>
													</div>
													<div class="form-group">
														<button class="btn btn-lg btn-primary btn-block" ng-click="doLogin(login);">Sign in</button>
													</div>
													<div class="form-group" ng-show="pager.isSubPage('login')">
														or <a ng-click="pager.setSubPage('registration')">Register an account</a>
													</div>
												</form>
												<form id="registrationForm" method="POST" ng-show="pager.isSubPage('registration')" ng-controller="RegistrationController" ng-init="registration.csrftoken = '<?php echo $_SESSION["RegisterToken"]; ?>';">
													<div class="form-group">
														<label for="username" class="sr-only">Username</label>
														<input type="text" name="username" id="username" ng-model="registration.username" class="form-control" placeholder="Username" required autofocus>
													</div>
													<div class="form-group">
														<label for="email" class="sr-only">Username</label>
														<input type="email" name="email" id="email" ng-model="registration.email" class="form-control" placeholder="Email" required>
													</div>
													<div class="form-group">
														<label for="password" class="sr-only">Password</label>
														<input type="password" name="password" id="password" ng-model="registration.password" class="form-control" placeholder="Password" autocomplete="off" required>
													</div>
													<div class="form-group">
														<label for="repeat_password" class="sr-only">Repeat Password</label>
														<input type="password" name="repeat_password" id="repeat_password" ng-model="registration.repeat_password" class="form-control" placeholder="Repeat Password" autocomplete="off" required>
													</div>
													<div class="form-group">
														<div id="registration_container"></div>
													</div>
													<div class="form-group">
														<button class="btn btn-lg btn-primary btn-block" ng-click="submitRegistration(registration);">Register</button>
													</div>
												</form>
												<div id="actionResult"></div>
												<script src="https://www.google.com/recaptcha/api.js?onload=loadCaptcha&render=explicit" async defer></script>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div ng-show="pager.isPage('history');" ng-controller="PaymentHistoryController" ng-init="loadPayments('<?php echo $_SESSION['GetPaymentsToken']; ?>');checkPaymentStatus('<?php echo $_SESSION['PaymentStatusToken']; ?>');">
					<div class="row">
						<div class="col-md-12">
							<h2 class="text-center">Payment History</h2>
							<hr>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<h2 class="text-center" ng-show="pending.length">Pending Payments</h2>
						</div>
					</div>
					<div class="row" ng-repeat="payment in pending | orderBy:'-date'">
						<div class="col-md-12">
							<h3>Date: <small>{{payment['date'] * 1000 | date:'dd.MM.yyyy - h:mma'}}</small></h3>
							<h3>Service: <small>{{payment['type']}}</small></h3>
							<h3>Amount: <small>{{payment['amount'] | currency:'':8}}</small></h3>
							<h3>Items: </h3>
							<ul>
								<li ng-repeat="item in payment['cart']">{{item['name']}}</li>
							</ul>
							<h3>Transaction ID: <small>{{payment['token'] | base64decode}}</small></h3>
							<hr>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<h2 class="text-center" ng-show="payments.length">Completed Payments</h2>
						</div>
					</div>
					<div class="row" ng-repeat="payment in payments | orderBy:'-date'">
						<div class="col-md-12">
							<h3>Date: <small>{{payment['date'] * 1000 | date:'dd.MM.yyyy - h:mma'}}</small></h3>
							<h3>Service: <small>{{payment['type']}}</small></h3>
							<h3>Amount: <small>{{payment['amount'] | currency:'':8}}</small></h3>
							<h3>Items: </h3>
							<ul>
								<li ng-repeat="item in payment['cart']">{{item['name']}} (<a ng-click="downloadProduct(item['productid'],payment['token'],'<?php echo $_SESSION['DownloadToken']; ?>')">Download</a>)</li>
							</ul>
							<h3>Transaction ID: <small>{{payment['token'] | base64decode }}</small></h3>
							<hr>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<h2 class="text-center" ng-hide="payments.length || pending.length">It seems you have not made any payments yet</h2>
						</div>
					</div>
				</div>
				<div ng-controller="AccountSettingsController" ng-show="pager.isPage('account_settings');" ng-init="loadInfo('<?php echo $_SESSION['AccountSettingsToken']; ?>');">
					<div class="row">
						<div class="col-md-12">
							<h2 class="text-center">Account Settings</h2>
							<hr>
						</div>
					</div>
					<div class="row">
						<div class="col-md-4 col-md-offset-4">
							<h4>Username</h4>
							<form>
							  	<div class="form-group">
							    	<input type="text" class="form-control disabled" disabled="true" value="{{username}}" id="username" placeholder="Username">
							  	</div>
							</form>
						</div>
					</div>
					<div class="row">
						<div class="col-md-4 col-md-offset-4">
							<h4>Email</h4>
							<form>
							  	<div class="form-group">
							    	<input type="text" class="form-control" ng-model="email" value="{{email}}" id="email" placeholder="Email">
							  	</div>
							  	<button ng-click="saveEmail(email,'<?php echo $_SESSION['UpdateAccountSettingsToken']; ?>');" type="submit" class="btn btn-info">Save</button>
							</form>
						</div>
					</div>
					<div class="row">
						<div class="col-md-4 col-md-offset-4">
							<h4>Password</h4>
							<form>
							  	<div class="form-group">
							    	<input type="password" class="form-control" ng-model="password" value="{{password}}" id="password" placeholder="Password">
							  	</div>
							  	<button ng-click="savePassword(password,'<?php echo $_SESSION['UpdateAccountSettingsToken']; ?>');" type="submit" class="btn btn-info">Save</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>