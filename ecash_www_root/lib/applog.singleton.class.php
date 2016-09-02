<?

/**
 * Applog - Singleton Wrapper
 * applog wrapper using the singleton pattern
 */

class Applog_Singleton
{
	static private $instance = array();

	function __construct($sub_dir, $size_limit, $file_limit, $site_name, $rotate="")
	{
		include_once('applog.1.php');
		self::$instance[$sub_dir] = new Applog($sub_dir, $size_limit, $file_limit, $site_name, $rotate);
	}

	static public function Get_Instance($sub_dir, $size_limit, $file_limit, $site_name, $rotate="")
	{
		if ( !isset(self::$instance[$sub_dir]) )
		{
			new Applog_Singleton($sub_dir, $size_limit, $file_limit, $site_name, $rotate);
		}
		return self::$instance[$sub_dir];
	}
}