<?php

// setup the catalog based on VMODE
switch (@$_SERVER['VMODE'])
{
	case 'live':
		$m_catalog = array (
			'local' => 'writer.statpro.ept.tss:3306',
			'db-v1' => 'writer.statpro.ept.tss:3306',
			'db-v2' => 'writer.statpro.ept.tss:3307',
		);
		break;

	case 'test':
		$m_catalog = array (
			'local' => 'writer.statpro.ept.tss:3305',
			'db-v1' => 'db101.clkonline.com:3309',
			'db-v2' => 'db101.clkonline.com:3309',
		);
		break;

	case 'beta':
		$m_catalog = array (
			'local' => 'writer.statpro.ept.tss:3305',
			'db-v1' => 'writer.statpro.ept.tss:3305',
			'db-v2' => 'writer.statpro.ept.tss:3307',
		);
		break;

	case 'alpha':
		$m_catalog = array (
			'local' => 'writer.statpro.ept.tss:3307',
			'db-v1' => 'writer.statpro.ept.tss:3305',
			'db-v2' => 'db101.clkonline.com:3309',
		);
		break;

	default:
		throw new SourcePro_Exception("No catalog defined for mode ".$_SERVER['VMODE']);
		break;
}

?>
