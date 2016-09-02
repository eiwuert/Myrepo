<?php
class LuminanceView
{
	protected $lumRegistry;
	protected $_strings;
	protected $page_content = array();
	
	public function __construct()
	{
	}

	public function displayContent($data, $is_last){}
	public function getStrings(){}
	protected function setString($key, $value){}
	
	protected function getTemplate($template, $page_data)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r(TEMPLATES_PATH.$template,true)."\n",FILE_APPEND);

		if (is_file(TEMPLATES_PATH.$template))
		{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);

			ob_start();
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
			include(TEMPLATES_PATH.$template);
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
			$template = ob_get_contents();
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($template,true)."\n",FILE_APPEND);

			ob_end_clean();

//			$template = file_get_contents(TEMPLATES_PATH.$template);
//file_put_contents(LOGS_PATH."tmp.out.txt","OB ended clean\n",FILE_APPEND);

			return $template;
		}
		return null;
	}		
	
	public static function loadTemplate($template, $display_targets = null)
	{
		if (!$display_targets)
		{
			$display_targets = unserialize(TARGETS);
		}
		else
		{
			$display_targets = explode(',', $display_targets);
		}



		$target_templates = array();
		$targets = array();
		foreach ($display_targets as $target)
		{
			$targets[$target] = constant($target);
			$theme = lum_getDisplayTargetTheme($target);
			if (file_exists(BASE_TEMPLATE_PATH.$theme.'/'.$template))
			{
				$template_data = file_get_contents(BASE_TEMPLATE_PATH.$theme.'/'.$template);
				$pattern = '!\[cms(.*?)\]!';
				$matches = array();
				preg_match_all($pattern, $template_data, $matches);
				$target_templates[$target] = $matches[1];
			}
		}

		return array($target_templates, $targets, 1);    			
	}		
	
	protected function fillTemplate($data)
	{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($data,true)."\n",FILE_APPEND);

		$this->page_content[$data['page_id']] = unserialize(base64_decode($data['page_content']));
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($this->page_content[$data['page_id']],true)."\n",FILE_APPEND);			
		$template = $this->getTemplate($data['template'], $data);
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($template,true)."\n",FILE_APPEND);
		$variables = $this->loadTemplate($data['template']);
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($variables,true)."\n",FILE_APPEND);
		$variables = $variables[0];

		if ($variables)
		{
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($variables,true)."\n",FILE_APPEND);
			$current_target = CURRENT_DISPLAY_TARGET;
			if (!isset($this->page_content[$data['page_id']][CURRENT_DISPLAY_TARGET]))
				$current_target = 'DISPLAY_PC';
				
			foreach ($variables as $target=>$replace)
			{
				if ($target != $current_target)
					continue;
				
				foreach ($replace as $cms)
				{
					$cmsval = $cms;
					if (substr($cmsval,0,8) == 'include ')
					{
						$cmsval = substr($cmsval,8);
						/**
						 * Includes are a special case the value is actually a page id
						 * So...that means we need to find out which template this include is using
						 * then fill the template just like we're doing here, return the new content
						 * and replace the cmsinclude with the new page content.
						 **/
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($this->page_content,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($target,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($cmsval,true)."\n",FILE_APPEND);

	
						$include = lum_call('Pages', 'get', array('page_id'=>$this->page_content[$data['page_id']][$target][$cmsval], 'lang_code'=>$this->lumRegistry->language->lang_code));
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($include,true)."\n",FILE_APPEND);
						
						$inc_content = $this->fillTemplate($include);
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($inc_content,true)."\n",FILE_APPEND);

						$template = str_replace("[cms$cms]", $inc_content, $template);
//file_put_contents(LOGS_PATH."tmp.out.txt",__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($template,true)."\n",FILE_APPEND);
					}
					else 
					{
						$type = "";
//$debug_trigger = false;
						if (substr($cmsval,0,6) == 'image '){
							$cmsval = substr($cmsval,6);
//$debug_trigger = true;
						}
						if (substr($cmsval,0,5) == 'text '){
							$cmsval = substr($cmsval,5);
						}
						if (substr($cmsval,0,5) == 'page '){
							$cmsval = substr($cmsval,5);
						}
						if (substr($cmsval,0,9) == 'richtext ')
						{
							$type = "richtext";
							$cmsval = substr($cmsval,9);
						}
						if (isset($this->page_content[$data['page_id']][$target][$cmsval]))
						{
//if ($debug_trigger){
//file_put_contents(LOGS_PATH."tmp.out.txt","GOOD:".__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($this->page_content[$data['page_id']],true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($target,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($cmsval,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r("[cms$cms]",true)."\n",FILE_APPEND);
//}
							$template = str_replace("[cms$cms]", stripslashes(lum_htmlDecode($this->page_content[$data['page_id']][$target][$cmsval])), $template);
						}
						else 
						{
							$template = str_replace("[cms$cms]", '', $template);
//if ($debug_trigger){
//file_put_contents(LOGS_PATH."tmp.out.txt","BAD:".__FILE__.' :: '.__METHOD__.' :: '.__LINE__."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($this->page_content[$data['page_id']],true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($target,true)."\n",FILE_APPEND);
//file_put_contents(LOGS_PATH."tmp.out.txt",print_r($cmsval,true)."\n",FILE_APPEND);
//}
						}
						$template = str_replace(BASE_URL, '/', $template);
					}
				}
			}
		}		
		return $template;		
	}	
	
}

?>
