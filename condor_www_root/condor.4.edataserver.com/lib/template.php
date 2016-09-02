<?php
require_once('attachment.php');
require_once('Template/Parser.php');
require_once('Template/ArrayTokenProvider.php');

/**
 * This class defines a Condor template.
 *
 * @author Brian Feaver
 */
class Template
{
	private $sql;
	private $template_id;
	private $template_data;
	private $subject;
	private $template_name;
	private $attached_templates;
	private $attachment_data;
	private $content_type;
	private $company_id;
	
	const TOKEN_IDENT = '%%%';
	
	function __construct(&$sql)
	{
		$this->sql =& $sql;
		$this->template_id = NULL;
		$this->template_name = NULL;
		$this->template_data = NULL;
		$this->attached_templates = array();
		$this->attachment_data = array();
		$this->content_type = NULL;
		$this->company_id = NULL;
	}
	
	/**
	 * Returns the template ID for the given name.
	 *
	 * @param string $name
	 * @return int
	 */
	public function Get_Template_Id($name = NULL, $company_id = NULL)
	{
		$ret_val = FALSE;
		if(isset($this->template_id))
		{
			$ret_val = $this->template_id;
		}
		else if(is_string($name) && is_numeric($company_id))
		{
			$this->company_id = $company_id;
			$tpl = $this->Get_Template_Id_From_Cache($name, $company_id);
			if(!is_object($tpl))
			{
				$tpl = $this->Get_Template_Id_From_DB($name, $company_id);
			}
			if(is_object($tpl))
			{
				$this->template_name = $tpl->name;
				$ret_val = $this->template_id = intval($tpl->template_id);
			}
		}
		return $ret_val;
	}
	
	public function Delete_Cache($template_id, $company_id, $template_name = NULL, $mode = EXECUTION_MODE)
	{
		$ret_val = false;
		if(!is_string($template_name))
		{
			$template_name = $this->Get_Name_By_Id($template_id);
		}
		if(is_string($template_name))
		{
		
			$cache = Cache::Singleton($mode);
			$cache->delete($this->Get_Template_Cache_Key($template_id));
			$cache->delete($this->Get_Template_Id_Cache_Key($template_name, $company_id));
			
			$cids = $this->Get_Shared_With($template_id);
			if(is_array($cids))
			{
				foreach($cids as $company_id)
				{
					$cache->delete($this->Get_Template_Id_Cache_Key($template_name, $company_id));
				}
			}
			
			$ret_val = true;
		}
		return $ret_val;
	}
	
	/**
	 * Returns an array of company ids that this template_id is 
	 * shared with
	 *
	 * @param int $template_id
	 * @return array
	 */
	protected function Get_Shared_With($template_id)
	{
		$return = false;
		$query = "SELECT 
			company_id 
		FROM
			shared_template
		WHERE
			template_id={$template_id}
		";
		try 
		{
			$res = $this->sql->Query($query);
			$return = array();
			while(($row = $res->Fetch_Object_Row()))
			{
				$return[] = $row->company_id;
			}
		}
		catch (Exception $e)
		{
			$return = false;
		}
		return $return;
	}
	
	private function Remove_Shared_by_Id($template_id)
	{
		$ret_val = true;
		$query = "DELETE FROM 
			shared_template 
		WHERE 
			template_id=$template_id";
		try 
		{
			$this->sql->Query($query);
			
		}
		catch (Exception $e)
		{
			$ret_val = false;
		}
		return $ret_val;
	}
	
	private function Get_Name_By_Id($template_id)
	{
		$ret_val = FALSE;
		$query = "SELECT
					name
				FROM
					template
				WHERE
					template_id={$template_id}
		";
		try 
		{
			$res = $this->sql->Query($query);
			if($row = $res->Fetch_Object_Row())
			{
				$ret_val = $row->name;
			}
		}
		catch (Exception $e)
		{
			
		}
		return $ret_val;
	}
	
	/**
	 * Checks memcache for the template id instead
	 * of querying the database. Returns either 
	 * the object containing name/template_id 
	 * or false.
	 *
	 * @param string $name
	 * @param int $company_id
	 * @return stdClass
	 */
	private function Get_Template_Id_From_Cache($name, $company_id)
	{
		$ret_val = FALSE;
		if(is_numeric($company_id))
		{
			$this->company_id = $company_id;
		}
		
		$key = $this->Get_Template_Id_Cache_Key($name, $company_id);
		$cache = Cache::Singleton(EXECUTION_MODE);
		$tpl = $cache->Get($key);
		if(is_object($tpl))
		{
			$ret_val = $tpl;
		}
		return $ret_val;
	}
	
	
	/**
	 * Query the database for a template id based on name/company id
	 * provided. Returns an stdClass object containing the template
	 * name and template_id
	 *
	 * @param string $name
	 * @param int $company_id
	 * @return object
	 */
	private function Get_Template_Id_From_DB($name, $company_id )
	{
		$ret_val = FALSE;
		
		// No such thing as magic
		if(get_magic_quotes_gpc())
		{
			$name = stripslashes($name);
		}
			
		$name = $this->sql->Escape_String($name);
		$query = "SELECT
					tpl.name,
					tpl.template_id
				FROM
					template tpl
				LEFT JOIN 
					shared_template st 
				ON 
					(tpl.template_id = st.template_id AND st.company_id = $company_id)
				WHERE
					tpl.name = '$name'
				AND
					(tpl.company_id = $company_id OR st.company_id = $company_id)
				AND
					tpl.status = 'ACTIVE'";
			
		$result = $this->sql->Query($query);
			
		if(($row = $result->Fetch_Object_Row()))
		{
			$ret_val = $row;
		}
		//Now that we have the results, cache them if we can.
		$cache = Cache::Singleton(EXECUTION_MODE);
		$key = $this->Get_Template_Id_Cache_Key($name,$company_id);
		$cache->Set($key,$ret_val,18000);
		
		return $ret_val;
	}
	
