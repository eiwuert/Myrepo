<?php

	require_once(COMMON_LIB_DIR . "template_parser.1.php");
	require_once(LIB_DIR.'condor_api.php');

	class Condor_Template_Query
	{
		private $server;
		private $mysqli;
		
		public function __construct(Server $server)
		{
			$this->server = $server;
			$this->mysqli = $server->MySQLi();
		}
		
		public function Create_Template($template_name, $template_subject, $template_data, $type = 'DOCUMENT', $content_type='text/html')
		{
			$query = "
				insert into
					".CONDOR_DB_NAME.".template
				set
					template_id = NULL,
					name = '" . $this->mysqli->Escape_String($template_name) . "',
					company_id = " . $this->server->company_id . ",
					user_id = " . $this->server->agent_id . ",
					date_created = now(),
					date_modified = now(),
					subject = '" . $this->mysqli->Escape_String($template_subject) . "',
					data = '" . $this->mysqli->Escape_String($template_data) . "',
					content_type='".$this->mysqli->Escape_String($content_type)."',
					status = 'ACTIVE'";
			
			if ($type != 'DOCUMENT')
			{
				$query .= ", type = '".$this->mysqli->Escape_String($type)."'";
			}

			$this->mysqli->Query($query);
			$id = $this->mysqli->Insert_Id();
			$condor_api = Condor_API::Get_API_Object($this->server);
			$condor_api->Delete_Template_Cache($id,$template_name);

			return $id;
		}
		

		/**
		 * Checks for any shared templates with the same name and removes them. This would usually
		 * be run when creating a new template that may have the same name as a shared template.
		 *
		 * @param string $template_name The name of the templte to check and remove.
		 */
		public function Remove_Shared($template_name)
		{
			// Check for and remove any shared template references if they create a template with
			// the same name.
			$result = $this->mysqli->Query("
				SELECT st.template_id
				FROM ".CONDOR_DB_NAME.".template t
					JOIN ".CONDOR_DB_NAME.".shared_template st ON t.template_id = st.template_id
				WHERE t.name = '".$this->mysqli->Escape_String($template_name)."'
					AND st.company_id = {$this->server->company_id}"
			);
		
			// If we have any results, get rid of them. We should only ever have one row.
			if(($row = $result->Fetch_Object_Row()))
			{
				$this->mysqli->Query("
					DELETE FROM ".CONDOR_DB_NAME.".shared_template
					WHERE template_id = $row->template_id
						AND company_id = {$this->server->company_id}"
				);
			}
		}
		
		/**
		 * Removes shared templates from shared_template based on ID. This would be used
		 * when deactivating templates.
		 *
		 * @param int $template_id The template ID to deactivate.
		 */
		public function Remove_Shared_By_ID($template_id)
		{
			$this->mysqli->Query("
				DELETE FROM ".CONDOR_DB_NAME.".shared_template
				WHERE template_id = $template_id"
			);
		}

		/**
		 * Updates the template by shifting all current information to the new template and setting the
		 *	current template inactive.
		 *
		 * @param integer $template_id
		 * @param string $template_subject
		 * @param string $template_data
		 * @param string $content_type
		 * @return integer
		 */
		public function Update_Template($template_id, $template_subject, $template_data, $content_type='text/html')
		{
			$template_id_new = FALSE;
			$template = $this->Fetch_Most_Recent($template_id);

			// If we deactivate the template succesfully, create the new updated template
			if ($this->Deactivate_Template($template->template_id))
			{
				try 
				{
					$template_id_new = $this->Create_Template(
						$template->name,
						$template_subject,
						$template_data,
						$template->type,
						$content_type
					);
				}
				catch (Exception $e)
				{
					$template_id_new = FALSE;
				}
				if (!is_numeric($template_id_new))
				{
					$this->mysqli->Query("UPDATE template SET status='ACTIVE' WHERE template_id=$template_id");	
				}
				else 
				{
					$this->Migrate_Attachments($template->template_id, $template_id_new);
					$this->Update_Shared_Templates($template->template_id, $template_id_new);
				}
				
			}
			return $template_id_new;
		}
		
		/**
		 * Sets the template specified by $template_id to INACTIVE.
		 *
		 * @param int $template_id Template ID to set INACTIVE.
		 */
		public function Deactivate_Template($template_id)
		{
			$ret_val = FALSE;
			
			$this->mysqli->Query("
				UPDATE ".CONDOR_DB_NAME.".template
				SET status = 'INACTIVE'
				WHERE template_id = $template_id
					AND company_id = {$this->server->company_id}"
			);

			if($this->mysqli->Affected_Row_Count() > 0)
			{				
				$condor_api = Condor_API::Get_API_Object($this->server);
				$condor_api->Delete_Template_Cache($template_id, NULL);
				$ret_val = TRUE;
			}
			
			return $ret_val;
		}
		
		/**
		 * Updates the shared_template table with the new template ID of a modified
		 * template.
		 *
		 * @param int $template_id_old The old template ID.
		 * @param int $template_id_new The new template ID.
		 */
		private function Update_Shared_Templates($template_id_old, $template_id_new)
		{
			$this->mysqli->Query("
				UPDATE ".CONDOR_DB_NAME.".shared_template
				SET template_id = $template_id_new
				WHERE template_id = $template_id_old"
			);
		}
		
		/**
		 * Removes an attachment from a template and returns the new template_id.
		 * Creates a new revision of the template and copies everything, except
		 * for the attachment that is being removed.
		 *
		 * @param int $template_id
		 * @param int $attachment_id
		 * @return int
		 */
		public function Remove_Attachment($template_id, $template_attachment_id)
		{
			$template = $this->Fetch_Most_Recent($template_id);
			$this->Deactivate_Template($template_id);
			$template_id_new = $this->Create_Template(
				$template->name,
				$template->subject,
				$template->data
			);
			
			$this->Migrate_Attachments(
				$template->template_id,
				$template_id_new,
				$template_attachment_id
			);
			
			return $template_id_new;
		}
		
		public function Check_Name_Exists($template_name)
		{
			$query = "
				SELECT COUNT(*) count FROM ".CONDOR_DB_NAME.".template
				WHERE name = '".$this->mysqli->Escape_String($template_name)."'
					AND company_id = {$this->server->company_id}
					AND status = 'ACTIVE'";
			
			return ($this->mysqli->Query($query)->Fetch_Object_Row()->count > 0);
		}
		
		public function Check_Template_Attachment_Exists($template_id, $attachment_id, $attachment_type)
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					COUNT(*) count
				FROM
					".CONDOR_DB_NAME.".template_attachment
				WHERE
					template_id = $template_id
					AND attachment_id = $attachment_id
					AND type = '$attachment_type'";
			
			return ($this->mysqli->Query($query)->Fetch_Object_Row()->count > 0);				
		}
		
		public function Check_ID_Valid($template_id)
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					COUNT(*) AS count
				FROM
					".CONDOR_DB_NAME.".template
				WHERE
					template_id = $template_id
					AND status = 'ACTIVE'
					AND company_id = {$this->server->company_id}";
			
			return ($this->mysqli->Query($query)->Fetch_Object_Row()->count > 0);
		}
		
		public function Check_ID_Exists($template_id)
		{
			$query = "
				select count(*) count from ".CONDOR_DB_NAME.".template
				where template_id = $template_id
				and company_id = " . $this->server->company_id;
			
			return ($this->mysqli->Query($query)->Fetch_Object_Row()->count > 0);			
		}
		
		public function Attach_Template($template_id_parent, $template_id_child)
		{
			if ($template_id_child == $template_id_parent)
			{
				throw new Exception("Attempt to attach template to itself.");
			}
			
			$query = "
				insert into
					".CONDOR_DB_NAME.".template_attachment
				set
					date_created = now(),
					template_id = $template_id_parent,
					type='TEMPLATE',
					attachment_id = $template_id_child";
			
			$this->mysqli->Query($query);
			$this->server->log->Write($query);					
		}
		
		public function Attach_File($template_id, $mime_type, $bin, $uri)
		{
			$query = "
				insert into
					".CONDOR_DB_NAME.".attachment
				set
					date_created = now(),
					uri='" . $this->mysqli->Escape_String($uri) . "',
					content_type='" . $this->mysqli->Escape_String($mime_type) . "',
					data='" . $this->mysqli->Escape_String($bin) ."'";
			
			$this->mysqli->Query($query);
			$this->server->log->Write($query);
			
			$attachment_id = $this->mysqli->Insert_Id();
			
			$query = "
				insert into
					".CONDOR_DB_NAME.".template_attachment
				set
					template_id=$template_id,
					date_created=now(),
					type='FILE',
					attachment_id=$attachment_id";
			
			$this->mysqli->Query($query);
			$this->server->log->Write($query);
		}
		
		public function Fetch_All($type = 'DOCUMENT')
		{
			$type = $this->mysqli->Escape_String($type);
			
			$query = "
				SELECT
					tp.template_id,
					tp.name,
					tp.user_id user_id_modifier,
					DATE_FORMAT(
						(
							SELECT
								MIN(tp1.date_created)
							FROM
								".CONDOR_DB_NAME.".template tp1
							WHERE
								tp1.name = tp.name
						), '%m/%d/%Y %h:%i:%s %p'
					) AS date_created,
					DATE_FORMAT(tp.date_modified, '%m/%d/%Y %h:%i:%s %p') AS date_modified,
					tp.subject,
					tp.type,
					tp.content_type,
					ag_modifier.name_last modifier_name_last,
					IFNULL(ag_modifier.name_first, 'Deleted User') modifier_name_first,
					ag_creator.name_last creator_name_last,
					IFNULL(ag_creator.name_first, 'DELETED USER') creator_name_first,
					IF(ISNULL(fcs.template_name), 'FALSE', 'TRUE') AS default_cover
				FROM
					".CONDOR_DB_NAME.".template tp
					LEFT JOIN agent ag_modifier ON ag_modifier.agent_id = tp.user_id
					LEFT JOIN agent ag_creator ON ag_creator.agent_id = (
						SELECT user_id
						FROM ".CONDOR_DB_NAME.".template tp1
						WHERE tp1.name = tp.name AND tp1.company_id = tp.company_id
						ORDER BY date_created ASC LIMIT 1
					)
					LEFT JOIN ".CONDOR_DB_NAME.".fax_cover_sheet fcs ON tp.company_id = fcs.company_id AND tp.name = fcs.template_name
				WHERE
					tp.company_id = {$this->server->company_id}
					AND status = 'ACTIVE'
					AND type = '$type'
				ORDER BY tp.name";
			
			$result = $this->mysqli->Query($query);
			
			$templates = array();
			
			while ($template = $result->Fetch_Object_Row())
			{
				if($template->default_cover == 'TRUE')
				{
					$template->default_cover = true;
				}
				else
				{
					$template->default_cover = false;
				}
				
				$templates[] = $template;
			}
						
			return $templates;		
		}
		
		public function Fetch_Shared()
		{
			$result = $this->mysqli->Query("
				SELECT
					st.template_id,
					t.name,
					t.user_id AS user_id_modifier,
					DATE_FORMAT(
						(
							SELECT MIN(tp1.date_created)
							FROM ".CONDOR_DB_NAME.".template tp1
							WHERE tp1.name = t.name
						),
						'%m/%d/%Y %h:%i:%s %p'
					) AS date_created,
					DATE_FORMAT(t.date_modified, '%m/%d/%Y %h:%i:%s %p') AS date_modified,
					t.subject,
					'SHARED' AS type,
                    t.content_type,
					a_modifier.name_last	AS modifier_name_last,
					IFNULL(a_modifier.name_first, 'DELETED USER')	AS modifier_name_first,
					a_creator.name_last		AS creator_name_last,
					IFNULL(a_creator.name_first, 'DELETED USER')	AS creator_name_first
				FROM
					".CONDOR_DB_NAME.".shared_template st
					JOIN ".CONDOR_DB_NAME.".template t ON st.template_id = t.template_id
					LEFT JOIN agent a_modifier on a_modifier.agent_id = t.user_id
					LEFT JOIN agent a_creator on a_creator.agent_id = (
						SELECT user_id
						FROM ".CONDOR_DB_NAME.".template tp1
						WHERE tp1.name = t.name AND tp1.company_id = t.company_id
						ORDER BY date_created ASC LIMIT 1
					)
				WHERE
					st.company_id = {$this->server->company_id}
				AND	t.status = 'ACTIVE' 
				ORDER by t.name DESC
			"
			);
			
			$templates = array();
			
			while(($template = $result->Fetch_Object_Row()))
			{
				$templates[] = $template;
			}
			
			return $templates;
		}
		
		public function Fetch_Attachments($template_id)
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					t.name AS template_name,
					a.uri AS file_name,
					a.data,
					a.content_type,
					ta.template_attachment_id,
					ta.attachment_id
				FROM
					".CONDOR_DB_NAME.".template_attachment ta
					LEFT JOIN ".CONDOR_DB_NAME.".attachment a on (a.attachment_id = ta.attachment_id and ta.type='FILE')
					LEFT JOIN ".CONDOR_DB_NAME.".template t on (t.template_id = ta.attachment_id and ta.type='TEMPLATE')
				WHERE
					ta.template_id = $template_id";
						
			$result = $this->mysqli->Query($query);
			
			$attachments = array();
			
			while ($attachment = $result->Fetch_Object_Row())
			{
				$attachment_obj = new stdClass();
				$attachment_obj->type = (is_null($attachment->template_name) ? 'File' : 'Template');
				$attachment_obj->name = (is_null($attachment->template_name) ? $attachment->file_name : $attachment->template_name);
				$attachment_obj->id = $attachment->attachment_id;
				$attachment_obj->template_attachment_id = $attachment->template_attachment_id;
				$attachment_obj->data = $attachment->data;
				$attachment_obj->content_type = $attachment->content_type;
						
				$attachments[] = $attachment_obj;
			}
			
			return $attachments;
		} 
		
		/**
		 * Inserts all the attachments from a deactivated template to the new template.
		 * If a removed_attachment is specified it will copy all of the attachments except
		 * the one specified.
		 *
		 * @param int $template_id_old
		 * @param int $template_id_new
		 * @param int $removed_attachment
		 */
		public function Migrate_Attachments($template_id_old, $template_id_new, $removed_attachment = null)
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				INSERT INTO ".CONDOR_DB_NAME.".template_attachment
				SELECT
					null,
					$template_id_new,
					date_created,
					content_type,
					type,
					attachment_id
				FROM
					".CONDOR_DB_NAME.".template_attachment
				WHERE
					template_id = $template_id_old";
			
			if(!is_null($removed_attachment))
			{
				$query .= " AND template_attachment_id != $removed_attachment";
			}

			$this->mysqli->Query($query);
			
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				UPDATE ".CONDOR_DB_NAME.".template_attachment
				SET
					attachment_id = $template_id_new
				WHERE
					attachment_id = $template_id_old
					AND type = 'TEMPLATE'";
			
			$this->mysqli->Query($query);		
				
		}

		/**
		 * Returns a single template
		 *
		 * @param integer $template_id
		 * @return string
		 */
		public function Fetch_Single($template_id)
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					tp.template_id,
					tp.name,
					tp.user_id user_id_modifier,
					DATE_FORMAT(
						(
							SELECT MIN(tp1.date_created)
							FROM
								".CONDOR_DB_NAME.".template tp1
							WHERE
								tp1.name = tp.name
						), '%m/%d/%Y %h:%i:%s %p') AS date_created,
					DATE_FORMAT(tp.date_modified, '%m/%d/%Y %h:%i:%s %p') AS date_modified,
					tp.subject,
					tp.data,
					tp.content_type
				FROM
					".CONDOR_DB_NAME.".template tp
					LEFT JOIN agent ag_modifier on ag_modifier.agent_id = tp.user_id
					LEFT JOIN agent ag_creator on ag_creator.agent_id = (
						SELECT user_id
						FROM ".CONDOR_DB_NAME.".template tp1
						WHERE tp1.name = tp.name AND tp1.company_id = tp.company_id
						ORDER BY date_created ASC LIMIT 1
					)
				WHERE
					tp.template_id = $template_id";
			
			$result = $this->mysqli->Query($query);
			
			$template_obj = ($result->Fetch_Object_Row());
			$template_obj->attachments = $this->Fetch_Attachments($template_id);
			
			return $template_obj;
		}
		
		public function Fetch_Most_Recent($template_id)
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					tp.template_id,
					tp.name,
					tp.user_id user_id_modifier,
					DATE_FORMAT(
						(
							SELECT
								MIN(tp1.date_created)
							FROM
								".CONDOR_DB_NAME.".template tp1
							WHERE
								tp1.name = tp.name
							AND
								tp1.company_id = tp.company_id
						), '%m/%d/%Y %h:%i:%s %p') AS date_created,
					DATE_FORMAT(tp.date_modified, '%m/%d/%Y %h:%i:%s %p') date_modified,
					tp.subject,
					tp.data,
					tp.type,
					tp.content_type
				FROM
					".CONDOR_DB_NAME.".template tp
					LEFT JOIN agent ag_modifier ON ag_modifier.agent_id = tp.user_id
					LEFT JOIN agent ag_creator ON ag_creator.agent_id = (select user_id from ".CONDOR_DB_NAME.".template tp1 order by date_created asc limit 1)
					JOIN ".CONDOR_DB_NAME.".template tp2 ON tp2.name = tp.name
					LEFT JOIN ".CONDOR_DB_NAME.".shared_template st ON tp.template_id = st.template_id
						AND st.company_id = {$this->server->company_id}
				WHERE
					(tp.company_id = {$this->server->company_id} OR (st.company_id = {$this->server->company_id} AND tp.company_id != {$this->server->company_id}))
					AND tp2.template_id = $template_id
					AND tp.status = 'ACTIVE'";
			
			$result = $this->mysqli->Query($query);
			
			$template_obj = ($result->Fetch_Object_Row());
			$template_obj->attachments = $this->Fetch_Attachments($template_obj->template_id);
						
			return $template_obj;
		}
		
		/**
		 * Validates template tokens against the token list table. Returns an
		 * array of invalid tokens or an empty array if there are no invalid
		 * tokens.
		 *
		 * @param string $data
		 * @return array
		 */
		public function Validate_Tokens($data)
		{
			$template_parser = new Template_Parser($data, "%%%");
			$template_tokens = $template_parser->Get_Tokens(true);
			
			// We only want unique tokens
			$template_tokens = array_unique($template_tokens);
			
			$query = "
				SELECT
					token
				FROM
					tokens
				WHERE
					company_id = {$this->server->company_id}";
			
			$result = $this->mysqli->Query($query);
			
			$company_tokens = array();
			while (($row = $result->Fetch_Object_Row()))
			{
				$company_tokens[] = $row->token;
			}
			
			return array_diff($template_tokens, $company_tokens);
		}

		/**
		 * Returns a list of a template's history.
		 *
		 * @param string $template_name
		 * @return array
		 */
		public function Fetch_History($template_name)
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					tp.template_id,
					tp.name,
					tp.user_id user_id_modifier,
					tp.status,
					DATE_FORMAT(tp.date_created, '%m/%d/%Y %h:%i:%s %p') AS date_created,
					DATE_FORMAT(tp.date_modified, '%m/%d/%Y %h:%i:%s %p') AS date_modified,
					tp.subject,
					tp.content_type,
					ag_modifier.name_last modifier_name_last,
					ag_modifier.name_first modifier_name_first,
					ag_creator.name_last creator_name_last,
					ag_creator.name_first creator_name_first
				FROM
					".CONDOR_DB_NAME.".template tp
					JOIN agent ag_modifier on ag_modifier.agent_id = tp.user_id
					JOIN agent ag_creator on ag_creator.agent_id = (
								SELECT user_id 
								FROM ".CONDOR_DB_NAME.".template AS tp1
								WHERE tp1.company_id = tp.company_id
								AND tp1.name = tp.name 
								ORDER BY date_created ASC LIMIT 1
					)
				WHERE
					tp.company_id = {$this->server->company_id}
					AND tp.name = '$template_name'
				ORDER BY tp.date_modified DESC";
			
			$result = $this->mysqli->Query($query);
			
			$template_history = array();
			
			while(($row = $result->Fetch_Object_Row()))
			{
				$template_history[] = $row;
			}
			
			return $template_history;
		}
		
		/**
		 * Returns a list of tokens available to the company.
		 *
		 * @return array
		 */
		public function Fetch_Tokens()
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					t.token,
					t.description,
					DATE_FORMAT(t.date_created, '%m/%d/%Y %h:%i:%s %p') AS date_created,
					IFNULL(td.raw_data, 'Empty') AS test_data,
					td.token_data_type AS test_data_type
				FROM
					tokens as t
					LEFT JOIN token_data AS td USING (token_data_id)
				WHERE
					t.company_id = {$this->server->company_id}
				ORDER BY 
					t.token
			";

			$result = $this->mysqli->Query($query);
			
			$tokens = array();
			
			while(($row = $result->Fetch_Object_Row()))
			{
				$tokens[] = $row;
			}
			
			return $tokens;
		}
		
		/**
		 * Returns a list of tokens and the associated test data
		 * 
		 * @return array
		 */
		public function Fetch_Token_Test_Data()
		{
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					t.token,
					IFNULL(td.raw_data, NULL) AS test_data,
					td.token_data_type AS test_data_type
				FROM
					tokens as t
					LEFT JOIN token_data AS td USING (token_data_id)
				WHERE
					t.company_id = {$this->server->company_id}
				ORDER BY
					t.token
			";

			$result = $this->mysqli->Query($query);

			$tokens = array();

			while(($row = $result->Fetch_Object_Row()))
			{
				$tokens[$row->token] = $row;
			}

			return $tokens;
		}
		/**
		 * Checks the token database to see if the token already exists.
		 *
		 * @param string $token_name The token name to check.
		 * @return bool
		 */
		public function Check_Token_Exists($token_name)
		{
			$token_name = $this->mysqli->Escape_String($token_name);

			$query = "
				/* ".__METHOD__." */
				SELECT
					COUNT(token) AS count
				FROM
					tokens
				WHERE
					token = '$token_name'
					AND company_id = {$this->server->company_id}
			";
			
			return ($this->mysqli->Query($query)->Fetch_Object_Row()->count > 0);
		}
		
		/**
		 * Creates the token in the condor_admin database.
		 *
		 * Revision History:
		 *	bszerdy - 02/05/2009 - Added the test_data and test_data_type variables
		 * 
		 * @param string $name				The name of the token to add.
		 * @param string $description		The description for the token.
		 * @param string $test_data			The test data for the token.
		 * @param string $test_data_type	The data type of the test data.
		 */
		public function Create_Token($name, $description, $test_data, $test_data_type)
		{
			$name			= $this->mysqli->Intelligent_Escape($name);
			$description	= $this->mysqli->Intelligent_Escape($description);
			$test_data		= $this->mysqli->Intelligent_Escape($test_data);
			$test_data_type	= $this->mysqli->Intelligent_Escape($test_data_type);

			// set the test data
			if (($test_data != 'Empty') || (!empty($test_data)))
			{
				$query = "
					INSERT INTO
						token_data
					SET
						date_created = NOW(),
						raw_data = $test_data,
						token_data_type = $test_data_type
				";

				$this->mysqli->Query($query);
				
				$and_data = ", token_data_id = {$this->mysqli->Insert_Id()}";
			}

			$query = "
				/* ".__METHOD__." */
				INSERT INTO
					tokens
				SET
					token = $name,
					date_created = NOW(),
					company_id = {$this->server->company_id},
					description = $description
					$and_data
			";
			
			$this->mysqli->Query($query);
		}
		
		/**
		 * Returns the information for a single token.
		 *
		 * Revision history:
		 *	bszerdy - 02/05/2009 - remove the percent sign before returning the token.
		 *
		 * @param string $name
		 * @return object
		 */
		public function Fetch_Single_Token($name)
		{
			$name = $this->mysqli->Intelligent_Escape($name);
			
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				SELECT
					t.token,
					t.description,
					t.date_created,
					t.company_id,
					IFNULL(t.token_data_id, 0) AS token_data_id,
					IFNULL(td.raw_data, 'Empty') AS test_data,
					td.token_data_type AS test_data_type
				FROM
					tokens AS t
					LEFT JOIN token_data AS td USING (token_data_id)
				WHERE
					t.token = $name
					AND t.company_id = {$this->server->company_id}";
			
			$result = $this->mysqli->Query($query);
			
			return $result->Fetch_Object_Row();
		}
		
		/**
		 * Modifies a token's description.
		 *
		 * @param string $name
		 * @param string $description
		 * @param string $test_data
		 * @param string $test_data_type
		 */
		public function Modify_Token($name, $description, $test_data, $test_data_type, $test_data_id)
		{
			$name			= $this->mysqli->Intelligent_Escape($name);
			$description	= $this->mysqli->Intelligent_Escape($description);
			$test_data		= $this->mysqli->Intelligent_Escape($test_data);
			$test_data_type = $this->mysqli->Intelligent_Escape($test_data_type);
			$and_data		= "";

			// Update the test data
			if ((!empty($test_data_id)) && ($test_data_id != 0))
			{
				$query = "
					UPDATE
						token_data
					SET
						raw_data = $test_data,
						token_data_type = $test_data_type
					WHERE (
						token_data_id = {$test_data_id})
				";

				$this->mysqli->Query($query);
			}
			else if ($test_data_id == 0)
			{
				$query = "
					INSERT INTO
						token_data
					SET
						date_created = NOW(),
						raw_data = $test_data,
						token_data_type = $test_data_type
				";

				$this->mysqli->Query($query);

				$and_data = ", token_data_id = {$this->mysqli->Insert_Id()}";
			}

			// Update the actual token
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				UPDATE
					tokens
				SET
					description = $description
					$and_data
				WHERE
					token = $name
					AND company_id = {$this->server->company_id}";
			
			$this->mysqli->Query($query);
		}
		
		/**
		 * Sets the default cover sheet for faxes for a company.
		 *
		 * @param int $company_id
		 * @param string $template_name
		 */
		public function Set_Default_Cover_Sheet($company_id, $template_name)
		{
			$company_id = $this->mysqli->Escape_String($company_id);
			$template_name = $this->mysqli->Escape_String($template_name);
			
			$query = "
				/* File: ".__FILE__.", Line: ".__LINE__." */
				INSERT INTO ".CONDOR_DB_NAME.".fax_cover_sheet (
					company_id,
					template_name
				)
				VALUES (
					$company_id,
					'$template_name'
				)
				ON DUPLICATE KEY UPDATE template_name = '$template_name'";
			
			$this->mysqli->Query($query);
		}
	}
?>
