<?php

//require '/virtualhosts/libolution/AutoLoad.1.php';
//AutoLoad_1::addSearchPath('/virtualhosts/vendor_api/code/');
//AutoLoad_1::addSearchPath('/virtualhosts/amg.vendorapi.edataserver.com/code/');
class VendorAPI_StateObject {
	protected $data;
	protected $version;
	protected $updated_version;
	protected $data_parts;
	public function getData() { return $this->data; }
	public function getParts()
	{
		$parts = $this->data_parts;
		array_unshift($parts, $this->data);
		return $parts;
	}
}

class VendorAPI_StateObjectPart
{
	protected $state;
	protected $data;
	public function getData() { return $this->data; }
}

class VendorAPI_StateObjectMultiPart extends VendorAPI_StateObjectPart
{
	protected $state, $index, $iterative_data;
}

$pdo = new PDO("sqlite:{$_SERVER['argv'][1]}");
$rows = $pdo->query("SELECT * from state_object");
while (($row = $rows->fetch(PDO::FETCH_OBJ)))
{
	$state = unserialize(gzuncompress($row->state_object));
	
	$parts = $state->getParts();

	$versions = array();
	foreach ($parts as $table=>$p)
	{
		foreach ($p->getData() as $v=>$d)
		{
			if (!isset($versions[$v]))
			{
				$versions[$v] = array();
			}
			$versions[$v][$table] = $d;
		}
	}

	ksort($versions);
	var_dump($versions);
}

