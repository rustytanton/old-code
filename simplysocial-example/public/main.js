angular.module("simplysocial", ["ngSanitize","ngRoute"])

	.directive("ssDeviceProfile", function($window) {
		return {
			restrict : 'A',
			link : function(scope, ele) {
				scope.$watch(function() {
					return $window.innerWidth;
				}, function(width) {
					var device;
					if (width < 640) {
						device = "mobile";
					} else if (width < 1024) {
						device = "tablet";
					} else {
						device = "desktop";
					}
					ele.removeClass("mobile tablet desktop").addClass(device);
				});
				angular.element($window).bind('resize', function() {
					scope.$apply();
				});
			}
		};
	})

	.controller("ssHeaderController", function($scope, $window, $timeout) {
		var userIntentTimeout;
		$scope.doSearch = function(keyEvent) {
			if (keyEvent.which === 13) {
				$window.alert('Would do a search here...');
			}
		};
		$scope.showUserMenu = function() {
			$timeout.cancel(userIntentTimeout);
			$scope.userMenuVisible = true;
		};
		$scope.hideUserMenu = function() {
			userIntentTimeout = $timeout(function() {
				$scope.userMenuVisible = false;
			}, 250);
		};
		$scope.newMessageModal = function() {
			$scope.showCreateModal = true;
		};
		$scope.closeNewMessageModal = function() {
			$scope.showCreateModal = false;
		};
		$scope.postMessage = function() {
			this.closeNewMessageModal();
		};
	})

	.controller("ssFollowersController", function() {})
	.controller("ssFollowingController", function() {})

	.controller("ssMessagesController", function($scope, $filter) {
		$scope.allMessages = [
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "How to Get Inspired: the Right Way - Designmodo <a href=\"http://bit.ly/1lE4uJc\">bit.ly/1lE4uJc</a> Good stuff from <a href=\"http://twitter.com/designmodo\">@designmodo!</a>",
				type : "text",
				comments : [
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Jed Bridges",
						content : "Great way to start the week. Thanks for sharing!"
					},
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Ren Walker",
						content : "Feeling inspired npw... thanks for great article @designmodo"
					}
				],
				time : "25m"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "Sed faucibus imperdiet tincidunt. Phasellus ut consectetur nibh, non egestas odio. Curabitur eu ultricies tellus, quis aliquam nisi. Morbi dolor ex, sollicitudin vel massa ut, scelerisque vulputate odio. Curabitur pretium sodales arcu sit amet vestibulum. Integer euismod commodo ultrices. Proin ante orci, accumsan sit amet tellus id, maximus faucibus ipsum.",
				type : "text",
				comments : [
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Jed Bridges",
						content : "Great way to start the week. Thanks for sharing!"
					},
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Ren Walker",
						content : "Feeling inspired npw... thanks for great article @designmodo"
					}
				],
				time : "45m"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Fusce ac leo non neque fringilla accumsan. Etiam velit diam, tristique a hendrerit ut, pretium sed diam.",
				type : "photo",
				src : "/images/message-picture-example1.jpg",
				comments : [
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Jed Bridges",
						content : "Great way to start the week. Thanks for sharing!"
					}
				],
				time : "2h"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "How to Get Inspired: the Right Way - Designmodo bit.ly/1lE4uJc Good stuff from @designmodo!",
				type : "text",
				comments : [
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Jed Bridges",
						content : "Great way to start the week. Thanks for sharing!"
					},
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Ren Walker",
						content : "Feeling inspired npw... thanks for great article @designmodo"
					}
				],
				time : "25m"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "How to Get Inspired: the Right Way - Designmodo bit.ly/1lE4uJc Good stuff from @designmodo!",
				type : "text",
				comments : [],
				time : "35m"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "How to Get Inspired: the Right Way - Designmodo bit.ly/1lE4uJc Good stuff from @designmodo!",
				type : "text",
				comments : [
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Jed Bridges",
						content : "Great way to start the week. Thanks for sharing!"
					},
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Ren Walker",
						content : "Feeling inspired npw... thanks for great article @designmodo"
					}
				],
				time : "4h"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "Sed faucibus imperdiet tincidunt. Phasellus ut consectetur nibh, non egestas odio. Curabitur eu ultricies tellus, quis aliquam nisi. Morbi dolor ex, sollicitudin vel massa ut, scelerisque vulputate odio. Curabitur pretium sodales arcu sit amet vestibulum. Integer euismod commodo ultrices. Proin ante orci, accumsan sit amet tellus id, maximus faucibus ipsum.",
				type : "photo",
				src : "/images/message-picture-example2.jpg",
				comments : [
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Jed Bridges",
						content : "Great way to start the week. Thanks for sharing!"
					},
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Ren Walker",
						content : "Feeling inspired npw... thanks for great article @designmodo"
					}
				],
				time : "1d"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Fusce ac leo non neque fringilla accumsan. Etiam velit diam, tristique a hendrerit ut, pretium sed diam.",
				type : "text",
				comments : [
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Jed Bridges",
						content : "Great way to start the week. Thanks for sharing!"
					}
				],
				time : "25m"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "How to Get Inspired: the Right Way - Designmodo bit.ly/1lE4uJc Good stuff from @designmodo!",
				type : "text",
				comments : [
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Jed Bridges",
						content : "Great way to start the week. Thanks for sharing!"
					},
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Ren Walker",
						content : "Feeling inspired npw... thanks for great article @designmodo"
					}
				],
				time : "1h"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "Sed faucibus imperdiet tincidunt. Phasellus ut consectetur nibh, non egestas odio. Curabitur eu ultricies tellus, quis aliquam nisi. Morbi dolor ex, sollicitudin vel massa ut, scelerisque vulputate odio. Curabitur pretium sodales arcu sit amet vestibulum. Integer euismod commodo ultrices. Proin ante orci, accumsan sit amet tellus id, maximus faucibus ipsum.",
				type : "video",
				src : "/images/message-video-example1.jpg",
				comments : [
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Jed Bridges",
						content : "Great way to start the week. Thanks for sharing!"
					},
					{
						avatar : "/images/user-picture-example.jpg",
						name : "Ren Walker",
						content : "Feeling inspired npw... thanks for great article @designmodo"
					}
				],
				time : "25m"
			},
			{
				avatar : "/images/user-picture-example.jpg",
				name : "Sam Soffes",
				content : "How to Get Inspired: the Right Way - Designmodo bit.ly/1lE4uJc Good stuff from @designmodo!",
				type : "text",
				comments : [],
				time : "35m"
			}
		];
		$scope.filterMessages = function(type) {
			$scope.messagesFilter = type;
			if (type == 'all') {
				$scope.messages = $scope.allMessages;
			} else {
				$scope.messages = $filter('filter')($scope.allMessages, { type : type }, true);
			}
		};
		$scope.filterMessages('all');
	})
	
	.controller("ssProfileController", function() {})
	.controller("ssSettingsController", function() {})

	.config(function($routeProvider, $locationProvider) {
		$routeProvider
			.when("/followers", {
				templateUrl: '/partials/followers.html',
				controller: 'ssFollowersController',
			})
			.when("/following", {
				templateUrl: '/partials/following.html',
				controller: 'ssFollowingController',
			})
			.when("/profile", {
				templateUrl: '/partials/profile.html',
				controller: 'ssProfileController',
			})
			.when("/settings", {
				templateUrl: '/partials/settings.html',
				controller: 'ssSettingsController',
			})
			.when("/", {
				templateUrl: '/partials/messages.html',
				controller: 'ssMessagesController',
			})
			.otherwise({
				templateUrl: '/partials/404.html'
			});

		$locationProvider.html5Mode(true);
	});