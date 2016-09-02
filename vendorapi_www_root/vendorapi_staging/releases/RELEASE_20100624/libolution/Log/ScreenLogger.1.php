<?php

	/**
	 * Basic logging class to write to the screen.
	 * Same as a streamlogger on STDOUT
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com
	 *
	 */
	class Log_ScreenLogger_1 extends Log_StreamLogger_1 implements Log_ILog_1
	{
		public function __construct()
		{
			parent::__construct(STDOUT);
		}
	}
?>