<?php
/**
 * This includes all of our common functions necessary for building a page
 * 
 * @package jennyCMS v3.0
 * 
 * This is where we specify which plugins to use and 
 * where we keep our plugin loading functions
 * 
 */

define('LUM_REUIRED_PLUGINS',serialize(array(
	'Languages'=>1, // this plugin should always come first
	'PluginManager'=>1,
	'Members'=>1,
	'Roles'=>1,
	'Themes'=>1
)));

$GLOBALS['LUM_PLUGIN_INTERFACE_JS'] = '';
$GLOBALS['LUM_PLUGIN_INDEX_NAVIGATION'] = '';
$GLOBALS['LUM_PLUGIN_LOADED'] = array();
$GLOBALS['LUM_PLUGIN_SEO_CHECK'] = array();
$GLOBALS['LUM_PLUGIN_GET_CONTENT'] = array();

/**
 * Gets the list of installed, active, plugins from the database and
 * adds them to our list of plugins to load
 * 
 * @since 3.00
 */
function init_installed_plugins()
{
	global $gDB;
	init_plugin_class('PluginManager');
	list($arr, $count) = PluginManager::spGetList(array('status'=>1), $gDB);
	if ($count > 0)
	{
		$plugins = lum_carray('LUM_REUIRED_PLUGINS');
		foreach ($arr as $plugin)
		{
			$plugins[$plugin['name']] = 0;	
		}
		ksort($plugins);
		define('LUM_PLUGINS',serialize($plugins));
	}
	$arr = lum_carray('LUM_PLUGINS');
}

/**
 * Gets the seo functions ready to go so we can parse URLs. When
 * a url is parsed each part is compared against the seo functions
 * to determine what kind of plugin is required to parse the 
 * particluar section of the url.
 * 
 * @since 3.00
 */
function init_plugins_seo() 
{
	#1. iterate through each plugin
	#2. include interface.php
	$arr = lum_carray('LUM_PLUGINS');
	foreach ($arr as $plugin=>$required)
	{
		if (!is_file(PLUGINS_PATH.$plugin.'/seo_func.php'))
		{
			$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = false;
		}
		else 
		{
			$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = true;
			include_once(PLUGINS_PATH.$plugin.'/seo_func.php');
		}

	}
}

/**
 * Shortcut to calling a specific's plugin function
 * 
 * @since 3.00
 */
function plugin_call($plugin, $func, $params = null)
{
	init_plugin_class($plugin);
	init_plugin_web_service($plugin);

	if (function_exists($func))
	{
		return $func($params);
	}
	else 
	{
		die("Fatal Error: $func does not exist");
	}
}

/**
 * Shortcut to calling a specific's plugin content function
 * 
 * @since 3.00
 */
function content_call($db, $plugin, $func, $params = null)
{
	//echo $plugin;
	init_plugin_class($plugin);
	init_plugin_content($plugin);

	$plugin = $plugin.'Content';
	if (class_exists($plugin))
	{
		//echo "class exists!";
		$obj = new $plugin($db);
		if (method_exists($plugin, $func))
		{
			$obj->$func($params);
			return $obj;
		}
		else 
		{
			die("Fatal Error: $plugin -> $func does not exist");
		}
	}
	else 
	{
		die("Fatal Error: $plugin does not exist");
	}
	return $obj;
}

function getInstalledPlugins()
{
	$installed = array();
	if ($handle = opendir(PLUGINS_PATH)) 
	{
	    while (false !== ($file = readdir($handle))) 
	    {
	        if ($file != '.' && $file != '..' && is_dir(PLUGINS_PATH.'/'.$file)) 
	        {
	        	
	        	$class = PLUGINS_PATH.$file.'/class.php';
	    		if (is_file($class))    	
	    		{
	    			$data = file_get_contents($class);
	    			$lines = explode("\n", $data);
	    			if (strpos($lines[2], 'Plugin:') !== false)
	    			{
		    			$temp = split(': ', $lines[2]);
		    			$name = $temp[1];
		    			$temp = split(': ', $lines[3]);
		    			$version = $temp[1];
		    			$temp = split(': ', $lines[4]);
		    			$description = $temp[1];
						$installed[] = array('name'=>$name, 'version'=>$version, 'description'=>$description);
	    			}
	    		}
	        }
	    }
	}
	return $installed;
}

