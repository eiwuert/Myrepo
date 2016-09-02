// JavaScript Document
/* ###############################################
// file: simple.js
// desc: js file for simple SMC Lite
// ############################################### */

function getUrl(url)
{
	top.location = url;
}

function doLogOut(doClose)
{
	//alert(getCookie('SC'));
	clearCookie ('SCLOGIN');
	
	this.location = '/simple/';
}

// clear cookie
function clearCookie (name) 
{
	var argv = clearCookie.arguments;
	var argc = clearCookie.arguments.length;
	var path = "/";
	var value = "";
	var domain = ".silvercash.com";
	document.cookie = name + "=" + escape (value) + "; expires=-1M" +
		((path == null) ? "" : ("; path=" + path)) +
		((domain == null) ? "" : ("; domain=" + 
domain));
}

// #############################################
// returns the object baring the id
// #############################################
function getObjectID (id)
{
	if (document.all) 
		return document.all[id];
	return document.getElementById (id);
}
// #############################################
// popup div
// #############################################

function showHiddenPopup(div,x,y,noCenter) 
{
	theDIV = document.getElementById(div);
	

		
		
		if (x == null || y == null)
		{
			x=event.clientX;
			y=event.clientY;
		}
		
		theDIV.style.display = 'block';
		
		if (noCenter == null || noCenter == 0) 
		{	
			theDIV.style.left = x - (theDIV.offsetWidth/2) + 50; 
			theDIV.style.top = y + 10 - (theDIV.offsetHeight/2);
		}
		else
		{
			theDIV.style.left = x +5; 
			theDIV.style.top = y - 5;
		}
		
	
	
	//alert("w: " + theDIV.offsetWidth + " H: " + theDIV.offsetHeight);
}
function hideHiddenPopup(div) {
	theDIV = document.getElementById(div);	
	theDIV.style.display = 'none';	
}



function columnSort( fieldToSet, valueToSet, formName )
{
	// alert("entering: columnSort, \r\nfieldToSet=" + fieldToSet + ", \r\nvalueToSet=" + valueToSet + ", \r\nformName=" + formName);
	document.forms[formName].elements[fieldToSet].value = valueToSet;
	document.forms[formName].submit();
}