
(function($) {
	$.fn.validationEngineLanguage = function() {};
	$.validationEngineLanguage = {
		newLang: function() {
			$.validationEngineLanguage.allRules = 	{
					"required":{    			// Add your regex rules here, you can take telephone as an example
						"regex":"none",
						"alertText":"* Este campo es obligatorio",
						"alertTextCheckboxMultiple":"* Por favor seleccione una opci\u00F3n",
						"alertTextCheckboxe":"* Se requiere esta casilla"},
					"required_reference":{
						"regex":"none",
						"alertText":"* Este campo es obligatorio si se suministra una referencia"},
					"required_reference_phone":{
						"regex":"/^[2-9][0-9][0-9]-[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]$/",
						"alertText":"* Este campo es obligatorio si se suministra una referencia o n\u00FAmero de tel\u00E9fono v\u00E1lido"},
					"length":{
						"regex":"none",
						"alertText":"*Entre ",
						"alertText2":" y ",
						"alertText3": " caracteres permitidos"},
					"maxCheckbox":{
						"regex":"none",
						"alertText":"* Los cheques permiti\u00F3 super\u00F3"},	
					"minCheckbox":{
						"regex":"none",
						"alertText":"* Por favor, seleccione ",
						"alertText2":" opciones"},	
					"confirm":{
						"regex":"none",
						"alertText":"* Su campo no est\u00E1 emparejando"},		
					"phone":{  //USA only
						"regex":"/^[2-9][0-9][0-9]-[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]$/",
						"alertText":"* N\u00FAmero de tel\u00E9fono v\u00E1lido"},	
					"ssn":{
						"regex":"/^[0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9][0-9][0-9]$/",
						"alertText":"* N\u00FAmero de seguro social v\u00E1lido"},	
					"aba":{
						"regex":"/^[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]$/",
						"alertText":"* N\u00FAmero aba bancaria v\u00E1lida"},	
					"zip":{   //USA only
						"regex":"/^[0-9][0-9][0-9][0-9][0-9]$/",
						"alertText":"* Inv\u00E1lido c\u00F3digo postal de 5 d\u00EDgitose"},
					"email":{
						"regex":"/^[a-zA-Z0-9_\.\-]+\@([a-zA-Z0-9\-]+\.)+[a-zA-Z0-9]{2,4}$/",
						"alertText":"* Direcci\u00F3n de correo electr\u00F3nico no es v\u00E1lido"},
					"password":{
						"regex":"/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,32}$/",
						"alertText":"* Esta contrase\u00F1a no es lo suficientemente fuerte"},
					"date":{
						"regex":"/^(19|20)[0-9]{2}-(1[0-2]|0[1-9])-(3[01]|[12][0-9]|0[1-9])$/",
						"alertText":"* Fecha no v\u00E1lida, debe estar en formato AAAA-MM-DD"},
					"onlyNumber":{
						"regex":"/^[0-9]+$/",
						"alertText":"* S\u00F3lo n\u00FAmeros"},	
					"noSpecialCaracters":{
						"regex":"/^[0-9a-zA-Z]+$/",
						"alertText":"* No hay signos especiales permitidos"},	
					"ajaxUser":{
						"file":"validateUser.php",
						"extraData":"name=eric",
						"alertTextOk":"* Este usuario est\u00E1 disponible",	
						"alertTextLoad":"* Cargando, por favor espere",
						"alertText":"* Este usuario ya est\u00E1 en uso"},	
					"ajaxName":{
						"file":"validateUser.php",
						"alertText":"* Este nombre ya est\u00E1 en uso",
						"alertTextOk":"* Este nombre est\u00E1 disponible",	
						"alertTextLoad":"* Cargando, por favor espere"},		
					"onlyLetter":{
						"regex":"/^[a-zA-Z\ \']+$/",
						"alertText":"* S\u00F3lo letras"},
					"doCheckCreditCard":{
						"nname":"doCheckCreditCard",
						"alertText":"* Por favor, introduzca un n\u00FAmero de tarjeta de cr\u00E9dito v\u00E1lida"}	
						}	
					
		}
	}
})(jQuery);

$(document).ready(function() {	
	$.validationEngineLanguage.newLang()
});