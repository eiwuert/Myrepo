<?php
	//********************************************************************************************
	//cc_validate.php
	//created: 01-14-2005
	//created by: Mel Leonard (mel.leonard@thesellingsource.com)
	//
	//this class checks to see if a credit card number is valid, expect the month and year numeric
	//
	//Sample Usage
	//$cc = new CC_Validate;
	//$cc->CC_Validate('visa', '4111111111111111', '8', '2008');
	//
	//this information still needs to be sent to a credit card proccessor to validate the credit
	//
	//rules to credit card format
	//mastercard: must have a prefix of 51 to 55 and must be 16 digits in length
	//visa: must have a prefix of 4 and must be either 13 or 16 digits  in length
	//american express: must have a prefix of 34 or 37 and must be 15 digits in length
	//discover: must have a prefix of 3, 1800 or 2131 and must be either 15 or 16 digits in length
	//
	//in addition to formatting rules, the credit card number should pass the Mod 10 algorithm
	//Mod 10 algorithm:
	//e.g. credit card number  : 4 6 5 7 6 6 5 8 2 5 3 7 6 4 4 3
	//first reverse the number : 3 4 4 6 7 3 5 2 8 5 6 6 7 5 6 4
	//                             |   |   |   |   |   |   |   |
	//double every second digit:   8   12  6   4   10  12  10  8
	//                             |   |   |   |   |   |   |   |
	//add two digits together:     8   3   6   4   1   3   1   8
	//add the new digits together: 8 + 3 + 6 + 4 + 1 + 3 + 1 + 8 = 34
	//add the unused digits:     3 + 4 + 7 + 5 + 8 + 6 + 7 + 6 + 34 = 80
	// divide the resulting number by ten if the remainder is 0 , then the number is valid
	// in this case  80 mod 10 is zero, we are good to go
	//
	//*********************************************************************************************


	class CC_Validate
	{
			var $cc_type;
			var $cc_number;
			var $exp_month;
			var $exp_year;

		//constructor
		function CC_Validate($type, $num, $expm, $expy)
		{
			switch(strtolower($type))
			{
				case 'mc':
				case 'mastercard':
				case 'MasterCard':
				case 'm':
				case '1':
					$this->cc_type = "MC";
					break;
				case 'vs':
				case 'vi':
				case 'visa':
				case 'VISA':
				case 'v':
				case '2':
					$this->cc_type = "VS";
					break;
				case 'ax':
				case 'american express':
				case 'AmericanExpress':
				case 'americanexpress':
				case 'a':
				case '3':
					$this->cc_type = "AX";
					break;
				case 'ds':
				case 'discover':
				case 'Discover':
				case '5':
					$this->cc_type = "DS";
					break;
				default:
					break;
			}

			$this->cc_number = $num;
			$this->exp_month = intval ($expm);
			if (!empty ($expy))
			{
				$this->exp_year = intval ($expy) < 2000 ? intval ("20".$expy) : intval ($expy);
			}
		}

		//check to see if the credit card type is supplied
		function CC_Type()
		{
			$status = (empty($this->cc_type)) ? FALSE : TRUE;
			return $status;
		}

		//check to see if the credit card number is supplied and it is in the right format
		function CC_Number()
		{
			//in case if the credit card number is not normalized, kill all non numeric
			$this->cc_number = ereg_replace("[^0-9]", "", $this->cc_number);

			if (empty($this->cc_number))
			{
				$status = FALSE;
			}
			else
			{
				// Is the number in the correct format?
				switch($this->cc_type)
				{
					case "MC":
						$status = (ereg("^5[1-5][0-9]{14}$", $this->cc_number) == TRUE) ? TRUE : FALSE;
						break;
					case "VS":
						$status = (ereg("^4[0-9]{12}([0-9]{3})?$", $this->cc_number) == TRUE) ? TRUE : FALSE;
						break;
					case "AX":
						$status = (ereg("^3[47][0-9]{13}$", $this->cc_number) == TRUE) ? TRUE : FALSE;
						break;
					case "DS":
						$status = (ereg("^6011[0-9]{12}$", $this->cc_number) == TRUE) ? TRUE : FALSE;
						break;
					default:
						// Should never be executed
						$status = FALSE;
				}

			}
			return $status;
		}

		//check to see if the credit card number passes Mod 10 algoritm
		function Valid_Number()
		{
			if ($this->CC_Number() == TRUE)
			{
				//apply the mode 10 algorithm
				//first reverse the credit card number
				$this->cc_number = strrev($this->cc_number);

				//now double every second digit
				$total = 0;
				for ($i=1; $i<strlen($this->cc_number); $i+=2)
				{
					$digit = (substr($this->cc_number, $i, 1) * 2);

					//if it is more than on digit, add digits together
					//note that the highest number in a credit card can only be 9,
					//therefore the highest digit here can only be 18
					$digit =  ($digit >= 10) ? ((substr($digit, 0, 1)) + (substr($digit, 1, 1))) : $digit;
					//total the digits as we go
					$total += $digit;

				}
				//add up the numbers that were not used to the total
				for ($i=0; $i<=strlen($this->cc_number); $i+=2)
				{
					$total += substr($this->cc_number, $i, 1);
				}
				//if mod10 return 0, credit card is good
				$status = ($total%10 == 0) ? TRUE : FALSE;

			}
			else
			{
				$status = FALSE;
			}
			return $status;
		}

		//check to see if the expiration month is supplied and it is the correct format
		function Exp_Month()
		{
			if (empty($this->exp_month))
			{
				$status = FALSE;
			}
			else
			{
				$status = (is_numeric($this->exp_month) && ($this->exp_month > 0) && ($this->exp_month < 13)) ? TRUE : FALSE;
			}
			return $status;
		}

		//check to see if the expiration year is supplied
		function Exp_Year()
		{

			$status = (empty($this->exp_year)) ? FALSE : TRUE;
			return $status;
		}

		//check to see if the card is expired
		function Valid_Card()
		{
			$current_month = date('n');
			$current_month = intval ($current_month);
			$current_year = date('Y');
			$current_year = intval ($current_year);

			if ($this->exp_year > $current_year)
			{
				return TRUE;
			}
			else if (($this->exp_year == $current_year) && ($this->exp_month >= $current_month))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}

		}
	}

?>
