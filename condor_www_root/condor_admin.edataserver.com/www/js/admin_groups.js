<script type='text/javascript'>

// global variables set by the php script. 
// this is how i pass data to the form.
var groups = %%%groups%%%;
//var master_tree = %%%sorted_master_tree%%%;
var master_tree_ids = %%%sorted_master_keys%%%;
var last_group_id = %%%last_group_id%%%;
var last_action = %%%last_action%%%;
var group_sections = %%%all_group_sections%%%;

// global constants
var ADD_GROUPS = 'add_groups';
var DELETE_GROUPS = 'delete_groups';
var MODIFY_GROUPS = 'modify_groups';
var FIELD_GROUP_NAME = 'Group Name';
var FIELD_GROUP_STRUCTURE = 'Group Structure';
var MESG_EMPTY = 'This field cannot be empty';
var MESG_LONG_LENGTH = "You must limit this field to 50 characters";
var MESG_GROUP_EXISTS = 'You must enter a unique group name. This group name already exists';
//var MESG_GROUP_STRUCT_EXISTS = "You must enter a unique group configuration. This configuration already exists";



function select_all_sections()
{
	for (var a = 0; a < master_tree_ids.length; a++)
	{
		document.getElementById(master_tree_ids[a]['value']).checked = true;
	}
}

function deselect_all_sections()
{
	for (var a = 0; a < master_tree_ids.length; a++)
	{
		document.getElementById(master_tree_ids[a]['value']).checked = false;
	}
}

/**
 *
 */
function display_group()
{
	var group_index;

	_clear_section_tree();
	group_index = document.group_info.groups.selectedIndex;
	document.group_info.action.value = '';
	_display_info(group_index);
}




/**
 *
 */
function _display_info(group_index)
{
	var group_id = groups[group_index]['group_id'];

	// group
	document.group_info.group_name.value = groups[group_index]['group_name'];

	// company name
	document.group_info.company.value = groups[group_index]['company_id'];

	// group sections
   if (group_sections.length > 0)
   {
		for (var i = 0; i < group_sections.length; i++)
		{
			if (group_sections[i]['group_id'] == group_id)
			{
				document.getElementById(group_sections[i]['section_id']).checked = true;
			}
		}
   }
}




/**
 *
 */
function get_last_settings()
{
	if (last_action == ADD_GROUPS )
	{
		document.group_info.add_groups_radio_btn.checked = true;
		radio_btn_actions(ADD_GROUPS);
	}
	else if (last_action == MODIFY_GROUPS)
	{
		document.group_info.mod_groups_radio_btn.checked = true;
		radio_btn_actions(MODIFY_GROUPS);

		if (last_group_id > 0)
		{
			document.group_info.mod_groups_radio_btn.checked = true;
			radio_btn_actions(MODIFY_GROUPS);

			// loop through groups 
			for (var a = 0;  a < groups.length; a++)
			{
				if (groups[a]['group_id'] == last_group_id)
				{
					document.group_info.groups.selectedIndex = a;
					display_group();
					break;
				}
			}
		}
	}
	else
	{
		document.group_info.remove_groups_radio_btn.checked = true;
		radio_btn_actions(DELETE_GROUPS);
	}
}


/**
 *
 */
function check_it(section) {
	if( document.getElementById(section).checked == true ) {
		check_parents(section);
    }
}

function check_parents(section) {
    document.getElementById(section).checked = true;
    
    if( document.getElementById('section_'+section+'_parent').value != 2 ) {
		check_parents( document.getElementById('section_'+section+'_parent').value );
    }
}


/**
 *
 */
function _confirm_group_doesnt_exists()
{
	var result = false;
	var new_group_name = document.group_info.group_name.value;
	var selected_index = document.group_info.groups.selectedIndex;

	for (var a = 0; a < groups.length; a++)
	{
		if (selected_index != a)
		{
			if (groups[a]['group_name'] == new_group_name)
			{
				result = true;
				break;
			}
		}
	}

	return result;
}





/**
 *
 */
function _confirm_field_length()
{
	var result = false;

	if (document.group_info.group_name.value.length <= 50)
	{
		result = true;
	}

	return result;
}




/**
 *
 */
function _confirm_group_name_doesnt_exist()
{
	var result = true;

	for (var i in groups)
	{
		if (document.group_info.group_name.value == groups[i]['group_name'])
		{
			result = false;
		}
	}

	return result;
}




/**
 *
 *
 */
