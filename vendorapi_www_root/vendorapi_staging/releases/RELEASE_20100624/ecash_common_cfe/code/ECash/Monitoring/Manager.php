<?php

class ECash_Monitoring_Manager extends Object_1
{
	public function getRequestLog()
	{
		return new ECash_Monitoring_RequestLog();
	}
	
	public function getSoap()
	{
		return new ECash_Monitoring_Soap();
	}	
	
	public function getTimer()
	{
		return new ECash_Monitoring_Timer();
	}

	public function getPerformance()
	{
		return new ECash_Monitoring_Performance();
	}	
	
	public function getProcess()
	{
		return new ECash_Monitoring_Process();
	}
}
?>