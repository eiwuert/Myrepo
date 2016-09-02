<?php

/**
 * Caches lists loaded from another concrete loader. The caching occurs via eaccellerator. If eaccellerator is not
 * installed then this will basically just be a pass through class.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_SuppressionList_CachingLoader implements VendorAPI_SuppressionList_ILoader
{
	const KEY_PREFIX = '87slg8s,';
	const CACHE_TTL = 3600;

	/**
	 * @var VendorAPI_SuppressionList_ILoader
	 */
	private $loader;

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @param VendorAPI_SuppressionList_ILoader $loader
	 * @param string $prefix
	 */
	public function __construct(VendorAPI_SuppressionList_ILoader $loader, $prefix)
	{
		$this->loader = $loader;
		$this->prefix = self::KEY_PREFIX . $prefix;
	}

	/**
	 * Returns an array of suppression lists by the given name and type.
	 *
	 * @param string $name
	 * @param string $type
	 * @return VendorAPI_SuppressionList_Wrapper
	 */
	public function getByName($name, $type = NULL)
	{
		$key = base64_encode($this->prefix . '|' . $name . '|' . $type);

		$cached_list = $this->getCachedList($key);

		if (empty($cached_list))
		{
			$list = $this->loader->getByName($name, $type);
			$this->cacheList($list, $key, self::CACHE_TTL);
		}
		else
		{
			$list = $cached_list;
		}

		return $list;
	}

	private function getFtok($key)
	{
		touch('/tmp/suppression-lists-cache-' . $key);
		return ftok('/tmp/suppression-lists-cache-' . $key, 'a');
	}

	private function getCachedList($key)
	{
		$shm = shm_attach($this->getFtok($key));

		$list = NULL;
		if (is_int($shm))
		{
			$data = shm_get_var($shm, 0);

			if (is_array($data))
			{
				list($cache_time, $list) = $data;
				
				if ($cache_time < time())
				{
					$list = NULL;
					$this->removeShm($key);
				}
			}

			shm_detach($shm);
		}

		return $list;
	}

	private function cacheList($list, $key, $ttl)
	{
		$this->removeShm($key);

		$data = array(time() + $ttl, $list);

		$header_size = (PHP_INT_SIZE * 4) + 8;
		$var_size = (((strlen(serialize($data)) + (PHP_INT_SIZE * 4)) / PHP_INT_SIZE) * PHP_INT_SIZE) + PHP_INT_SIZE;
		$shm = shm_attach($this->getFtok($key), $header_size + $var_size, 0666);

		if (is_int($shm))
		{
			shm_put_var($shm, 0, $data);
		}
	}

	private function removeShm($key)
	{
		$shm = shm_attach($this->getFtok($key));

		if (is_int($shm))
		{
			shm_remove($shm);
		}
		shm_detach($shm);
	}
}

?>