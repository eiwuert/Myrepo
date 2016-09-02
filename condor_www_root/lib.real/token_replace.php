<?php
	class Token_Replacer
	{
		private $token_prefix; // characters prefixing tokens in subject data
		private $token_suffix; // characters suffixing tokens in subject data
		
		public function Token_Replacer($token_prefix = '%%%', $token_suffix = '%%%')
		{
			$this->token_prefix = $token_prefix;
			$this->token_suffix = $token_suffix;
		}
		
		public function Replace($subject, $tokens)
		{
			foreach ($tokens as $token => $value)
			{
				$subject = str_replace($this->token_prefix . $token . $this->token_suffix, $value, $subject);
			}
			return $subject;
		}
	}
?>