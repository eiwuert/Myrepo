
function change_paydate_model(div){
	// visibility
	$("#div_biweekly").hide('');
	$("#label_biweekly_date_tm").hide('');
	$('[id^="paydate_biweekly_day_div_"]').hide('');
	$('[id^="paydate_twice_biweekly_day_div_"]').hide('');
	$(".paydate_div").hide('');

	$("#paydate_div_"+div.toLowerCase()).show('');

	//delete old error divs on change
	$('.formError').remove();
	
	// validation
	$(".paydate_div").find(':input').removeClass("validate[required]");
	$(".paydate_div").find(':input').removeClass("fieldset4");

	$("#paydate_div_"+div.toLowerCase()).find(':input').addClass("validate[required]");
	$("#paydate_div_"+div.toLowerCase()).find(':input').addClass("fieldset4");
}

function funcShowDayWeek(val)
{
	$(".biweekly_twice_day").selectedIndex = $(".biweekly_day").selectedIndex;
	
	// visibility
	$('[id^="paydate_biweekly_day_div_"]').hide('');
	$('[id^="paydate_twice_biweekly_day_div_"]').hide('');
	
	$("#paydate_biweekly_day_div_"+val.toUpperCase()).show('');
	$("#paydate_twice_biweekly_day_div_"+val.toUpperCase()).show('');
	
	//delete old error divs on change
	$('.formError').remove();
	
	// validation
	$('[id^="paydate_biweekly_day_div_"]').not( ".hide" ).find(':input').removeClass("validate[required]");
	$('[id^="paydate_biweekly_day_div_"]').not( ".hide" ).find(':input').removeClass("fieldset4");

	$('[id^="paydate_twice_biweekly_day_div_"]').not( ".hide" ).find(':input').removeClass("validate[required]");
	$('[id^="paydate_twice_biweekly_day_div_"]').not( ".hide" ).find(':input').removeClass("fieldset4");

	$("#paydate_biweekly_day_div_"+val.toUpperCase()).not( ".hide" ).find(':input').addClass("validate[required]");
	$("#paydate_biweekly_day_div_"+val.toUpperCase()).not( ".hide" ).find(':input').addClass("fieldset4");

	$("#paydate_twice_biweekly_day_div_"+val.toUpperCase()).not( ".hide" ).find(':input').addClass("validate[required]");
	$("#paydate_twice_biweekly_day_div_"+val.toUpperCase()).not( ".hide" ).find(':input').addClass("fieldset4");
}

function funcBiweeklyDay()
{
	$(".biweekly_twice_day").selectedIndex = $(".biweekly_day").selectedIndex;
	$("#div_biweekly_once_date").show('');
	funcClearRadio('biweekly_date');
}

function funcBiweeklyDayLabel()
{
	$(".biweekly_day").selectedIndex = $(".biweekly_twice_day").selectedIndex;
	$("#div_biweekly_twice_date").show('');
	funcClearRadio('biweekly_date');
}

function biweekly_twice_day()
{
	$(".biweekly_day").selectedIndex = $(".biweekly_twice_day").selectedIndex;
}

function div_twicemonthly_show(type,index)
{	// This value has to be set to every other week to process correctly, even though it is in twice per month
	$("#pay_frequency").selectedIndex = index;
	
	// visibility
	$('[id^="div_twicemonthly_"]').hide('');
	$('#div_twicemonthly_'+type).show('');
	
	//delete old error divs on change
	$('.formError').remove();

	// validation
	$('[id^="div_twicemonthly_"]').find(':input').removeClass("validate[required]");
	$('[id^="div_twicemonthly_"]').find(':input').removeClass("fieldset4");
	
	$('#div_twicemonthly_'+type).find(':input').addClass("validate[required]");
	$('#div_twicemonthly_'+type).find(':input').addClass("fieldset4");
}

function div_monthly_show(type)
{
	// visibility
	$('[id^="div_monthly_"]').hide('');
	$('#div_monthly_'+type).show('');
	
	//delete old error divs on change
	$('.formError').remove();
	
	// validation
	$('[id^="div_monthly_"]').find(':input').removeClass("validate[required]");
	$('[id^="div_monthly_"]').find(':input').removeClass("fieldset4");

	$('#div_monthly_'+type).find(':input').addClass("validate[required]");
	$('#div_monthly_'+type).find(':input').addClass("fieldset4");
}

function funcClearRadio(name)
{
	var button_name = "paydate["+name+"]";
	$('input[name="'+button_name+'"]').prop('checked', false);
	//$('#label_'+name).show('');
}
