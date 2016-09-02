<?php
	$url = $_SERVER['REQUEST_URI'];
	$url_bits = explode('/',$url);
	if (in_array($url_bits[1],array('es','en'))){
		$lang = $url_bits[1];
		$url = substr($url,3);
	} 
	if (lum_getCurrentLanguage() == 'es')
	{
		define ('CMS_LANGUAGE','es');
		$lang = 'en';
	}
	else
	{
		if ($lang == 'es')
		{
			define ('CMS_LANGUAGE','es');
			$lang = 'en';
		}
		else
		{
			define ('CMS_LANGUAGE','en');
			$lang = 'es';
		}
	}
	$url = '/'.$lang.$url;
	if ($lang == 'en')
	{
		echo '<a href="'.$url.'" class="english"></a>';
	}
	else
	{
		echo '<a href="'.$url.'" class="spanish"></a>';
	}
?>			
