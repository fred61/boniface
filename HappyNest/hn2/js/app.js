'use strict'

var happyNestApp = angular.module('happyNestApp', [
  'ngRoute'
 ,'ngResource'
]);

console.log(happyNestApp);

happyNestApp.config(['$routeProvider',
                    function($routeProvider) {
                      $routeProvider.
                        when('/list', {
                          templateUrl: 'partials/list.html',
                          controller: "ListController"
                        }).
                        when('/detail/:happyParentId', {
                          templateUrl: 'partials/detail.html',
                          controller: "DetailController"
                        }).
                        otherwise({
                          redirectTo: '/list'
                        });
                    }]);

var appRootURL = function() {
  var scriptSource;
  
  if (document.currentScript) {
    scriptSource= document.currentScript.src;
  } else {  
    var scripts = document.getElementsByTagName('script')
       ,script = scripts[scripts.length - 1];
  
    scriptSource= script.src;
    
  }
  
  return scriptSource.replace('hn2/js/app.js', '');
}();

//var productionRootURL= "http://www.thehappynest.ch/"
//TODO this does not work at present, problems with password protection and stuff
happyNestApp.constant('applicationRootURL', appRootURL);

happyNestApp.config(['$resourceProvider', function($resourceProvider) {
  // Don't strip trailing slashes from calculated URLs
  $resourceProvider.defaults.stripTrailingSlashes = false;
}]);