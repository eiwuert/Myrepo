<?php

interface ECash_Models_IFactory
{
	public static function loadBy(array $where, array $override_dbs = NULL);	
}


?>