<?php

    /**
     * Copyright 2009 - 2011 Color Shift, Inc.
     * 
     * @package Luminance v4.0
     * 
     **/
    
    // if the site cannot be accessed due to a database error
    // it will redirect to this page in the root of the site
    define('PAGE_NOT_AVAILABLE', ROOT_PATH.'not-available.htm');
    
    // if the page is not found and we couldn't load the page
    // not found from the CMS
    define('PAGE_NOT_FOUND', ROOT_PATH.'not-found.htm');    
    
    //define ('BASE_URL', 'http://multiloansource.net');
    //define ('BASE_URL_OFFSET', '/');
    //define ('SSL_BASE_URL', 'https://multiloansource.net');
    //define ('COOKIE_DOMAIN', 'multiloansource.net');
    
    define ('BASE_URL', 'https://multiloansource.net');
    define ('BASE_URL_OFFSET', '/');
    define ('SSL_BASE_URL', 'multiloansource.net');
    define ('COOKIE_DOMAIN', 'multiloansource.net');
    
    //define ('BASE_URL', 'http://localhost/aalm/English/web2');
    //define ('BASE_URL_OFFSET', '/aalm/English/web2/');
    //define ('SSL_BASE_URL', 'https://localhost/aalm/English/web2');
    //define ('COOKIE_DOMAIN', 'localhost');
    
    //define ('DB_NAME', 'jcms'); 
    define ('DB_NAME', 'web_cms'); 
    define ('DB_USER', 'cms_admin');
    //define ('DB_PASSWORD', ''); 
    define ('DB_PASSWORD', 'FW9v24b'); 
    define ('DB_HOST', 'localhost'); 
    define ('DB_PREFIX', 'lum_');
    
    // Used when building a new installation on an
    // exisitng site. Just makes it easier to navigate because
    // it will switch default home url from '/' to '/home' or whatever
    // you set it below - however the database seo_name for the home
    // page needs to match that setting as well.
    define ('_CMS_INDEX', false);    
    define('DEFAULT_HOME_SEO_NAME', 'home');
    
    // so we could have another name for the admin area.
    define('TOOLS_STEP', 'tools');
    define('TOOLS_PAGE', substr(BASE_URL_OFFSET,1).TOOLS_STEP);
    define('TOOLS_PATH', '');
	
    // turns caching on or off. Page level caching control is available
    // in the Pages plugin
    define ('_USE_CACHING', false);
    
    // are we in debug mode or not? Debugging turns logging on.
    define ('_USE_DEBUG', true);
    
    // should we echo logging too?
    define ('_ENABLE_LOUD_DEBUG', false);
     
    // display target types
    define('DISPLAY_PC', 'PC');
    define('DISPLAY_TABLET', 'Tablet');
    define('DISPLAY_MOBILE_ADVANCED', 'Advanced Mobile');
    define('DISPLAY_MOBILE_BASIC', 'Basic Mobile');
    define('DISPLAY_TV', 'TV/Game Console');

    // what displays we're targeting for this site
    // the first target is considered the default display target and is also
    // used as the master list of available templates when editing pages
    define('TARGETS', serialize(array('DISPLAY_PC')));
    define('TARGET_THEMES', serialize(array(DISPLAY_PC=>'Default')));
    
    define('REMOTE_DEVICE_DETECTION_URL', 'http://detect.jennycms.com');

    // ==================================== DO NOT MODIFY ANYTHING BELOW THIS LINE ===================================== //
    
    // defines that are not user configurable
    define('_LUMINANCE_', true);
    define('WEB_SERVICE_ERROR', -1);
    define('APPLICATION_PATH', ROOT_PATH.'cms-application/');
    define('INCLUDES_PATH', ROOT_PATH.'cms-includes/');
    define('PLUGINS_PATH', ROOT_PATH.'cms-plugins/');
    define('BASE_TEMPLATE_PATH', ROOT_PATH.'cms-themes/');
    define('ADMIN_PATH', ROOT_PATH.'cms-admin/');
    define('HTML_CACHE_PATH', ROOT_PATH.'cms-htmlcache/');
    define('PREVIEW_PATH', ROOT_PATH.'cms-preview/');
    define('LANG_CACHE_PATH', ROOT_PATH.'cms-lang/');
    define('QUERY_CACHE_PATH', ROOT_PATH.'cms-querycache/');
    define('FEED_CACHE_PATH', ROOT_PATH.'cms-feedcache/');
    define('LOGS_PATH', ROOT_PATH.'cms-logs/');
    define('IMAGE_PATH', "userfiles/images");
    define('_MAIN_LOG', LOGS_PATH.'_main_log.txt');
    define('_ERROR_LOG', LOGS_PATH.'_error_log.txt');
    
    // include path
    ini_set('include_path',INCLUDES_PATH.':'.ini_get('include_path'));	
?>
