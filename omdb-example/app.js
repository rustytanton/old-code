angular.module("OmdbSearch", ["ngRoute"])

	.service("OmdbApi", function($http) {
		this.search = function(params) {
			params.callback = 'JSON_CALLBACK';
			return $http.jsonp("http://www.omdbapi.com/", {
				params : params
			});
		};
	})

	.controller("HomeController", function ($scope, $location) {
		$scope.search = function() {
			$location.url("/search/" + $scope.t);
		};
	})

	.controller("MovieController", function ($scope, $routeParams, OmdbApi) {
		OmdbApi.search($routeParams).then(function(result) {
			$scope.done = true;

			$scope.title = result.data.Title;
			delete result.data.Title;
			
			$scope.poster = result.data.Poster;
			delete result.data.Poster;

			delete result.data.imdbID;
			delete result.data.Type;

			$scope.movie = result.data;
		}, function() {
			$scope.error = true;
		});		
	})

	.controller("SearchController", function ($scope, $routeParams, OmdbApi) {
		$scope.searching = true;
		$scope.$routeParams = $routeParams;
		OmdbApi.search($routeParams).then(function(result) {
			$scope.searching = false;
			if (result.data.Error) {
				$scope.error = true;
			} else {
				$scope.done = true;
				$scope.result = result.data.Search;
			}
		}, function() {
			$scope.searching = false;
			$scope.error = true;
		});
	})

	.config(function($routeProvider, $locationProvider) {
		$routeProvider
			.when("/movie/:i", {
				templateUrl: '/partials/movie.html',
				controller: 'MovieController',
			})
			.when("/search/:s", {
				templateUrl: '/partials/search.html',
				controller: 'SearchController',
			})
			.otherwise({
				templateUrl: '/partials/home.html',
				controller: 'HomeController',
			});
		$locationProvider.html5Mode(true);
	});