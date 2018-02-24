'use strict';

/* Controllers */

happyNestApp.controller('AppController', [ '$scope', '$resource', 'applicationRootURL', function($scope, $resource, appRootURL) {
  console.log("AppController: ", appRootURL);
  
  $scope.selectedSession={type:"playgroup", showHistory:false};
  
  $scope.sessionTypes= {
      all: {name : "All", predicate : function(sessionOccurence){ return true;}},
      playgroup: {name : "Playgroup", predicate : function(sessionOccurence){ return sessionOccurence.session.name == "Playgroup"; }},
      english: {name : "English", predicate : function(sessionOccurence){ return sessionOccurence.session.name.startsWith("English"); }},
      music: {name : "Music", predicate : function(sessionOccurence){ return sessionOccurence.session.name == "Music"; }},
      waiting: {name : "Waiting List", predicate : function(sessionOccurence){ return sessionOccurence.session.name == "Waiting List" }}
  };
  
  
  $scope.HappyParent= $resource(appRootURL + 'hn/api/v1/parents/');
  $scope.parents= $scope.HappyParent.query();
  $scope.dirtyIndices= {};
} ]);

happyNestApp.controller('ListController', ['$scope', function($scope){
  
  $scope.parents.$promise.then(function(){
    $scope.sessionOccurences= flattenForest($scope.parents, $scope.selectedSession, $scope.sessionTypes);
  });

  $scope.changeSession= function() {
    console.log("changeSession: ", $scope.selectedSession);
    $scope.sessionOccurences= flattenForest($scope.parents, $scope.selectedSession, $scope.sessionTypes);
  }
  
  $scope.save= function() {
    
    for(var parentIndex in $scope.dirtyIndices) {
      console.log("saving", parentIndex);
      $scope.parents[parentIndex].$save();
    }
  }
  
}]);

happyNestApp.controller('DetailController',  ['$scope', '$routeParams', function($scope, $routeParams){
  console.log("DetailController pre promise");
  $scope.parents.$promise.then(function(){
    console.log("DetailController");
    $scope.happyParent= $scope.parents[$routeParams.happyParentId];
    $scope.happyParentMaster= angular.copy($scope.happyParent);
  });
  
  $scope.cancel= function(){
    $scope.parents[$routeParams.happyParentId]= $scope.happyParentMaster;
    console.log("cancel", $routeParams.happyParentId);
  };
  
  $scope.ok= function() {
    $scope.dirtyIndices[$routeParams.happyParentId]= true;
    console.log("OK", $scope.dirtyIndices);
  }
}]);

//TODO split between flattenForest and flattenTree is not useful any more, merge.
function flattenForest(parents, selectedSession, sessionTypes)
{
  var sessions= [];
  
  for (var parentIndex= 0; parentIndex < parents.length; parentIndex++) {
    var parentSessions= flattenTree(parents, parentIndex, selectedSession, sessionTypes);
    console.log("flattenForest, parentSessions: ", parentSessions);
    sessions= sessions.concat(parentSessions);
  }
  
  console.log("flattenForest, sessions:", sessions);
  return sessions;
}

function flattenTree(parents, parentIndex, selectedSession, sessionTypes)
{
  var p= parents[parentIndex];
  console.log("flattenTree for parent", p);
  
  var sessions= [];
  console.log("flattenTree, sessions initialised", sessions);
  
  var firstParentOccurence= true;
  var happyParent= {index:parentIndex, soCount:0, name:p.name, salutation:p.salutation, address:p.address};

  var predicate= sessionTypes[selectedSession.type].predicate;
  if (typeof predicate != "function") {
    predicate=function(){return true;}
  }
  
  for (var j= 0; j < p.children.length; j++)
  {
    console.log("flattenTree, for loop entered");
    
    var c= p.children[j];
    var happyChild= {happyParent:happyParent, soCount:0, name:c.name, nickname:c.nickname};
    var firstChildOccurence= true;
    
    for (var si in c.sessions)
    {
      if (c.sessions[si].length > 0) {
        var firstSessionOccurence= true;
        var session= {soCount:0, happyChild:happyChild, name:c.sessions[si][0].session.name};
        
        var occurences= [];
        var pass= false;

        for (var k= 0; k < c.sessions[si].length; k++)
        {
          var so= c.sessions[si][k];
          
          var sessionOccurence= {session:session, validFrom:so.valid_from, validTo:so.valid_to, weekdays:so.weekdays,
              firstOccurence:false, firstChildOccurence:false, firstParentOccurence:false};
          
          if (predicate(sessionOccurence) && sessionOccurence.validTo == null && (selectedSession.showInactive || sessionOccurence.weekdays != "")) {
            pass= true;
            occurences.push(sessionOccurence);
          } else if (selectedSession.showHistory) {
            occurences.push(sessionOccurence);
          }
        }
        
        if (pass) {
          occurences[0].firstOccurence= true;
          occurences[0].firstChildOccurence= firstChildOccurence; firstChildOccurence= false;
          occurences[0].firstParentOccurence= firstParentOccurence; firstParentOccurence= false;
          
          occurences.forEach(function(elt, i) {
              sessions.push(elt);
          });
          
          session.soCount+= occurences.length;
          session.happyChild.soCount+= occurences.length;
          session.happyChild.happyParent.soCount+= occurences.length;
        }
      }
    }
  }
  console.log("flattenTree, sessions about to be returned", sessions);
  return sessions;

}
