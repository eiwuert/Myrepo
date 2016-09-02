<?php
/**
 * This script will check the crontab to see if the Scrubbers are currently setup to run.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

$crontab = shell_exec("crontab -l");

$lines = explode("\n", $crontab);

$writer = new XMLWriter();
$writer->openMemory();
$writer->setIndent(TRUE);
$writer->setIndentString("\t");

$writer->startDocument('1.0', 'UTF-8');
$writer->startElement('monitor');

foreach ($lines as $line)
{
	$m = array();
	if (preg_match('/^\*.*Scrubber.php.*(clk|com)/', $line, $m))
	{
		$writer->startElement('scrubber');
		$writer->writeElement('module', $m[1]);
		$writer->writeElement('last_update', date('Y-m-d H:i:s'));
		$writer->endElement();
	}
}

$writer->endDocument();

file_put_contents('/var/tmp/scrubber_monitor.xml', $writer->flush());