	/**
	 * Take the 
	 *
	 * @param string $name
	 * @param int $company_id
	 * @return string
	 */
	private function Get_Template_Id_Cache_Key($name, $company_id)
	{
		$cache_id = $this->Get_Cache_Id();
		return "condor_template_id_{$name}_{$company_id}_{$cache_id}";
	}
	
	/**
	 * Fetches/stores a cache id so that we 
	 * can easily clear the entire condor
	 * cache should the need arise.
	 *
	 * @return unknown
	 */
	private function Get_Cache_Id()
	{
		$cache = Cache::Singleton(EXECUTION_MODE);
		$key = $cache->get('condor_template_cache_id');
		if(is_numeric($key))
		{
			return $key;
		}
		else 
		{
			$cache->Set('condor_template_cache_id',1,1800000);
			return 1;
		}	
	}
	
	/**
	 * Returns the name of the template.
	 *
	 * @return string
	 */
	public function Get_Name()
	{
		return $this->template_name;
	}
	
	/**
	 * Returns the templates content-type.
	 *
	 * @return string
	 */
	public function Get_Content_Type()
	{
		return $this->content_type;
	}
	
	
	public function Load_Template_By_Id($id, $content_type = CONTENT_TYPE_TEXT_HTML)
	{
		$ret_val = FALSE;
		$tpl = $this->Load_Template_By_Id_From_Cache($id);
		if(!is_object($tpl))
		{
			$tpl = $this->Load_Template_By_Id_From_Db($id, $content_type);
			if(is_object($tpl))
			{
				$cache = Cache::Singleton(EXECUTION_MODE);
				$cache->Set($this->Get_Template_Cache_Key($id),$tpl, 18000);
			}
		}
		if(is_object($tpl))
		{
			$this->template_data = $tpl->data;
			$this->subject= $tpl->subject;
			$this->content_type = $tpl->content_type;
			//Now load all the attachments.
			foreach($tpl->attachments as $att)
			{
				switch($att->type)
				{
					case 'FILE':
						$attachment = new Attachment(
							$att->data,
							Attachment::TYPE_FILE,
							$att->uri,
							$att->content_type
						);
						$this->attachment_data[] = $attachment;
						break;
					case 'TEMPLATE':
						$template = new Template($this->sql);
						$template->Load_Template_By_Id($att->attachment_id, $att->content_type);
						$this->attached_templates[] = $template;
						break;
				}
			}
			$ret_val = true;
		}
		return $ret_val;
	}
	
	/**
	 * Generate a key for cached templates based om tpl_id
	 *
	 * @param int $tpl_id
	 * @return string
	 */
	private function Get_Template_Cache_Key($tpl_id)
	{
		$cache_id = $this->Get_Cache_Id();
		return "condor_template_data_{$tpl_id}_{$cache_id}";
	}
	
	/**
	 * Attempts to load a template from memcache. Returns
	 * false or the object contianing the data.
	 *
	 * @param int $id
	 * @return mixed
	 */
	private function Load_Template_By_Id_From_Cache($id)
	{
		$ret_val = false;
		$key = $this->Get_Template_Cache_Key($id);
		$cache = Cache::Singleton(EXECUTION_MODE);
		$ret_val = $cache->Get($key);
		
		return $ret_val;
	}
	
	/**
	 * Loads the template and all it's attachments into the object by ID.
	 *
	 * @param int $id
	 * @return boolean
	 */
	public function Load_Template_By_Id_From_Db($id, $content_type = CONTENT_TYPE_TEXT_HTML)
	{
		$ret_val = FALSE;
		
		if(!isset($this->template_id))
		{
			$this->template_id = $id;
		}
		
		// Get the main template data
		$query = "
			/* File: ".__FILE__.", Line: ".__LINE__." */
			SELECT
				data,
				subject,
				content_type
			FROM
				template
			WHERE
				template_id = $this->template_id";
		
		$result = $this->sql->Query($query);
		
		if(($row = $result->Fetch_Object_Row()))
		{
	
			// The passed in content-type takes precedence over the template content-type
			if($content_type != CONTENT_TYPE_TEXT_HTML)
			{
				$row->content_type = $content_type;
			}
			$row->attachments = $this->Load_Attachments();
			$ret_val = $row;
		}
		
		return $ret_val;
	}
	
