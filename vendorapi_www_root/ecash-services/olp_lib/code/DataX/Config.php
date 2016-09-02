<?php
/**
 * DataX_Config class exposes DataX configuration information for OLP
 * 
 * @author Adam L. Englander <adam.englander@sellingsource.com>
 *
 */
class DataX_Config
{
	const DATAX_TYPE_IDV = 'DATAX_IDV';
	const DATAX_TYPE_PERF = 'DATAX_PERF';
	const DATAX_TYPE_REWORK = 'DATAX_REWORK';
	/**
	 * Mapping of call types to Authentication source IDs
	 *
	 * @var array
	 */
	static protected $source_ids = array(
		'idv-l5' => 0, //DATAX_IDV_PREQUAL
		'idv-l1' => 1, //DATAX_IDV_CLK
		'perf-l3' => 2, //DATAX_PERF
		'idv-l7' => 3, //DATAX_IDV_PW
		'idv-rework' => 4, //DATAX_IDV_REWORK
		'impact-idve' => 5, //DATAX_IDVE_IMPACT
		'pdx-rework' => 6, //DATAX_PDX_REWORK
		'df-phonetype' => 7,//No BFW reference
		'idv-compucredit' => 8, //DATAX_CCRT
		'agean-perf' => 9, //DATAX_AGEAN_PERF
		'agean-title' => 10, //DATAX_AGEAN_TITLE
		'aalm-perf' => 11, //No BFW reference
		'impactfs-idve' => 12, //DATAX_IDVE_IFS
		'impactpdl-idve' => 13, //DATAX_IDVE_IPDL
		'impactcf-idve' => 14, //DATAX_IDVE_ICF
		'lcs-perf' => 15, //No BFW reference
		'qeasy-perf' => 16, //No BFW reference
		'impactic-idve' => 18, //No BFW reference
		'hms_nsc-perf' => 19, //No BFW reference
		'hms_bgc-perf' => 20, //No BFW reference
		'hms_ezc-perf' => 21, //No BFW reference
		'hms_csg-perf' => 22, //No BFW reference
		'hms_tgc-perf' => 23, //No BFW reference
		'hms_gtc-perf' => 24, //No BFW reference
		'hms_obb-perf' => 25, //No BFW reference
		'hms_cvc-perf' => 26, //No BFW reference
		'opm_bsc-perf' => 27, //No BFW reference
		'dmp_mcc-perf' => 28, //No BFW reference
		'clk-perf' => 29, //No BFW reference
		'unit-test-call-type' => -99999, //Test entry for unit test
	);
	
	
	/**
	 * Returns the source id for a call type
	 *
	 * @param string $call_type
	 * @return integer
	 */
	public static function getSourceId($call_type)
	{
		$call_type = strtolower($call_type);
		if (isset(self::$source_ids[$call_type]))
		{
			$source_id = self::$source_ids[$call_type];
		}
		else
		{
			$source_id = FALSE;
		}
		return $source_id;
	}
	
	/**
	 * Returns a call type for a source id
	 *
	 * @param integer $source_id
	 * @return string
	 */
	public static function getCallTypeFromSourceId($source_id)
	{
		$call_type = array_search($source_id, self::$source_ids);
		
		if (!$call_type)
		{
			$call_type = 'UNDEFINED';
		}
		
		return $call_type;
	}
	
	/**
	 * Returns a DataX type for a source id
	 * The default is IDV regardless of the name unless it is specifically
	 * the CLK performance call or rework calls
	 *
	 * @param integer $source_id
	 * @return string
	 */
	public static function getDataxType($source_id)
	{
		switch ($source_id)
		{
			case self::$source_ids['perf-l3']: //CLK Performance Call
				return self::DATAX_TYPE_PERF; 
			case self::$source_ids['idv-rework']: //CLK Rework
			case self::$source_ids['pdx-rework']:	//Rework
				return self::DATAX_TYPE_REWORK;
			default:
				return self::DATAX_TYPE_IDV;			
		}
	}
	
}
?>
