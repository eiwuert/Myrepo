var last_menu = null;

function Toggle_Menu(element_id)
{
	var visibility;
	var element = document.getElementById(element_id);
	if(element != null)
	{
		var is_vis = element.style.visibility;
		if(is_vis == "visible" || is_vis == "")
		{
			visibility = "hidden";
		}
		else
		{
			visibility = "visible";
		}
	}
	if ((last_menu != null) && (last_menu != element))
	{
		last_menu.style.visibility = "hidden";
	}
	element.style.visibility = visibility;
	last_menu = element;
}

/*
This is called on the search screen and clears the search values.
*/
function Clear_Search()
{
	document.getElementById("criteria_type_1").value = "application_id";
	document.getElementById("criteria_type_2").value = "";
	document.getElementById("search_deliminator_1").value = "is";
	document.getElementById("search_deliminator_2").value = "is";
	document.getElementById("search_criteria_1").value = "";
	document.getElementById("search_criteria_2").value = "";
}