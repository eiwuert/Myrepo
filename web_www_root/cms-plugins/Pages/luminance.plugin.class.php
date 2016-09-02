<?php
/**
 * Copyright 2009 - 2011 Color Shift, Inc.
 * 
 * @package Luminance v4.0
 *
 * This class determines which language the site should be displayed in.
 * 
 **/

class LuminancePluginPages
{
	private $lumRegistry;
	private $lang_code;
	private $model;
	private $_cache_page = true;
	
	function __construct(&$registry)
	{
		$this->lumRegistry = $registry;
		$this->model = new LuminancePagesModel($registry->db); // model needs database access
		$this->view = new LuminancePagesView($registry); // view needs registry access
	}

	public function parseBit($bit, $is_last, $last_bit_id)
	{

		$data = $this->model->getbyCode(array('bit'=>$bit, 'last_bit_id'=>$last_bit_id, 'lang_code'=>$this->lumRegistry->language->lang_code, 'is_default'=>$this->lumRegistry->language->is_default));

		if ($data)
		{
			$this->setCachePage($data['cache_page']);
			return $this->view->displayContent($data, $is_last);
		}
		return WEB_SERVICE_ERROR;
	}
	
	public function cachePage()
	{
		return $this->_cache_page;
	}
	
	private function setCachePage($bool)
	{
		$this->_cache_page = $bool;
	}
	
	public function getStrings()
	{
		return $this->view->getStrings();
	}
	
	public function getLastBitId()
	{
		return $this->view->getLastPageId();
	}	
	
