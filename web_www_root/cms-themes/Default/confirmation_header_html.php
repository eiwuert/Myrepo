<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>[META_TITLE]</title>
		<meta name="description" content="[META_DESCRIPTION]" />
		<meta name="keywords" content="[META_KEYWORDS]" />
<!--		<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js'></script>
		<script type="text/javascript" src="<?php lum_getThemeJsUrl();?>/jquery-ui-1.8.13.custom.min.js"></script>
-->
		<link href="<?php lum_getThemeCssUrl();?>/main.css" rel="stylesheet" type="text/css" />
                <link href="<?php lum_getThemeCssUrl();?>/application.form.css" rel="stylesheet" type="text/css" />
		<link rel="stylesheet" type="text/css" media="all" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/base/jquery-ui.css">
		<link rel="stylesheet" type="text/css" media="all" href="https://fonts.googleapis.com/css?family=Acme">
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
		<script type="text/javascript" src="<?php lum_getThemeJsUrl();?>/jquery.flexslider-min.js"></script>
		<script type="text/javascript" src="<?php lum_getThemeJsUrl();?>/jquery.validationEngine.js"></script>
		<script type="text/javascript" src="<?php lum_getThemeJsUrl();?>/jquery.validationEngine-<?= lum_getCurrentLanguage() == '' ? lum_isDefaultLanguage(): lum_getCurrentLanguage();?>.js"></script>
		<script type="text/javascript" src="<?php lum_getThemeJsUrl();?>/jquery.jfontsize-1.0.min.js"></script>
		<script type="text/javascript" src="<?php lum_getThemeJsUrl();?>/site.js"></script>
		<script type="text/javascript" src="<?php lum_getThemeJsUrl();?>/confirmation.form.js"></script>
		<script type="text/javascript">
			var RecaptchaOptions = {
				lang : '<?= lum_getCurrentLanguage() == '' ? lum_isDefaultLanguage(): lum_getCurrentLanguage();?>',
				theme : 'clean',
			};
		</script>
		<script src='https://www.google.com/recaptcha/api.js'></script>
	</head>
	<body>