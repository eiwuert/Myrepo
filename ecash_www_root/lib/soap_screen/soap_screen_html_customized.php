<?php

	/**
	*   Customized HTML module for the Soap_Screen WSDL invocation tool.
	*
	*   This HTML module acts as a template of sorts.
	*
	*   @author    Don Adriano and David Hickman
	*   @link      http://SellingSource.com
	*   @since     2005.12.27
	*   @version   1.0
	*/

	class Soap_Screen_Html_Customized extends Soap_Screen_Html
	{
		public function Get_Form_Data_Row_Html( &$token_array )
		{
			$token_array['row_class'] = ($token_array['row_num'] % 2 == 0 ? 'class="rowOne"' : 'class="rowTwo"');
			$html = '
				<tr %%%row_class%%%>
					<td><b><u>%%%field_name%%%</u></b>:</td>
					<td><input type="text" name="%%%field_name%%%" size="60"></td>
				</tr>';
			return $this->Resolve_Template( $html, $token_array );
		}
	}

?>
