// multiselect.js
/*

	USAGE:

	<html>
		<head>
		...
			<script type="text/javascript" src="js/multiselect.js"></script>
			<script type="text/javascript">
				d_arr = Array ();
				d_arr["One"] = 1;
				d_arr["Two"] = 2;
				d_arr["Three"] = 3;
				d_arr["Four"] = 4;
				d_arr["Five"] = 5;
				d_arr["Six"] = 6;
				d_arr["Seven"] = 7;

				multi = new multiselect ("test_a", 170, "All Sites", "all");

				multi.css ();
			</script>
		</head>
		<body onload="multi.init(d_arr)">
			<form>
				<script type="text/javascript">
					multi.display ();
				</script>
			</form>
		</body>
	</html>

	NOTES:

		The associative array used to populate the multiselect is in this
		format:
			array_name[label_string] = value_string

		The populate() method is called by the init method in the body onload
		event.  It may also be called as the result of an AJAX call, but ONLY
		after the page has loaded.  Attempting to call it before the page has
		loaded will result in an error.

		The select_name argument in the object constructor is the name of the
		form field submitted.  It must not include a hyphen since that's used
		for internal name parsing.  Underscores are ok.

		hsize is the horizontal size of the multiselect in pixels.  If any
		entries take up more space than hsize, the dropdown list expands to
		accomodate.  The multiselect itself does not.  Should items selected
		combine to be longer than hsize, the displayed values in the multiselect
		will be truncated, with an ellipsis (...) to indicate the truncation.
		The display does not affect the stored form values.

		The default_label and default_value arguments are only used when nothing
		is selected.  Selecting any entry in the multiselect will replace the
		default.  Should all entries be deselected, the defaults reappear.  If
		no defaults are desired, simply leave them off the constructor call.
		Either or both can be given an empty string.

		delimiter is the character string used to separate the actual data values
		from each other in the submitted form.  Default value is a comma.

		display_delimiter is the string for separating the values that appear in
		the multiselect itself.  Default value for display_delimiter is the string
		stored in delimiter.

		After opening the multiselect, you must click on it or the dropdown button
		to close it again.  Clicking on the dropdown list will merely select or
		deselect entries.

		Upon submit, the multiselect sends a delimited list of values, with
		no white space.  The delimiter string should NOT be used in the value strings.

*/
function multiselect (select_name, hsize, default_label, default_value, delimiter, display_delimiter)
{
	// properties
	this.name = select_name;
	this.obj = this.name + 'Object';
	eval(this.obj + " = this");
	if (document.all)
	{
		this.width = hsize ? hsize : 200;
	}
	else
	{
		this.width = hsize ? hsize - 2 : 198;
	}
	this.data_array = Array;
	this.enclosure = false;
	this.field = false;
	this.button = false;
	this.label = false;
	this.tape_measure = false;
	this.content = false;
	this.default_label = default_label ? default_label : "";
	this.default_value = default_value ? default_value : "";
	this.delimiter = delimiter ? delimiter : ",";
	this.display_delimiter = display_delimiter ? display_delimiter : this.delimiter;

	// methods
	this.css = multiselect_css;
	this.populate = multiselect_populate;
	this.display = multiselect_display;
	this.ellipsis_truncate = multiselect_ellipsis_truncate;
	this.init = multiselect_init;
}

function multiselect_css ()
{
	css_str  = "<style type=\"text/css\">\n";

	// enclosure
	css_str += "\t#"+this.name+"-enclosure {";
	css_str += "position: relative;";
	css_str += "width: "+this.width+"px;";
	css_str += document.all ? "height: 22px;" : "height: 20px;";
	css_str += "border: solid 1px #A5ACB2;";
	css_str += "padding: 0px;";
	css_str += "overflow: hidden;";
	css_str += "}\n";

	// label
	css_str += "\t#"+this.name+"-label {";
	css_str += "position: absolute;";
	css_str += "top: 1px;";
	css_str += "left: 1px;";
	css_str += "width: 50px;";
	css_str += document.all ? "width: "+(this.width - 22)+"px;" : "width: "+(this.width - 30)+"px;";
	css_str += "height: 18px;";
	css_str += "font-family: arial, helvetica;";
	css_str += "font-size: 10pt;";
	css_str += "-moz-user-select: none;";
	css_str += "cursor: default;";
	css_str += "padding: 0px 5px;";
	css_str += "margin: 0px;";
	css_str += "border: 0px;";
	css_str += "overflow: hidden;";
	css_str += "text-overflow: ellipsis;";
	css_str += "}\n";

	// button
	css_str += "\t#"+this.name+"-button {";
	css_str += "position: absolute;";
	css_str += "top: 1px;";
	css_str += document.all ? "left: "+(this.width - 20)+"px;" : "left: "+(this.width - 18)+"px;";
	css_str += "width: 17px;";
	css_str += "height: 18px;";
	css_str += "background-image: url(images/select_button_default.png);";
	css_str += "background-repeat: no-repeat;";
	css_str += "margin: 0px;";
	css_str += "padding: 0px;";
	css_str += "}\n";

	// tapemeasure
	css_str += "\t#"+this.name+"-tapemeasure {";
	css_str += "position: absolute;";
	css_str += "width: auto;";
	css_str += "height: 1px;";
	css_str += "display: hidden;";
	css_str += "overflow: hidden;"
	css_str += "}\n";

	// content
	css_str += "\t#"+this.name+"-content {";
	css_str += "position: absolute;";
	css_str += "top: 0px;";
	css_str += "left: 0px;";
	css_str += "width: auto;";
	css_str += "height: 100px;";
	css_str += "font-family: arial, helvetica;";
	css_str += "font-size: 10pt;";
	css_str += "background-color: white;";
	css_str += "border: solid 1px #A5ACB2;";
	css_str += "padding: 0px;";
	css_str += "overflow: -moz-scrollbars-vertical;";
	css_str += "overflow-y: auto;";
	css_str += "overflow-x: visible;";
	css_str += "display: none;";
	css_str += "}\n";

	// divs inside content
	css_str += "\t#"+this.name+"-content div {";
	css_str += "position: relative;";
	css_str += "height: 18px;";
	css_str += "padding: 0px 0px 0px 5px;";
	css_str += "background-color: white;";
	css_str += "color: black;";
	css_str += "-moz-user-select: none;";
	css_str += "cursor: default;";
	css_str += "}\n";

	// input inside divs inside content
	css_str += "\t#"+this.name+"-content div input {";
	css_str += "margin: 0px 5px 0px 0px;";
	css_str += "}\n";


	css_str += "</style>\n";

	document.write (css_str);
}

