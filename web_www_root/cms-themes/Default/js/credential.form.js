$(function() {
	$(".submit").click(function(){
		
		//return false;
		var field_name = ".fieldset";  // easy hack
		//var field_name = "."+$(this).prop('name');
		var field_elems = $(field_name);
		var valid_result = [];
		var valid_pass = true;
		var result;

		//delete old error divs on change
		$('.formError').remove();
		
		//validate the the fields within the fieldset for the step first
		field_elems.each(function() {
			result = $.validationEngine.loadValidation(this);
			if (result) {
				valid_pass = false;
			}
			valid_result[valid_result.length] = result;
		});
		
		if (valid_pass) {
			// visibility hide page and display 'please wait'
			$('#normal_page').hide('');
			$('.waiting_page').show('');
			$('html, body').scrollTop($(".waiting_page").offset().top);
			$( "#application_form" ).submit();
		}
		return valid_pass;
		
	});
});

$(document).ready(function($) {
	$('#user_string1').strength({
		strengthClass: 'strength',
		strengthMeterClass: 'strength_meter',
		strengthButtonClass: 'button_strength',
		strengthButtonText: 'Show Password',
		strengthButtonTextToggle: 'Hide Password'
        });
});

function session_checking()
{
	
	$.post( "session", function( data ) {
		if(data == "-1")
		{
			var lang_str = {en:"Your session has expired.", es:"Su sesi\u00F3n ha caducado."};
			var lang = $('#user_language').val();
			var str = lang_str[lang];
			alert(lang_str[$('#user_language').val()]);
			location.reload();
		}
	});
}
// test session every 60,000 microseconds (1 minute)
var validateSession = setInterval(session_checking, 60000);