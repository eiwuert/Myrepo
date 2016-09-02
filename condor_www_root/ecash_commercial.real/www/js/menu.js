// SCRIPT FILE menu.js
var last_menu = null;

function Toggle_Menu(element_id, parent_id)
{

	var visibility;
	var element = document.getElementById(element_id);
	var parentObj = document.getElementById(parent_id);

	if(element != null)
	{
		var is_vis = element.style.visibility;
		if(is_vis == "visible")
		{
			visibility = "hidden";
		}
		else
		{
			visibility = "visible";
		}
		element.style.zIndex=11;
	}
	if ((last_menu != null) && (last_menu != element))
	{
		last_menu.style.visibility = "hidden";
	}
	if(parentObj != null)
	{
		element.style.left = parentObj.offsetLeft + "px";
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
	document.getElementById("search_option").checked = false;;	
}

function changeCompany()
{
	var selectedCompanyForm = document.getElementById('change_company_form');
	selectedCompanyForm.submit();
}

function tooltip(e, contents, offsetX, offsetY)
{
    var tt = document.getElementById('tooltip');
    if(!e){
        tt.style.visibility = "hidden";
        return;
    }
    offsetX = offsetX ? offsetX : 30;
    offsetY = offsetY ? offsetY : 30;

    tt.innerHTML = contents;
    tt.style.left = e.pageX +"px";
    var newtop = eval(e.pageY) + offsetY;
    tt.style.top = newtop +"px";
    tt.style.visibility = "visible";

}

