// Custom select style
$(document).ready(function(){

	hideHiddenDivs();
	setupFaqButton();
	setupFormValidation();

});

function hideHiddenDivs()
{
	$('.hide').hide();
}
function setupFormValidation()
{
	if ($('.validate_form').length)
		$('.validate_form').validationEngine();
}

function setupFaqButton()
{
	// Back to top
	$('#faqs').before('<div id="toTop">^ Back To Top</div>');
	$('#toTop').hide();
	$(window).scroll(function() {

		if($(this).scrollTop() != 0) {
			$('#toTop').fadeIn();	
		} else {
			$('#toTop').fadeOut();
		}
	});
	$('#toTop').click(function() {
		$('body,html').animate({scrollTop:0},800);
	});	
}