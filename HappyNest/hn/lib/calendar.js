'use strict';

var asOf;
var datesTable;

function ExtendedDate(date)
{
	this.delegate= date;
}
ExtendedDate.prototype= { };

ExtendedDate.prototype.getTime= function() {return this.delegate.getTime()}
ExtendedDate.prototype.getDate= function() {return this.delegate.getDate()}
ExtendedDate.prototype.getDay= function() {return this.delegate.getDay()}
ExtendedDate.prototype.getMonth= function() {return this.delegate.getMonth()}
ExtendedDate.prototype.getFullYear= function() {return this.delegate.getFullYear()}

ExtendedDate.prototype.setTime= function(x) {return this.delegate.setTime(x)}
ExtendedDate.prototype.setDate= function(x) {return this.delegate.setDate(x)}
ExtendedDate.prototype.setMonth= function(x) {return this.delegate.setMonth(x)}
ExtendedDate.prototype.setFullYear= function(x) {return this.delegate.setFullYear(x)}

ExtendedDate.prototype.toLocaleString= function(x,y) {return this.delegate.toLocaleString(x,y)}

ExtendedDate.prototype.truncateTime= function() {
	this.delegate.setTime(this.delegate.getTime() - this.delegate.getTime() % 86400000);	
}
ExtendedDate.prototype.sameDay= function(otherDate)
{
	console.log("sameDay %o, %o", this, otherDate);
	
	var result= this.delegate.getDate() == otherDate.delegate.getDate()
		&& this.delegate.getMonth() == otherDate.delegate.getMonth()
		&& this.delegate.getFullYear() == otherDate.delegate.getFullYear();
	
	return result;
	//TODO test otherDate for methods being present and do something reasonable when they're not.
}


angular.module('calendarApp', [])
	.controller('CalendarCtrl', function($scope, $window) {

	//$window.location.search holds the query part of the URL including the ? at the start
	//convert it into an object with properties and values:
	var query= $window.location.search.substr(1).split("&").reduce(
				function(previousValue, arrayValue, index, array)
				{
					var kv= arrayValue.split("=");
					previousValue[kv[0]]= kv[1];
					return previousValue;
				},
				{}
			);

	$scope.today= new ExtendedDate(new Date());
	$scope.today.truncateTime();

	//PM: asOf is in seconds UTC
	if (typeof query.asOf === 'undefined') {
		$scope.asOf= new ExtendedDate(new Date());
	} else {
		$scope.asOf= new ExtendedDate(new Date(parseInt(query.asOf) * 1000));
	}
	console.log("asOf is %o", $scope.asOf);
	
	$scope.pageAsOf= new ExtendedDate(new Date($scope.asOf.getTime()));
	
	$scope.datesTable= function() {
		
		if (typeof asOf === 'undefined' || $scope.asOf.getTime() != asOf.getTime()) {
			console.log("recalculating date table, scope asOf is %o", $scope.asOf)
			asOf= new Date($scope.asOf.getTime());
			
			datesTable=[];
			
			var date= new ExtendedDate(new Date($scope.asOf.getTime()));
			date.setDate(1);
			
			var mondayOffset= (date.getDay() + 6) % 7;
			
			date.setDate(1 - mondayOffset);
			
			for(var i= 0; i < 5; i++) {
				datesTable[i]= [];
				for(var j= 0; j < 7; j++) {
					datesTable[i][j]= new ExtendedDate(new Date(date.getTime()));
					date.setDate(date.getDate() + 1);
				}
			}
			console.log("done recalculating date table, scope asOf is %o", $scope.asOf)
		}
			
		return datesTable;
			
	}
	
	$scope.setAsOf= function(cell) {
		$scope.asOf= cell;
		document.location.search= "asOf=" + (cell.getTime() / 1000);
	}
	
	$scope.prevYear= function() {
		$scope.asOf.setFullYear($scope.asOf.getFullYear() - 1);
	}
	
	$scope.prevMonth= function() {
		$scope.asOf.setMonth($scope.asOf.getMonth() - 1);
	}
	
	$scope.nextYear= function() {
		$scope.asOf.setFullYear($scope.asOf.getFullYear() + 1);
	}
	
	$scope.nextMonth= function() {
		$scope.asOf.setMonth($scope.asOf.getMonth() + 1);
	}
	
});
