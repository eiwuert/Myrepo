<?php

/**
 * Creates Nirvana aggregate sources based on a configuration array. The configuration array
 * is generally parsed from an INI file by parse_ini_file. Sources are specified in the
 * configuration as source.[name]=[class], where name is comprised of a-z, 0-9, and underscores.
 * The specified class is assumed to have an parameter-less constructor and eachconfiguration
 * entry of the format source.[name].[property] will be translated to the method call set{Property}
 * on the source instance. For example, source.test.url will call setUrl on the source instance.
 * @author Andrew Minerd
 */
class Nirvana_SourceFactory {
	public function getSources(array $config) {
		$sources = array();

		foreach ($config as $name=>$value) {
			if (preg_match('#^source\.([a-zA-Z0-9_]+)$#', $name, $m)) {
				$source = $this->createInstance($value);
				$this->setProperties("$name.", $config, $source);

				$sources[$m[1]] = $source;
			}
		}

		return $sources;
	}

	public function getObject($name, array $config) {
		if (!isset($config[$name])) {
			return false;
		}

		$source = $this->createSource($config[$name]);
		$this->setProperties("{$name}.", $config, $source);
		return $source;
	}

	private function createInstance($type) {
		return new $type();
	}

	private function setProperties($prefix, array $config, $source) {
		foreach ($config as $opt_name=>$opt_value) {
			if (strncmp($opt_name, $prefix, strlen($prefix)) == 0) {
				$opt_name = substr($opt_name, strlen($prefix));
				$source->{'set'.$opt_name}($opt_value);
			}
		}
	}
}