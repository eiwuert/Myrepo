//real good code by Nick White
//hax0red modifications by Justin Foell

var form_values;
var group = 'group';
var layer = 'layer';
var edit = 'edit';
var view = 'view';
var scroll = 'scroll';

function Get_Current_Layer_In_Group(group_id)
{
	var layer_array = document.getElementsByTagName('DIV');
	for(var i = 0; i < layer_array.length; i++)
	{
		var layer_id = layer_array[i].id;
		//get the count for the group we're interested in
		if(layer_id.indexOf(group + group_id) == 0 && this.Is_Visible(layer_array[i]))
		{
			//alert('current layer: ' + layer_id);
			return layer_array[i];
		}
	}
}

function Mozilla_Cursor_Workaround()
{
	//alert('running cursor workaround');
	var layer_array = document.getElementsByTagName('DIV');
	for(var i = 0; i < layer_array.length; i++)
	{
		var scroll_index = layer_array[i].id.indexOf(scroll);
		if(scroll_index >= 0)
		{
			var parent_layer_id = layer_array[i].id.substring(0, scroll_index);
			var parent_layer = document.getElementById(parent_layer_id);
			if(parent_layer.style.visibility == "hidden")
			{
				//alert('layer: ' + parent_layer_id + ' is hidden, hiding scrollers');
				layer_array[i].style.overflow = "hidden";
				layer_array[i].style.visibility = "hidden";
			}
			else
			{
				//alert('layer: ' + parent_layer_id + ' is visible, showing scrollers');
				layer_array[i].style.overflow = "auto";
				layer_array[i].style.visibility = "visible";
			}
		}
	}
}

function Is_Visible(div_element)
{
	var visibility = div_element.style.visibility;
	if(visibility == "visible" || visibility == "")
	{
		return true;
	}
	return false;
}


function Hide_Edit_Layers()
{
	var div_array = document.getElementsByTagName('DIV');
	for(var i = 0; i < div_array.length; i++)
	{
		var div_id = div_array[i].id;

		var edit_index = div_id.indexOf(edit);

		if(div_id.indexOf(group) == 0 && div_id.indexOf(scroll) < 0 &&
		edit_index >= 0 && this.Is_Visible(div_array[i]))
		{
			this.Hide(div_array[i]);
			var view_id = div_id.substring(0, edit_index);
			//alert('view_id: ' + view_id);
			var view_layer = document.getElementById(view_id + view);
			if(view_layer != null)
			{
				view_layer.style.visibility = "visible";
			}
		}
	}
}

function Hide(div_element)
{
	//alert('hiding: ' + div_element.id);
	div_element.style.visibility = "hidden";
}

function Show_Layer(group_id, layer_id, mode)
{
	this.Check_Data();
	this.Hide(this.Get_Current_Layer_In_Group(group_id));
	if(mode == edit)
	{	//hide other edit layers if we're going into edit mode
	this.Hide_Edit_Layers();
	}
	document.getElementById(group + group_id + layer + layer_id + mode).style.visibility = "visible";
	Mozilla_Cursor_Workaround();
	//alert('send docs scroller: ' + document.getElementById('group1layer1editscroll').style.overflow);
}

function Save_Initial_Values()
{
	// Get a count for all of the forms
	var form_count = document.forms.length;

	// If we found forms and there's more than just a submit
	if(form_count > 1)
	{
		this.form_values = new Array(form_count);
		// For each Form
		for(var i = 0; i < form_count; i++)
		{
			// -1 because it counts the submit button as 1.
			var field_count = document.forms[i].elements.length - 1;

			this.form_values[i] = new Array(field_count);

			// Cycle through the fields for this form.
			for(var x = 0; x < field_count; x++)
			{
				this.form_values[i][x] = document.forms[i].elements[x].value;
			}
		}
	}
}

function Save_Form_Values(form_index)
{
	// -1 because it counts the submit button as 1.
	var field_count = document.forms[form_index].elements.length - 1;

	this.form_values[form_index] = new Array(field_count);

	// Cycle through the fields for this form.
	for(var x = 0; x < field_count; x++)
	{
		this.form_values[form_index][x] = document.forms[form_index].elements[x].value;
	}
}