	/**
	 * Querys the database for all attachments
	 * And returns it as an awesome array of stdClass objects
	 *
	 */
	private function Load_Attachments()
	{
		$ret_val = array();
		// Get attachments
		$query = "
		/* File: ".__FILE__.", Line: ".__LINE__." */
		SELECT
			ta.type,
			ta.attachment_id,
			IF(ta.type = 'FILE', a.content_type, ta.content_type) AS content_type,
			a.data,
			a.uri
		FROM
			template_attachment ta
			LEFT JOIN attachment a ON ta.attachment_id = a.attachment_id
		WHERE
			ta.template_id = $this->template_id";
		$result = $this->sql->Query($query);

		while(($row = $result->Fetch_Object_Row()))
		{
			$ret_val[] = $row;
		}
		return $ret_val;
	}
	
	/**
	 * Loads the template and all it's attachments into the object by name.
	 *
	 * @param string $name
	 * @return array
	 */
	public function Load_Template_By_Name($name, $company_id)
	{
		$ret_val = FALSE;
		$this->company_id = $company_id;
		
		// This will set the template_id in the class
		if($this->Get_Template_Id($name, $company_id) !== FALSE)
		{
			$ret_val = $this->Load_Template_By_Id($this->template_id);
		}
		
		return $ret_val;
	}
	
	/**
	 * Returns the main template data for this template.
	 *
	 * @return string
	 */
	public function Get_Template_Data()
	{
		$ret_val = FALSE;
		
		if($this->template_data != NULL)
			$ret_val = $this->template_data;
		
		return $ret_val;
	}
	
	/**
	 * Returns the attachment data in an array. This data is only for attachments
	 * that are considered files. If there are no attached files, it returns an
	 * empty array.
	 *
	 * @return array
	 */
	public function Get_Attachment_Data()
	{
		$ret_val = array();
		
		if(!empty($this->attachment_data))
			$ret_val = $this->attachment_data;
		
		return $ret_val;
	}
	
	/**
	 * Returns an array of template attachments or an empty array if there are no
	 * template attachments.
	 *
	 * @return array
	 */
	public function Get_Attached_Templates()
	{		
		return $this->attached_templates;
	}
	
	/**
	 * Returns an array of all tokens in the template.
	 * @return array
	 */
	public function Get_Tokens()
	{
		$parser = new Template_Parser(self::TOKEN_IDENT, new Template_ArrayTokenProvider(array()));
		$parser->setTemplateData($this->template_data);
		return $parser->getTokens(FALSE);
	}
	
	/**
	 * Returns the whole template as an array.
	 *
	 * @return array
	 */
	public function As_Array()
	{
		$ret_val = array();
		
		$data = $this->Get_Template_Data();
		
		// If we don't have template data, just return the empty array
		if($data)
		{
			$file_attachments = $this->Get_Attachment_Data();
			$template_attachments = $this->Get_Attached_Templates();
			
			$ret_val = array(
				'data' => $data,
				'file_attachments' => $file_attachments,
				'template_attachments' => $template_attachments
			);
		}
		
		return $ret_val;
	}
	
	/**
	 * Returns the subject of the template when sent as an email.
	 *
	 * @return string
	 */
	public function Get_Subject()
	{
		return $this->subject;
	}
	
	/**
	 * Returns whether or not this template contains tokens that
	 * require encryption
	 *
	 * @return boolean
	 */
	
	public function Require_Encryption()
	{
		$ret_val = FALSE;
		if(isset($this->template_data) && !empty($this->template_data))
		{
			$tokens = $this->Get_Tokens();
			$encryptable_tokens = $this->Get_Tokens_To_Encrypt();
			$ret_val =(count(array_intersect($tokens,$encryptable_tokens)) > 0);
		}
		return $ret_val;
	}
	
	/**
	 * Grabs all the token names for things 
	 * that should be encrypted. Limits to only
	 * this company if it can, otherwise just grabs
	 * everything.
	 *
	 * @return array
	 */
	private function Get_Tokens_To_Encrypt()
	{
		
		try 
		{
			
			$ret_val = array();
			if(is_numeric($this->company_id))
			{
				$query = 'SELECT
						token
					FROM
						condor_admin.tokens
					WHERE
						company_id = '.$this->company_id.'
					AND
						encrypted = 1
				';
			}
			else 
			{
				$query = 'SELECT 
						token
					FROM
						condor_admin.tokens
					WHERE
						encrypted = 1
				';
			}
			$res = $this->sql->Query($query);
			while($row = $res->Fetch_Object_Row())
			{
				$ret_val[] = substr($row->token,3,strlen($row->token) - 6);
			}
			return $ret_val;
		}
		catch (Exception $e)
		{
			return $ret_val;
		}
			
	}
}
?>