/**
 * Loads in cron jobs for each plugin that uses them
 * 
 * Called by the /cron.php
 *
 * @since 2.00
 */
function init_plugins_cron_jobs() 
{
	$arr = lum_carray('LUM_PLUGINS');
	foreach ($arr as $plugin=>$required)
	{
		if (!is_file(PLUGINS_PATH.$plugin.'/cron.php'))
		{
			$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = false;
		}
		else 
		{
			$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = true;
			include_once(PLUGINS_PATH.$plugin.'/cron.php');
		}
	}
}

/**
 * Loads in all of the plugin classes
 * 
  * @since 3.00
 */
function init_plugin_classes() 
{
	#1. iterate through each plugin
	#2. include interface.php
	$arr = lum_carray('LUM_PLUGINS');
	foreach ($arr as $plugin=>$required)
	{
		init_plugin_class($plugin);
	}
}

/**
 * Loads in the class for a single plugin
 * 
  * @since 3.00
 */
function init_plugin_class($plugin) 
{
	if (!is_file(PLUGINS_PATH.$plugin.'/class.php'))
	{
		lum_logMe("Failed to load plugin: $plugin");
		lum_logMe(PLUGINS_PATH.$plugin.'/class.php');
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = false;
	}
	else 
	{
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = true;
		include_once(PLUGINS_PATH.$plugin.'/class.php');
	}
}


/**
 * Loads in all of the plugin web services
 * 
  * @since 3.00
 */
function init_plugins_web_service() 
{
	#1. iterate through each plugin
	#2. include interface.php
	$arr = lum_carray('LUM_PLUGINS');
	foreach ($arr as $plugin=>$required)
	{
		init_plugin_web_service($plugin);
	}
}

/**
 * Loads in the web services for a single plugin
 * 
  * @since 3.00
 */
function init_plugin_web_service($plugin) 
{
	if (!is_file(PLUGINS_PATH.$plugin.'/web_service.php'))
	{
		//lum_logMe("Failed to load plugin: $plugin");
		//lum_logMe(PLUGINS_PATH.$plugin."/web_service.php");
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = false;
	}
	else 
	{
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = true;
		include_once(PLUGINS_PATH.$plugin.'/web_service.php');
	}
}



/**
 * Loads in all of the pseudo stored procedures
 * 
 * @since 3.00
 */
function init_plugins_sp() 
{
	#1. iterate through each plugin
	#2. include interface.php
	$arr = lum_carray('LUM_PLUGINS');
	foreach ($arr as $plugin=>$required)
	{
		init_plugin_sp($plugin);
	}
}

/**
 * Loads in pseudo stored procedures for a single plugin
 * 
 * @since 3.00
 */
function init_plugin_sp_func($plugin) 
{
	if (!is_file(PLUGINS_PATH.$plugin.'/sp_func.php'))
	{
		//lum_logMe("Failed to load plugin: $plugin");
		//lum_logMe(PLUGINS_PATH.$plugin."/sp_func.php");
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = false;
	}
	else 
	{
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = true;
		include_once(PLUGINS_PATH.$plugin.'/sp_func.php');
	}
}


/**
 * Loads in all of the plugins' content functions
 * 
 * @since 3.00
 */
function init_all_plugins_content() 
{
	#1. iterate through each plugin
	#2. include interface.php
	$arr = lum_carray('LUM_PLUGINS');
	foreach ($arr as $plugin=>$required)
	{
		init_plugin_content($plugin);
	}
}

/**
 * Loads the content functions for a single plugin
 * 
 * @since 3.00
 */
