(function() {
	var app = angular.module("ShopFixAdminApp",[],function($httpProvider) {
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
	
	app.controller("LoginController",function($scope,$http,$window) {
		$scope.login = {};
		$scope.doLogin = function(login) {
			angular.element("#error").html("");
			login.captcha_response = $window.loginResponse;
			$http.post("login.php",{login: btoa(JSON.stringify(login)),token: login.csrftoken})
			.success(function(data, status, headers, config) {
			   	if (data.result == 'failure') {
			   		angular.element("#error").html("<div class=\"alert alert-danger\">Error: " + data.errorMessage + "</div>");
			   		$scope.login = {
			   			csrftoken: login.csrftoken
			   		};
			   	} else {
			   		window.location.replace("./admincp.php");
			   	}
			});
		};
	});
})();