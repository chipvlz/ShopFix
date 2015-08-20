(function() {
	var app = angular.module("ShopFixApp",[],function($httpProvider) {
		$httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
 		var param = function(obj) {
			var query = '', name, value, fullSubName, subName, subValue, innerObj, i;
			for(name in obj) {
				value = obj[name];
				if (value instanceof Array) {
					for(i=0; i<value.length; ++i) {
						subValue = value[i];
						fullSubName = name + '[' + i + ']';
						innerObj = {};
						innerObj[fullSubName] = subValue;
						query += param(innerObj) + '&';
					}
				} else if (value instanceof Object) {
					for(subName in value) {
						subValue = value[subName];
						fullSubName = name + '[' + subName + ']';
						innerObj = {};
						innerObj[fullSubName] = subValue;
						query += param(innerObj) + '&';
					}
				} else if (value !== undefined && value !== null)
					query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
				}
				return query.length ? query.substr(0, query.length - 1) : query;
		};
		$httpProvider.defaults.transformRequest = [function(data) {
			return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
		}];
	});

	app.factory('messenger', function($rootScope) {
		var sharedService = {};   
 		sharedService.postOnPaymentHistoryClicked = function() {
			this.broadcastMessage('onPaymentHistoryClicked');
		};
		sharedService.broadcastMessage = function(msgname) {
			$rootScope.$broadcast(msgname);
		};
		return sharedService;
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

    app.filter('base64decode', function() {
	  	return function(input) {
		    return atob(input);
		 };
	});

	app.filter('range', function() {
	  	return function(input, min, max) {
		    min = parseInt(min);
		    max = parseInt(max);
		    for (var i=min; i<=max; i++)
		      	input.push(i);
		    return input;
		 };
	});

	app.controller("PageController",function(messenger) {
		this.page = "main";
		this.subpage = "";
		this.loggedIn = 0;
		this.setPage = function(p) {
			this.page = p;
			if (p == 'history') {
				messenger.postOnPaymentHistoryClicked();
			}
		};
		this.isPage = function(p) {
			return this.page === p;
		};
		this.setSubPage = function(p) {
			angular.element("#actionResult").html("");
			this.subpage = p;
		};
		this.isSubPage = function(p) {
			return this.subpage === p;
		};
	});

	app.controller("TabController",function() {
		this.tab = "description";
		this.isTabHighlighted = function(tab) {
			return this.tab === tab;
		};
		this.highlightTab = function(tab) {
			this.tab = tab;
		};
	});

	app.factory('Shop', function() {
		var Shop = {
			price: 0,
			cart: {},
			hasItem: function() { return Object.keys(Shop.cart).length > 0; },
			totalPrice: function() { return Shop.price; },
			setTotalPrice: function(newPrice) { Shop.price = newPrice; },
			addToPrice: function(addedPrice) { Shop.price += addedPrice; },
			removeFromPrice: function(removedPrice) { Shop.price -= removedPrice; },
		    getCart: function() { return Shop.cart; },
			setCart: function(c) { Shop.cart = c; Shop.price = 0; for (var key in Shop.cart) { var product = Shop.cart[key]; Shop.addToPrice((product.price * product.quantity)); } },
			addToCart: function(product) { if (Shop.cart[product.productid]) { product.quantity++; } else { Shop.cart[product.productid] = product; product.quantity = 1; } Shop.addToPrice(product.price); },
			removeFromCart: function(product) { delete Shop.cart[product.productid]; Shop.removeFromPrice((product.price * product.quantity)); },
			emptyCart: function() { Shop.cart = {}; Shop.price = 0; },
		};
		return Shop;
	});

	app.factory('Session', function($http,$window,Shop) {
  		var Session = {
    		data: {},
    		saveSession: function(callback) { 
				$http.post("cart.php",{cart: btoa(JSON.stringify(Shop.getCart())),token: angular.element("#cartToken").attr("csrf")})
				.success(function(data, status, headers, config) {
				   	if (data.result == 'failure') {
				   		if (callback) {
				   			callback(false);
				   		}
				   	} else {
				   		if (callback) {
				   			callback(true);
				   		}
				   	}
				});
    		},
    		updateSession: function(callback) { 
      			$http.get('cart.php?csrf='+angular.element("#cartToken").attr("csrf")).then(function(r) {
      			 	if (r.data.result == "failure") {
      			 		Session.data = {};
      			 	} else {
      			 		Session.data = JSON.parse(r.data);
      			 	}
      			 	if (callback) {
      			 		callback();
      			 	} 
      			});
    		}
  		};
  		Session.updateSession(null);
  		return Session; 
	});

	app.controller("LoginController",function($scope,$sce,$http,$window,notifyService) {
		$scope.login = {};
		$scope.doLogin = function(login) {
			angular.element("#actionResult").html("");
			login.captcha_response = $window.loginResponse;
			$http.post("login.php",{login: btoa(JSON.stringify(login)),token: login.csrftoken})
			.success(function(data, status, headers, config) {
			   	if (data.result == 'failure') {
			   		angular.element("#actionResult").html("<div class=\"alert alert-danger\">Error: " + data.errorMessage + "</div>");
			   		$scope.login = {
			   			csrftoken: login.csrftoken
			   		};
			   	} else {
			   		var notify = notify = notifyService.createNotify("Login was successful... Redirecting","success");
					notifyService.closeAfterDelay(notify,2500,function() {
						window.location.replace('index.php');
					});
			   	}
			});
		};
		$scope.doLogout = function(csrf) {
			$http.post("logout.php",{token:csrf})
			.success(function(data, status, headers, config) {
				window.location.replace("./index.php");
			});
		};
	});

	app.controller("RegistrationController",function($scope,$http,$window) {
		$scope.registration = {};
		$scope.submitRegistration = function(registration) {
			angular.element("#actionResult").html("");
			registration.captcha_response = $window.registrationResponse;
			$http.post("register.php",{registration: btoa(JSON.stringify(registration)),token: registration.csrftoken})
			.success(function(data, status, headers, config) {
			   	if (data.result == 'failure') {
			   		angular.element("#actionResult").html("<div class=\"alert alert-danger\">Error: " + data.errorMessage + "</div>");
			   		if (data.errorMessage == "Username is already taken") {
			   			$scope.registration.username = "";	
			   		}
			   		$scope.registration.password = "";
			   		$scope.registration.repeat_password = "";
			   		$scope.registration.csrftoken = registration.csrftoken;
			   	} else {
			   		angular.element("#actionResult").html("<div class=\"alert alert-success\">" + data.successMessage + "</div>");
			   		$scope.registration = {
			   			csrftoken: registration.csrftoken
			   		};
			   	}
			});
		};
	});

	app.controller("PaymentHistoryController",function($scope,$http,notifyService,Shop,Session) {
		$scope.payments = {};
		$scope.pending = {};
		$scope.psToken = "";
		$scope.lpToken = "";

		$scope.$on('onPaymentHistoryClicked', function() {
			$scope.loadPayments($scope.lpToken);
			$scope.checkPaymentStatus($scope.psToken);
   		});

		$scope.checkPaymentStatus = function(csrf) {
			$scope.psToken = csrf;
			$http.post('paymentstatus.php', {token:csrf})
				.success(function(data, status, headers, config) {
				if (data.result == "success") {
					var notify = notifyService.createNotify(data.successMessage,"info");
					notifyService.closeAfterDelay(notify,2500,function() {
						Shop.emptyCart();
						Session.saveSession(null);
						$scope.loadPayments($scope.lpToken);
					});
				}
			});
		};

		$scope.loadPayments = function(csrf) {
			$scope.lpToken = csrf;
			$http.post('payments.php', {token:csrf})
			.success(function(data, status, headers, config) {
				if (data.result !== "failure") {
					$scope.payments = data.payments;
					$scope.pending = data.pending;
				}
			});
		};
		$scope.downloadProduct = function(productid,transaction_id,csrf) {
			window.location.replace("download.php?token=" + csrf + "&productid="+productid+"&transaction_id="+transaction_id);
		};
	});

	app.controller("AccountSettingsController",function($scope,$http,notifyService) {
		
		$scope.username = "";
		$scope.email = "";
		$scope.password = "";

		$scope.loadInfo = function(csrf) {
			$http.get('account_settings.php?token='+csrf).then(function(r) {
      			 if (r.data.result !== "failure") {
      			 	$scope.username = r.data.username;
      			 	$scope.email = r.data.email;
      			 	$scope.password = r.data.password;
      			 }
      		});
		};

		angular.element("#accountSettingsResult").html("");

		$scope.saveEmail = function(email,csrf) {
			var notify = null;
			$http.post('account_settings.php', {email:btoa(email),token:csrf})
			.success(function(data, status, headers, config) {
				var notify = null;
				if (data.result !== "failure") {
					 notify = notifyService.createNotify("Email has been updated","info");
				} else {
					notify = notifyService.createNotify("Could not update Email","danger");
				}
				notifyService.closeAfterDelay(notify,2500);
			});
		};
		$scope.savePassword = function(pw,csrf) {
			$http.post('account_settings.php', {pw:btoa(pw),token:csrf})
			.success(function(data, status, headers, config) {
				var notify = null;
				if (r.data.result !== "failure") {
					 notify = notifyService.createNotify("Password has been updated","info");
				} else {
					notify = notifyService.createNotify("Could not update Password","danger");
				}
				notifyService.closeAfterDelay(notify,2500);
			});
		};
	});

	app.controller("ShopController",function($scope,$http,Shop,Session,notifyService) {
		$scope.Shop = Shop;
		$scope.updateShop = function() {
			Shop.setCart(Shop.getCart());
			Session.saveSession(null);
		};
		$scope.removeItem = function(product) {
			Shop.removeFromCart(product);
			Session.saveSession(function(success) {
				var notify = null;
				if (success) {
					 notify = notifyService.createNotify("Removed <strong>"+product.name+"</strong> from cart","success");
				} else {
					notify = notifyService.createNotify("Could not removed <strong>"+product.name+"</strong> from cart","danger");
				}
				notifyService.closeAfterDelay(notify,2500);
			});
		};
		Session.updateSession(function() {
			var cart = Session.data;
			$scope.Shop.setCart(cart);
		});
		$scope.emptyCart = function() {
			$scope.Shop.emptyCart();
			Session.saveSession(function(success) {
				var notify = null;
				if (success) {
					 notify = notifyService.createNotify("Cart has been cleared","info");
				} else {
					notify = notifyService.createNotify("Could not clear cart :(");
				}
				notifyService.closeAfterDelay(notify,2500);
			});
		};
		$scope.checkoutWithPaypal = function(csrf) {
			$scope.errorMessage = null;
			var notify = notifyService.createNotify("Checking out with PayPal, please wait...","info");
			$http.post("checkout.php",{total: Shop.totalPrice(),cart: btoa(JSON.stringify(Shop.getCart())),mode: "paypal",token: csrf})
			.success(function(data, status, headers, config) {
				if (data.result !== "failure") {
					notify.close();
					window.location.replace(data);
				} else {
					notify.update("type","danger");
					notify.update("message",data.errorMessage);
					notifyService.closeAfterDelay(notify,2500);
				}
			})
			.error(function(data, status, headers, config) {
				notify.update("type","danger");
				notify.update("message","An error has occured while processing your checkout. Please try again later.");
				notifyService.closeAfterDelay(notify,2500);
			});
		};
		$scope.checkoutWithBTC = function(csrf) {
			var notify = notifyService.createNotify("Checking out with Bitcoin, please wait...","info");
			$http.post("checkout.php",{total: Shop.totalPrice(),cart: btoa(JSON.stringify(Shop.getCart())),mode: "btc",token: csrf})
			.success(function(data, status, headers, config) {
				if (data.result !== "failure") {
					notify.close();
					$scope.btcaddress = data.btcaddress;
					$scope.btcamount = data.btcamount;
				} else {
					notify.update("type","danger");
					notify.update("message",data.errorMessage);
					notifyService.closeAfterDelay(notify,2500);
				}
			});
		};
	});

	app.controller("ProductsController",function(Shop,Session,notifyService,$scope,$http) {
		$scope.products = [];
		$scope.loadProducts = function(csrf) {
			$http.post('products.php', {token:csrf})
			.success(function(data, status, headers, config) {
				if (data.result == undefined || data.result !== "failure") {
					$scope.products = data.products;
				}
			});
		};
	
		$scope.addProductToCart = function(product) {
			Shop.addToCart(product);
			Session.saveSession(function(success) {
				var notify = null;
				if (success) {
					 notify = notifyService.createNotify("Added <strong>"+product.name+"</strong> to cart","success");
				} else {
					notify = notifyService.createNotify("Could not add <strong>"+product.name+"</strong> to cart","danger");
				}
				notifyService.closeAfterDelay(notify,2500);
			});
		};
	});
	
}) ();