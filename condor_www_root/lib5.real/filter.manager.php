<?php
/**
 * Handles conversions between different formats using external tools.
 *
 * Currently supports converting:
 * 		HTML -> PDF
 * 		HTML -> PostScript
 * 
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */
class Filter_Manager
{
	// Input constants
	const INPUT_HTML = 'Html';
	
	// Output constants
	const OUTPUT_PDF = 'Pdf';
	const OUTPUT_PS = 'Ps';
	
	const HTMLDOC_BIN = 'htmldoc';
	
	/**
	 * Performs the transformation between the given content-types.
	 *
	 * @param string $input
	 * @param string $input_content_type
	 * @param string $output_content_type
	 * @return string
	 */
	public static function Transform($input, $input_content_type, $output_content_type)
	{
		switch($input_content_type)
		{
			case self::INPUT_HTML:
				$input_type = self::INPUT_HTML;
				break;
			default:
				assert(false);
		}
		
		switch($output_content_type)
		{
			case self::OUTPUT_PDF:
				$output_type = self::OUTPUT_PDF;
				break;
			case self::OUTPUT_PS:
				$output_type = self::OUTPUT_PS;
				break;
			default:
				assert(false);
		}
		
		$function = $input_type.'_To_'.$output_type;
		
		$ret_val = false;
		
		if(is_callable(array('Filter_Manager', $function)))
		{
			$ret_val = call_user_func(array('Filter_Manager', $function), $input);
		}
		
		return $ret_val;
	}
	
	/**
	 * Returns the common extension for the content-type given.
	 *
	 * @param string $content_type
	 * @return string
	 */
	public static function Get_Extension($content_type)
	{
		switch($content_type)
		{
			case 'application/pdf':
				$extension = ".pdf";
				break;
			case 'text/html':
				$extension = ".html";
				break;
			case 'text/rtf':
				$extension = ".rtf";
				break;
			default:
				$extension = ".bin";
				break;
		}
		
		return $extension;
	}
	
	/**
	 * Converts an HTML file into a PDF. Currently implemented with HTMLDoc.
	 *
	 * @param string $input
	 * @return string
	 */
	private static function Html_To_Pdf($input)
	{
		$ret_val = FALSE;
		
		$descriptor_spec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('file', '/dev/null', 'a') // Errors will go into oblivion (for now)
		);
		
		$pipes = array();
		
		$cmd = self::HTMLDOC_BIN.' --quiet --webpage --footer ... -t pdf14 -';
		$filter = proc_open($cmd, $descriptor_spec, $pipes);
		
		if(is_resource($filter))
		{
			// Write the contents to HTMLDOC
			fwrite($pipes[0], $input);
			fclose($pipes[0]);
			
			// Get the return
			$ret_val = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			
			proc_close($filter);
		}
		
		return $ret_val;
	}
	
	/**
	 * Converts an HTML file into a PostScript file.
	 *
	 * @param string $input
	 * @return string
	 */
	private static function Html_To_Ps($input)
	{
		$ret_val = false;
		
		$descriptor_spec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('file', '/dev/null', 'a') // Errors will go into oblivion (for now)
		);
		
		$pipes = array();
		
		$cmd = self::HTMLDOC_BIN.' --quiet --no-embed --gray --webpage --footer ... -t ps2 -';
		$filter = proc_open($cmd, $descriptor_spec, $pipes);
		
		if(is_resource($filter))
		{
			// Write the contents to HTMLDOC
			fwrite($pipes[0], $input);
			fclose($pipes[0]);
			
			// Get the return
			$ret_val = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			
			proc_close($filter);
		}
		
		return $ret_val;
	}
}
?>
