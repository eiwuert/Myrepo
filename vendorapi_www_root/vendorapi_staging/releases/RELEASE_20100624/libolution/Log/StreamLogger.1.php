<?php

	/**
	 * Class for logging to a stream resource (STDOUT, STDERR, an open file, etc.)
	 * Note that the stream logger does not close the stream when it is finished using it.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 */
	class Log_StreamLogger_1 extends Object_1 implements Log_ILog_1
	{
		/**
		 * @var resource
		 */
		protected $stream_resource;

		/**
		 * @param resource $stream_resource
		 */
		public function __construct($stream_resource)
		{
			if (!is_resource($stream_resource))
			{
				throw new Exception("Invalid resource supplied for stream logger.");
			}

			$this->stream_resource = $stream_resource;
		}

		/**
		 * method for writing
		 *
		 * @param string $message
		 */
		public function write($message, $log_level = Log_ILog_1::LOG_INFO)
		{
			fputs($this->stream_resource, $message . PHP_EOL);
		}
	}

?>