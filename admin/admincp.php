<?php
	defined("ROOT_DIR") ?: define('ROOT_DIR', dirname(__FILE__) . "/..");
	require_once ROOT_DIR . '/class.logger.php';
	require_once ROOT_DIR . '/class.sessionmanager.php';
	require_once ROOT_DIR . '/class.settings.php';
	require_once ROOT_DIR . '/admin/admin_config.php';

	if (!SessionManager::i()->isAdminLoggedIn()) {
		Logger::i()->writeLog("Tried to access this script without permissions. Was that you?",'access');
		SessionManager::i()->destroySession(true,"index.php");
		die();
	}
	$_SESSION["LogoutToken"] = SessionManager::GenerateToken();
	$_SESSION["GetCustomersToken"] = SessionManager::GenerateToken();
	$_SESSION["UpdateCustomersToken"] = SessionManager::GenerateToken();
	$_SESSION["AddProductToken"] = SessionManager::GenerateToken();
	$_SESSION["UpdateProductToken"] = SessionManager::GenerateToken();
	$_SESSION["LoadProductsToken"] = SessionManager::GenerateToken();
	$_SESSION["SettingsToken"] = SessionManager::GenerateToken();
	$_SESSION['LoadLogsToken'] = SessionManager::GenerateToken();
?>

