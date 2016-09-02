<?php

/** Generate a random Bank ABA that passes checksum.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Populate_ABA extends OLP_Populate_Number
{
	/**
	 * @var PopulateItemArray
	 */
	protected $real_list;
	
	/** Initializes class.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->data['aba'] = 0;
		
		// Because we sometimes need real ones. Randomly picked from real list.
		// Source: https://www.fededirectory.frb.org/fpddir.txt
		$this->real_list = new OLP_Populate_Array(array(
			'011000028', '011300142', '011601142', '021206469', '031100102',
			'031101208', '031318499', '041208421', '041208735', '041215553',
			'042101268', '042102270', '044114716', '051501257', '052100466',
			'052100741', '052202225', '053105936', '053112482', '053201872',
			'056008849', '061101197', '061104246', '061202025', '061202957',
			'062201711', '063104626', '063113811', '064107994', '064202763',
			'064207946', '065200528', '071001180', '071105536', '071208297',
			'071905817', '071905930', '071909363', '071925538', '071925923',
			'071925965', '073903503', '073913836', '073913959', '073918077',
			'073922869', '075908001', '081000553', '081206328', '081501227',
			'081507166', '081517729', '082904373', '082905343', '084107699',
			'084301408', '086503424', '091008299', '091200848', '091204080',
			'091300641', '091803274', '091809980', '091901192', '096010415',
			'101102182', '101203256', '104113343', '104900750', '104901241',
			'111909210', '111916724', '112323387', '113000049', '113005549',
			'113008083', '113118630', '121141615', '122038691', '122216439',
			'122239270', '122243855', '123205054', '124103582', '211288640',
			'211370626', '211372378', '211770200', '211870142', '221371563',
			'243374221', '253171621', '256078514', '263184488', '265472415',
			'271070814', '275071330', '303085227', '303085476', '311376850',
		));
	}
	
	/** Generate a random ABA with valid checksum.
	 *
	 * @param bool $min If TRUE, returns a real ABA from list.
	 * @param mixed $max Not used
	 * @return string
	 */
	public function getRandomItem($min = NULL, $max = NULL)
	{
		if ($min === NULL)
		{
			return $this->generateRandom();
		}
		else
		{
			return $this->real_list->getRandomItem();
		}
	}
	
	/** Generates a random ABA that will pass MOD10.
	 *
	 * @return string
	 */
	protected function generateRandom()
	{
		$this->pad_length = 2;
		switch (mt_rand(1, 3))
		{
			case 1:
				$this->aba = parent::getRandomItem(0, 12);
				break;
			case 2:
				$this->aba = parent::getRandomItem(21, 32);
				break;
			case 3:
				$this->aba = parent::getRandomItem(61, 72);
				break;
		}
		
		$this->pad_length = 6;
		$this->aba .= parent::getRandomItem(1, str_repeat('9', $this->pad_length));
		
		// Determine checksum digit
		$checksum =
			(10 -
				(
					3 * ($this->aba[0] + $this->aba[3] + $this->aba[6]) +
					7 * ($this->aba[1] + $this->aba[4] + $this->aba[7]) +
					1 * ($this->aba[2] + $this->aba[5])
				) % 10
			) % 10;
		
		// Checksum is the ninth digit
		$this->aba .= $checksum;
		
		return $this->aba;
	}
}

?>