// MUST BE REWRITTEN -- SHOULD RESIZE CONTENT DIV TO FIT CONTENTS
function multiselect_populate (data_arr)
{
	this.data_array = Array ();

	// wipe the existing data
	this.content.innerHTML = "";
	this.field.value = this.default_value;
	this.label.innerHTML = this.default_label;

	max_entry_width = 0;
	for (var i in data_arr)
	{
		this.tape_measure.innerHTML = i;
		max_entry_width = Math.max (this.tape_measure.offsetWidth, max_entry_width);
	}
	max_entry_width += 22;

	if ((max_entry_width + 18) > this.enclosure.offsetWidth)
	{
		this.content.style.width = document.all ? (max_entry_width + 18) + "px" : (max_entry_width + 10) + "px";
	}
	else
	{
		this.content.style.width = document.all ? this.enclosure.offsetWidth + "px" : (this.enclosure.offsetWidth - 2) + "px";
		max_entry_width = document.all ? this.enclosure.offsetWidth - 22 : this.enclosure.offsetWidth - 20;
	}

	var content_str = "";
	for (var i in data_arr)
	{
		content_str += "<div ";
		content_str += "onmouseover=\"hover(this, 'on')\" ";
		content_str += "onmouseout=\"hover(this, 'off')\" ";
		content_str += "onclick=\"click_entry(this, '"+this.name+"')\" ";
		content_str += "style=\"width:"+max_entry_width+"px;\"";
		content_str += ">";
		content_str += "<input type=\"checkbox\" value=\""+data_arr[i]+"\" ";
		content_str += "onclick=\"click_entry(this, '"+this.name+"')\" ";
		content_str += "/>";
		content_str += i + "</div>\n";
	}
	this.content.innerHTML = content_str;
}

function multiselect_display ()
{
	display_str  = "<div id=\""+this.name+"-enclosure\" ";
	display_str += "onmouseover=\"hover_button(this, 'in')\" ";
	display_str += "onmouseout=\"hover_button(this, 'out')\" ";
	display_str += "onmousedown=\"click_button(this, 'down')\" ";
	display_str += "onmouseup=\"click_button(this, 'up')\" ";
	display_str += ">";

	display_str += "<input id=\""+this.name+"\" type=\"hidden\" name=\""+this.name+"\" value=\"\" />\n";
	display_str += "<div id=\""+this.name+"-button\"></div>\n";
	display_str += "<div id=\""+this.name+"-label\"></div>\n";

	display_str += "</div>\n";
	display_str += "<div id=\""+this.name+"-tapemeasure\"></div>\n";
	display_str += "<div id=\""+this.name+"-content\"></div>\n";
	document.write (display_str);
}

function multiselect_ellipsis_truncate (test_str)
{
	this.tape_measure.innerHTML = test_str;
	if (this.tape_measure.offsetWidth > this.label.offsetWidth)
	{
		while (this.tape_measure.offsetWidth + this.ellipsis_width > this.label.offsetWidth)
		{
			test_str = test_str.slice (0, -1);
			this.tape_measure.innerHTML = test_str;
		}
		test_str += "...";
	}
	this.tape_measure.innerHTML = "";
	return test_str;
}

