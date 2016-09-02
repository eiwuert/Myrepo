<?php

	function Get_Command_Line_Parameters( &$argc, &$argv )
	{
		$k = $v = '';
		$result = array();
		
		for ( $i = 1; $i < $argc; $i++ )
		{
			$arg = $argv[$i];
			Get_Key_Value_Urldecoded( $arg, $k, $v );
			$k = strtolower($k);
			switch ( $k )
			{
				case 'companyid' :
				case 'company_id' :
					$result['company_id'] = $v;
					break;

				case 'mode' :
					$v = strtoupper($v);
					$result['mode'] = $v;
					switch ( $v )
					{
						case 'LIVE'  : break;
						case 'RC'    : break;
						case 'LOCAL' : break;
						default      :
							$result['mode'] = 'LOCAL';
							break;
					}

				default:
					$result[$k] = $v;
					break;
			}
		}

		if ( !isset( $result['mode'] ) )
		{
			$result['mode'] = 'LOCAL';
		}

		if ( !isset( $result['company_id'] ) )
		{
			$result['company_id'] = '1';
		}

		return $result;
	}


	function Get_Key_Value_Urldecoded( $str, &$key, &$val, $separator='=' )
	{
	
		$key = '';
		$val = '';
		
		if ( !isset($str) || $str == '' ) return;
		$pos = strpos( $str, $separator );
		
		if ( !is_numeric($pos) ) {
			$val = $str;
			return;
		}

		$len = strlen($str);
		$key = urldecode(substr( $str, 0, $pos ));

		// for length values of zero, substr() returns the original string rather than '' (weird!)
		$number_chars_after_separator = ($len - $pos - 1);
		$val = $number_chars_after_separator > 0 ? urldecode(substr( $str, -$number_chars_after_separator )) : '';
	}

?>