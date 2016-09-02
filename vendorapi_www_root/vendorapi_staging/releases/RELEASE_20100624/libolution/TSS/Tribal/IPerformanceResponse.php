<?php

/**
 * Response interface that the Tribal calls require
 */
interface TSS_Tribal_IPerformanceResponse extends TSS_Tribal_IResponse
{
	public function getDecision();
       
       	public function getCode();

	public function getMessage();

        //public function getDecisionDatetime();

	//public function getServerIP();
}