function multiselect_init (data_array)
{
	this.enclosure = document.getElementById (this.name+"-enclosure");
	this.field = document.getElementById (this.name);
	this.button = document.getElementById (this.name+"-button");
	this.label = document.getElementById (this.name+"-label");
	this.tape_measure = document.getElementById (this.name+"-tapemeasure");
	this.content = document.getElementById (this.name+"-content");

	// get the ellipsis width
	this.tape_measure.innerHTML = "...";
	this.ellipsis_width = this.tape_measure.offsetWidth;
	this.tape_measure.innerHTML = "";

	// set the height so it can be accessed later
	this.content.style.height = "100px";
	this.populate (data_array);
}

// non-member functions used by all multiselects
// (these are not part of the object in case there are multiple multiselects on the page)

// Only needed because of IE's standards non-compliance.
function hover (obj_ref, on_or_off)
{
	if (!obj_ref.childNodes.item(0).checked)
	{
		if (on_or_off == "on")
		{
			obj_ref.style.backgroundColor = "#B2B4BF";
		}
		else
		{
			obj_ref.style.backgroundColor = "white";
		}
	}
}

function fetch_target_obj (obj_ref, id_fragment)
{
	base_name = obj_ref.id.split ("-")[0];
	target_obj = document.getElementById (base_name+"-"+id_fragment);
	return target_obj;
}

function hover_button (obj_ref, in_or_out)
{
	button_obj = fetch_target_obj (obj_ref, "button");
	if (in_or_out == "in")
	{
		button_obj.style.backgroundImage = "url(images/select_button_mouseover.png)";
	}
	else
	{
		button_obj.style.backgroundImage = "url(images/select_button_default.png)";
	}
}

function click_button (obj_ref, up_or_down)
{
	button_obj = fetch_target_obj (obj_ref, "button");
	if (up_or_down == "down")
	{
		button_obj.style.backgroundImage = "url(images/select_button_pressed.png)";
		content_obj = fetch_target_obj (obj_ref, "content");
		enclosure_obj = fetch_target_obj (obj_ref, "enclosure");
		content_obj.style.left = enclosure_obj.offsetLeft + "px";

		// determine if we're going to display the content above or below the multiselect
		// based on the multiselect's vertical position in the browser windown
		if ((enclosure_obj.offsetTop - document.body.scrollTop) < (document.body.clientHeight / 2))
		{
			// less than halfway down: display below
			content_obj.style.top = (enclosure_obj.offsetTop + enclosure_obj.offsetHeight - 1) + "px";
		}
		else
		{
			// more than halfway down: display above (and account for browser differences)
			if (document.all)
			{
				content_obj.style.top = (enclosure_obj.offsetTop - parseInt (content_obj.style.height) + 1) + "px";
			}
			else
			{
				content_obj.style.top = (enclosure_obj.offsetTop - parseInt (content_obj.style.height) - 1) + "px";
			}
		}

		// toggle visibility based on prior state, making sure it displays on top of other elements
		content_obj.style.zIndex = 1000;
		content_obj.style.display = content_obj.style.display == "block" ? "none" : "block";
	}
	else
	{
		button_obj.style.backgroundImage = "url(images/select_button_mouseover.png)";
	}

}

function click_entry (obj_ref, multiselect_name)
{
	clicked = false;
	unclicked = false;
	eval ("multiselect_obj = "+multiselect_name+"Object");
	if (obj_ref.childNodes.length > 0)
	{
		target_checkbox = obj_ref.childNodes.item(0);
		target_div = obj_ref;
		if (target_checkbox.checked == true)
		{
			target_checkbox.checked = false;
			obj_ref.style.backgroundColor = "white";
			unclicked = true;
		}
		else
		{
			target_checkbox.checked = true;
			obj_ref.style.backgroundColor = "#B2B4BF";
			clicked = true;
		}
	}
	else
	{
		target_checkbox = obj_ref;
		target_div = obj_ref.parentNode;
		if (obj_ref.checked == true)
		{
			obj_ref.checked = false;
			target_div.style.backgroundColor = "white";
			unclicked = true;
		}
		else
		{
			obj_ref.checked = true;
			target_div.style.backgroundColor = "#B2B4BF";
			clicked = true;
		}
	}
	if (target_checkbox.checked == true)
	{
		multiselect_obj.data_array[target_div.childNodes.item (1).nodeValue] = target_checkbox.value;
	}
	else
	{
		delete multiselect_obj.data_array[target_div.childNodes.item (1).nodeValue];
	}
	val_str = "";
	lab_str = "";
	for (var i in multiselect_obj.data_array)
	{
		val_str += multiselect_obj.data_array[i]+multiselect_obj.delimiter;
		lab_str += i+multiselect_obj.display_delimiter;
	}
	if (!document.all)
	{
		lab_str = multiselect_obj.ellipsis_truncate (lab_str);
	}
	multiselect_obj.field.value = val_str.length == 0 ? multiselect_obj.default_value : val_str.slice (0, -1);
	// may need to look at the slice on lab_str in light of added ellipsis_truncate method for mozilla
	multiselect_obj.label.innerHTML = lab_str.length == 0 ? multiselect_obj.default_label : lab_str.slice (0, -1);

}


