/*
Example usage:

==================================================
ajax_server_test.php:
--------------------------------------------------
<?php
echo $_REQUEST["x"]."\n".$_REQUEST["y"];
?>
==================================================

==================================================
ajax.html:
--------------------------------------------------
<html>
<head>
<title>Ajax Object Test</title>

<script type="text/javascript" src="js/ajax.1.js"></script>

<script type="text/javascript">

foo = new ajax ("ajax_server_test.php");

function go ()
{
	foo.set_target ("targ");
	foo.set_methods (new_func);
	dat = new Array ();
	dat["x"] = "This is a test";
	dat["y"] = "of the ajax object";
	foo.query (dat);
}

function new_func (str)
{
	split_str = str.split ("\n");
	output_str = "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\">\n";
	for (i in split_str)
	{
		output_str += "<tr><td>"+split_str[i]+"</td></tr>\n";
	}
	output_str += "</table>\n";
	this.target.innerHTML = output_str;
}

</script>

</head>
<body>

<a href="javascript:go()">GO</a><br />

<div id="targ"></div>

</body>
</html>
==================================================
*/
var ajax_instance;

function ajax (server_script, target_id)
{
	// properties;
	this.server = server_script;
	this.target = target_id ? document.getElementById (target_id) : false;
	this.ajax_obj = ajax_fetch_obj ();

	// set up the external object reference used in the state function
	ajax_instance = this;

	// methods
	this.set_server = function (server_script)
	{
		this.server = server_script;
	}

	this.set_target = function (target_id)
	{
		this.target = document.getElementById (target_id);
	}

	this.state = function ()
	{
		// ajax_instance is needed because this.state ends up belonging to
		// the ajax_obj, not the ajax class.
		switch (ajax_instance.ajax_obj.readyState)
		{
			// loading...
			case 1:
				ajax_instance.loading ();
				break;
			case 4:
				ajax_instance.done (ajax_instance.ajax_obj.responseText);
				break;
		}
	}

	this.send_query = function (query_str)
	{
		this.ajax_obj.open ("GET", this.server+query_str);
		this.ajax_obj.onreadystatechange = this.state;
		this.ajax_obj.send ("");
	}

	this.query = function (data_arr)
	{
		query_str = "";
		if (data_arr instanceof Array)
		{
			for (idx in data_arr)
			{
				query_str += idx+"="+data_arr[idx]+"&";
			}
			query_str = query_str.substr (0, query_str.length-1);
			this.send_query ("?"+query_str);
		}
		else
		{
			return false;
		}
	}

	this.set_methods = function (done_func, loading_func)
	{
		this.done = done_func;
		if (loading_func)
		{
			this.loading = loading_func;
		}
	}

	// this is a stub designed to be overloaded
	this.loading = function ()
	{
		this.target.innerHTML = "loading...";
	}

	// this is a stub designed to be overloaded
	this.done = function (returned_data)
	{
		this.target.innerHTML = returned_data;
	}
}

function ajax_fetch_obj ()
{
	var x;
	var browser = navigator.appName;
	if (browser == "Microsoft Internet Explorer")
	{
		x = new ActiveXObject ("Microsoft.XMLHTTP");
	}
	else
	{
		x = new XMLHttpRequest ();
	}
	return x;
}

