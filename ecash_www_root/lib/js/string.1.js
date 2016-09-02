// string_lib.js
// additions to the JavaScript String object functionality

String.prototype.trim = trim_str;

function trim_str (str)
{
	str = this != window ? this : str;
	return str.replace (/^\s+/g, '').replace (/\s+$/g, '');
}

