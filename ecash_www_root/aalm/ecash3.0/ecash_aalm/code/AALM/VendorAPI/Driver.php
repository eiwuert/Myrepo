<?php

class AALM_VendorAPI_Driver extends ECash_VendorAPI_Driver
{
	/**
	 * Returns VendorAPI_PurchasedLeadStore_Memcache if it's enabled
	 * for the particular company.
	 *
	 * @return VendorAPI_PurchasedLeadStore_Memcache
	 */
	public function getPurchasedLeadStore()
	{
		return new VendorAPI_PurchasedLeadStore_Memcache(
						$this->enterprise,
						$this->getMemcachePool()
					);
	}
}

