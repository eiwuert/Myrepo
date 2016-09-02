// JavaScript color_manager object with associated utility functions
// written by David Bryant

// global variables
var hexmap = "0123456789ABCDEF";

//global functions
function dec2hex(decimal){
	a = decimal % 16;
	b = (decimal - a)/ 16;
	hex = ""+hexmap.charAt(b)+hexmap.charAt(a);
	return hex;
}

function hex2dec (hex_code)
{
	return parseInt(hex_code, 16);
}

// the color_manager object itself
function color_manager (color_1, color_2, steps, shading_delta)
{
	// properties
	this.color_array = Array (steps);
	this.delta = shading_delta ? shading_delta : 25;
	this.steps = steps;
	this.test_css  = "<style type=\"text/css\">\n\t.test_block {position:relative;width:25px;";
	this.test_css += "height:"+(Math.round (100/this.steps))+"px;overflow:hidden;padding:0px;margin:0px;}\n</style>\n";

	// methods
	this.chiaroscuro = color_chiaroscuro;
	this.interpolate = color_interpolate;
	this.convert_syntax = color_convert_syntax;
	this.parse_hexcolor = color_parse_hexcolor;
	this.display_test_block = color_display_test_block;

	// initialization
	this.color_array = this.interpolate (color_1, color_2);
}

function color_chiaroscuro (color_obj)
{
	// generate shadow
	var temp_r = color_obj.r_dec - this.delta;
	var temp_g = color_obj.g_dec - this.delta;
	var temp_b = color_obj.b_dec - this.delta;

	temp_r = temp_r < 0 ? 0 : temp_r;
	temp_g = temp_g < 0 ? 0 : temp_g;
	temp_b = temp_b < 0 ? 0 : temp_b;

	color_obj.shadow = "#"+dec2hex (temp_r)+dec2hex (temp_g)+dec2hex (temp_b);

	temp_r = color_obj.r_dec + this.delta;
	temp_g = color_obj.g_dec + this.delta;
	temp_b = color_obj.b_dec + this.delta;

	temp_r = temp_r > 255 ? 255 : temp_r;
	temp_g = temp_g > 255 ? 255 : temp_g;
	temp_b = temp_b > 255 ? 255 : temp_b;

	color_obj.hilite = "#"+dec2hex (temp_r)+dec2hex (temp_g)+dec2hex (temp_b);

	temp_r = color_obj.r_dec + (this.delta * 2);
	temp_g = color_obj.g_dec + (this.delta * 2);
	temp_b = color_obj.b_dec + (this.delta * 2);

	temp_r = temp_r > 255 ? 255 : temp_r;
	temp_g = temp_g > 255 ? 255 : temp_g;
	temp_b = temp_b > 255 ? 255 : temp_b;

	color_obj.paper = "#"+dec2hex (temp_r)+dec2hex (temp_g)+dec2hex (temp_b);
}

function color_interpolate (color_1, color_2)
{
	var color_arr = Array ();

	var color_obj_1 = this.parse_hexcolor (color_1);
	var color_obj_2 = this.parse_hexcolor (color_2);

	this.chiaroscuro (color_obj_1);
	this.chiaroscuro (color_obj_2);

	// get the distance between the two end point colors and
	// break it into a triplet, dividing each value by the
	// number of steps.  This gives a 3D slope in color space.
	var r_step = (color_obj_2.r_dec - color_obj_1.r_dec) / (this.steps-1);
	var g_step = (color_obj_2.g_dec - color_obj_1.g_dec) / (this.steps-1);
	var b_step = (color_obj_2.b_dec - color_obj_1.b_dec) / (this.steps-1);

	// cumulatively add the 3D slope to color_1 in a loop
	// on steps, recording each resulting triplet in the array
	var rd = 0;
	var gd = 0;
	var bd = 0;
	var hex = "";

	color_arr[color_arr.length] = color_obj_1;
	for (var i=1; i<this.steps-1; i++)
	{
		rd = color_obj_1.r_dec + (r_step * i);
		gd = color_obj_1.g_dec + (g_step * i);
		bd = color_obj_1.b_dec + (b_step * i);
		color_obj_temp = null;
		color_obj_temp = this.parse_hexcolor ("" + dec2hex(rd) + dec2hex(gd) + dec2hex(bd));
		this.chiaroscuro (color_obj_temp);
		color_arr[color_arr.length] = color_obj_temp;
	}
	color_arr[color_arr.length] = color_obj_2;
	return color_arr;
}

function color_convert_syntax (color_str)
{
	hex_color = color_str.substr (0, 1) == "#" ? true : false;
	if (hex_color)
	{
		return color_str.toUpperCase ();
	}
	else
	{
		regex_1 = /rgb\(/;
		regex_2 = /, /g;
		regex_3 = /\)/;
		color_str = color_str.replace (regex_1, "");
		color_str = color_str.replace (regex_2, "|");
		color_str = color_str.replace (regex_3, "");
		int_array = color_str.split ("|");
		color_str = "#"+dec2hex (int_array[0])+dec2hex (int_array[1])+dec2hex (int_array[2]);
		return color_str;
	}
}

function color_parse_hexcolor (hexcolor)
{
	var parsed_color = new Object ();
	var offset = hexcolor.charAt (0) == "#" ? 1 : 0;

	parsed_color.r_hex = hexcolor.substr (0+offset, 2);
	parsed_color.g_hex = hexcolor.substr (2+offset, 2);
	parsed_color.b_hex = hexcolor.substr (4+offset, 2);

	parsed_color.r_dec = parseInt (parsed_color.r_hex, 16);
	parsed_color.g_dec = parseInt (parsed_color.g_hex, 16);
	parsed_color.b_dec = parseInt (parsed_color.b_hex, 16);

	parsed_color.base = "#"+parsed_color.r_hex+parsed_color.g_hex+parsed_color.b_hex;

	return parsed_color;
}

function color_display_test_block (string_flag)
{
	var output_str = "<table cellpadding=\"0\" cellspacing=\"0\">";
	for (var i=0; i<this.color_array.length; i++)
	{
		output_str += "\t<tr>\n";
		output_str += "\t\t<td class=\"test_block\" style=\"width:10px;height:10px;background-color:"+this.color_array[i].shadow+"\"></td>\n";
		output_str += "\t\t<td class=\"test_block\" style=\"width:10px;height:10px;background-color:"+this.color_array[i].base+"\"></td>\n";
		output_str += "\t\t<td class=\"test_block\" style=\"width:10px;height:10px;background-color:"+this.color_array[i].hilite+"\"></td>\n";
		output_str += "\t\t<td class=\"test_block\" style=\"width:10px;height:10px;background-color:"+this.color_array[i].paper+"\"></td>\n";
		output_str += "\t</tr>\n";
	}
	output_str += "</table>\n";

	if (string_flag)
	{
		return output_str;
	}
	else
	{
		document.write (output_str);
		return true;
	}
}

