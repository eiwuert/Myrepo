<?php

class RegExpTranslator
{
	static public $exclude_min = array(
		//age
		"/^[0-9]$|^[1][0-7]$/" => "18",
		"/^[0-9]$|^[1][0-8]$/" => "19",
		"/^[0-9]$|^[1][0-9]$/" => "20",
		"/^[0-9]$|^[1][0-9]$|^[2][0]$/" => "21",
		//income
		"/^[0-9]{0,3}(\.[0-9][0-9])?$/" => "1000",
		"/^[0-9]{0,3}(\.[0-9][0-9])?$|^[1][0][0-9][0-9](\.[0-9][0-9])?$/" => "1100",
		"/^[0-9]{0,3}(\.[0-9][0-9])?$|^[1][0-1][0-9][0-9](\.[0-9][0-9])?$/" => "1200",
		"/^[0-9]{0,3}(\.[0-9][0-9])?$|^[1][0-2][0-9][0-9](\.[0-9][0-9])?$/" => "1300",
		"/^[0-9]{0,3}(\.[0-9][0-9])?$|^[1][0-3][0-9][0-9](\.[0-9][0-9])?$/" => "1400",
		"/^[0-9]{0,3}(\.[0-9][0-9])?$|^[1][0-4][0-9][0-9](\.[0-9][0-9])?$/" => "1500",
		"/^[0-9]{0,3}(\.[0-9][0-9])?$|^[1][0-5][0-9][0-9](\.[0-9][0-9])?$/" => "1600",
		"/^[0-9]{0,3}(\.[0-9][0-9])?$|^[1][0-6][0-9][0-9](\.[0-9][0-9])?$/" => "1700",
		"/^[0-9]{0,3}(\.[0-9][0-9])?$|^[1][0-7][0-9][0-9](\.[0-9][0-9])?$/" => "1800",
		"/^[0-9]{0,3}(\.[0-9][0-9])?$|^[1][0-8][0-9][0-9](\.[0-9][0-9])?$/" => "1900",
	);

	static public $exclude_boolean = array(
		"/^true$|^1$|^yes$/i" => "no",
		"/^false$|^0$|^no$/i" => "yes",
	);

	static public $max_characters = array(
		"/^[\s\S]{26,}$/" => "25",
		"/^[\s\S]{27,}$/" => "26",
		"/^[\s\S]{28,}$/" => "27",
		"/^[\s\S]{29,}$/" => "28",
		"/^[\s\S]{30,}$/" => "29",
		"/^[\s\S]{31,}$/" => "30",
		"/^[\s\S]{32,}$/" => "31",
		"/^[\s\S]{33,}$/" => "32",
		"/^[\s\S]{34,}$/" => "33",
		"/^[\s\S]{35,}$/" => "34",
		"/^[\s\S]{36,}$/" => "35",
		"/^[\s\S]{37,}$/" => "36",
		"/^[\s\S]{38,}$/" => "37",
		"/^[\s\S]{39,}$/" => "38",
		"/^[\s\S]{40,}$/" => "39",
		"/^[\s\S]{41,}$/" => "40",
		"/^[\s\S]{42,}$/" => "41",
		"/^[\s\S]{43,}$/" => "42",
		"/^[\s\S]{44,}$/" => "43",
		"/^[\s\S]{45,}$/" => "44",
		"/^[\s\S]{46,}$/" => "45",
		"/^[\s\S]{47,}$/" => "46",
		"/^[\s\S]{48,}$/" => "47",
		"/^[\s\S]{49,}$/" => "48",
		"/^[\s\S]{50,}$/" => "49",
		"/^[\s\S]{51,}$/" => "50",
	);

}

?>