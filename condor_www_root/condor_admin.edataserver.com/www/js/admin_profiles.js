<script type='text/javascript'>

// global variables set by the php script. 
// this is how i pass data to the form.
var agents = %%%agents%%%
var last_action = %%%last_action%%%;
var last_agent_id = %%%last_agent_id%%%;

// global constants
var ADD_PROFILE = 'add_profile';
var MODIFY_PROFILE = 'modify_profile';
var FIELD_PASSWORD = 'Password';
var FIELD_LOGIN = 'Login';
var FIELD_EXISTING_USER	 = 'Existing Agent';
var MESG_PASSWORD =  'Your passwords do not match';
var MESG_LOGIN = 'You must enter a unique login. This login already exists';
var MESG_EMPTY = 'This field cannot be empty';
var MESG_PASSWORD_MISMATCH = 'The passwords do not match'
var MESG_LONG_LENGTH = "You must limit this field to 50 characters"



/**
 *
 */
function display_user()
{
	var user_idx;

	// clear the action so that a submit just brings us right back
	document.agent_profile.action.value = '';

	// keep the index into the user list as a global so other functions can ref it
	user_idx = document.agent_profile.agent.selectedIndex;

	// get user login info
	document.agent_profile.agent_login.value = agents[user_idx]['agent_login'];
	document.agent_profile.name_first.value = agents[user_idx]['name_first'];
	document.agent_profile.name_last.value	= agents[user_idx]['name_last'];
	document.agent_profile.password1.value	= '';
	document.agent_profile.password2.value	= '';
}




/**
 *
 */
function get_last_settings()
{
	if (last_action == ADD_PROFILE)
	{
		document.agent_profile.add_profile_radio_btn.checked = true;
		radio_btn_actions(ADD_PROFILE);
	}
	else  // last action is modify
	{
		document.agent_profile.mod_profile_radio_btn.checked = true;

		if (last_agent_id > 0)
		{
			document.agent_profile.mod_profile_radio_btn.checked = true;
			radio_btn_actions(MODIFY_PROFILE);

			for (var a = 0; a < agents.length; a++)
			{
				if (agents[a]['id'] == last_agent_id)
				{
					document.agent_profile.agent.selectedIndex = a;
					display_user();
					break;
				}
			}
		}
	}
}





/**
 *
 */
function _add_profile()
{
	if (_are_fields_populated(ADD_PROFILE))
	{
		if (_confirm_field_length())
		{
			if (_confirm_passwords_match())
			{
				if (_confirm_login_doesnt_exist())
				{
					document.agent_profile.action.value = ADD_PROFILE;
					document.agent_profile.submit();
				}
				else
				{
					_display_alert(FIELD_LOGIN, MESG_LOGIN);
				}
			}
			else
			{
				_display_alert(FIELD_PASSWORD, MESG_PASSWORD);
			}
		}
		else
		{
			_display_alert(_get_exceeded_length_field(), MESG_LONG_LENGTH);
		}
	}
	else
	{
		_display_alert(_get_empty_error_field(ADD_PROFILE), MESG_EMPTY);
	}
}




/**
*/
function save()
{
	document.agent_profile.commit.disabled = true;
	if (document.agent_profile.commit.value == 'Add Profile')
	{
		_add_profile();	
	}
	else // modify
	{
		_modify_profile();	
	}

	document.agent_profile.commit.disabled = false;
}




/**
 *
 */
function _get_exceeded_length_field()
{
	var result = "Unknown Field";

	if (document.agent_profile.name_first.value.length > 50)
	{
		result = "First Name";
	}
	else if ( document.agent_profile.name_last.value.length > 50)
	{
		result = "Last Name";
	}
	else if ( document.agent_profile.agent_login.value.length > 50)
	{
		result = "Login";
	}
	else if (document.agent_profile.password1.value.length > 50)
	{
		result = "New Password";
	}
	else if (document.agent_profile.password2.value.length > 50)
	{
		result = "Conf. Password";
	}

	return result;
}



/**
 *
 */
function _confirm_field_length()
{
	var result = false;

	if ( document.agent_profile.name_last.value.length <= 50
			&& document.agent_profile.name_first.value.length <= 50
			&& document.agent_profile.agent_login.value.length <= 50
			&& document.agent_profile.password1.value.length <= 50
			&& document.agent_profile.password2.value.length <= 50)
	{
		result = true;
	}

	return result;
}



/**
 *
 */