	// used when display the roles tool
	public function getPermissionTypes()
	{
		return array(
			'Pages\All',
			'Pages\View',
			'Pages\Add',
			'Pages\Edit',
			'Pages\Delete',
			'Pages\Change Status',
			'Pages\Can See Hidden Templates'
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
	public function saveStructure($params)
	{
		$error = $this->model->saveStructure($params);
		
		if ($error)
			return lum_showError("Unable to save site structure: ".$error);

		return lum_showSuccess();
	}
	
	public function getPermalink($params)
	{
		return $this->model->getPermalink($params);
	}		
	
	public function getMenu($params)
	{
		return $this->model->getMenu($params);
	}
	
	public function getNavList($params)
	{
		$params['lang_code'] = $this->lumRegistry->language->default;
		return $this->model->getNavList($params);
	}
	
	public function getTemplateList($params)
	{
		if (!lum_requirePermission('Pages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->getTemplateList($params);
	}	
	
	public function getTemplate($params)
	{
		list($targets, $target_desc, $count) = $this->view->loadTemplate($params['template'], $params['display_targets']);
		return lum_showSuccess(array('targets'=>$targets, 'target_desc'=>$target_desc), $count);
	}
	
	public function displayMenu($params)
	{
		$this->view->buildMenu(lum_call('Pages', 'getMenu', array('page_id'=>1, 'include_root'=>true, 'show_on_menu'=>'1')));
	}

	public function displaySubMenu($params)
	{
		$this->view->buildMenu(lum_call('Pages', 'getMenu', array('page_id'=>$this->lumRegistry->page_id, 'include_root'=>true, 'show_on_menu'=>'1')), true);
	}
	
	public function displaySiblingMenu($params)
	{
		$sql = "select parent_id from lum_pages where page_id = ?";
		$value_array = array($this->lumRegistry->page_id);
		$row = $this->lumRegistry->db->getRow($sql, $value_array, true);
		if ($row)
		{
			$this->view->buildMenu(lum_call('Pages', 'getMenu', array('page_id'=>$row['parent_id'], 'include_root'=>false, 'show_on_menu'=>'1')), true);
		}
	}	

	public function getPageContent($params)
	{
		if (!lum_requirePermission('Pages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
		
		
		$localized = null;
		if ($params['page_id'] != '')
		{
			$localized = $this->model->getLocalized($params);
					
			if ($localized === false)
			{
				return lum_showError("Unable to retreive page data");
			}
		}
		
		#1. open up the template
		#2. find the editable areas
		#3. assign content from database to those areas
		list($targets, $target_desc, $count) = $this->view->loadTemplate($params['template'], $params['display_targets']);

		$content = array();
		if ($localized)
		{
			foreach ($localized as $row)
			{
				$content[$row['lang_code']] = unserialize(base64_decode($row['page_content']));
			}
		}


		list($langs, $count) = lum_call('Languages', 'getList', array('status'=>1, 'sort'=>'def desc, language', 'dir'=>'asc'));
		$includes = $this->model->getIncludesList(array('lang_code'=>$this->lumRegistry->language->default));
		$rows = array();

		if ($targets && $langs)
		{
			foreach ($langs as $lang)
			{
				$lang_code = $lang['lang_code'];
				foreach ($targets as $target=>$matches)
				{
					$rows[$lang_code][$target] = '';
					foreach ($matches as $cms)
					{
						$cmsval = $cms;
						if (substr($cmsval,0,6) == 'image ')
						{
							$cmsval = substr($cmsval,6);
							$label = $cmsval;
							$id = str_replace(" ", "_", $label);
	
							$value = '';
							if (isset($content[$lang_code][$target][$cmsval]))
								$value =  stripslashes(html_entity_decode($content[$lang_code][$target][$cmsval], ENT_QUOTES, 'UTF-8'));						
							
							$rows[$lang_code][$target] .= '
							<p class="cms_image">'.$label.'
							<input id="c'.$lang_code.'::'.$target.'::cms_'.$id.'" name="'.$lang_code.'::'.$target.'::cms_'.$label.'" style="width:300px" value="'.addslashes($value).'"/>
							<a href="javascript:;" onclick="mcImageManager.browse({lum_id : \'c'.$lang_code.'\\\\:\\\\:'.$target.'\\\\:\\\:cms_'.$id.'\', oninsert: removeBaseUrl});">[Browse]</a>
							</p>
							';
						}
						
						if (substr($cmsval,0,5) == 'text ')
						{
							$cmsval = substr($cmsval,5);
							$label = $cmsval;
							$id = str_replace(" ", "_", $label);
							
							$value = '';
							if (isset($content[$lang_code][$target][$cmsval]))
								$value =  stripslashes(html_entity_decode($content[$lang_code][$target][$cmsval], ENT_QUOTES, 'UTF-8'));	
								
							$value = str_replace('"', '&quot;', $value);
								
							$rows[$lang_code][$target] .= '
							<p class="cms_textbox">
								<label for="c'.$lang_code.'::'.$target.'::cms_'.$id.'">'.$label.'</label>
								<input id="c'.$lang_code.'::'.$target.'::cms_'.$id.'" name="'.$lang_code.'::'.$target.'::cms_'.$label.'" class="text translate" title="'.$label.'" value="'.$value.'"/>
							</p>
							';
						}
						
						if (substr($cmsval,0,9) == 'richtext ')
						{
							$cmsval = substr($cmsval,9);
							$label = $cmsval;
							$id = str_replace(" ", "_", $label);

							$value = '';
							if (isset($content[$lang_code][$target][$cmsval]))
								$value =  stripslashes(html_entity_decode($content[$lang_code][$target][$cmsval], ENT_QUOTES, 'UTF-8'));								
							
							$rows[$lang_code][$target] .= '
							<p class="cms_richtext">'.$label.'<br/>
								<textarea id="c'.$lang_code.'::'.$target.'::cms_'.$id.'" name="'.$lang_code.'::'.$target.'::cms_'.$label.'" rows="15" cols="80" style="width: 80%" class="tinymce" title="'.$label.'">'.$value.'</textarea>
							</p>
							';
						}
						if (substr($cmsval,0,8) == 'include ')
						{
							$cmsval = substr($cmsval,8);
							$label = $cmsval;
							$id = str_replace(" ", "_", $label);
							
							$value = '';
							if (isset($content[$lang_code][$target][$cmsval]))
								$value =  stripslashes(html_entity_decode($content[$lang_code][$target][$cmsval], ENT_QUOTES, 'UTF-8'));	

							$rows[$lang_code][$target] .= '
							<p class="cms_include">
							<label for="c'.$lang_code.'::'.$target.'::cms_'.$label.'">'.$label.'</label>
							<select id="c'.$lang_code.'::'.$target.'::cms_'.$label.'" name="'.$lang_code.'::'.$target.'::cms_'.$label.'">';

							$found_include = false;
							foreach ($includes as $include)
							{
								$sel = '';
								if ($value)
								{
									if ($include['page_id'] == $value)
										$sel = ' selected="selected"';
								}
								else
								{
									// let's try to be slightly intelligent, at least with header and footer includes
									if (!$found_include && stripos($label, 'header') !== false)
									{
										// the words 'header' is in the label, so let's see if we have a header include
										if (stripos($include['name'], 'header') !== false)
										{
											$sel = ' selected="selected"';
											$found_include = true;
										}
									}
									
									if (!$found_include && stripos($label, 'footer') !== false)
									{
										// the words 'footer' is in the label, so let's see if we have a header include
										if (stripos($include['name'], 'footer') !== false)
										{
											$sel = ' selected="selected"';
											$found_include = true;
										}
									}									
								}
								$rows[$lang_code][$target] .= '<option value="'.$include['page_id'].'"'.$sel.'>'.$include['name'].'</option>';
							}
							
							$rows[$lang_code][$target] .= '</select></p>';
						}
					}
				}
			}
		}
		
		return lum_showSuccess(array('content'=>$rows, 'target_desc'=>$target_desc), 1); 	
	}
	public function get($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($this->model,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($params,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r(debug_backtrace(),true)."\n",FILE_APPEND);

		return $this->model->get($params);
	}
	
	public function getLocalized($params)
	{
		if (!lum_requirePermission('Pages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		return $this->model->getLocalized($params);
	}	
	
	public function update($params)
	{
		//if (!lum_requirePermission('Pages\Edit', false))
		//	return lum_showError(lum_getString('[NO_PERMISSION]'));
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($params,true)."\n",FILE_APPEND);
			
		lum_clearPageCache();
		if ($params['preview'] == 1)
		{
			$params = $this->model->preview($params);
			ob_start();
			$this->view->displayContent($params, true);
			$preview = ob_get_clean();
			
			$strings = $this->view->getStrings();
			foreach ($strings as $code=>$text)
			{
				lum_setString($code, $text);
			}
			$preview = lum_replaceStrings($preview);
			$file = HTML_CACHE_PATH.'preview.html';
			file_put_contents($file, $preview);
			return lum_showSuccess();
		}
		else
			return $this->model->update($params);
	}	
	
	public function copyPage($params)
	{
		if (!lum_requirePermission('Pages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
			
		lum_clearPageCache();
		return $this->model->copyPage($params);
	}	
	
	public function getList($params)
	{
		return $this->model->getList($params);
	}
	
	public function delete($params)
	{
		if (!lum_requirePermission('Pages\Delete', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));
		
		lum_clearPageCache();
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $page_id)
			{
				if ($page_id == 1)
					continue;
				
				$params['page_id'] = $page_id;
				if (!$this->model->delete($params))
				{
					return lum_showError('An error occurred and the page couuld not be deleted');
				}
			}
			return lum_showSuccess();
		}
		else
		{
			if ($params['page_id'] == 1)
				return lum_showError('You cannot delete the home page');
				
			return $this->model->delete($params);
		}
	}
	
	
	public function deactivate($params)
	{
		if (!lum_requirePermission('Pages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 0;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $page_id)
			{
				$params['page_id'] = $page_id;
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
		if (!lum_requirePermission('Pages\Edit', false))
			return lum_showError(lum_getString('[NO_PERMISSION]'));		
		
		$params['status'] = 1;
		if (isset($params['ids']))
		{
			//we're bulk deleting
			foreach ($params['ids'] as $page_id)
			{
				$params['page_id'] = $page_id;
				
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
		
	public function getPageTree($params)
	{
		$params['lang_code'] = $this->lumRegistry->language->lang_code;
		return $this->model->getPageTree($params);
	}
	
	public function getBreadCrumbs($page_tree)
	{
		return $this->view->getBreadCrumbs($page_tree);		
	}
}

class LuminancePagesModel extends LuminanceModel
{
	function __construct($db)
	{
		$this->_db = $db;
		$this->_table = DB_PREFIX."pages";
		$this->_key = "page_id";
		$this->_localized = true;
	}
	
	public function copyPage($params)
	{
		parent::setRequiredParams(array
		(
			'page_id'
		));
		
		$page = $this->get($params);
		
		$sql = "insert into lum_pages (`parent_id`, `show_parent_in_url`, `template`, `seo_name`, `date_added`, `status`, `admin_only`, `show_on_menu`, `menu_click`, `secured`, `external_url`, `open_new_window`, `is_include`, `cache_page`, `changefreq`, `priorty`, `last_modified`, `display_targets`, `menu_class`) SELECT `parent_id`, `show_parent_in_url`, `template`, concat(`seo_name`, '-', (select count(page_id) from lum_pages where seo_name = '".$page['seo_name']."') + 1), `date_added`, `status`, `admin_only`, `show_on_menu`, `menu_click`, `secured`, `external_url`, `open_new_window`, `is_include`, `cache_page`, `changefreq`, `priorty`, `last_modified`, `display_targets`, `menu_class` FROM `lum_pages` WHERE page_id=?";
		$value_array = array($params['page_id']);
		$id = $this->_db->doInsert($sql, $value_array);

		if ($id)
		{
			$sql = "insert into lum_pages_localized (`page_id`, `name`, `meta_title`, `meta_keywords`, `meta_description`, `page_content`, `lang_code`) SELECT $id, concat(`name`, '(', (select count(page_id) from lum_pages where seo_name = '".$page['seo_name']."') + 1, ')'), `meta_title`, `meta_keywords`, `meta_description`, `page_content`, `lang_code` FROM `lum_pages_localized` WHERE page_id=?";
			$value_array = array($params['page_id']);
			if ($this->_db->doQuery($sql, $value_array) === false)
				return lum_showError($this->_db->getError());
			$this->updateOrder($id);
			$this->rebuildTree();
			return lum_showSuccess();
		}
		return lum_showError($this->_db->getError());
	}	
	
	public function preview($params)
	{
		parent::setRequiredParams(array
		(
			'parent_id',
			'show_parent_in_url',
			'template',
			'meta_title'=>LOCALIZED,
			'meta_keywords'=>LOCALIZED,
			'meta_description'=>LOCALIZED,
			'name'=>LOCALIZED,
			'seo_name',
			'page_content'=>LOCALIZED,
			'date_added',
			'status',
			'admin_only',
			'show_on_menu',
			'menu_click',
			'secured',
			'external_url',
			'lang_code'=>LOCALIZED,
			'is_include',
			'display_targets',
			'cache_page',
			'menu_class',
			'open_new_window',
			'page_id'
		));

		$params['display_targets'] = (is_array($params['display_targets[]']) ? join(',', $params['display_targets[]']) : $params['display_targets[]']);
		$params['use_lang'] = $params['lang_code'];
		$params['page_content'] = $this->getPageData($params);
		$params = $this->getLocalizedPageData($params);
		$params['date_added'] = date('Y-m-d H:i:s');

		$params['meta_title'] = lum_htmlEncode(stripslashes($params['meta_title']));
		$params['meta_keywords'] = lum_htmlEncode(stripslashes($params['meta_keywords']));
		$params['meta_description'] = lum_htmlEncode(stripslashes($params['meta_description']));
		$params['name'] = lum_htmlEncode(stripslashes($params['name']));
		$params = $this->getLocalizedStrings($params);
		
		return $params;
	}	
	
	public function update($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'parent_id',
			'show_parent_in_url',
			'template',
			'meta_title'=>LOCALIZED,
			'meta_keywords'=>LOCALIZED,
			'meta_description'=>LOCALIZED,
			'name'=>LOCALIZED,
			'seo_name',
			'page_content'=>LOCALIZED,
			'date_added',
			'status',
			'admin_only',
			'show_on_menu',
			'menu_click',
			'secured',
			'external_url',
			'lang_code'=>LOCALIZED,
			'is_include',
			'display_targets',
			'cache_page',
			'menu_class',
			'open_new_window',
			'page_id'
		));
		
		$new = $params['new'];
		
		$params['display_targets'] = (is_array($params['display_targets[]']) ? join(',', $params['display_targets[]']) : $params['display_targets[]']);
		$params['use_lang'] = $params['lang_code'];
		$params['page_content'] = $this->getPageData($params);
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($params,true)."\n",FILE_APPEND);
		$params = $this->getLocalizedPageData($params);
		$params['date_added'] = date('Y-m-d H:i:s');

		$params['meta_title'] = lum_htmlEncode(stripslashes($params['meta_title']));
		$params['meta_keywords'] = lum_htmlEncode(stripslashes($params['meta_keywords']));
		$params['meta_description'] = lum_htmlEncode(stripslashes($params['meta_description']));
		$params['name'] = lum_htmlEncode(stripslashes($params['name']));
		$params = $this->getLocalizedStrings($params);
		
		$success = false;

		if ($new)
		{
			$id = parent::insert($params);
			if ($id)
			{
				$success = true;
				// might not be able to do triggers on mysql server so
				// do it here instead
				$this->updateOrder($id);
				$this->rebuildTree();
			}
		}
		else
		{
			$success = parent::update($params);
		}
		
		if (!$success)
			return lum_showError(parent::getError());
		else
			return lum_showSuccess();
	}

	private function getLocalizedStrings($params)
	{
		list($langs, $count) = lum_call('Languages', 'getList', array('status'=>1, 'sort'=>'def desc, language', 'dir'=>'asc'));
		foreach ($langs as $lang)
		{
			if ($lang['def'])
				continue;
			
			$params['use_lang'] = $lang['lang_code'];
			$params[$lang['lang_code'].'-meta_title'] = lum_htmlEncode(stripslashes($params[$lang['lang_code'].'-meta_title']));
			$params[$lang['lang_code'].'-meta_keywords'] = lum_htmlEncode(stripslashes($params[$lang['lang_code'].'-meta_keywords']));
			$params[$lang['lang_code'].'-meta_description'] = lum_htmlEncode(stripslashes($params[$lang['lang_code'].'-meta_description']));
			$params[$lang['lang_code'].'-name'] = lum_htmlEncode(stripslashes($params[$lang['lang_code'].'-name']));
		}
		return $params;
	}	

	private function updateOrder($id)
	{
		$sql = 'update '.$this->_table.'
			set `order` = (
			   select max(`order`) from (
			      select `order` from '.$this->_table.'
			   ) as x
			   )+1
			where page_id = '.$id;
		$this->_db->doQuery($sql);
	}

	private function getLocalizedPageData($params)
	{
		list($langs, $count) = lum_call('Languages', 'getList', array('status'=>1, 'sort'=>'def desc, language', 'dir'=>'asc'));
		foreach ($langs as $lang)
		{
			if ($lang['def'])
				continue;
			
			$params['use_lang'] = $lang['lang_code'];
			$params[$lang['lang_code'].'-page_content'] = $this->getPageData($params);
		}
		return $params;
	}

	public function getTemplateList($params)
	{
		$theme = lum_getDefaultDisplayTargetTheme();
		$installed = array();
		if ($handle = opendir(BASE_TEMPLATE_PATH.$theme)) 
		{
		    while (false !== ($file = readdir($handle))) 
		    {
			if ($file != '.' && $file != '..' && is_file(BASE_TEMPLATE_PATH.$theme.'/'.$file)) 
			{
				$data = file_get_contents(BASE_TEMPLATE_PATH.$theme.'/'.$file);
				$lines = explode("\n", $data);
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",count($lines)."\n",FILE_APPEND);
				
				if (count($lines)>2)
				if (strpos($lines[2], "Template:") !== false)
				{
					if (!lum_requirePermission('Pages\Can See Hidden Templates', false))
					{
						if (isset($lines[4]) && stripos($lines[4], 'hidden') !== false && stripos($lines[4], 'true') !== false)
						{
							continue;
						}
					}
					
					$temp = explode(': ', $lines[2]);
					$name = trim($temp[1]);
					$temp = explode(': ', $lines[3]);
					$description = trim($temp[1]);
					$installed[$name] = $file;
						
					
				}
			}
		    }
		}
		
		ksort($installed);
		$arr = array();
		foreach ($installed as $name=>$file)
		{
			$arr[] = array("name"=>$name, "file"=>$file);
		}
		return lum_showSuccess($arr, count($arr));
	}

	public function getIncludesList($params)
	{
		$sql = 'select * from '.$this->_table.' t left join '.$this->_table.'_localized lt on lt.page_id = t.page_id where is_include = 1 and status = 1 and lang_code = ? order by name asc';
		//var_dump($sql);
		
		$value_array = array($params['lang_code']);
		//var_dump($value_array);
		$rows = $this->_db->getRows($sql, $value_array, true);
		if ($rows)
			return $rows;
		
		return false;
	}	

	public function saveStructure($params)
	{
		$order = 2; // start after home which is 1
		if (isset($params['on_menu']))
		{
			foreach ($params['on_menu'] as $obj)
			{
				if ($obj->item_id == 1 || $obj->item_id == 'root') // dont mess with the home page
					continue;
				
				$sql = 'update '.$this->_table.' set show_on_menu = 1, parent_id = ?, `order` = ? where '.$this->_key.' = ?';
				
				if ($obj->parent_id == 'root')
					$obj->parent_id = 1;
					
				$value_array = array($obj->parent_id, $order, $obj->item_id);
				if ($this->_db->doQuery($sql, $value_array) === false)
				{
					if (strpos($this->_db->getError(), 'Duplicate') !== false)
						return "You cannot have two pages with the same URL Name under the same Parent page";
					
					return $this->_db->getError();
				}
				$order++;
			}
		}

		if (isset($params['not_on_menu']))
		{
			foreach ($params['not_on_menu'] as $obj)
			{
				if ($obj->item_id == 1 || $obj->item_id == 'root') // dont mess with the home page
					continue;

				$sql = 'update '.$this->_table.' set show_on_menu = 0 where '.$this->_key.' = ?';
				$value_array = array($obj->item_id);

				if ($this->_db->doQuery($sql, $value_array) === false)
				{
					if (strpos($this->_db->getError(), 'Duplicate') !== false)
						return "You cannot have two pages with the same URL Name under the same Parent page";
					
					return $this->_db->getError();
				}
			}
		}

		$sql = "select page_id from lum_pages where show_on_menu = 0 order by is_include asc, page_id asc";
		$rows = $this->_db->getRows($sql, null, true);

		if ($rows)
		{
			foreach ($rows as $row)
			{
				$sql = "update lum_pages set `order` = ? where page_id = ?";
				$value_array = array($order, $row['page_id']);
				$this->_db->doQuery($sql, $value_array);
				$order++;
			}
		}

		
		$this->rebuildTree();
		return null;
	}

	public function getNavList($params)
	{
		$find_template = null;
		if (isset($params['template']) && $params['template'] != '')
			$find_template = $params['template'];
			
		if (isset($params['include_root']))
			$include_root = $params['include_root'];
		else $include_root = 0;
			
		$tree = $this->getTree(1, $include_root, isset($params['show_on_menu']), $find_template, $params['lang_code']);
		return array($tree, count($tree));
	}

	private function rebuildTree($parent = 1, $left = 1) 
	{ 
		// the right value of this node is the left value + 1 
		$right = $left+1; 
		
		// get all children of this node 
		$sql = 'SELECT page_id FROM '.$this->_table.' WHERE parent_id=? order by `order` asc';
		$value_array = array($parent);
		$rows = $this->_db->getRows($sql, $value_array, true);

		foreach ($rows as $row)
		{
			// recursive execution of this function for each 
			// child of this node 
			// $right is the current right value, which is 
			// incremented by the rebuild_tree function 
			$right = $this->rebuildTree($row['page_id'], $right); 
		} 
		
		// we've got the left value, and now that we've processed 
		// the children of this node we also know the right value 
		$sql = 'UPDATE '.$this->_table.' SET lft=?, rgt=? WHERE page_id = ?';
		$value_array = array($left, $right, $parent); 
		if ($this->_db->doQuery($sql, $value_array) === false)
		{
			$this->setError($this->_db->getError(), __FUNCTION__, $sql, $value_array);
			return false;
		}
		
		// return the right value of this node + 1 
		return $right+1; 
	} 

	public function getPageTree($params)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt","PARAMS:  ".print_r($params,true)."\n",FILE_APPEND);

		if (!isset($params['next__show_parent_in_url'])) $params['next__show_parent_in_url'] = true;
		if (!isset($page['show_parent_in_url'])) $page['show_parent_in_url'] = true;
			
		if (!isset($params['page_tree']))
		{
			$params['page_tree'] = array();
			$params['page_tree'][] = array('seo_name'=>$params['seo_name'], 'name'=>$params['name'], 'show_parent_in_url'=> $params['show_parent_in_url'] , 'menu_click'=>(isset($params['menu_click'])? $params['menu_click'] : 0));
		}

		$page = $this->get(array("page_id"=>$params['parent_id'], 'lang_code'=>$params['lang_code']));
//file_put_contents(LOGS_PATH."tmp.out.txt","PAGE:  ".print_r(array("page_id"=>$params['parent_id'], 'lang_code'=>$params['lang_code']),true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($page,true)."\n",FILE_APPEND);
		if ($params['next__show_parent_in_url'])
			$params['page_tree'][] = array('seo_name'=>$page['seo_name'], 'name'=>$page['name'], 'show_parent_in_url'=>$page['show_parent_in_url'], 'menu_click'=>$page['menu_click']);
			
		if (isset($page['parent_id'])) $params['parent_id'] = $page['parent_id'];
		if (isset($page['show_parent_in_url'])) $params['next__show_parent_in_url'] = $page['show_parent_in_url'];
		if (isset($page['show_parent_in_url'])) $params['show_parent_in_url'] = $page['show_parent_in_url'];
		if (isset($page['menu_click'])) $params['menu_click'] = $page['menu_click'];

		if (isset($page['page_id']) && ($page['page_id'] > 1))
		{
			$params['page_tree'] = $this->getPageTree($params);
		}
		return $params['page_tree'];
	}
	
	public function getPermalink($row)
	{
		if ($row['external_url'])
			return $row['external_url'];
			
		$page_tree = $this->getPageTree(array('parent_id'=>$row['parent_id'], 'seo_name'=>$row['seo_name'], 'name'=>$row['name'], 'lang_code'=>(isset($row['lang_code'])? $row['lang_code']:'')));

		$page_tree = array_reverse($page_tree);
		
		
		
		$permalink = '/';
		
		if (defined('MULTI_LINGUAL') && MULTI_LINGUAL)
			$permalink .= lum_getCurrentLanguage();
		
		for ($i=0;$i<count($page_tree);$i++)
		{
			if ($page_tree[$i]['seo_name'] && $page_tree[$i]['seo_name'] != DEFAULT_HOME_SEO_NAME)
			{
				if ($permalink != '/')
					$permalink .= '/';
					
				$permalink .= $page_tree[$i]['seo_name'];
			}
		}
		return $permalink;
	}
	
	public function getMenu($params)
	{
		$tree = $this->getTree($params['page_id'], $params['include_root'], $params['show_on_menu'], null, lum_getCurrentLanguage());
		$node_count = 0;

		$menu = array();
		$refs = array();
		if (!isset($is_submenu)) $is_submenu = false;
		for ($i=0;$i<count($tree);$i++)
		{
			// let's look for any pages associated with this nav id
			$bcheck = ($tree[$i]['parent_id'] > 1);
			if ($is_submenu)
				$bcheck &= isset($menu[$tree[$i]['parent_id']]);

			if ($bcheck)
			{
				if (isset($menu[$tree[$i]['parent_id']]))
				{
					$tree[$i]['children'] = array();
					$tree[$i]['name'] = str_replace("&nbsp;", "", $tree[$i]['name']);
					$tree[$i]['seo_name'] = str_replace("&nbsp;", "", $tree[$i]['seo_name']);
					$tree[$i]['external_url'] = str_replace("&nbsp;", "", $tree[$i]['external_url']);
					$tree[$i]['permalink'] = $this->getPermalink($tree[$i]);
					$menu[$tree[$i]['parent_id']]['children'][] = $tree[$i];
					$refs[$tree[$i]['page_id']] = &$menu[$tree[$i]['parent_id']]['children'][count($menu[$tree[$i]['parent_id']]['children']) - 1];
				}
				if (isset($refs[$tree[$i]['parent_id']]))
				{
					$tree[$i]['children'] = array();
					$tree[$i]['name'] = str_replace("&nbsp;", "", $tree[$i]['name']);
					$tree[$i]['seo_name'] = str_replace("&nbsp;", "", $tree[$i]['seo_name']);
					$tree[$i]['permalink'] = $this->getPermalink($tree[$i]);
					$tree[$i]['external_url'] = str_replace("&nbsp;", "", $tree[$i]['external_url']);
					$refs[$tree[$i]['parent_id']]['children'][] = $tree[$i];
					$refs[$tree[$i]['page_id']] = &$refs[$tree[$i]['parent_id']]['children'][count($refs[$tree[$i]['parent_id']]['children']) - 1];
				}
			}
			else 
			{
				$menu[$tree[$i]['page_id']] = $tree[$i];
				$menu[$tree[$i]['page_id']]['children'] = array();
				$menu[$tree[$i]['page_id']]['name'] = str_replace("&nbsp;", "", $menu[$tree[$i]['page_id']]['name']);
				$menu[$tree[$i]['page_id']]['permalink'] = $this->getPermalink($tree[$i]);
			}		
		}
		return $menu;		
	}

	private function getTree($root = 1, $include_root = false, $show_on_menu = 1, $template = null, $lang_code = 'en') 
	{

		$tree = array();
		$node_count = 0;
		// retrieve the left and right value of the $root node 
		$sql = 'SELECT lft, rgt FROM '.$this->_table.' WHERE page_id='.$root; 
		$row = $this->_db->getRow($sql, null, true);
		
		// start with an empty $right stack 
		$right = array(); 
		
		// now, retrieve all descendants of the $root node 
		$add = "";
		if (!$include_root)
			$add = " and lft <> ".$row['lft'];
		
		$show = 'and show_on_menu = 0';
		if ($show_on_menu == 1)
			$show = 'and show_on_menu = 1';
		if ($show_on_menu == 'all')
			$show = '';
			

		$find_template = '';
		if ($template)
			$find_template = 'and template = \''.$template.'\'';
			
		$sql = 'SELECT t.page_id, lft, rgt, tl.name, menu_class, menu_click, external_url, seo_name, parent_id, show_parent_in_url, open_new_window FROM '.$this->_table.' t left join '.$this->_table.'_localized tl on tl.page_id = t.page_id and tl.lang_code = ? WHERE lft BETWEEN ? AND ? '.$add.' and status = 1 and is_include = 0 '.$show.' '.$find_template.' ORDER BY lft ASC;';

		$value_array = array($lang_code, $row['lft'], $row['rgt']);
		$rows = $this->_db->getRows($sql, $value_array, true);
		if ($rows)
		{ 
			foreach ($rows as $row)
			{
				// only check stack if there is one 
				if (count($right)>0) 
				{ 
					// check if we should remove a node from the stack 
					while (count($right) > 0 && $right[count($right)-1]<$row['rgt']) 
					{ 
						array_pop($right); 
					} 
				} 
		
				// display indented node title 
				if ($include_root)
				{
					$row['name'] = str_repeat('&nbsp;&nbsp;&nbsp;',count($right)).$row['name'];
					$tree[$node_count] = $row;
					$tree[$node_count]['depth'] = count($right);	   	   
					$node_count++;
				}
				else 
				{
					if ($row['page_id'] != $root)
					{
						$row['name'] = str_repeat('&nbsp;&nbsp;&nbsp;',count($right)).$row['name'];
						$tree[$node_count] = $row;
						$tree[$node_count]['depth'] = count($right);
						$node_count++;
					}
				}
				// add this node to the stack 
				$right[] = $row['rgt']; 
			} 
		}
		return $tree;
	} 

	private function getPageData($params)
	{
	   	$target_cms = array();
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($params,true)."\n",FILE_APPEND);

		foreach ($params as $param=>$value)
		{
			// format is DISPLAY_TARGET::cms_<whatever> for the default language
			// and lang_code::DISPLAY_TARGET::cms_<whatever> for localized content
			if (strpos($param, "::cms_") !== false)
			{
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($param,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($value,true)."\n",FILE_APPEND);

				if (isset($params['use_lang']) && strpos($param, $params['use_lang'].'::') === false)
					continue;

				list($lang_code, $target, $cms_param) = explode('::', $param);
				
				if (!isset($target_cms[$target]))
					$target_cms[$target] = array();
				$lookup = $lang_code.'::'.$target.'::'."cms_";
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($lookup,true)."\n",FILE_APPEND);
				
				$param_new = str_replace($lookup, "", $param);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($param_new,true)."\n",FILE_APPEND);
				$temp = explode(' (', $param_new);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($temp,true)."\n",FILE_APPEND);
				$param_new = $temp[0];
				$param_new = str_replace("_", " ", $param_new);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($param_new,true)."\n",FILE_APPEND);
				unset($params[$param]);
				
				$target_cms[$target][$param_new] = str_replace("\r", "", $value);
				$target_cms[$target][$param_new] = str_replace("\n", "", $target_cms[$target][$param_new]);
				$target_cms[$target][$param_new] = str_replace("\r\n", "", $target_cms[$target][$param_new]);
				$target_cms[$target][$param_new] = str_replace('../../userfiles', '/userfiles', $target_cms[$target][$param_new]);
				$target_cms[$target][$param_new] = str_replace('../userfiles', '/userfiles', $target_cms[$target][$param_new]);
				$target_cms[$target][$param_new] = lum_htmlEncode($target_cms[$target][$param_new]);
			}
		}
		return base64_encode(serialize($target_cms));
	}

	public function get($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'page_id'
		));
		return parent::get($params);
	}	

	public function getLocalized($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'page_id'
		));
		return parent::getLocalized($params);
	}	

	public function getByCode($params)
	{
		parent::setRequiredParams(array
		(
			'lang_code',
			'bit',
			'last_bit_id',
			'is_default' // is the default language of the site
		));

		$value_array = array($params['lang_code'], $params['bit']);
		$sql = "select * from ".$this->_table." c left join ".$this->_table."_localized l on l.page_id = c.page_id where l.lang_code = ? and c.seo_name = ?";
		$rows = $this->_db->getRows($sql, $value_array, true);

		if ($rows)
		{
			$possible = false;
			foreach ($rows as $row)
			{
				// if all of these match it's definitley the one!
				if ($row['parent_id'] > 1 && $row['show_parent_in_url'] && $row['parent_id'] == $params['last_bit_id'])
				{
					return $row;
				}
				
				// could be this one if they don't want to show the parent in the url
				if ($row['parent_id'] > 1 && !$row['show_parent_in_url'])
				{
					$possible = $row;
				}
				elseif ($params['last_bit_id'] == 0 && $row['parent_id'] <= 1)
				{
					$possible = $row;
				}

			}
			if ($possible)
				return $possible;
		}
		else
		{
			return false;
		}
	}		
	
	public function delete($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'page_id'
		));
		return parent::delete($params);
	}		
	
	
	public function getList($params, $table = null)
	{
		$params['filters'] = null;
		$params['values'] = array();
		$params['where'] = '';
		
		if (isset($params['lang_code']))
		{
			$params['filters'] .= ' lum_pages_localized.lang_code = ? ';
			$params['values'][] = $params['lang_code'];
		}
		if (isset($params['query']) && $params['query'] != '')
		{
			$params['where'] .= ($params['where'] == '' ? '' : ' and ');
			$params['where'] .= "(name like '%%".addslashes($params['query'])."%%' or seo_name like '%%".addslashes($params['query'])."%%')";
		}
		
		if (isset($params['template']) && $params['template'] != '')
		{
			$params['where'] .= ($params['where'] == '' ? '' : ' and ');
			$params['where'] .= " template = '".addslashes($params['template'])."' ";
		}
		
		$params['join'] = ' left join lum_pages_localized plp on plp.page_id = t.parent_id and plp.lang_code = lum_pages_localized.lang_code ';
		$params['select'] = ' t.*, lum_pages_localized.*, plp.name as parent_name ';
		
		$rows = parent::getList($params);
		//var_dump($this->sql_query);
		return $rows;
	}
	
	public function changeStatus($params, $table = null, $key = null)
	{
		parent::setRequiredParams(array
		(
			'page_id',
			'status'
		));
		return parent::changeStatus($params);
	}	
}

