<?php


	/***
	
	
		csv parser (php4)
		--
		believe it or not, this code parses csv files.
		
		static method, call for each line:
			
			array CSV_Parser::Parse(string buffer [,string separator [,string text_delimiter]])
			
		returns an array of columns
		
		
		- john hargrove (john.hargrove@thesellingsource.com)
		
	
	***/
	
	
	define('CSVST_INNER', 1);
	define('CSVST_OUTER', 2);

	
	class CSV_Parser
	{
		// ================================================
		// static
		// call this method for each line in your input file
		// the input will be automatically trimmed,
		// so passing in each result from a file() call should
		// work fine.
		// NOTE: using file() can be dangerous if your input
		// files are using macintosh line-endings.
		// The solution to this is to use:
		//    ini_set('auto_detect_line_endings', true);
		// which has a negligible performance decrease
		function Parse_Line($szBuffer, $szSeparator=',', $szTextDelimiter='"')
		// ================================================
		{
			// default parser state
			$state = CSVST_OUTER;
			// return data
			$result = array();			
			// starting offset
			$offset = 0;
			
			// clean the input line
			$szBuffer = trim($szBuffer);
			
			
			// make sure we actually have something to parse, to parse with, and such
			if ( strlen($szSeparator) == 0 || strlen($szTextDelimiter) == 0 || strlen($szBuffer) == 0 )
			{
				// shoot back an empty array if so
				return $result;
			}
			
			// iterate through the input line 
			while ( $offset < strlen($szBuffer) )
			{
				// is the remaining buffer large enough to harbor this token?
				if ( strlen($szBuffer) > $offset + strlen($szSeparator) - 1 )
				{
					// get the potential token
					$tok = substr($szBuffer,$offset,strlen($szSeparator));
					
					// did we find our delimiter token?
					if ( $tok == $szSeparator )
					{
						if ( $state != CSVST_INNER )
						{
							// place this field on the output array
							$result[] = $word;
							// wipe out the old
							$word = '';
							// advance the cursor
							$offset += strlen($szSeparator);
							// next please
							continue;
						}
						else if ( $state == CSVST_INNER )
						{
							$word .= $tok;
							$offset += strlen($szSeparator);
							continue;
						}
					}
					
				}
				// once again, making sure our buffer is large enough
				if ( strlen($szBuffer) > $offset + strlen($szTextDelimiter) - 1 )
				{
					// get the potential token
					$tok = substr($szBuffer,$offset,strlen($szTextDelimiter));
					
					if ( $tok == $szTextDelimiter )
					{
						// note our state changes
						if ( $state == CSVST_INNER )
							$state = CSVST_OUTER;
						else if ( $state == CSVST_OUTER )
							$state = CSVST_INNER;
						// advance the cursor
						$offset += strlen($szTextDelimiter);
						// next please
						continue;
					}				
				}
				// continue building our current data
				$tok = $szBuffer[$offset];
				$word .= $tok;
				// advance the cursor
				$offset++;
				// wash, rinse, repeat
			}
			// push the remaining data
			$result[] = $word;
			
			// go home happy
			return $result;
		}
	}
	

?>