function _confirm_login_doesnt_exist()
{
	var result = true;

	for (var i in agents)
	{
		if (document.agent_profile.agent_login.value == agents[i].agent_login)
		{
			result = false;
		}
	}

	return result;
}



/**
 *
 */
function _display_alert(field, mesg)
{
	alert("Error Field: " + field + "\nError Message: " + mesg + ".");
}



/**
 *
 */
function _get_empty_error_field(action)
{
	var result = 'Unknown';

	if (document.agent_profile.name_first.value == '')
	{
		result = 'First Name';
	}
	else if (document.agent_profile.name_last.value == '')
	{
		result = 'Last Name';
	}
	else if (document.agent_profile.agent_login.value == '')
	{
		result = 'Login';
	}
	else // the next fields relate to the action
	{
		// passwords can be blank if not add
		if (action == ADD_PROFILE)
		{
			if (document.agent_profile.password1.value == '' )
			{
				result = 'New Password';
			}
			else if (document.agent_profile.password2.value == '' )
			{
				result = 'Conf. Password';
			}
		}
	}

	return result;
}



/**
 *
 */
function _are_fields_populated(action)
{
	var result = false;

	// is there info in name and login fields
	if (document.agent_profile.name_first.value != ''
		&& document.agent_profile.name_last.value != ''
		&& document.agent_profile.agent_login.value != '')
	{
		if (action == ADD_PROFILE)
		{
			if (document.agent_profile.password1.value != '' 
				 && document.agent_profile.password2.value != '') 
			{
				result = true;
			}
		}
		else // action is not add
		{
			result = true;
		}
	}

	return result;
}




/**
 *
 */
function _modify_profile()
{
	if (_are_fields_populated(MODIFY_PROFILE))
	{
		if (_confirm_field_length())
		{
			if (_confirm_passwords_match())
			{
				if (document.agent_profile.password1.value == '')
				{
					document.agent_profile.password1.disabled = true;
				}

				document.agent_profile.action.value = MODIFY_PROFILE;
				document.agent_profile.submit();
			}
			else
			{
				_display_alert(FIELD_PASSWORD, MESG_PASSWORD_MISMATCH);
			}
		}
		else
		{
			_display_alert(_get_exceeded_length_field(), MESG_LONG_LENGTH);
		}
	}
	else
	{
		_display_alert(_get_empty_error_field(MODIFY_PROFILE), MESG_EMPTY);
	}
}




/**
 *
 */
function _confirm_passwords_match()
{
	var result = false;

	if (document.agent_profile.password1.value == document.agent_profile.password2.value)
	{
		result = true;
	}

	return result;
}



/**
 *
 */
function radio_btn_actions(btn)
{
	//_clear_fields();

	if (btn == ADD_PROFILE)
	{
		_set_fields(ADD_PROFILE);
	}
	else // modify
	{
		_set_fields(MODIFY_PROFILE);
	}
}




/**
 *
 */
function _set_fields(action)
{
	isDisabled = false;
	if (action == ADD_PROFILE)
	{
		// disable existing agent list
		document.agent_profile.agent.disabled = true;

		// set commit butto
		document.agent_profile.commit.value = 'Add Profile';

		// set the commit button
		document.agent_profile.commit.disabled = false;

		// get user login info
		document.agent_profile.agent_login.value = '';
		document.agent_profile.name_first.value = '';
		document.agent_profile.name_last.value	= '';
		document.agent_profile.password1.value	= '';
		document.agent_profile.password2.value	= '';

		document.agent_profile.agent.selectedIndex = -1;
	}
	else // modify
	{
		// disable existing agent list
		document.agent_profile.agent.disabled = false;

		// set the commit button
		document.agent_profile.commit.value = 'Modify Profile';

		// set the commit button
		document.agent_profile.commit.disabled = false;

		// set selected agent index to 0
		if (agents.length > 0)
		{
			document.agent_profile.agent.selectedIndex = 0;
			display_user(0);
		}
	}

	// profile info
	document.agent_profile.name_first.disabled = isDisabled;
	document.agent_profile.name_last.disabled = isDisabled;
	document.agent_profile.password1.disabled = isDisabled;
	document.agent_profile.password2.disabled = isDisabled;
}




/**
 *
 */
function _clear_fields()
{
	// clear the selected index
	document.agent_profile.agent.selectedIndex = -1;

	// clear profile info
	document.agent_profile.name_first.value = '';
	document.agent_profile.name_last.value = '';
	document.agent_profile.agent_login.value = '';
	document.agent_profile.password1.value = '';
	document.agent_profile.password2.value = '';
}



</script>
