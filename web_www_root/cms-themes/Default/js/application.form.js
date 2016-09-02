// request amount slider
var _default_request_value = 700;
var _request_request_step = 50;
var _request_div_pixels = 500;
var _request_div_factor = 1;
var _request_slide_min = 200;
var _request_slide_max = 1000;
var _request_slide_off = 5;
var _request_slide_old_val = 0;

var popupWindow = null;
function centeredPopup(url,winName,w,h,scroll){
	LeftPosition = (screen.width) ? (screen.width-w)/2 : 0;
	TopPosition = (screen.height) ? (screen.height-h)/2 : 0;
	settings = 'height='+h+',width='+w+',top='+TopPosition+',left='+LeftPosition+',scrollbars='+scroll+',resizable';
	popupWindow = window.open(url,winName,settings);
}

$(function() {
	$(".reference_input").change(function(){
		setReferenceValidate();
	});
});

$(function() {
	$(".select-input").change(function(){
		$(this).css('color', '#2C3E50');
	});
});

function setReferenceValidate(){
	// remove errors on any test
	$('.formError').remove();
	
	// check to see any of reference 1 is set
	var text1 = "";
	text1 += $('#ref_01_name_full').val();
	text1 += $('#ref_01_phone').val();
	text1 += $('#ref_01_relationship').val();
	if (text1 != "") {
		// if so require all of reference 1 inputs
		$('#ref_01_name_full').addClass("validate[required_reference]");
		$('#ref_01_relationship').addClass("validate[required_reference]");
		$('#ref_01_phone').addClass("validate[required_reference_phone]");
		$('#ref_01_name_full').addClass("fieldset6");
		$('#ref_01_relationship').addClass("fieldset6");
		$('#ref_01_phone').addClass("fieldset6");
	} else {
		// if none set, require none
		$('#ref_01_name_full').removeClass("validate[required_reference]");
		$('#ref_01_relationship').removeClass("validate[required_reference]");
		$('#ref_01_phone').removeClass("validate[required_reference_phone]");
		$('#ref_01_name_full').removeClass("fieldset6");
		$('#ref_01_relationship').removeClass("fieldset6");
		$('#ref_01_phone').removeClass("fieldset6");
	}
	
	// check to see any of reference 2 is set
	var text1 = "";
	text1 += $('#ref_02_name_full').val();
	text1 += $('#ref_02_phone').val();
	text1 += $('#ref_02_relationship').val();
	if (text1 != "") {
		// if so require all of reference 2 inputs
		$('#ref_02_name_full').addClass("validate[required_reference]");
		$('#ref_02_relationship').addClass("validate[required_reference]");
		$('#ref_02_phone').addClass("validate[required_reference_phone]");
		$('#ref_02_name_full').addClass("fieldset6");
		$('#ref_02_relationship').addClass("fieldset6");
		$('#ref_02_phone').addClass("fieldset6");
	} else {
		// if none set, require none
		$('#ref_02_name_full').removeClass("validate[required_reference]");
		$('#ref_02_relationship').removeClass("validate[required_reference]");
		$('#ref_02_phone').removeClass("validate[required_reference_phone]");
		$('#ref_02_name_full').removeClass("fieldset6");
		$('#ref_02_relationship').removeClass("fieldset6");
		$('#ref_02_phone').removeClass("fieldset6");
	}
}

function setLabelPosition() {
	var label = $('#request_val');
	var pre_label = $('#request_space');
	var thumb = $($('#request_slide').children('.ui-slider-handle'));
	var thumb_val = thumb.offset().left;
	if (_request_slide_old_val > 0){
		if (parseInt($('#requested_amount').val()) > _request_slide_old_val) {
			_request_slide_off = -22;
		} else {
			if (parseInt($('#requested_amount').val()) < _request_slide_old_val){
				_request_slide_off = 30;
			}
		}
	}
	var offset = thumb_val - pre_label.offset().left-_request_slide_off;
	label.css({"display": 'block', "position": "relative", "left": offset});
	_request_slide_old_val = $('#requested_amount').val();
}

$(function(){
	$('#request_slide').slider({ 
		range: "max",
		max: _request_slide_max,
		min: _request_slide_min,
		step: _request_request_step,
		value: _default_request_value,
		slide: function(e,ui) {
			$('#requested_amount').val(ui.value);
			$('#request_val').html(ui.value);
			setLabelPosition();
		}
	});
});
$(document).ready(function() {
	$('#requested_amount').val(_default_request_value);
	$('#request_val').html(_default_request_value);
	setLabelPosition();
	$('.flexslider').flexslider({
		directionNav: false
	});
	setLabelPosition();
});

// progress bar and msform
var current_fs, next_fs, previous_fs; //fieldsets
var left, opacity, scale; //fieldset properties which we will animate
var animating; //flag to prevent quick multi-click glitches
var eighteenYr = -18 * 365;

$(function() {
	$(".phone").change(function(){
	
		var number = $(this).val();
		number = number.replace(/\D/g,'');
		if (number.length > 3) {
			number = number.substr(0,3)+'-'+number.substr(3);
		}
		if (number.length > 7) {
			number = number.substr(0,7)+'-'+number.substr(7);
		}
		$(this).val(number);
	});
});

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
	$(".next").click(function(){
	
		var field_name = "."+$(this).prop('name');
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
		if (valid_pass){
			if(animating) return false;
			animating = true;
			current_fs = $(this).parent();
			next_fs = $(this).parent().next();
			//activate next step on progressbar using the index of next_fs
			$("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active");
			
			//show the next fieldset
			next_fs.show(); 
			//hide the current fieldset with style
			current_fs.animate({opacity: 0}, {
				step: function(now, mx) {
					//as the opacity of current_fs reduces to 0 - stored in "now"
					//1. scale current_fs down to 80%
					scale = 1 - (1 - now) * 0.2;
					//2. bring next_fs from the right(50%)
					left = (now * 50)+"%";
					//3. increase opacity of next_fs to 1 as it moves in
					opacity = 1 - now;
					current_fs.css({'transform': 'scale('+scale+')'});
					next_fs.css({'left': left, 'opacity': opacity});
				}, 
				duration: 800, 
				complete: function(){
					current_fs.hide();
					animating = false;
				}, 
				//this comes from the custom easing plugin
				easing: 'easeInOutBack'
			});
		} else {
			return false;
		}
	});
	
	$(".previous").click(function(){

		//delete old error divs on change
		$('.formError').remove();
		
		if(animating) return false;
		animating = true;
		
		current_fs = $(this).parent();
		previous_fs = $(this).parent().prev();
	
		//de-activate current step on progressbar
		$("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");
		
		//show the previous fieldset
		previous_fs.show(); 
		//hide the current fieldset with style
		current_fs.animate({opacity: 0}, {
			step: function(now, mx) {
				//as the opacity of current_fs reduces to 0 - stored in "now"
				//1. scale previous_fs from 80% to 100%
				scale = 0.8 + (1 - now) * 0.2;
				//2. take current_fs to the right(50%) - from 0%
				left = ((1-now) * 50)+"%";
				//3. increase opacity of previous_fs to 1 as it moves in
				opacity = 1 - now;
				current_fs.css({'left': left});
				previous_fs.css({'transform': 'scale('+scale+')', 'opacity': opacity});
			}, 
			duration: 800, 
			complete: function(){
				current_fs.hide();
				animating = false;
			}, 
			//this comes from the custom easing plugin
			easing: 'easeInOutBack'
		});
	});
	
	$(".submit").click(function(){
		
		//return false;
		var field_name = ".fieldset7";  // easy hack
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