function save()
{
	document.group_info.commit.disabled = true;
	if (document.group_info.commit.value == 'Add Group')
	{
		_add_modify_groups(ADD_GROUPS);
	}
	else if (document.group_info.commit.value == 'Modify Group')
	{
		_add_modify_groups(MODIFY_GROUPS);
	}
	else
	{
		_delete_group();
	}

	document.group_info.commit.disabled = false;
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
function _are_fields_populated(action)
{
	var result = false;

	if (document.group_info.group_name.value != '')
	{
		result = true;
	}

	return result;
}




/**
 *
 */
function _are_sections_selected()
{
	var result = false;

	for (var a = 0; a < master_tree_ids.length; a++)
	{
		if (document.getElementById(master_tree_ids[a]['value']).checked)
		{
			result = true;
			break;
		}

	}

	return result;
}



/**
 *
 */
function _delete_group()
{
	document.group_info.action.value = DELETE_GROUPS;

	group_index = document.group_info.groups.selectedIndex;
	document.group_info.company.value = groups[group_index]['company_id'];

	document.group_info.submit();
}




/**
 *
 */
function _add_modify_groups(action)
{
	if (_are_fields_populated())
	{
		if (_are_sections_selected())
		{
			if (_confirm_field_length())
			{
				if (!_confirm_group_doesnt_exists())
				{
					if (action == ADD_GROUPS)
					{
						document.group_info.action.value = ADD_GROUPS;
						document.group_info.submit();
					}
					else
					{
						document.group_info.action.value = MODIFY_GROUPS;
						document.group_info.submit();
					}
				}
				else
				{
					_display_alert(FIELD_GROUP_NAME, MESG_GROUP_EXISTS);
				}
			}
			else
			{
				_display_alert(FIELD_GROUP_NAME, MESG_LONG_LENGTH);
			}
		}
		else
		{
			_display_alert(FIELD_GROUP_STRUCTURE, MESG_EMPTY);
		}
	}
	else
	{
		_display_alert(FIELD_GROUP_NAME, MESG_EMPTY);
	}
}






/**
 *
 */
function _clear_section_tree()
{
   if (document.group_info.groups.length > 0)
   {
		for (var i = 0; i < master_tree_ids.length; i++)
		{
			document.getElementById(master_tree_ids[i]['value']).checked = false;
		}
   }
}




/**
 *
 */
function _disable_section_tree()
{
   if (document.group_info.groups.length > 0)
   {
		for (var i = 0; i < master_tree_ids.length; i++)
		{
			document.getElementById(master_tree_ids[i]['value']).disabled = true;
		}
   }
}



/**
 *
 */
function _enable_section_tree()
{
   if (document.group_info.groups.length > 0)
   {
		for (var i = 0; i < master_tree_ids.length; i++)
		{
			document.getElementById(master_tree_ids[i]['value']).disabled = false;
		}
   }
}



/**
 *
 */
function radio_btn_actions(btn)
{
	_clear_fields();

	if (btn == ADD_GROUPS)
	{
		_set_fields(ADD_GROUPS);
	}
	else if (btn == MODIFY_GROUPS) 
	{
		_set_fields(MODIFY_GROUPS);
	}
	else // delete
	{
		_set_fields(DELETE_GROUPS);
	}
}




/**
 *
 */

function _set_fields(action)
{
	if (action == ADD_GROUPS)
	{
		// disable existing groups list
		document.group_info.groups.disabled = true;

		// set commit butto
		document.group_info.commit.value = 'Add Group';

		// set the commit button
		document.group_info.commit.disabled = false;
		document.group_info.group_name.disabled = false;
		document.group_info.company.disabled = false;
		_enable_section_tree();
	}
	else if (action == MODIFY_GROUPS)
	{
		// disable existing groups list
		document.group_info.groups.disabled = false;

		// set the commit button
		document.group_info.commit.value = 'Modify Group';

		// set the commit button
		document.group_info.commit.disabled = false;
		document.group_info.group_name.disabled = false;
		document.group_info.company.disabled = false;


		// set selected groups index to 0
		if (groups.length > 0)
		{
			document.group_info.groups.selectedIndex = 0;
			display_group(0);
		}
		_enable_section_tree();
	}
	else if (action == DELETE_GROUPS)
	{
		// disable existing groups list
		document.group_info.groups.disabled = false;

		// set the commit button
		document.group_info.commit.value = 'Remove Group';

		// set the commit button
		document.group_info.commit.disabled = false;
		document.group_info.group_name.disabled = true;


		document.group_info.company.disabled = false;

		// set selected groups index to 0
		if (groups.length > 0)
		{
			document.group_info.groups.selectedIndex = 0;
			display_group(0);
		}

		_disable_section_tree();
	}
}




/**
 *
 */
function _clear_fields()
{
	// clear the selected index
	document.group_info.groups.selectedIndex = -1;

	// clear profile info
	document.group_info.company.value = '';
	document.group_info.group_name.value = '';

	// clear privs
	_clear_section_tree();
}
</script>