function init_plugin_content($plugin) 
{
	#1. iterate through each plugin
	#2. include interface.php
	$arr = lum_carray('LUM_PLUGINS');
	if (!is_file(PLUGINS_PATH.$plugin.'/content.class.php'))
	{
	//	lum_logMe("Failed to load plugin: $plugin");
	//	lum_logMe(PLUGINS_PATH.$plugin."/content.class.php");
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = false;
		return false;
	}
	else 
	{
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = true;
		include_once(PLUGINS_PATH.$plugin.'/content.class.php');
		return true;
	}
}

//  *** administrative tools interface ***



/**
 * Loads in all of the plugins admin tools menus
 * 
 * @since 2.00
 */
function init_plugins_admin_menu() 
{
	#1. iterate through each plugin
	#2. include interface.php
	$arr = lum_carray('LUM_PLUGINS');
	foreach ($arr as $plugin=>$required)
	{
		init_plugin_admin_menu($plugin);
	}
}

/**
 * Loads the admin tools menu for a single plugin
 * 
 * @since 2.00
 */
function init_plugin_admin_menu($plugin) 
{
	if (!is_file(PLUGINS_PATH.$plugin.'/admin/admin.php'))
	{
		lum_logMe("Failed to load plugin: $plugin");
		lum_logMe(PLUGINS_PATH.$plugin.'/admin/admin.php');
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = false;
	}
	else 
	{
		$GLOBALS['LUM_PLUGIN_LOADED'][$plugin] = true;
		include_once(PLUGINS_PATH.$plugin.'/admin/admin.php');
	}
}


/**
 * Sets interface for administrative tools
 * 
 * Called by the plugin
 *
 * @since 1.00
 */
function add_plugins_admin_interface($str) 
{
	$GLOBALS['LUM_PLUGIN_INTERFACE_JS'] .= $str;
}

/**
 * Displays the admin tools interface menu
 * 
 * Called by the plugin
 *
 * @since 1.00
 */
function show_plugins_admin_interface() 
{
	echo $GLOBALS['LUM_PLUGIN_INTERFACE_JS'];
}



function include_plugins_css()
{
	#1. iterate through each plugin
	#2. include interface.php
	$arr = lum_carray('LUM_PLUGINS');
	$str = '';
	foreach ($arr as $plugin=>$required)
	{
		include_plugin_css($plugin);
	}
}

function include_plugin_css($plugin)
{
	$str = '';
	if (is_file(PLUGINS_PATH.$plugin.'/admin/admin.css'))
	{
		$str .= '<link rel="stylesheet" type="text/css" href="<?= BASE_URL_OFFSET?>cms-plugins/'.$plugin.'/admin/admin.css" />'."\r\n";
	}
	echo $str;
}


function register_seo_check($plugin, $func)
{
	if (!in_array($func, $GLOBALS['LUM_PLUGIN_SEO_CHECK']))
	{
		$GLOBALS['LUM_PLUGIN_SEO_CHECK'][$plugin] = $func;
	}
}

function register_get_content($plugin, $func)
{
	if (!in_array($func, $GLOBALS['LUM_PLUGIN_GET_CONTENT']))
	{
		$GLOBALS['LUM_PLUGIN_GET_CONTENT'][$plugin] = $func;
	}
}

function get_plugin_content($sqlObj, $data, $is_last = false)
{
	if (isset($GLOBALS['LUM_PLUGIN_GET_CONTENT'][$data->plugin]))
	{
		return $GLOBALS['LUM_PLUGIN_GET_CONTENT'][$data->plugin]($sqlObj, $data->result, $is_last);
	}
	return null;
}

function find_item_by_seo_name($name)
{
	foreach ($GLOBALS['LUM_PLUGIN_SEO_CHECK'] as $plugin=>$func)
	{
		if ($func)
		{
			$data = $func($name);
			if ($data !== false && $data->result)
				return $data;
		}
	}
	return false;
}

function init_plugins($plugins)
{
	foreach ($plugins as $plugin=>$required)
	{
		init_plugin_sp_func($plugin);
		init_plugin_content($plugin);
	}
}

?>
