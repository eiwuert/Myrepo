<?php
// Directory paths
define('BASE_DIR', dirname(__FILE__) . '/../');
define('CODE_DIR', dirname(__FILE__) . '/../include/code/');
define('OLP_LDB_DIR', dirname(__FILE__) . '/../include/modules/olp/olp_ldb/');
define('LIB_DIR', '/virtualhosts/lib/');

require_once(CODE_DIR . 'Enterprise_Data.php');
require_once(OLP_LDB_DIR . 'agean_ldb.php');

/**
 * PHPUnit test class for the OLP_LDB class.
 *
 * @author Rob Voss <rob.voss@sellingsource.com>
 */


class AgeanLDBTest extends PHPUnit_Framework_TestCase
{
	public static function insertApplicationDataProvider()
	{
		return array(
					array(
						'banking_start_date' 	=> 'AC-07',
						'residence_start_date' 	=> '2007-12-01',
						'date_of_hire'			=> '2007-12-25',
						'work_title' 			=> 'Santie Claws'
						),
					array(
						'banking_start_date' 	=> '2007-3-23',
						'residence_start_date' 	=> '2007-12-1',
						'date_of_hire'			=> '2007-12-25',
						'work_title' 			=> 'Santie Claws'
						)
					);
	}
	
	/**
	 * Enter description here...
	 *
	 * @dataProvider insertApplicationDataProvider
	 */
	public function testInsertApplication($b_start_date, $r_start_date, $date_hire, $work_title)
	{
		$db = $this->getMock('MySQL_4');
		
		$data = array(
					'banking_start_date' 	=> $b_start_date,
					'residence_start_date' 	=> $r_start_date,
					'date_of_hire'			=> $date_hire,
					'work_title' 			=> $work_title);

		$expected_data = array('data' => array('banking_start_date'   => '2007-03-23',
												'residence_start_date' => '2007-12-01',
												'date_hire'            => $data['date_of_hire'],
												'job_title'            => $data['work_title'])
		);
		
		$agean_ldb = new Agean_LDB($db, 'cbnk', $data);
		$tmp = $agean_ldb->Insert_Application();
		$this->assertEquals($expected_data, $tmp);
	}
}
?>
