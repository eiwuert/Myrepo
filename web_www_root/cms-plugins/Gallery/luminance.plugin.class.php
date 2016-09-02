<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * 
 **/

class LuminancePluginGallery
{
	private $lumRegistry;
	private $lang_code;
	private $model;

	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
		$this->model = new LuminanceGalleryModel($registry->eDB ? $registry->eDB : $registry->db); // model needs database access
		$this->view = new LuminanceGalleryView($registry->eDB ? $registry->eDB : $registry->db); // model needs database access
		
		
	}

	public function getPermissionTypes()
	{
		return array(
			'Gallery\All',
			'Gallery\View',
			'Gallery\Edit Photos',
			'Gallery\Edit',
			'Gallery\Delete'
		);
		
		/*
		  
			will store permissions in the database like this
	
			$perms = array('Users\Accounts\Super User',
						   'Users\Accounts\View',
						   'Users\Roles\Add');
			
			$perms_enc = base64_encode(serialize($perms));
		
		*/
	}
	
	// ===== RPC Methods ===== //
	public function get($params)
	{
		return $this->model->get($params);
	}
	
	public function savePhotos($params)
	{
		if (!lum_requirePermission('Gallery\Edit', false) && !lum_requirePermission('Gallery\Edit Photos', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		$params['lang_code'] = $this->lumRegistry->language->default;
		$this->model->savePhotos($params);
		return $this->model->loadThumbnails($params);
	}
	
	public function showSlideshow($params)
	{
		if (isset($params['db']))
		{
			$this->lumRegistry->db = $params['db'];
			$this->model->setDb($params['db']);
			$this->view->setDb($params['db']);
		}	
		$params['lang_code'] = $this->lumRegistry->language->lang_code;
		$obj = $this->model->get($params);
		$this->view->showSlideshow($obj);
	}
	
	public function loadThumbnails($params)
	{
		if (!lum_requirePermission('Gallery\Edit', false) && !lum_requirePermission('Gallery\Edit Photos', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		$params['lang_code'] = $this->lumRegistry->language->default;
		return $this->model->loadThumbnails($params);
	}
	
	public function getLocalized($params)
	{
		if (!lum_requirePermission('Gallery\Edit', false) && !lum_requirePermission('Gallery\Edit Photos', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->getLocalized($params);
	}	
	
	public function update($params)
	{
		if (!lum_requirePermission('Gallery\Edit', false) && !lum_requirePermission('Gallery\Edit Photos', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		lum_clearPageCache();
		return $this->model->update($params);
	}	
	
	public function getList($params)
	{
		if (!lum_requirePermission('Gallery\View', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->getList($params);
	}

	public function delete($params)
	{
		if (!lum_requirePermission('Gallery\Delete', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
		
		lum_clearPageCache();
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $gallery_id)
			{
				$params['gallery_id'] = $gallery_id;
				if (!$this->model->delete($params))
				{
					return lum_showError('An error occurred and the proerty could not be deleted');
				}
			}
			return lum_showSuccess();
		}
		else
		{
			return $this->model->delete($params);
		}
	}
	
	public function deactivate($params)
	{
		if (!lum_requirePermission('Gallery\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 0;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $gallery_id)
			{
				$params['gallery_id'] = $gallery_id;
				if (!$this->model->changeStatus($params))
				{
					return false;
				}
			}
			return true;
		}
		else
		{
			return $this->model->changeStatus($params);
		}
	}	
	
	public function activate($params)
	{
		if (!lum_requirePermission('Gallery\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 1;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $gallery_id)
			{
				$params['gallery_id'] = $gallery_id;
				
				if (!$this->model->changeStatus($params))
				{
					return false;
				}
			}
			return true;
		}
		else
		{
			return $this->model->changeStatus($params);
		}
	}
	
	public function loadRawImages($params)
	{
		return $this->model->loadRawImages($params);
	}
	
	public function processRawPhotos($params)
	{
		return $this->model->processRawPhotos($params);
	}
	
	public function purgeRawImages($params)
	{
		return $this->model->purgeRawPhotos($params);
	}	
	
}

class LuminanceGalleryModel extends LuminanceModel
{
	private $path;
	private $url_path;
	
	function __construct($db)
	{
		$this->_db = $db;
		$this->_table = DB_PREFIX."gallery";
		$this->_key = "gallery_id";
		$this->_localized = true;
		$this->path = ROOT_PATH;
		$this->url_path = BASE_URL.'cms-plugins/Gallery/temp/';
	}
	
	public function setDb($db)
	{
		$this->_db = $db;
	}
	
	public function update($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'gallery_id',
			'name',
			'width_large',
			'height_large',
			'width_thumb',
			'height_thumb',
			'photos'=>LOCALIZED,
			'lang_code'=>LOCALIZED
		));
		
		$new = $params['new'];
		
		$params['date_added'] = date('Y-m-d H:i:s');

		$success = false;

		if ($new)
		{
			$params['added_by'] = $params['signed_in']['user_id'];
			$id = parent::insert($params);
			
			if ($id)
			{
				$params['gallery_id'] = $id;
				$success = true;
			}
		}
		else
		{
			$params['updated_by'] = $params['signed_in']['user_id'];
			$success = parent::update($params);
		}
		
		if (!$success)
			return lum_showError(parent::getError());
		else
			return lum_showSuccess(array('gallery_id'=>$params['gallery_id']));
	}

	public function get($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'gallery_id'
		));
		$row =  parent::get($params);
		if ($row)
		{
			$row['photos'] = unserialize(base64_decode($row['photos']));
		}
		return $row;
	}	

	public function getLocalized($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'gallery_id'
		));
		$rows = parent::getLocalized($params);
		return $rows;
	}	

	public function delete($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'gallery_id'
		));
		return parent::delete($params);
	}		
	
	public function getList($params, $table = null)
	{
		$params['filters'] = null;
		$params['values'] = array();
		if (isset($params['lang_code']))
		{
			$params['filters'] .= ' lang_code = ? ';
			$params['values'][] = $params['lang_code'];
		}
		if (isset($params['query']) && $params['query'] != '')
		{
			$params['where'] = "(name like '%%".addslashes($params['query'])."%%' or t.gallery_id like '%%".addslashes($params['query'])."%%')";
		}
		return parent::getList($params);
	}
	
	public function changeStatus($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'gallery_id',
			'status'
		));
		return parent::changeStatus($params);
	}
	
	public function loadRawImages($params)
	{
		list($raw, $count) = $this->getRawPhotoList($params['folder']);
		$obj = $this->get(array('gallery_id'=>$params['folder']));
		$html = '';
		foreach ($raw as $file=>$arr)
		{
			$id = str_replace('.','_', $file);
			$id = str_replace(' ','_', $id);
			$html .= '
			<div class="raw_image">
			<img src="'.$arr['url'].'" id="'.$id.'" title="'.$file.'" width="'.$obj['width_large'].'" class="cropme"/>
			<br/>
			<table>
			<tr>
			<td>Filename:</td>
			<td>'.$file.'</td>
			</tr>
			<tr>
			<td>Type:</td>
			<td>'.$arr['stat']['mime'].'</td>
			</tr>
			<tr>
			<td>Width:</td>
			<td>'.$arr['stat']['0'].'</td>
			</tr>
			<tr>
			<td>Height:</td>
			<td>'.$arr['stat']['1'].'</td>
			</tr>
			</table>
			<input type="hidden" size="4" name="'.$id.'_f" value="'.$file.'"/>
			<input type="hidden" size="4" id="'.$id.'_x" name="'.$id.'_x" /> 
			<input type="hidden" size="4" id="'.$id.'_y" name="'.$id.'_y" /> 
			<input type="hidden" size="4" id="'.$id.'_x2" name="'.$id.'_x2" /> 
			<input type="hidden" size="4" id="'.$id.'_y2" name="'.$id.'_y2" /> 
			<input type="hidden" size="4" id="'.$id.'_w" name="'.$id.'_w" /> 
			<input type="hidden" size="4" id="'.$id.'_h" name="'.$id.'_h" />
			</div>';
		}
		
		if ($html == '')
			$html = '<p>There are no raw images that need processing</p>';
		
		return array($html, 1);
	
	}
	
	public function getRawPhotoList($folder)
	{
		
		// this is the project folder for a work in progress
		// we use the entire parent id for this one
		$parent_path = $this->path.'cms-plugins/Gallery/temp/'.$folder;
		$url_path = $this->url_path.$folder;

		$photos = array();
		if ($handle = opendir($parent_path)) 
		{
			// this is what we're going to match our files against
			// any jpg, png or gif
			$regex = "/^.*\.(jpg|jpeg|png|gif)$/i";
			while (false !== ($file = readdir($handle))) 
			{
			    $photo_data = array();

			    if (preg_match_all($regex, $file, $photo_data, PREG_PATTERN_ORDER))
			    {
				    // we have a match!
				    $filepath = $parent_path.'/'.$file;
				    $stat = getimagesize($filepath);
				    $photos[$file] = array('url'=>$url_path.'/'.$file, "stat"=>$stat);
			    }
			}
		}
		else 
		{
			return lum_showError("Unable to access the raw folder: ". $parent_path);
		}
		
		// now let's sort by the index
		ksort($photos);
		
		return array($photos, count($photos));
	}
		
	public function processRawPhotos($params)
	{
		/**
		 * we pass in the $_POST into this function
		 * 
		 * we should have something like this:
		 * 
		 * array(
		 * 	<photo_id>_f=>'filename.jpg',
		 *  <photo_id>_x=>starting x point
		 *  <photo_id>_y=>starting y point
		 *  <photo_id>_x2=>ending x point
		 *  <photo_id>_y2=>ending y point
		 *  <photo_id>_w=>width
		 *  <photo_id>_h=>height
		 * 
		 * );
		 * 
		 */
	
		// set up our paths
		$thumb_path = $this->path.GalleryDefines::PATH_THUMB.$params['folder'];
		$large_path = $this->path.GalleryDefines::PATH_LARGE.$params['folder'];
		$project_path = $this->path.'cms-plugins/Gallery/temp/'.$params['folder'].'/';	
		
		if (!is_dir($thumb_path))
		{
			mkdir($thumb_path);
			chmod($thumb_path, 0775);
		}
	

		if (!is_dir($large_path))
		{
			mkdir($large_path);
			chmod($large_path, 0775);
		}
			
		
		$gallery_obj = $this->get(array('gallery_id'=>$params['folder']));	
			
		$index = $count + 1;
		foreach ($params as $param=>$value)
		{
			// let's key off of the filename
			if (substr($param, -2, 2) == '_f')
			{
				$id = substr($param,0,-2);
	
				// does the source image exist? It may if they did a refresh on the page
				if (!is_file($project_path.$value))
					continue;
				
				// now create the large image
				$new_file = substr($value, 0,-4);
				$new_file = preg_replace('`[^-_0-9a-zA-Z]`','',$new_file);
				$arr = array(
					'source'=>$project_path.$value,
					'dest'=>$large_path.'/'.$new_file.'.jpg',
					'x1'=>$params[$id.'_x'],
					'x2'=>$params[$id.'_x2'],
					'y1'=>$params[$id.'_y'],
					'y2'=>$params[$id.'_y2'],
					'WIDTH_LARGE'=>$gallery_obj['width_large'],
					'HEIGHT_LARGE'=>$gallery_obj['height_large'],
					'WIDTH_THUMB'=>$gallery_obj['width_thumb'],
					'HEIGHT_THUMB'=>$gallery_obj['height_thumb']
				);


				$this->makeLargePhoto($arr);
		
				// now we're going to make the two smaller photos based on the new large one.
				// Because the large photos is a different aspect from the smaller photos,
				// we're going to automatically crop out the center of the large one and resize
				// so we clear out the crop marks
				
				$arr['x1'] = '';
				$arr['x2'] = '';
				$arr['y1'] = '';
				$arr['y2'] = '';
				
				$arr['source'] = $large_path.'/'.$new_file.'.jpg';
				$arr['dest'] = $thumb_path.'/'.$new_file.'.jpg';
	
				$this->makeThumbPhoto($arr);
				
				$obj = $this->getLocalized(array('gallery_id'=>$params['folder']));
				
				foreach ($obj as $lang)
				{
					$url = '/'.str_replace($this->path, '', $arr['dest']);
					$photos = unserialize(base64_decode($lang['photos']));
					if (is_array($photos))
					{
						$photos[] = array('url'=>$url, 'caption'=>'', 'title'=>'', 'link'=>'');
					}
					else
					{
						$photos = array();
						$photos[] = array('url'=>$url, 'caption'=>'', 'title'=>'', 'link'=>'');
					}
					
					$sql = "update lum_gallery_localized set photos = ? where gallery_id = ? and lang_code = ?";
					$photos = base64_encode(serialize($photos));
					$value_array = array($photos, $params['folder'], $lang['lang_code']);

					if ($this->_db->doQuery($sql, $value_array) === false)
					{
						return lum_showError("Unable to update the database with new photos");
					}
					
				}

				// now remove the uploaded file
				unlink($project_path.$value);

				$index++;
			}
		}
	
		return array(null, 0);
	}
	
	public function purgeRawPhotos($params)
	{
		$project_path = $this->path.'cms-plugins/Gallery/temp/'.$params['folder'];
		$files = scandir($project_path);
		if ($files && is_array($files))
		{
			foreach ($files as $file)
			{
				if (is_file($project_path.'/'.$file))
				{
					unlink($project_path.'/'.$file);
				}
			}
		}
	
		return array(null, 0);
	}	
	
	/**
	 * makeLargePhoto
	 * 
	 * Takes the input file and makes are our large size photo based on the crop marks
	 *
	 * @param array $params
	 */
	private function makeLargePhoto($params)
	{
		
		$params['targ_w'] = $params['WIDTH_LARGE'];
		$params['targ_h'] = $params['HEIGHT_LARGE'];
	
		$img = $this->cropAndResize($params);	
	}
	
	/**
	 * makeThumbPhoto
	 * 
	 * Takes the large size file and makes are our thumb size photo cropped out of the center of the large one
	 *
	 * @param array $params
	 */
	
	private function makeThumbPhoto($params)
	{
		$params['targ_w'] = $params['WIDTH_THUMB'];
		$params['targ_h'] = $params['HEIGHT_THUMB'];
	
		$img = $this->cropAndResize($params);	
	}
	
	/**
	 * cropAndResize
	 * 
	 * Does the cropping and resizing of photos based on the parameters passed in.
	 *
	 * @param array $params
	 */
	
	private function cropAndResize($params)
	{
		$stat = getimagesize($params['source']);
		$params['orig_w'] = $stat[0];
		$params['orig_h'] = $stat[1];
		$params['type'] = $stat[2];
			
		
		
		lum_logMe("SOURCE:".$params['source']);
		lum_logVar($params);
		$ratio = $params['orig_w'] / $params['targ_w'];
		$bRatioAdjust = true;
		
		lum_logMe("Ratio: $ratio");
			
		// let's see if they set a crop area
		if ($params['x1'] == '')
		{
			// they didn't so let's see if we need to crop
			$targ_ratio = round($params['targ_w'] / $params['targ_h'], 2);
			$orig_ratio = round($params['orig_w'] / $params['orig_h'], 2);
			lum_logMe("targ ratio:".$targ_ratio);
			lum_logMe("orig ratio:".$orig_ratio);
			
			if ($targ_ratio == $orig_ratio)
			{
				// we just need to resize it
				$params['x1'] = 0;
				$params['x2'] = $params['targ_w'];
				$params['y1'] = 0;
				$params['y2'] = $params['targ_h'];	
				lum_logMe("Same Ratio!");
			}
			else 
			{
				// we need to do a crop then resize
				
				// do we need to resize the height or width?
				if ($targ_ratio > $orig_ratio)
				{
					lum_logMe("in y adjustment");
					$params['x1'] = 0;
					$params['x2'] = $params['targ_w'];
					
					// ok we're just going to crop the center of the image
					
					// next we find the vertical center of the original image
					$center = round(($params['orig_h'] / $ratio) / 2);
					lum_logMe("center: $center");
					// and finally subtract half of the crop area height
					$params['y1'] = $center - round($params['targ_h'] / 2);
					
					// and add half of the crop area height
					$params['y2'] = $center + round($params['targ_h'] / 2);
				}
				else 
				{
					lum_logMe("in x adjustment");
					$params['y1'] = 0;
					$params['y2'] = $params['orig_h'];
					
					// we need to figure out our scale at this point because the image is smaller than the 
					// target size
					
					$scale = $params['orig_h'] / $params['targ_h'];
					lum_logMe("Scale: $scale");
					
					// ok we're just going to crop the center of the image
					
					// next we find the vertical center of the original image
					$center = round($params['orig_w'] / 2);
					
					// and finally subtract half of the crop area height
					$params['x1'] = $center - round(($params['targ_w'] * $scale) / 2);
					
					// and add half of the crop area height
					$params['x2'] = $center + round(($params['targ_w'] * $scale) / 2);
					$bRatioAdjust = false;
				}
				// then we just let things happen as usual		
			}
			
		}
		
		// ratio adjust means that we need to adjust the crop marks for a larger sized photo
		// because we crop against smaller ones on the web site.
		if ($bRatioAdjust)
		{
			$x1 = round(intval($params['x1']) * $ratio);
			$x2 = round(intval($params['x2']) * $ratio);
			$y1 = round(intval($params['y1']) * $ratio);
			$y2 = round(intval($params['y2']) * $ratio);
		}
		else 
		{
			$x1 = $params['x1'];
			$x2 = $params['x2'];
			$y1 = $params['y1'];
			$y2 = $params['y2'];
		}
			
		if (_DEBUG)
		{
			lum_logMe("x1: $x1");
			lum_logMe("x2: $x2");
			lum_logMe("y1: $y1");
			lum_logMe("y2: $y2");
		}
		
		$crop_width = $x2 - $x1;
		$crop_height = $y2 - $y1;
		
		//TODO handle other file types here!
		
		$img_r = null;
		if ($params['type'] == IMAGETYPE_JPEG)
		{
			$img_r = imagecreatefromjpeg($params['source']);
		}
	
		if ($params['type'] == IMAGETYPE_PNG)
			$img_r = imagecreatefrompng($params['source']);
	
		if ($params['type'] == IMAGETYPE_GIF)
			$img_r = imagecreatefromgif($params['source']);
			
		if ($params['type'] == IMAGETYPE_BMP)
			$img_r = imagecreatefromwbmp($params['source']);
			
		$dst_r = imagecreatetruecolor( $params['targ_w'], $params['targ_h'] );
	
		imagecopyresampled($dst_r, $img_r, 0, 0, $x1, $y1, $params['targ_w'], $params['targ_h'], $crop_width, $crop_height);
		imagejpeg($dst_r, $params['dest'], 90);
		imagedestroy($img_r);	
		return $dst_r;
	}
	
	public function loadThumbnails($params)
	{
		$obj = $this->getLocalized(array('gallery_id'=>$params['gallery_id']));
		$thumbs = array();
		foreach ($obj as $lang)
		{
			$photos = unserialize(base64_decode($lang['photos']));
			$html = '';
			$index = 0;
			foreach ($photos as $photo)
			{
				$id = 'thumb_'.$index;
				
				$thumb = $photo['url'];
				$large = str_replace('thumb/', 'large/', $thumb);
				
				$html .= "
					<li id='$id' class='photolistitem' >
							<div class='imagecontainer'>
								<img src='".rtrim(BASE_URL,'/').$thumb."?".microtime()."' class='thumbnailimage' id='".$id."_img' title='".$thumb."'/>
							</div>
							<div style='text-align: center; font-size: 11px; margin-bottom: 7px;'>
								<a href='".rtrim(BASE_URL,'/').$large."?".microtime()."' target='_blank'>View Large</a>
							</div>
							
							Title<br/>
							<input type='text' id='".$id."_title_".$lang['lang_code']."' class='edit_title' value='".addslashes(decodeURI($photo['title']))."'/>
							<br/><br/>
							<fieldset>
								<legend>Slide Show Options</legend>
								Caption<br/>
								<input type='text' id='".$id."_caption_".$lang['lang_code']."' class='edit_caption' value='".addslashes(decodeURI($photo['caption']))."'/>
								<br/>Link URL<br/>
								<input type='text' id='".$id."_link_".$lang['lang_code']."' class='edit_link' value='".addslashes(decodeURI($photo['link']))."'/>
							</fieldset>
							";
							
							
				if ($params['lang_code'] == $lang['lang_code'])
					$html .= "<div class='deletethumbnail' id='".$id."_del' title='".$thumb."'>Delete</div>";
					
				$html .= "</li>";				
				$index++;
			}
			$thumbs[$lang['lang_code']] = encodeURI($html);
		}
		
		return array($thumbs, 1);
	}
	
	public function savePhotos($params)
	{
		$physical = $this->getGalleryPhysicalList($params['gallery_id']);
		
		// the default language and has the order of the thumbnails
		$photos = array();
		$base_photos = array();
		$index = 0;
		foreach ($params['photos']->{$params['lang_code']} as $photo)
		{
			$temp = preg_split('\/', $photo->url);
			$file = $temp[(count($temp) - 1)];

			$i = array_search($file, $physical);
			if ($i !== false)
				$physical[$i] = null;
				
			$photos[] = array('url'=>$photo->url, 'title'=>$photo->title, 'caption'=>$photo->caption, 'link'=>$photo->link);
			$temp = explode('_', $photo->id);
			$base_photos[intval(trim($temp[1]))] = $index;
			$index++;
		}
		
		var_dump($physical);
		foreach ($physical as $file)
		{
			var_dump($file);
			if ($file)
			{
				$this->deletePhoto($params['gallery_id'], $file);
			}
		}
		
		$this->updatePhotos($params['gallery_id'], $params['lang_code'], $photos);
		
		
		foreach ($params['photos'] as $lang_code=>$arr)
		{
			if ($lang_code == $params['lang_code'])
				continue;
			
			$holder = array();
			foreach ($arr as $photo)
			{
				$temp = explode('_', $photo->id);
				$holder[$base_photos[intval(trim($temp[1]))]] = array('url'=>$photo->url, 'title'=>$photo->title, 'caption'=>$photo->caption, 'link'=>$photo->link);
			}
			$photos = array();
			ksort($holder, SORT_NUMERIC);
			foreach ($holder as $photo)
			{
				$photos[] = $photo;
			}
			$this->updatePhotos($params['gallery_id'], $lang_code, $photos);
		}
	}
	
	private function updatePhotos($gallery_id, $lang_code, $photos)
	{
		
		$sql = "insert lum_gallery_localized (photos, gallery_id, lang_code) values (?,?,?) on duplicate key update photos = ?";
		$photos = base64_encode(serialize($photos));
		$value_array = array($photos, $gallery_id, $lang_code, $photos);

		if ($this->_db->doQuery($sql, $value_array) === false)
		{
			return false;
		}
		
		return true;
	}
	
	private function getGalleryPhysicalList($folder)
	{
		// this is the project folder for a work in progress
		// we use the entire parent id for this one
		$thumb_path = $this->path.GalleryDefines::PATH_THUMB.$folder;
		$photos = array();
		if ($handle = opendir($thumb_path)) 
		{
			// this is what we're going to match our files against
			// any jpg, png or gif
			$regex = "/^.*\.(jpg|jpeg|png|gif)$/i";
			while (false !== ($file = readdir($handle))) 
			{
			    $photo_data = array();

			    if (preg_match_all($regex, $file, $photo_data, PREG_PATTERN_ORDER))
			    {
				    // we have a match!
				    $filepath = $parent_path.'/'.$file;
				    $stat = getimagesize($filepath);
				    $photos[] = $file;
			    }
			}
		}
		return $photos;
	}

	private function deletePhoto($folder, $file)
	{
		$thumb_path = $this->path.GalleryDefines::PATH_THUMB.$folder.'/';
		$large_path = $this->path.GalleryDefines::PATH_LARGE.$folder.'/';
		
		var_dump($thumb_path.$file);
		var_dump($large_path.$file);
		
		unlink($thumb_path.$file);
		unlink($large_path.$file);
	}
	
}


class LuminanceGalleryView extends LuminanceView
{
	private $_last_property_id;
	
	function __construct($registry)
	{
		$this->lumRegistry = $registry;
	}
	
	public function setDb($db)
	{
		$this->lumRegistry->db = $db;
	}	
	
	public function displayContent($data, $is_last)
	{
		return false;		
	}

	public function getStrings()
	{
		return $this->_strings;
	}
	
	protected function setString($key, $value)
	{
		if (!$this->_strings)
			$this->_strings = array();
			
		$this->_strings[$key] = $value;
	}
	
	private function setLastPageId($property_id)
	{
		$this->_last_property_id = $property_id;
	}
	
	public function getLastPageId()
	{
		return $this->_last_property_id;
	}
	
	public function showSlideshow($obj)
	{
		$width = $obj['width_large'];
		$height = $obj['height_large'];
		foreach ($obj['photos'] as $photo)
		{
			$url = str_replace('thumb/', 'large/', $photo['url']);
			include(TEMPLATES_PATH.'slideshow.inc.php');
		}
	}
	
}


?>
