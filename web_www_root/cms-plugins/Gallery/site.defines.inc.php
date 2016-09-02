<?php

    /**
     * Copyright 2009 - 2011 Color Shift, Inc.
     * 
     * @package Luminance v4.0
     * 
     **/
    
    // used for promos
    class GalleryDefines extends Enum {
	const PATH_LARGE = 'userfiles/gallery/large/';
	const PATH_THUMB = 'userfiles/gallery/thumb/';
	const WIDTH_LARGE = 700;
	const HEIGHT_LARGE = 467;
	const WIDTH_THUMB = 218;
	const HEIGHT_THUMB = 145;
	
	public $descriptions = array(
	  self::WIDTH_LARGE=>'Large Image Width',
	  self::HEIGHT_LARGE=>'Large Image Height',
	  self::WIDTH_THUMB=>'Thumbnail Image Width',
	  self::HEIGHT_THUMB=>'Thumbnail Image Height',
	);
	
	public static function getConstants($class = __CLASS__)
	{
	    return parent::getConstants($class);
	}
    }    
    
?>