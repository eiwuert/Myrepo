// links that are currently disabled
var savedLinks = new Array();

// milliseconds
var timeoutLength = 5000;

/**
* Disables/enables the get_next_app link (fraud & funding areas)
*/
function Get_Next_App_Checker( link_id, destination )
{
	link = document.getElementById(link_id);

	// Could use additional handling of canceled requests to enable the button
	if( savedLinks.toString().indexOf(link_id) !== -1 || Check_Data() == false )
	{
		// disable
		link.href = '#';
	}
	else
	{
		// enable
		link.href = destination;
		savedLinks.push( link_id );
		setTimeout( "Shift_Link_Array('"+link_id+"')", timeoutLength );
	}
}

/**
* enables the get_next_app button
*
* @param string link_id id of get next app link tag
*/
function Shift_Link_Array( link_id )
{
	if( savedLinks.toString().indexOf(link_id) !== -1 )
	{
		savedLinks.shift();
	}
}

/**
* Disables something after clicking, then restores it after timeoutLength milliseconds
*
* @param string button_id the id of the button/link to disable
*/
function Disable_Button( button_id )
{
	var myButton = document.getElementById( button_id );

	// Its an input tag (input button, input submit, etc)
	if( myButton.tagName == "input" )
	{
		myButton.disabled = true;

		setTimeout( "Enable_Button('" + button_id + "')", timeoutLength );
	}
	// Its a link, probably
	else if( myButton.tagName == "a" && myButton.href.length > 0 )
	{
		// Dont do anything if it has already been saved
		if (savedLinks.toString().indexOf(button_id) === -1)
			setTimeout( "Disable_Link('" + button_id + "')", 5 );
	}
}

/**
* Disables an <a href> tag by pushing the link and setting the href to void()
*
* @param string link_id the id of the link to disable
*/
function Disable_Link( link_id )
{
	myButton = document.getElementById( link_id );
	savedLinks.push(new Array(link_id, myButton.href));
	myButton.href = "#";
	setTimeout( "Enable_Button('" + link_id + "')", timeoutLength );
}

/**
* Enables a button/link that has been disabled
*
* @param string button_id the id of the button/link to enable
*/
function Enable_Button( button_id )
{
	myButton = document.getElementById( button_id );

	// Its a form button
	if( myButton.toString() == "[object HTMLInputElement]" )
	{
		myButton.disabled = false;
	}
	// Its a link
	else
	{
		var stuff = savedLinks.shift();
		myButton.href = stuff[1];
	}
}