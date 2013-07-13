var idleTime	= 30000;
var timeOut		= '';

function init() {
	
	Event.observe(document.body, 'mousemove', resetIdle, true);
	
	setIdle();
	
}

function onIdleFunction(){
	
	var newLocation = location.href;
	window.location=newLocation.split("?",1);
		
}

function resetIdle(){
	
	window.clearTimeout( timeOut );
	setIdle();
	
}

function setIdle(){
	
	timeOut = window.setTimeout( "onIdleFunction()", idleTime );
	
}

Event.observe(window, 'load', init, false);