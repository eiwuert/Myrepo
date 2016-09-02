<?php 
define("KEY", "sellingsource");
define("LENGTH", 10);

class Code_Url
{		
	function Code_Url()
	{
   
	}
		
	function Encode_Url($input)
	{
		$time = time();
		/* Encrypt data */
		/* Open the cipher */
		$td = mcrypt_module_open('rijndael-256', '', 'ofb', '');				   
		$iv ="thisisatestfor the sellingsource";
		$ks = mcrypt_enc_get_key_size($td);	
		/* Create key */
		$key = substr(md5(KEY.$time), 0, $ks);				
		/* Intialize encryption */
		mcrypt_generic_init($td, $key, $iv);		   
   		$encrypted = mcrypt_generic($td, $input);		
   		/* Terminate decryption handle and close module */
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);	
   		return ($time.base64_encode($encrypted) );   		
	}
	
	function Url_Encode_Url($input)
	{		
		return urlencode(Code_Url::Encode_Url($input));	
	}
	
	function Url_Decode_Url($input)
	{		
		return Code_Url::Decode_Url( urldecode($input) );	
	}
	
	function Decode_Url($encrypted)
	{		
		$time = substr($encrypted, 0, LENGTH);				
		/* Encrypt data */
		/* Open the cipher */
		$td = mcrypt_module_open('rijndael-256', '', 'ofb', '');				   
		$iv ="thisisatestfor the sellingsource";
		$ks = mcrypt_enc_get_key_size($td);	
		/* Create key */
		$key = substr(md5(KEY.$time), 0, $ks);		
		/* Initialize encryption module for decryption */
   		mcrypt_generic_init($td, $key, $iv); 
   		/* Decrypt encrypted string */
   		$decrypted = mdecrypt_generic($td, base64_decode(substr($encrypted, LENGTH)) );	
		/* Terminate decryption handle and close module */
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);	
   		return $decrypted;
	}
}
/*Example
$a = Code_Url::Url_Encode_Url("This is a test");			
echo $a."\n";
echo Code_Url::Decode_Url($a);
echo "\n";
echo Code_Url::Url_Decode_Url($a);
*/
?>
