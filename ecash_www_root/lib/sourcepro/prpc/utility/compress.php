<?PHP
	class SourcePro_Prpc_Utility_Compress extends SourcePro_Prpc_Utility_Base
	{
		public function __construct ($data, $method, $direction) 
		{
			switch ($method)
			{
				case SourcePro_Prpc_Base::PRPC_COMPRESS_NO:
					$this->m_result = $data;
				break;
				
				case SourcePro_Prpc_Base::PRPC_COMPRESS_GZ:
					$this->m_result = call_user_func (array ($this, "_Gzip_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				break;
				
				case SourcePro_Prpc_Base::PRPC_COMPRESS_BZ:
					$this->m_result = call_user_func (array ($this, "_Bzip_".(($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "In" : "Out")), $data);
				break;
				
				default:
					$this->m_result = $data;
				break;
			}
			
			if ($this->m_result === FALSE || (!strlen ($this->m_result) && strlen ($data)))
			{
				throw new SourcePro_Exception((($direction == SourcePro_Prpc_Base::PRPC_DIRECTION_IN) ? "Compression" : "DeCompression")." failed using ".$method. "({$data})", 1000);
			}
		}
		
		public function __destruct ()
		{
		}

		private function _Gzip_In ($data)
		{
			return gzcompress ($data);
	 	}
	 	
		private function _Gzip_Out ($data)
		{
			return @gzuncompress ($data);
		}
		private function _Bzip_In ($data)
		{
			return bzcompress ($data);
	 	}
	 	
		private function _Bzip_Out ($data)
		{
			return @bzdecompress ($data);
		}
	}
?>