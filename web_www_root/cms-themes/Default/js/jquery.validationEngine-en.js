
(function($) {
	$.fn.validationEngineLanguage = function() {};
	$.validationEngineLanguage = {
		newLang: function() {
			$.validationEngineLanguage.allRules = 	{
					"required":{    			// Add your regex rules here, you can take telephone as an example
						"regex":"none",
						"alertText":"* This field is required",
						"alertTextCheckboxMultiple":"* Please select an option",
						"alertTextCheckboxe":"* This checkbox is required"},
					"required_reference":{
						"regex":"none",
						"alertText":"* This field is required if a reference is supplied"},
					"required_reference_phone":{
						"regex":"/^[2-9][0-9][0-9]-[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]$/",
						"alertText":"* This field is required if a reference is supplied or an invalid phone number"},
					"length":{
						"regex":"none",
						"alertText":"*Between ",
						"alertText2":" and ",
						"alertText3": " characters allowed"},
					"maxCheckbox":{
						"regex":"none",
						"alertText":"* Checks allowed exceeded"},	
					"minCheckbox":{
						"regex":"none",
						"alertText":"* Please select ",
						"alertText2":" options"},	
					"confirm":{
						"regex":"none",
						"alertText":"* Your field is not matching"},		
					"phone":{  //USA only
						"regex":"/^[2-9][0-9][0-9]-[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]|$/",
						"alertText":"* Invalid phone number"},	
					"ssn":{
						"regex":"/^[0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9][0-9][0-9]$/",
						"alertText":"* Invalid ssn number"},	
					"aba":{
						"regex":"/^[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$/",
						"alertText":"* Invalid bank aba number"},	
					"zip":{   //USA only
						"regex":"/^[0-9][0-9][0-9][0-9][0-9]$/",
						"alertText":"* Invalid 5 digit zip code"},	
					"email":{
						"regex":"/^[a-zA-Z0-9_\.\-]+\@([a-zA-Z0-9\-]+\.)+[a-zA-Z0-9]{2,4}$/",
						"alertText":"* Invalid email address"},	
					"password":{
						"regex":"/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,32}$/",
						"alertText":"* This password is not stong enough"},
					"date":{
						"regex":"/^(19|20)[0-9]{2}-(1[0-2]|0[1-9])-(3[01]|[12][0-9]|0[1-9])$/",
						"alertText":"* Invalid date, must be in YYYY-MM-DD format"},
					"onlyNumber":{
						"regex":"/^[0-9]+$/",
						"alertText":"* Numbers only"},	
					"noSpecialCaracters":{
						"regex":"/^[0-9a-zA-Z]+$/",
						"alertText":"* No special caracters allowed"},	
					"ajaxUser":{
						"file":"validateUser.php",
						"extraData":"name=eric",
						"alertTextOk":"* This user is available",	
						"alertTextLoad":"* Loading, please wait",
						"alertText":"* This user is already taken"},	
					"ajaxName":{
						"file":"validateUser.php",
						"alertText":"* This name is already taken",
						"alertTextOk":"* This name is available",	
						"alertTextLoad":"* Loading, please wait"},		
					"onlyLetter":{
						"regex":"/^[a-zA-Z\ \']+$/",
						"alertText":"* Letters only"},
					"doCheckCreditCard":{
						"nname":"doCheckCreditCard",
						"alertText":"Please enter a valid credit card number"}	
					}	
					
		}
	}
})(jQuery);

$(document).ready(function() {	
	$.validationEngineLanguage.newLang()
});