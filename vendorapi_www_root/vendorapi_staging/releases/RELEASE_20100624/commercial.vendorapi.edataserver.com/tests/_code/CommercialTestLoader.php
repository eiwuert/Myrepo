<?php
class CommercialTestLoader extends ECash_VendorAPI_Loader
{
	public function getDatabase()
	{
		return getTestDatabase();
	}
}
?>