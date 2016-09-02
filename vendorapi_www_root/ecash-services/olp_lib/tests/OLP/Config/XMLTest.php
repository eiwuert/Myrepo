<?php

/**
 * Tests OLP_Config_XML.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_Config_XMLTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testLoadXMLString().
	 *
	 * @return array
	 */
	public static function dataProviderLoadXMLString()
	{
		return array(
			array(
				<<<XML
<?xml version="1.0"?>
XML
				, FALSE
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<empty>
</empty>
XML
				, TRUE
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<empty />
XML
				, TRUE
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<has>
	<stuff />
</has>
XML
				, TRUE
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<database>
	<olp>
		<live>
			<host>writer.olp.ept.tss</host>
			<username>olp</username>
			<password>password</password>
			<database>olp</database>
		</live>
		<staging extends="live" />
		<reporting extends="live">
			<host>reporting1.olp.ept.tss</host>
		</reporting>
		<qa_manual>
			<host>db1.qa.tss</host>
			<port>3309</port>
			<username>olp</username>
			<password>password</password>
			<database>qa_olp</database>
		</qa_manual>
		<qa_semiautomated extends="qa_manual">
			<database>saqa_olp</database>
		</qa_semiautomated>
	</olp>
</database>
XML
				, TRUE
			),
		);
	}
	
	/**
	 * Tests that loadXMLString() properly handles simple XMLs.
	 *
	 * @dataProvider dataProviderLoadXMLString
	 *
	 * @param string $xml_string
	 * @param bool $expected_result
	 * @return void
	 */
	public function testLoadXMLString($xml_string, $expected_result)
	{
		$config = $this->getMock(
			'OLP_Config_XML',
			array(
				'loadSimpleXMLElement',
			)
		);
		
		$result = $config->loadXMLString($xml_string);
		
		$this->assertEquals($expected_result, $result);
	}
	
	/**
	 * Data provider for testLoadExceptions().
	 *
	 * @return array
	 */
	public static function dataProviderLoadExceptions()
	{
		return array(
			array(
				<<<XML
<?xml version="1.0"?>
<config>
	<item extends="non_existance_item" />
</config>
XML
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<config>
	<item1 extends="item2" />
	<item2 extends="item1" />
</config>
XML
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<config>
	<item1 extends="item2" />
	<item2 extends="item3" />
	<item3 extends="item1" />
</config>
XML
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<config>
	<item1 extends="item1" />
</config>
XML
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<config>
	<item1 extends="item2,item1" />
	<item2 />
</config>
XML
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<config>
	<item1 />
	<item2 extends="item1/fake" />
</config>
XML
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<config>
	<item1 />
	<item2 extends="/item1/fake" />
</config>
XML
			),
			
			array(
				<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<type_a>
			<tree>Data</tree>
			<form>True</form>
		</type_a>
		<type_b>
			<tree>Fourth</tree>
		</type_b>
	</simple>
	<complex extends="simple">
		<type_b extends="type_a" />
	</complex>
</config>
XML
			),
		);
	}
	
	/**
	 * Tests for the different failures.
	 *
	 * @dataProvider dataProviderLoadExceptions
	 * @expectedException RuntimeException
	 *
	 * @param $xml_string
	 * @return never
	 */
	public function testLoadExceptions($xml_string)
	{
		$config = new OLP_Config_XML();
		
		$config->loadXMLString($xml_string);
	}
	
	/**
	 * Data provider for testLoadSuccess().
	 *
	 * @return array
	 */
	public static function dataProviderLoadSuccess()
	{
		return array(
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree>Data</tree>
	</simple>
</config>
XML
				),
				array(
					'simple' => array(
						'tree' => 'Data',
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree>Data</tree>
		<tree>More Data</tree>
	</simple>
</config>
XML
				),
				array(
					'simple' => array(
						'tree' => array(
							'Data',
							'More Data',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree>Data</tree>
		<base>From Base</base>
	</simple>
</config>
XML
					, <<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree>Overwritten Data</tree>
	</simple>
</config>
XML
				),
				array(
					'simple' => array(
						'tree' => 'Overwritten Data',
						'base' => 'From Base',
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree>Data</tree>
		<base>From Base</base>
	</simple>
	<complex extends="simple">
		<tree>Overwritten Data</tree>
	</complex>
</config>
XML
				),
				array(
					'simple' => array(
						'tree' => 'Data',
						'base' => 'From Base',
					),
					'complex' => array(
						'tree' => 'Overwritten Data',
						'base' => 'From Base',
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree>Data</tree>
		<base>From Base</base>
	</simple>
	<complex extends="/simple">
		<tree>Overwritten Data</tree>
	</complex>
</config>
XML
				),
				array(
					'simple' => array(
						'tree' => 'Data',
						'base' => 'From Base',
					),
					'complex' => array(
						'tree' => 'Overwritten Data',
						'base' => 'From Base',
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<parent>
		<child>
			<tree>Data</tree>
		</child>
	</parent>
	<friend>
		<cousin extends="/parent/child" />
	</friend>
</config>
XML
				),
				array(
					'parent' => array(
						'child' => array(
							'tree' => 'Data',
						),
					),
					'friend' => array(
						'cousin' => array(
							'tree' => 'Data',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree>Data</tree>
	</simple>
	<complex extends="simple" />
</config>
XML
					, <<<XML
<?xml version="1.0"?>
<config>
	<secondary extends="complex" />
</config>
XML
				),
				array(
					'simple' => array(
						'tree' => 'Data',
					),
					'complex' => array(
						'tree' => 'Data',
					),
					'secondary' => array(
						'tree' => 'Data',
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree>Data</tree>
	</simple>
	<complex extends="simple" />
</config>
XML
					, <<<XML
<?xml version="1.0"?>
<config>
	<secondary extends="/complex" />
</config>
XML
				),
				array(
					'simple' => array(
						'tree' => 'Data',
					),
					'complex' => array(
						'tree' => 'Data',
					),
					'secondary' => array(
						'tree' => 'Data',
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree>Data</tree>
	</simple>
	<complex extends="simple/tree" />
</config>
XML
					, <<<XML
<?xml version="1.0"?>
<config>
	<secondary extends="complex" />
</config>
XML
				),
				array(
					'simple' => array(
						'tree' => 'Data',
					),
					'complex' => 'Data',
					'secondary' => 'Data',
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<simple>
		<tree />
	</simple>
	<complex extends="simple/tree" />
</config>
XML
				),
				array(
					'simple' => array(
						'tree' => array(),
					),
					'complex' => array(),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<deeper>
		<simple>
			<children>
				<tree>Data</tree>
			</children>
		</simple>
		<complex extends="simple/children" />
		<secondary extends="/deeper/simple/children" />
	</deeper>
</config>
XML
				),
				array(
					'deeper' => array(
						'simple' => array(
							'children' => array(
								'tree' => 'Data',
							),
						),
						'complex' => array(
							'tree' => 'Data',
						),
						'secondary' => array(
							'tree' => 'Data',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<companies>
		<Mazada>
			<slogan>Zoom Zoom</slogan>
		</Mazada>
		<Ford>
			<slogan>Fix Or Repair Daily</slogan>
		</Ford>
	</companies>
	<cars>
		<RX8 extends="/companies/Mazada">
			<description>Fun</description>
		</RX8>
		<F150 extends="/companies/Ford">
			<description>Common</description>
			<engine>Fat</engine>
		</F150>
		<F250 extends="F150">
			<engine>Fatter</engine>
		</F250>
	</cars>
</config>
XML
				),
				array(
					'companies' => array(
						'Mazada' => array(
							'slogan' => 'Zoom Zoom',
						),
						'Ford' => array(
							'slogan' => 'Fix Or Repair Daily',
						),
					),
					'cars' => array(
						'RX8' => array(
							'slogan' => 'Zoom Zoom',
							'description' => 'Fun',
						),
						'F150' => array(
							'slogan' => 'Fix Or Repair Daily',
							'description' => 'Common',
							'engine' => 'Fat',
						),
						'F250' => array(
							'slogan' => 'Fix Or Repair Daily',
							'description' => 'Common',
							'engine' => 'Fatter',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<level1>
		<level2>
			<level3>
				<tree>Data</tree>
				<form>True</form>
			</level3>
		</level2>
	</level1>
	<complex extends="level1/level2">
		<level3>
			<form>False</form>
		</level3>
	</complex>
</config>
XML
				),
				array(
					'level1' => array(
						'level2' => array(
							'level3' => array(
								'tree' => 'Data',
								'form' => 'True',
							),
						),
					),
					'complex' => array(
						'level3' => array(
							'tree' => 'Data',
							'form' => 'False',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<level1>
		<level3>
			<tree>Data</tree>
			<form>True</form>
		</level3>
		<level4>
			<tree>Fourth</tree>
		</level4>
	</level1>
	<complex extends="level1">
		<level3 extends="level4" />
		<level4 extends="/level1/level4" />
	</complex>
</config>
XML
				),
				array(
					'level1' => array(
						'level3' => array(
							'tree' => 'Data',
							'form' => 'True',
						),
						'level4' => array(
							'tree' => 'Fourth',
						),
					),
					'complex' => array(
						'level3' => array(
							'tree' => 'Fourth',
							'form' => 'True',
						),
						'level4' => array(
							'tree' => 'Fourth',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<uptown>
		<smiths>
			<child>Albert</child>
			<friend>John</friend>
		</smiths>
		<jones>
			<child>Albert</child>
			<friend>Jane</friend>
		</jones>
	</uptown>
	<downtown extends="/uptown">
		<smiths>
			<child>Robert</child>
		</smiths>
		<jones extends="smiths" />
	</downtown>
</config>
XML
				),
				array(
					'uptown' => array(
						'smiths' => array(
							'child' => 'Albert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Albert',
							'friend' => 'Jane',
						),
					),
					'downtown' => array(
						'smiths' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Robert',
							'friend' => 'Jane',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<uptown>
		<smiths>
			<child>Albert</child>
			<friend>John</friend>
		</smiths>
		<jones>
			<child>Albert</child>
			<friend>Jane</friend>
		</jones>
	</uptown>
	<downtown extends="/uptown">
		<smiths extends="/uptown/smiths">
			<child>Robert</child>
		</smiths>
		<jones extends="smiths" />
	</downtown>
</config>
XML
				),
				array(
					'uptown' => array(
						'smiths' => array(
							'child' => 'Albert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Albert',
							'friend' => 'Jane',
						),
					),
					'downtown' => array(
						'smiths' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<uptown>
		<smiths>
			<child>Albert</child>
			<friend>John</friend>
		</smiths>
		<jones>
			<child>Albert</child>
			<friend>Jane</friend>
		</jones>
	</uptown>
	<downtown extends="/uptown">
		<smiths extends="/uptown/smiths">
			<child>Robert</child>
		</smiths>
		<jones extends="smiths" />
	</downtown>
	<extra extends="downtown" />
</config>
XML
				),
				array(
					'extra' => array(
						'smiths' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
					),
					'uptown' => array(
						'smiths' => array(
							'child' => 'Albert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Albert',
							'friend' => 'Jane',
						),
					),
					'downtown' => array(
						'smiths' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<extra extends="downtown" />
	<uptown>
		<smiths>
			<child>Albert</child>
			<friend>John</friend>
		</smiths>
		<jones>
			<child>Albert</child>
			<friend>Jane</friend>
		</jones>
	</uptown>
	<downtown extends="/uptown">
		<smiths extends="/uptown/smiths">
			<child>Robert</child>
		</smiths>
		<jones extends="smiths" />
	</downtown>
</config>
XML
				),
				array(
					'extra' => array(
						'smiths' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
					),
					'uptown' => array(
						'smiths' => array(
							'child' => 'Albert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Albert',
							'friend' => 'Jane',
						),
					),
					'downtown' => array(
						'smiths' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
						'jones' => array(
							'child' => 'Robert',
							'friend' => 'John',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<parent>
		<child>
			<tree>Data</tree>
		</child>
	</parent>
	<friend extends="parent"></friend>
</config>
XML
				),
				array(
					'parent' => array(
						'child' => array(
							'tree' => 'Data',
						),
					),
					'friend' => array(
						'child' => array(
							'tree' => 'Data',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<parent>
		<child>
			<tree>Data</tree>
		</child>
	</parent>
	<friend extends="parent" />
</config>
XML
				),
				array(
					'parent' => array(
						'child' => array(
							'tree' => 'Data',
						),
					),
					'friend' => array(
						'child' => array(
							'tree' => 'Data',
						),
					),
				),
			),
			
			array(
				array(
					<<<XML
<?xml version="1.0"?>
<config>
	<parent>
		<child>
			<tree>Data</tree>
		</child>
	</parent>
	<friend extends="parent">
	</friend>
</config>
XML
				),
				array(
					'parent' => array(
						'child' => array(
							'tree' => 'Data',
						),
					),
					'friend' => "\n\t", /// @NOTE: This is generally not what you expect !!
				),
			),
		);
	}
	
	/**
	 * Tests for successful features of OLP_Config_XML.
	 *
	 * @dataProvider dataProviderLoadSuccess
	 *
	 * @param array $xml_strings
	 * @param array $expected_result
	 * @return void
	 */
	public function testLoadSuccess(array $xml_strings, $expected_result)
	{
		$config = new OLP_Config_XML();
		
		$append = FALSE;
		foreach ($xml_strings AS $xml_string)
		{
			$loaded = $config->loadXMLString($xml_string, $append);
			$this->assertTrue($loaded, "Could not load XML string.");
			$append = TRUE;
		}
		
		$this->assertEquals($expected_result, $config->getAsArray());
	}
}

?>