class LuminancePagesView extends LuminanceView
{
	private $_last_page_id;
	
	function __construct($registry)
	{
		$this->lumRegistry = $registry;
	}
	
	public function displayContent($data, $is_last)
	{
		// we may need these later in other plugins so we fill them in the string table
		$this->setString('[PAGE_ID]', $data['page_id']);
		$this->setString('[SEO_NAME]', $data['seo_name']);
		$this->setLastPageId($data['page_id']);
		$this->lumRegistry->page_id = $data['page_id'];
		// that's all the processing we need to do for this plugin 
		// if we're not the last bit in the url
		if (!$is_last)
		{
			return false;	
		}	

		$this->setString('[PAGE_TITLE]', stripslashes($data['name']));
		
		if ($data['meta_title'] != '')
			$this->setString('[META_TITLE]', $data['meta_title']);
		if ($data['meta_keywords'] != '')
			$this->setString('[META_KEYWORDS]', $data['meta_keywords']);
		if ($data['meta_description'] != '')
			$this->setString('[META_DESCRIPTION]', $data['meta_description']);
		
		
		if ($data['admin_only'] && !lum_call('Users', 'isSignedIn'))
		{
			lum_redirect("/");
		}
		
		
		$data['page_tree'] = lum_call('Pages', 'getPageTree', $data);
		$data['page_tree'] = array_reverse($data['page_tree']);
		$breadcrumbs = $this->getBreadcrumbs($data['page_tree']);
		$this->setString('{BREADCRUMBS}', $breadcrumbs);
				
		$html = $this->fillTemplate($data);
		echo $html;
		
		// yes, stop processing further bits!
		return true;		
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
	
	private function setLastPageId($page_id)
	{
		$this->_last_page_id = $page_id;
	}
	
	public function getLastPageId()
	{
		return $this->_last_page_id;
	}
	
	public function buildMenu($menu, $use_submenu = false)
	{
		if ($menu)
		{
			$html = "";
			$key = key($menu); 
			if ($use_submenu && count($menu[$key]['children']) > 0)
				$menu = $menu[$key]['children'];
	
			$i=0;
			foreach ($menu as $row)
			{
				$html .= $this->buildMenuItem($row, 0, ($i==0), ($i==(count($menu) - 1)));
				$i++;
			}
			echo $html;
		}		
	}
	
	private function buildMenuItem($row, $level, $first, $last)
	{
		$name = $row['name'];
		$permalink = $row['permalink'];
		$page_id = $row['page_id'];
		$children = '';

		if (count($row['children']) > 0)
		{
			$level++;

			if ($level <= SUBMENUS)
			{
				ob_start();
				include(TEMPLATES_PATH.'nav_parent.inc.php');
				$children .=  ob_get_clean();
			}
			
			foreach ($row['children'] as $child)
			    $children .= $this->buildMenuItem($child, $level, false, false);

			if ($level <= SUBMENUS)
			{
				ob_start();
				include(TEMPLATES_PATH.'nav_close_parent.inc.php');
				$children .=  ob_get_clean();
			}
		}
		
		$open_new_window = false;
		if ($row['open_new_window'])
			$open_new_window = true;
		
		$menu_class='';
		if ($row['menu_class'] != '')
			$menu_class = $row['menu_class'];
		
		$template = 'nav_clickable.inc.php';
		if (!$row['menu_click'])
			$template = 'nav_not_clickable.inc.php';
		
		if (!$row['menu_click'] && CURRENT_DISPLAY_TARGET != 'DISPLAY_PC')
			return '';
		
		ob_start();
		include(TEMPLATES_PATH.$template);
		$item = ob_get_clean();
		return $item;
	}
	
	public function getBreadcrumbs($page_tree)
	{
		$breadcrumbs = '';
		if ($page_tree)
		{
			$url = '';
			$last = count($page_tree) - 1;
			$used_lang_code = false;
			for ($i=0;$i<count($page_tree);$i++)
			{
				if (isset($page_tree[($i + 1)]) && $page_tree[$i]['seo_name'] != DEFAULT_HOME_SEO_NAME)
				{
					if (!$page_tree[($i + 1)]['show_parent_in_url'])
					{
						continue;
					}
				}
				
				if ($breadcrumbs != '')
					$breadcrumbs .= ' > ';
				
				if ($url != '/')
				{
					$url .= '/';
					if (!lum_isDefaultLanguage() && !$used_lang_code)
					{
						$used_lang_code = true;
						$url .= lum_getCurrentLanguage();
					}
				}
				
				
				if ($page_tree[$i]['seo_name'] != DEFAULT_HOME_SEO_NAME)
				{
					if (!lum_isDefaultLanguage() && !$used_lang_code)
						$url .= '/';
						
					$url .= $page_tree[$i]['seo_name'];
				}
				
				if ($i < $last && $page_tree[$i]['menu_click'])
					$breadcrumbs .= '<a href="'.$url.'">';
				
				$breadcrumbs .= $page_tree[$i]['name'];
				
				if ($i < $last && $page_tree[$i]['menu_click'])
					$breadcrumbs .= '</a>';
			}
		}
		return $breadcrumbs;
	}
}


?>