<!DOCTYPE html>
<html ng-app="AdminApp">
	<head>
		<title><?php echo htmlentities(ADMIN_USER) . " - AdminCP"; ?></title>

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
		<script type="text/javascript" src= "./js/modal.js"></script>

		<!-- Bootstrap -->
		<script type="text/javascript" src="../js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="../css/bootstrap.min.css">

		<link rel="stylesheet" href="./css/sidebar.css">
		<link rel="stylesheet" href="../css/shopfix.css"> <!-- Nav Bar and Link Type  -->

		<script type="text/javascript" src="../js/bootstrap-notify.min.js"></script>
		<link rel="stylesheet" href="../css/animate.min.css">

		<script type="text/javascript">
			(function() {
				var app = angular.module('AdminApp', [], function($httpProvider) {
  							$httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
 							
 							var param = function(obj) {
							    var query = '', name, value, fullSubName, subName, subValue, innerObj, i;
							      
							    for(name in obj) {
							    	value = obj[name];
							        
							      	if(value instanceof Array) {
							        	for(i=0; i<value.length; ++i) {
							          		subValue = value[i];
							          		fullSubName = name + '[' + i + ']';
							          		innerObj = {};
							          		innerObj[fullSubName] = subValue;
							          		query += param(innerObj) + '&';
							        	}
							      	} else if(value instanceof Object) {
							        	for(subName in value) {
							          		subValue = value[subName];
							          		fullSubName = name + '[' + subName + ']';
							          		innerObj = {};
							          		innerObj[fullSubName] = subValue;
							          		query += param(innerObj) + '&';
							        	}
							      	} else if(value !== undefined && value !== null)
							        	query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
							    	}
							      
							    	return query.length ? query.substr(0, query.length - 1) : query;
							  };
							 
							
							$httpProvider.defaults.transformRequest = [function(data) {
								return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
							}];
				});

				app.factory('notifyService', function() {
			        return {
			            createNotify: function(msg,type) {
			                return $.notify(msg, {
									allow_dismiss: false,
									type: type,
									showProgressbar: false,
									placement: {
										from: "top",
										align: "center"
									},
								});
			            },
			            createNotifyAllowDismiss: function(msg,type) {
			            	var notify = this.createNotify(msg,type);
			            	notify.update("allow_dismiss",true);
			            	return notify;
			            },
			            closeAfterDelay: function(notify,delay,callback) {
			            	setTimeout(function() {
			            		if (notify) {
			            			notify.close();
			            		}
			            		if (callback) {
			            			callback();
			            		}
			            	},delay);
			            }
			        };
			    });

				app.directive('fileModel', ['$parse', function ($parse) {
				    return {
				        restrict: 'A',
				        link: function(scope, element, attrs) {
				            var model = $parse(attrs.fileModel);
				            var modelSetter = model.assign;
				            
				            element.bind('change', function(){
				                scope.$apply(function(){
				                    modelSetter(scope, element[0].files[0]);
				                });
				            });
				        }
				    };
				}]);

				app.controller("PageController",function($scope) {
					this.tab = "main";
					this.selectTab = function(setTab) {
						this.tab = setTab;
					};
					this.isSelected = function (checkTab) {
						return this.tab === checkTab;
					};
				});

				app.controller("SettingsController",function($scope,$http) {
					$scope.settings = {
						paypal: {

						},
						btc: {

						},
						cms_settings: {

						}
					};
					$scope.loadSettings = function() {
						$http.get('settings.php?csrf=<?php echo $_SESSION["SettingsToken"]; ?>').then(function(r) {
		      			 	if (r.data.result !== "failure") {
		      			 		$scope.settings = r.data.settings;	
		      			 	} 
		      			});
					};
					$scope.loadSettings();
					$scope.updateSettings = function(settings) {
						angular.element("#result").html("");
						$http.post("settings.php",{settings:btoa(JSON.stringify(settings)),token: "<?php echo $_SESSION['SettingsToken']; ?>"})
						.success(function(data,status,headers,config) {
							if (data.result !== "failure") {
								angular.element("#result").html('<div class="alert alert-success" role="alert">Settings updated successfully</div>');
							} else {
								angular.element("#result").html('<div class="alert alert-danger" role="alert">'+data.errorMessage+'</div>');
							}
						});
					};
					$scope.settingsPanel = "cms_settings";
					$scope.isSettingsPanel = function(panel) {
						return $scope.settingsPanel == panel;
					};
					$scope.setSettingsPanel = function(panel) {
						$scope.settingsPanel = panel;
					};
				});

				app.controller("LogController",function($scope,$http) {
					$scope.all_logs = [];
					$scope.dev_logs = [];
					$scope.access_logs = [];
					$scope.selectedLogType = 'all';
					$scope.loadLogs = function() {
						$http.get('logs.php?csrf=<?php echo $_SESSION["LoadLogsToken"]; ?>').then(function(r) {
		      			 	if (r.data.result !== "failure") {
		      			 		$scope.all_logs = r.data.all_logs;
		      			 		$scope.dev_logs = r.data.dev_logs;
		      			 		$scope.access_logs = r.data.access_logs;	
		      			 	} 
		      			});
					};
					$scope.loadLogs();
					$scope.isLogSelected = function(log) {
						return $scope.selectedLogType == log;
					};
					$scope.selectLogs = function(logtype) {
						$scope.selectedLogType = logtype;
					};
					$scope.logsMessage = function(logtype) {
						if (typeof logtype === 'undefined') { logtype = 'all'; }
						var text = '';
						angular.forEach($scope.all_logs, function(value, key) {
							 if (logtype == "all") {
							 	text += value['message']+"\n";
							 } else if (value['mode'] == logtype) {
						     	text += value['message']+"\n";
						     }
						});
						return text;
					};
				});
				
				app.controller("ProductsController",function($scope,$http) {
					$scope.headers = ["#","Name","Price","Description","Available Items","Sold Out","Action"];
					$scope.product = {};
					$scope.products = [];
					$scope.loadProducts = function(csrf) {
						$http.post('../products.php', {token:csrf})
						.success(function(data, status, headers, config) {
							if (data.result == undefined || data.result !== "failure") {
								$scope.products = data.products;
							}
						});
					};
					$scope.addProduct = function(product,csrf) {
						angular.element("#actionResult").html("");
						var fd = new FormData();
				        fd.append('bigimage', $scope.bigimage);
				        fd.append('productfile', $scope.productfile);
				        fd.append('product',btoa(JSON.stringify(product)));
				        fd.append('token',csrf);
				        $http.post("add_product.php", fd, {
				            transformRequest: angular.identity,
				            headers: {'Content-Type': undefined}
				        })
						.success(function(data, status, headers, config) {
							if (data.result == 'failure') {
								angular.element("#actionResult").html("<div class=\"alert alert-danger\">Error: " + data.errorMessage + "</div>");
								$scope.product = {};
							} else {
								angular.element("#actionResult").html("<div class=\"alert alert-success\">" + data.successMessage + "</div>");
								$scope.product = {
									csrftoken: product.csrftoken
								};
							}
						});
					};
					$scope.updateProduct = function(product,csrf,action) {
						$scope.successMessage = null;
						$http.post("update_product.php",{product: btoa(JSON.stringify(product)),token: csrf, action:action})
						.success(function(data,status,headers,config) {
							if (data.result !== "failure") {
								$scope.successMessage = "Product updated successfully";
							} else {
								$scope.errorMessage = data.errorMessage;
							}
						});
					};
					$scope.deleteProduct = function(product,csrf) {
						if (confirm("Are you sure you want to delete this product?") == true) {
							$http.post("update_product.php",{product: btoa(JSON.stringify(product)),token: csrf, action:"delete"})
							.success(function(data,status,headers,config) {
								if (data.result !== "failure") {
									$scope.loadProducts("<?php echo $_SESSION['LoadProductsToken']; ?>");
								}
							});
						}
					};
				});

				app.controller("CustomerController",function($scope,$http,notifyService) {
					$scope.headers = ["#","Username","Email","Date Registered","IP Address","Actions"];
					$scope.loadData = function(csrf) {
						$scope.errorMessage = null;
						$http.post('customers.php', {token:csrf})
						.success(function(data, status, headers, config) {
							if (data.result !== "failure") {
								$scope.customers = data.customers;
							} else {
								var notify = notifyService.createNotify(data.errorMessage,"danger");
								notifyService.closeAfterDelay(notify,2500);
							}
						});
					};
					$scope.renewPassword = function(c,csrf) {
						$scope.errorMessage = null;
						if (confirm("Are you sure you want to reset the user's password?") == true) {
							$http.post('update_customer.php', {token:csrf,customerid:c.customerid,action:"renew"})
							.success(function(data, status, headers, config) {
								if (data.result !== "failure") {
									notifyService.createNotifyAllowDismiss("New Password for <strong>"+ c.name +"</strong> ("+ c.email + "): "+data.successMessage,"success");
								} else {
									var notify = notifyService.createNotify(data.errorMessage,"danger");
									notifyService.closeAfterDelay(notify,2500);
								}
							});
						}
					};

					$scope.deleteUser = function(c,csrf) {
						if (confirm("Are you sure you want to delete this user?") == true) {
							$http.post('update_customer.php', {token:csrf,customerid:c.customerid,action:"delete"})
							.success(function(data, status, headers, config) {
								if (data.result !== "failure") {
									$scope.loadData("<?php echo $_SESSION['GetCustomersToken']; ?>");
									var notify = notifyService.createNotifyAllowDismiss("User <strong>"+c.name+"</strong> deleted","Info");
									notifyService.closeAfterDelay(notify,2500);
								} else {
									var notify = notifyService.createNotify(data.errorMessage,"danger");
									notifyService.closeAfterDelay(notify,2500);
								}
							});
						}
					};

					$scope.loadData("<?php echo $_SESSION['GetCustomersToken']; ?>");
				});

			})();
		</script>

	</head>
	<body ng-controller="PageController as pager">
		<nav class="navbar navbar-default">
		  	<div class="container" style="width:100%;">
			  	<div class="col-md-12">
				    <div class="navbar-header">
				      	<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navitems">
				        	<span class="sr-only">Toggle navigation</span>
				        	<span class="icon-bar"></span>
				        	<span class="icon-bar"></span>
				        	<span class="icon-bar"></span>
				      	</button>
				      	<a class="navbar-brand" href="admincp.php"><span class="glyphicon glyphicon glyphicon-star"></span> <?php echo htmlentities(ADMIN_USER); ?></a>
				    </div>

				    <div class="collapse navbar-collapse" id="navitems">
				      	<ul class="nav navbar-nav">
				        	<li ng-class="{ active:pager.isSelected('main') }"><a ng-click="pager.selectTab('main')"><span class="glyphicon glyphicon glyphicon-user"></span> Admin Control Panel </a></li>
				        	<li class="dropdown"  ng-class="{ active:pager.isSelected('add_user')}">
				          		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><span class="glyphicon glyphicon-th"></span> Actions <span class="caret"></span></a>
				          		<ul class="dropdown-menu" role="menu">
				            		<li ng-class="{ active:pager.isSelected('view_users') }"><a ng-click="pager.selectTab('view_users')">View Users</a></li>
				            		<li class="divider"></li>
				            		<li ng-class="{ active:pager.isSelected('add_product') }"><a ng-click="pager.selectTab('add_product')">Add Product</a></li>
				            		<li ng-class="{ active:pager.isSelected('view_products') }"><a ng-click="pager.selectTab('view_products')">View Products</a></li>
				          		</ul>
				        	</li>
				        	<li ng-class="{ active:pager.isSelected('settings') }"><a ng-click="pager.selectTab('settings')"><span class="glyphicon glyphicon glyphicon-cog"></span> Settings </a></li>
				      	</ul>
				      	<ul class="nav navbar-nav navbar-right">
				        	<li><a href='logout.php?csrf=<?php echo $_SESSION["LogoutToken"]; ?>'><span class="glyphicon glyphicon glyphicon-log-out"></span> Logout</a></li>
				      	</ul>
				    </div>
				</div>
			</div>
		</nav>

		<div class="container" ng-show="pager.isSelected('main')">
			<div ng-controller="LogController">
				<div class="row">
					<div class="col-md-12">
						<div class="page-header">
						  	<h1>Board Logs</h1>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<ul class="nav nav-pills">
						  <li role="presentation" ng-class="{active:isLogSelected('all')}"><a ng-click="selectLogs('all')">All Logs</a></li>
						  <li role="presentation" ng-class="{active:isLogSelected('dev')}"><a ng-click="selectLogs('dev')">Developer Log</a></li>
						  <li role="presentation" ng-class="{active:isLogSelected('access')}"><a ng-click="selectLogs('access')">Access Log</a></li>
						</ul>
					</div>
				</div>
				<div class="row" ng-show="isLogSelected('all')">
					<div class="col-md-12">
						<textarea class="form-control" rows="10" resize="none" placeholder="All Logs..." readonly="readonly" readonly>{{logsMessage()}}</textarea>
					</div>
				</div>
				<div class="row" ng-show="isLogSelected('dev')">
					<div class="col-md-12">
						<textarea class="form-control" rows="10" resize="none" placeholder="Developer Logs..." readonly="readonly" readonly>{{logsMessage('dev')}}</textarea>
					</div>
				</div>
				<div class="row" ng-show="isLogSelected('access')">
					<div class="col-md-12">
						<textarea class="form-control" rows="10" resize="none" placeholder="Access Logs..." readonly="readonly" readonly>{{logsMessage('access')}}</textarea>
					</div>
				</div>
			</div>
		</div>

		<div class="container" ng-show="pager.isSelected('settings')" ng-controller="SettingsController">
			<div class="row">
				<div class="col-md-12">
					<ul class="nav nav-pills">
					  	<li role="presentation" ng-class="{active:isSettingsPanel('cms_settings')}"><a corners="flat" ng-click="setSettingsPanel('cms_settings')">CMS Settings</a></li>
			            <li role="presentation" ng-class="{active:isSettingsPanel('paypal_settings')}"><a corners="flat" ng-click="setSettingsPanel('paypal_settings')">PayPal API Settings</a></li>
			            <li role="presentation" ng-class="{active:isSettingsPanel('bitcoin_settings')}"><a corners="flat" ng-click="setSettingsPanel('bitcoin_settings')">Bitcoin API Settings</a></li>
					</ul>
				</div>
			</div>
			<div class="row">
				<div id="result" class="col-md-4 col-md-offset-4">
				</div>
			</div>
			<div class="row" ng-show="isSettingsPanel('cms_settings')">
				<div class="col-md-8 col-md-offset-2">
					<h2 class="text-center text-uppercase">CMS Settings</h2>
				</div>
				<div class="col-md-8 col-md-offset-2">
					<form>
						<div class="form-group">
						   	<label for="title">CMS Title</label>
						   	<input type="text" class="form-control" id="title" ng-model="settings.cms_settings.title" placeholder="ShopFix" required autofocus>
					  	</div>
					  	<div class="form-group">
						   	<label for="captcha_public">reCaptcha Public Key</label>
					    	<input type="text" class="form-control" id="captcha_public" ng-model="settings.cms_settings.captcha_public" placeholder="" required>
					  	</div>
					  	<div class="form-group">
					    	<label for="captcha_private">reCaptcha Private Key</label>
					    	<input type="text" class="form-control" id="captcha_private" ng-model="settings.cms_settings.captcha_private" placeholder="" required>
					  	</div>
					  	<button class="btn btn-lg btn-primary btn-block" ng-click="updateSettings(settings);">Save</button>
					 </form>
				</div>
			</div>
			<div class="row" ng-show="isSettingsPanel('paypal_settings')">
				<div class="col-md-8 col-md-offset-2">
					<h2 class="text-center text-uppercase">PayPal API Settings</h2>
				</div>
				<div class="col-md-8 col-md-offset-2">
					<form>
						<div class="form-group">
					    	<label for="username">Username</label>
					    	<input type="text" class="form-control" id="username" ng-model="settings.paypal.username" placeholder="PayPal API Username" required autofocus>
					  	</div>
					  	<div class="form-group">
					    	<label for="password">Password</label>
					    	<input type="password" class="form-control" id="password" ng-model="settings.paypal.password" placeholder="Paypal API Password" required>
					  	</div>
					  	<div class="form-group">
					    	<label for="signature">Signature</label>
					    	<input type="text" class="form-control" id="signature" ng-model="settings.paypal.signature" placeholder="Paypal API Signature" required>
					  	</div>
					  	<button class="btn btn-lg btn-primary btn-block" ng-click="updateSettings(settings);">Save</button>
					</form>
				</div>
			</div>
			<div class="row" ng-show="isSettingsPanel('bitcoin_settings')">
				<div class="col-md-8 col-md-offset-2">
					<h2 class="text-center text-uppercase">Bitcoin API Settings</h2>
				</div>
				<div class="col-md-8 col-md-offset-2">
					<form>
						<div class="form-group">
							<label for="api_key">Bitcoin API Key</label>
						 	<input type="text" class="form-control" id="api_key" ng-model="settings.btc.api_key" placeholder="Bitcoin API Key" required autofocus>
					  	</div>
					  	<div class="form-group">
					    	<label for="api_pin">Bitcoin API Pin</label>
					    	<input type="text" class="form-control" id="api_pin" ng-model="settings.btc.api_pin" placeholder="Bitcoin API Pin" required>
					  	</div>
					  	<button class="btn btn-lg btn-primary btn-block" ng-click="updateSettings(settings);">Save</button>
					</form>
				</div>
			</div>
		</div>

		<div class="container" ng-show="pager.isSelected('view_users')">
			<div ng-controller="CustomerController">
				<div class="row">
						<div class="table-responsive">
							<h2>Customers <small><a ng-click="loadData('<?php echo $_SESSION['GetCustomersToken']; ?>')"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></a></small></h2>
							<table class="table table-bordered" ng-show="customers.length">
							<thead>
								<tr>
									<th ng-repeat="header in headers">{{header}}</th>
								</tr>
							</thead>
							<tbody>
								<tr ng-repeat="customer in customers">
									<td>{{ customer.customerid }}</td>
								    <td>{{ customer.name }}</td>
								    <td>{{ customer.email }}</td>
								    <td>{{ customer.date | date }}</td>
								    <td>{{ customer.ip }}</td>
								    <td><a ng-click="renewPassword(customer,'<?php echo $_SESSION["UpdateCustomersToken"]; ?>')">Renew Password</a> - <a ng-click="deleteUser(customer,'<?php echo $_SESSION["UpdateCustomersToken"]; ?>')">Delete Account</a></td>
							  	</tr>
							</tbody>
							</table>
							<p ng-hide="customers.length">It seems you do not have any customers.</p>
						</div>
				</div>
			</div>
		</div>

		<div class="container" ng-show="pager.isSelected('add_product')">
			<div ng-controller="ProductsController" ng-init="product.csrftoken = '<?php echo $_SESSION["AddProductToken"]; ?>';">
				<div class="row">
					<div class="col-md-4 col-md-offset-4">
						<form id="addProductsForm" method="POST" enctype="multipart/form-data">
							<div class="form-group">
								<label for="name" class="sr-only">Product Name</label>
								<input type="text" name="name" id="name" ng-model="product.name" class="form-control" placeholder="Product Name" required autofocus>
							</div>
							<div class="form-group">
								<label for="price" class="sr-only">Price</label>
								<input type="text" name="price" id="price" ng-model="product.price" class="form-control" placeholder="Price" required>
							</div>
							<div class="form-group">
								<label for="password" class="sr-only">Description</label>
								<input type="text" name="description" id="description" ng-model="product.description" class="form-control" placeholder="Description" required>
							</div>
							<div class="form-group">
								<label for="available" class="sr-only">Available</label>
								<input type="text" name="available" id="available" ng-model="product.available" class="form-control" placeholder="How much are available?" required>
							</div>
							<div class="form-group">
								<label for="bigimageurl">Big Image (Preferable 800x800)</label>
								<input class="form-control" type="file" name="bigimage" file-model="bigimage">
							</div>
							<div class="form-group">
								<label for="productfile">Product File</label>
								<input class="form-control" type="file" name="productfile" file-model="productfile">
							</div>
							<div class="form-group">
								<button class="btn btn-lg btn-primary btn-block" ng-click="addProduct(product,'<?php echo $_SESSION['AddProductToken']; ?>');">Add</button>
							</div>
						</form>
						<div id="actionResult"></div>
					</div>
				</div>
			</div>
		</div>


		<div class="container" ng-show="pager.isSelected('view_products')" style="width:100%!important;">
			<div ng-controller="ProductsController" ng-init="loadProducts('<?php echo $_SESSION['LoadProductsToken']; ?>');">
				<div class="row" ng-show="successMessage.length">
					<div class="col-md-12">
						<div class="alert alert-success" role="alert">{{successMessage}}</div>
					</div>
				</div>
				<div class="row" ng-show="errorMessage.length">
					<div class="col-md-12">
						<div class="alert alert-danger" role="alert">{{errorMessage}}</div>
					</div>
				</div>
				<div class="row-fluid">
						<div class="col-md-12" style="width:100%;">
							<h2>Products <small><a ng-click="loadProducts('<?php echo $_SESSION['LoadProductsToken']; ?>');"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></a></small></h2>
							<table class="table table-hover" ng-show="products.length" style="width:100%;">
							<thead>
								<tr>
									<th ng-repeat="header in headers">{{header}}</th>
								</tr>
							</thead>
							<tbody>
								<tr ng-repeat="prod in products track by $index">
									<td>{{ prod.productid }}</td>
									<td class="col-md-2">
										<div class="input-group">
										  	<input type="text" class="form-control" value="{{ prod.name }}" ng-model="prod.name">
										</div>
									</td>
								    <td class="col-md-2">
								    	<div class="input-group">
								    		<span class="input-group-addon" id="dollar-sign-{{$index}}">€</span>
										  	<input type="number" step="0.01" min="0.99" class="form-control" value="{{ prod.price }}" ng-model="prod.price" aria-describedby="dollar-sign-{{$index}}">
										</div>
								    </td>
								    <td class="col-md-4">
										<div class="input-group col-md-12">
										  	<input type="text" class="form-control" value="{{ prod.description }}" ng-model="prod.description">
										</div>
									</td>
									<td class="col-md-1">
								    	<div class="input-group">
										  	<input type="number" min="1" class="form-control" value="{{ prod.available }}" ng-model="prod.available">
										</div>
								    </td>
								    <td class="col-md-1">
										<div class="checkbox">
											<label><input type="checkbox" ng-true-value="1" ng-false-value="0" ng-model="prod.soldOut" ng-checked="prod.soldOut" ng-click="updateProduct(prod,'<?php echo $_SESSION['UpdateProductToken']; ?>','soldOut')"> Sold Out</label>
										</div>
									</td>
									<td><a ng-click="deleteProduct(prod,'<?php echo $_SESSION['UpdateProductToken']; ?>');">Delete Product</a> - <a ng-click="updateProduct(prod,'<?php echo $_SESSION['UpdateProductToken']; ?>','product');">Update Product</a></td>
							  	</tr>
							</tbody>
							</table>
							<p ng-hide="products.length">It seems you do not have any products. <a ng-click="pager.selectTab('add_product')">You can add one here!</a></p>
						</div>
				</div>
			</div>
		</div>
	</body>
</html>