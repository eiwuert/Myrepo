
function set_check_arrow(doc_i){
	$("#legal_approve_docs_"+doc_i).removeAttr("disabled");
	set_good_arrow(doc_i);
}

function set_good_arrow(doc_i){
	var elem = $("#legal_approve_docs_"+doc_i);
	if (!$("#legal_approve_docs_"+doc_i).prop('checked')) {
		$("#esig_arrow_side_"+doc_i).attr("src", $("#yellow_arrow").val());
	} else {
		$("#esig_arrow_side_"+doc_i).attr("src", $("#green_arrow").val());
	}
}

$(function() {
	$(".ssn").change(function(){
	
		var number = $(this).val();
		number = number.replace(/\D/g,'');
		if (number.length > 3) {
			number = number.substr(0,3)+'-'+number.substr(3);
		}
		if (number.length > 6) {
			number = number.substr(0,6)+'-'+number.substr(6);
		}
		$(this).val(number);
	});
});


$(function() {
	$(".select-input").change(function(){
		$(this).css('color', '#2C3E50');
	});
});
$(function() {
	$.datepicker.setDefaults( $.datepicker.regional[$('#user_language').value]);
	$("#dob_datepicker").datepicker({
        	changeMonth: true,
        	changeYear: true,
		maxDate: "+22y",
		yearRange: "c-78:c+22",
		defaultDate: "-40y",
		dateFormat: "yy-mm-dd",
		valid: true
	});
});
$(function() {
	$(".submit").click(function(e) {
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
			$("#application_form").submit();
		} else {
			return valid_pass;
		}
	});
	
	$(".no_thanks").click(function(){
		
		//delete old error divs on change
		$('.formError').remove();
		
		$("#form_decline").submit();
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