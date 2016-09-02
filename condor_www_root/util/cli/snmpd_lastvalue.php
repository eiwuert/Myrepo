#!/usr/bin/php
<?php

require 'AutoLoad.1.php';

set_include_path(realpath(dirname(__FILE__).'/../lib/').':'.get_include_path());

$opt = getopt('i');

$last = new LastValue();

$rs = $last->get();

$i = 0;
if (isset($opt['i']))
	foreach ($rs as $r) echo sprintf('%4d', ++$i), '  ', str_pad($r['name'], 50, ' '), $r['value'], "\n";
else
	foreach ($rs as $r) echo $r['value'], "\n";

?>
