<?php

/**
 * The interface for packets sent to CRA
 *
 * @package ECashCra
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
interface ECashCra_IPacket
{
	/**
	 * Enter description here...
	 *
	 * @return DOMDocument
	 */
	public function getXml();
}

?>