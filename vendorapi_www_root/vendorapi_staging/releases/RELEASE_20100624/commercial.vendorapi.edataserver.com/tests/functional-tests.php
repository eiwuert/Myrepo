<?php
/**
 * Takes a test config and runs each testcase in it's
 * own instance of phpunit to prevent conflicts
 *
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 */
if (empty($argv[1]))
{
	$config = dirname(__FILE__).DIRECTORY_SEPARATOR.'functional-config.xml';
}
else
{
	$config = $argv[1];
}
if (!file_exists($config))
{
	die("Invalid config file {$config}\n");
}


function findBootStrap(DOMXPath $xpath)
{
	$node = $xpath->query("//phpunit");
	if ($node->length)
	{
		$value = $node->item(0)->attributes->getNamedItem('bootstrap');
		return $value->nodeValue;
	}
}

function findTestSuites(DOMXPath $xpath)
{
	$node_list = $xpath->query("//phpunit/testsuite");
	if ($node_list->length)
	{
		$return = array();
		foreach ($node_list as $node)
		{
			$name = $node->attributes->getNamedItem('name')->nodeValue;
			$return[$name] = array();
			$xpath_query = sprintf('//phpunit/testsuite[@name=\'%s\']/directory', $name);
			$test_node_dirs = $xpath->query($xpath_query);
			foreach ($test_node_dirs as $test_node)
			{
				$return[$name][] = $test_node->nodeValue;
			}
		}
		return $return;
	}
	return FALSE;
}

$phpunit = trim(`which phpunit`);
if (!is_executable($phpunit))
{
	die("Invalid phpunit bin ($phpunit).\n");
}

$domdoc = new DOMDocument();
$domdoc->load($config);
$xpath = new DOMXPath($domdoc);
$bootstrap = findBootStrap($xpath);

if (!empty($bootstrap) && !file_exists($bootstrap))
{
	die("Invalid bootstrap ($bootstrap).\n");
}
elseif (!empty($bootstrap))
{
	$phpunit = sprintf('%s --bootstrap %s', $phpunit, $bootstrap);
}

$test_suites = findTestSuites($xpath);

$error = 0;

function runTests($phpunit, $file, &$error_list, &$tests, &$assertions, &$failures)
{
	if (is_dir($file))
	{
		$files = glob($file.DIRECTORY_SEPARATOR.'*');
		foreach ($files as $f)
		{
			runTests($phpunit, $f, $error_list, $tests, $assertions, $failures);
		}
	}
	elseif (preg_match('/Test*.php/', $file))
	{
		echo("\t Running $file\n");
		$a = system("$phpunit $file", $exit)."\n";
		if (preg_match('/^Tests: ([\d]+), Assertions: ([\d]+), Errors: ([\d]+).$/', $a, $matches))
		{
			$tests += $matches[1];
			$assertions += $matches[2];
			$failures += $matches[3];
		}
		elseif (preg_match('/^OK \(([\d]+) tests, ([\d]+) assertions\)$/', $a, $matches))
		{
			$tests += $matches[1];
			$assertions += $matches[2];
		}

		if ($exit)
		{
			if (!is_array($error_list))
			{
				$error_list = array();
			}
			$error_list[] = $file;
		}
	}
}
$tests = $assertions = $failures = 0;
foreach ($test_suites as $suite => $directories)
{
	echo("Running $suite\n");
	foreach ($directories as $dir)
	{
		runTests($phpunit, $dir, $error_list, $tests, $assertions, $failures);
	}
}

echo("Test Totals: \n");
echo("Tests: $tests Assertions: $assertions Failures: $failures\n");
if (is_array($error_list) && count($error_list))
{
	echo("There are infact errors.\n");
	foreach ($error_list as $file)
	{
		echo("\t$file has errors\n");
	}
	exit(1);
}