function Check_Data(ask_confirm, return_did_save)
{
	if(ask_confirm == null)
	{
		ask_confirm = true;
	}

	if(return_did_save == null)
	{
		return_did_save = false;
	}

	// Get a count for all of the forms
	var form_count = document.forms.length;

	// If we found forms
	if(form_count > 0)
	{
		// For each Form
		for(var i = 0; i < form_count; i++)
		{
			// -1 because it counts the submit button as 1.
			var field_count = document.forms[i].elements.length - 1;

			// Cycle through the fields for this form.
			for(var x = 0; x < field_count; x++)
			{
				// If the hidden field does not match it's saved counter part
				// throw an error
				if(document.forms[i].elements[x].value != this.form_values[i][x])
				{
					//alert("changes");
					if(ask_confirm)
					{
						if(confirm('Changes have been made to ' + document.forms[i].name + ', save?'))
						{
							this.Save_Form(i);
							if(return_did_save) { return true; }
							else { return false; }
						}
						else
						{
							this.Restore_Initial_Values(document.forms[i],this.form_values[i]);
							return false;
						}
					}
					else
					{
						this.Save_Form(i);
						if(return_did_save) { return true; }
						else { return false; }
					}
				}
			}
		}
	}

	// changed 8/9/2005 by WF
	//   if nothing has been changed or save is successful, why return false?  Its all ok and ready to go
	return true;
//	return false;
}

// You chose not to save your changes, so we need to revert the field back to the way it was.
function Restore_Initial_Values(form,values)
{
	// -1 because it counts the submit button as 1.
	var field_count = form.elements.length - 1;

	// Cycle through the fields for this form.
	for(var x = 0; x < field_count; x++)
	{
		form.elements[x].value = values[x];
	}
}

// Submit the form cause you forgot to hit the submit button
function Save_Form(form_index)
{
	//alert('Submitting Form: ' + document.forms[form_index].name);
	document.forms[form_index].submit();
}


function Check_Due_Date(change_status)
{
	//did they change data and say OK? (submit another form)
	if(this.Check_Data(true, true))
	{
		alert('You will have to re-submit your original request');
		return false;
	}

	if ( document.Application.date_first_payment_month )
	{
		var payment_date_mm   = document.Application.date_first_payment_month.value;
		if (payment_date_mm.length	< 2)
		{
			payment_date_mm 	 = "0" + payment_date_mm;
		}

		var payment_date_dd   = document.Application.date_first_payment_day.value;
		if (payment_date_dd.length	< 2)
		{
			payment_date_dd	 = "0" + payment_date_dd;
		}

		var payment_date_yyyy = document.Application.date_first_payment_year.value;
		if (payment_date_yyyy.length	< 4)
		{
			payment_date_yyyy = "00" + payment_date_yyyy;
		}

		var payment_date_str	=	payment_date_mm	+ "-" +
		payment_date_dd	+ "-" +
		payment_date_yyyy		;

		if (payment_date_str != document.Application.paydate_0_string.value &&
		payment_date_str != document.Application.paydate_1_string.value &&
		payment_date_str != document.Application.paydate_2_string.value &&
		payment_date_str != document.Application.paydate_3_string.value	  )
		{
			if(confirm("WARNING: \nThe due date " + payment_date_str + " doesn't match any of the four \npaydate values." +
			" \n\n Click 'Cancel', or click 'OK' if you want to submit anyway."))
			{
				document.getElementById('submit_button').value = change_status;
				document.getElementById('change_status').submit();
			}
			else
			{
				return false;
			}
		}
		else
		{
			document.getElementById('submit_button').value = change_status;
			document.getElementById('change_status').submit();

		}
	}

	return true;
}


//this takes the name of the comment field to update, and the name of the form to submit
function Add_Comment(comment_id, form_id)
{
	comment_element = document.getElementById(comment_id);
	var comment = window.prompt ("Comment:");

	if(comment != null)
	{
		comment_element.value = comment;
		document.getElementById(form_id).submit();
	}
}

function Update_Status(button_name)
{

	//did they change data and say OK? (submit another form)
	if(this.Check_Data(true, true))
	{
		alert('You will have to re-submit your original request');
		return true;
	}

	//alert('submitting');
	status_form = document.getElementById('change_status');
	document.getElementById('submit_button').value = button_name;
	status_form.submit();
}

function OpenDialog(url)
{
	var args = OpenDialog.arguments.length;
	var opts = 'toolbar=no,location=no,directories=no,status=no,menubar=no';
	opts += ',scrollbars=no,resizable=no,copyhistory=no,width=400';
	opts += ',height=100,left=200,top=200,screenX=200,screenY=200';

	window.open(url, 'pop_up_comment', opts);
}
