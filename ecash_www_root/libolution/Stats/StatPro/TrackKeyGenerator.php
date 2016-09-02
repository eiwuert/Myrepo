<?php
/**
 * Class for generating track keys.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Stats_StatPro_TrackKeyGenerator implements TSS_IKeyGenerator
{
	/**
	 * Returns a randomly generated track key.
	 * 
	 * @return string
	 */
	public function generate()
	{
		return Util_Convert_1::bin2String(Util_Convert_1::hex2Bin(sha1(microtime().mt_rand().uniqid(mt_rand(), TRUE))));
	}
}
