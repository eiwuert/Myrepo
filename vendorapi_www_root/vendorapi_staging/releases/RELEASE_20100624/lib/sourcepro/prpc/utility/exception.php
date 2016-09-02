<?PHP
	class SourcePro_Prpc_Utility_Exception extends SourcePro_Prpc_Utility_Base 
	 {
	 	public function __construct ($data, $method) 
	 	{
	 		switch ($method)
	 		{
	 			case SourcePro_Prpc_Base::PRPC_EXCEPTION_PHP5:
					$this->m_result = $data;
	 			break;
	 		
	 			case SourcePro_Prpc_Base::PRPC_EXCEPTION_PHP4:
					$this->m_result = call_user_func (array ($this, "_Php4"), $data);
	 			break;
	 		
	 			case SourcePro_Prpc_Base::PRPC_EXCEPTION_ARRAY:
					$this->m_result = call_user_func (array ($this, "_Array"), $data);
	 			break;
	 		
	 			case SourcePro_Prpc_Base::PRPC_EXCEPTION_XML:
					$this->m_result = call_user_func (array ($this, "_Xml"), $data);
	 			break;
	 		
	 			case SourcePro_Prpc_Base::PRPC_EXCEPTION_STRING:
					$this->m_result = call_user_func (array ($this, "_String"), $data);
	 			break;
	 		
	 			default:
					$this->m_result = $data;
	 			break;
	 		}
	 		
	 		// Do not throw another exception here for recursion?
	 	}
	 	
	 	public function __destruct ()
	 	{
	 	}
	 	
	 	private function _Php4 ($data)
	 	{
	 		// Convert to Error_2 object
	 		include_once ("sourcepro/prpc/utility/error.2.php");
	 		$ret = new Error_2 (addslashes ($data->except->getMessage ()), '');
  			
	 		return $ret;
	 	}
	 	
	 	private function _Array ($data)
	 	{
	 		return array
	 		(
	 			"exception" => array
	 			(
	 				"code" => addslashes ($data->except->getCode ()),
	 				"message" => addslashes ($data->except->getMessage ()),
	 				"trace" => addslashes ($data->except->getTraceAsString ()),
	 			),
	 			"output" => addslashes ($data->output),
	 		);
	 	}
	 	
	 	private function _Xml ($data)
	 	{
			return 
				"<?xml version='1.0' ?>".
				"<response>".
					"<exception>".
						"<code value=\"".$data->except->getCode ()."\" />".
						"<message value=\"".$data->except->getMessage ()."\" />".
						"<trace value=\"".$data->except->getTraceAsString ()."\" />".
					"</exception>".
					"<output>".$data->output."</output>".
				"</response>";
	 	}
	 	
	 	private function _String ($data)
	 	{
			return "code='".addslashes ($data->except->getCode ())."'\r\nmessage='".addslashes ($data->except->getMessage ())."'\r\ntrace='".addslashes ($data->except->getTraceAsString ())."'\r\noutput=".addslashes ($data->output);
	 	}	
	 } 
?>
