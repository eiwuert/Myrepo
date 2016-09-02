<?php
	class SourcePro_Prpc_Utility_Serialization extends SourcePro_Prpc_Utility_Base
	{
		public function __construct ($data, $method, $direction)
		{
			switch ($method)
			{
				case SourcePro_Prpc_Base::PRPC_SERIALIZE_NONE:
					$this->m_result = $data;
				break;

				case SourcePro_Prpc_Base::PRPC_SERIALIZE_STANDARD:
					$this->m_result = call_user_func (array ($this, "_Standard_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				break;

				case SourcePro_Prpc_Base::PRPC_SERIALIZE_BINARY:
					//$this->m_result = call_user_func (array ($this, "_Binary_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				//break;

				case SourcePro_Prpc_Base::PRPC_SERIALIZE_WDDX:
					$this->m_result = call_user_func (array ($this, "_Wddx_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				break;

				case SourcePro_Prpc_Base::PRPC_SERIALIZE_SOAP:
					//$this->m_result = call_user_func (array ($this, "_Soap_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				//break;

				case SourcePro_Prpc_Base::PRPC_SERIALIZE_AMF:
					//$this->m_result = call_user_func (array ($this, "_Amf_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				//break;

				case SourcePro_Prpc_Base::PRPC_SERIALIZE_XMLRPC:
					//$this->m_result = call_user_func (array ($this, "_Xmlrpc_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				//break;

				case SourcePro_Prpc_Base::PRPC_SERIALIZE_POST:
					//$this->m_result = call_user_func (array ($this, "_Post_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				//break;

				case SourcePro_Prpc_Base::PRPC_SERIALIZE_GET:
					//$this->m_result = call_user_func (array ($this, "_Get_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				//break;

				default:
					$this->m_result = $data;
				break;
			}
			if ($this->m_result === FALSE || (!@strlen ($this->m_result) && strlen ($data)))
			{
				throw new SourcePro_Exception((($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "Serialization" : "DeSerialization")." of ($data) failed using ".$method, 1000);
			}
		}

		public function __destruct ()
		{
		}

		private function _Standard_In ($data)
		{
			return serialize ($data);
	 	}

		private function _Standard_Out ($data)
		{
			return unserialize ($data);
		}

		private function _Binary_In ($data)
		{
		}

		private function _Binary_Out ($data)
		{
		}

		private function _Wddx_In ($data)
		{
			return wddx_serialize_value ($data, "prpc");
		}

		private function _Wddx_Out ($data)
		{
			// Does not handle exceptions see: http://bugs.php.net/bug.php?id=29323
			return wddx_deserialize ($data);
		}

		private function _Soap_In ($data)
		{
		}

		private function _Soap_Out ($data)
		{
		}

		private function _Post_In ($data)
		{
		}

		private function _Post_Out ($data)
		{
		}
	}
?>
