<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<meta http-equiv="Content-type" value="text/html; charset=utf-8">
		<title>[ADMIN_TITLE]</title>
		<link href="<?php echo BASE_URL_OFFSET?>cms-admin/css/admin.css" rel="stylesheet" type="text/css" />
		<link href="<?php echo BASE_URL_OFFSET?>cms-admin/css/ui-lightness/jquery-ui-1.8.10.custom.css" rel="stylesheet" type="text/css"/>
		<link href="<?php echo BASE_URL_OFFSET?>cms-admin/css/validationEngine.jquery.css" rel="stylesheet" type="text/css"/>
		<link href="<?php echo BASE_URL_OFFSET?>cms-admin/css/jquery.jgrowl.css" rel="stylesheet" type="text/css" />
		<link href="<?php echo BASE_URL_OFFSET?>cms-admin/css/ui.sexyselect.0.55.css" rel="stylesheet" type="text/css" />
		<link href="<?php echo BASE_URL_OFFSET?>cms-admin/css/ui.dropdownchecklist.css" rel="stylesheet" type="text/css" />
		<link href="<?php echo BASE_URL_OFFSET?>cms-admin/css/jquery.toChecklist.min.css" rel="stylesheet" type="text/css" />
		<link href="<?= BASE_URL_OFFSET?>cms-plugins/<?=$plugin?>/admin/admin.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/jquery-1.5.2.min.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/jquery-ui-1.8.11.custom.min.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/jquery.ui.nestedSortable.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/jquery.json.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/jquery.jgrowl_minimized.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/ui.sexyselect.0.55.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/ui.dropdownchecklist.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/jquery.toChecklist.min.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/jquery.validationEngine.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/languages/jquery.validationEngine-en.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/jquery.jrpc.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/jquery.lum.grid.js"></script>
		<script type="text/javascript" src="<?php echo BASE_URL_OFFSET?>cms-admin/js/admin.js.php"></script>
		<?php
			$file = PLUGINS_PATH. "$plugin/admin/$action.js";
			if (is_file($file) && $user) :
		?>
		<script type="text/javascript" src="<?= BASE_URL_OFFSET?>cms-plugins/<?php echo $plugin; ?>/admin/<?php echo $action; ?>.js"></script>
		<?php
			endif;
			lum_getPluginJavascriptIncludes($plugin);
			lum_getPluginCssIncludes($plugin);
		?>
	</head>
<body>
	<div id="logo"><a href="<?php echo BASE_URL_OFFSET?>" target="_new"><img src="<?php echo BASE_URL_OFFSET?>cms-admin/images/logo.png" border="0" width="200" height="75" style="margin: 0px; padding: 10px;"></a></div>
	<?php if ($user) : ?>
	<ul id="nav">
		<li><a href="/<?=TOOLS_PAGE?>"><img src="<?php echo BASE_URL_OFFSET?>cms-admin/images/house.png" class="menu-icon"/>Admin Home</a></li>
		
		<?php ob_start();?>
		<?php
			include_once(PLUGINS_PATH.'Users/admin/admin.php');
			include_once(PLUGINS_PATH.'Options/admin/admin.php');
			include_once(PLUGINS_PATH.'Languages/admin/admin.php'); ?>		
		
		<?php
			$config = trim(ob_get_contents());
			ob_end_clean();
		?>
		<?php if ($config) : ?>		
		<li><a href="#"><img src="<?php echo BASE_URL_OFFSET?>cms-admin/images/config.png" class="menu-icon"/>Config</a>
		<ul>
		<?php
			echo $config;
		?>
		</ul>
		</li>
		<?php endif; ?>
		<?php ob_start();?>
				<?php include_once(PLUGINS_PATH.'Pages/admin/admin.php'); ?>
				<?php include_once(PLUGINS_PATH.'Gallery/admin/admin.php'); ?>
				<?php include_once(PLUGINS_PATH.'Strings/admin/admin.php'); ?>
		<?php
			$contents = trim(ob_get_contents());
			ob_end_clean();
		?>
		<?php if ($contents) : ?>
		<li><a href="#"><img src="<?php echo BASE_URL_OFFSET?>cms-admin/images/content.png" class="menu-icon"/>Content</a>
			<ul>
				<?php echo $contents ?>
			</ul>
		</li>
		<?php endif; ?>
		<li><a href="<?php echo BASE_URL_OFFSET?>"><img src="<?php echo BASE_URL_OFFSET?>cms-admin/images/page_go.png" class="menu-icon"/>Live Site</a></li>
		<li><a href="<?php echo BASE_URL_OFFSET?>tools?sign-out"><img src="<?php echo BASE_URL_OFFSET?>cms-admin/images/key_go.png" class="menu-icon"/>Sign Out: <?=$user->username?></a></li>		
	</ul>
	<?php endif; ?>
	<div id="content">
		