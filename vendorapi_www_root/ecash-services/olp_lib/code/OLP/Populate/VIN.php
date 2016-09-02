<?php

/** Generate a random, fake vin number.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_VIN extends OLP_Populate_PopulateItem
{
	/**
	 * @var PopulateItemArray
	 */
	protected $vin_templates;
	
	/**
	 * @var PopulateItemNumber
	 */
	protected $populate_number;
	
	/** The VIN template you want to use.
	 *
	 * @param string $vin_template
	 */
	public function __construct()
	{
		$this->data = array(
			'vin' => '',
		);
		
		$this->vin_templates = new OLP_Populate_Array(array(
			'3FRWW75R*6', '1GTEC14S*R', 'WVWTJ21G*L', '2FZMCHDJ*6',
			'WAUEL74F*6', 'JB3XE74C*M', '1P3AP24K*R', '2P4GP45G*X',
			'1FDWX37F*2', '1FTPF12V*6', '1M2P324Y*4', '2FWWHECA*Y',
			'1FUW3MCA*1', '3GCEC16T*Y', '1G3CW54C*K', 'VF3BD815*K',
			'1R1F3482*X', '1FALP51U*T', '4A3CS34T*L', '2GJFG35K*P',
			'JE3CU36X*L', '5MADN343*4', '4A3AB36F*6', 'WDBJF20F*V',
			'2GCFC29H*R', '2FTHF26F*S', '3HTMNAAM*3', '1N4BL11D*4',
			'1GCHK34K*R', '1FV6H6AA*1', '2GCEG25C*M', '2FZ6BJAB*1',
			'1MEHM59S*5', '1YVGF22E*1', '2GTEK19B*6', '1G6KY529*P',
			'1GCEC14K*R', '1FVHCRAV*4', '3FRNF75R*6', '1GCJK34G*3',
			'WV2YB025*L', '3B3XP45J*N', 'KNAFE121*6', '2FZJAZBD*2',
			'1XKWP4EX*3', 'JL6AAF1H*4', 'JH4UA265*W', '1NPAX4EX*2',
			'1B3ES42Y*X', '1G8AK55B*6', '2D4GZ572*7', 'JH4DB156*P',
			'1G8ZK827*X', '2FWJA3DE*4', '1GTFC24S*W', '4F4DR17U*R',
			'1FAFP65L*W', 'JT3HT05J*X', '2M592137*3', 'YV1CM59H*3',
			'1FAFP363*4', 'JNAMCU2H*1', '1FUGFDZB*X', '2FAHP74V*6',
			'1FUWDWDA*X', '6G2VX12G*4', '2B4GH45R*N', 'YS3DD35B*S',
		));
		
		$this->populate_number = new OLP_Populate_Number();
	}
	
	/** Generate a random vin.
	 *
	 * @param int $min Ignored
	 * @param int $max Ignored
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		if (!$max) $max = 17;
		
		$this->vin = $this->vin_templates->getRandomItem();
		
		while (strlen($this->vin) < $max)
		{
			$this->vin .= $this->populate_number->getRandomItem(0, 9);
		}
		
		$vin = strtoupper($vin);
		
		$this->vin = str_replace('*', $this->getChecksumChar($this->vin), $this->vin);
		
		return $this->vin;
	}
	
	protected function getChecksumChar($vin)
	{
		// Map of characters to translate to numerical values
		$chars = 'ABCDEFGHJKLMNPRSTUVWXYZ';
		$nums  = '12345678123457923456789';
		
		// How to weight each position in the vin.
		// Position 9(index 8) is actually used to check the sum against and is
		// not included in the sum, so it's weighted at 0.
		$weight_map = array(
			0 => 8, 1 => 7, 2 => 6, 3 => 5,
			4 => 4, 5 => 3, 6 => 2, 7 => 10,
			8 => 0, 9 => 9, 10 => 8, 11 => 7,
			12 => 6, 13 => 5, 14 => 4, 15 => 3,
			16 => 2,
		);
		
		// Map the letters to their numerical value
		$new_vin = strtr($vin, $chars, $nums);
		
		// Calculate the sum. Each position is weighted and we sum up the whole mess.
		$sum = 0;
		$len = strlen($vin);
		for ($i = 0; $i < $len; $i++)
		{
			$sum += ($new_vin[$i] * $weight_map[$i]);
		}
		
		// Mod 11 to get the remainder
		$check_sum = $sum % 11;
		
		// If the mod11 is 10, the check_digit is X.
		return $check_sum == 10 ? 'X' : $check_sum;
	}
	
}
?>
