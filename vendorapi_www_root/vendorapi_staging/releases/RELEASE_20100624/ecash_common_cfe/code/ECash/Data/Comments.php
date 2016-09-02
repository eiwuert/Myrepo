<?php

	class ECash_Data_Comments extends ECash_Data_DataRetriever
	{
		public function getCommentDetails($application_id)
		{
			$comments = array();

			$query = "
				SELECT
					date_format(comment.date_created, '%m/%d/%y %H:%i') as date_created,
					comment.comment,
					comment.type as type,
					comment.source as comment_source,
					comment.comment_id,
					comment.agent_id,
					agent.login,
					concat(lower(agent.name_last), ' ', lower(agent.name_first)) 	AS agent_name,
					lower(agent.name_first) as agent_first_name,
					lower(agent.name_last) as agent_last_name
				FROM
					comment,
					agent
				WHERE
					comment.application_id = :application_id
				and comment.agent_id = agent.agent_id
				ORDER BY
					comment.date_created desc";



			$comment_prefix = array(
				'withdraw' => 'WITHDRAWN',
				'deny'     => 'DENIED',
				'dnl'	=> 'DNL'
			);

			$st = DB_Util_1::queryPrepared($this->db, $query, array('application_id' => $application_id));

			while ($row = $st->fetch(PDO::FETCH_OBJ))
			{
				if (isset($comment_prefix[$row->type]))
				{
					$row->comment = $comment_prefix[$row->type] . " - " . $row->comment;
				}

				$row->agent_name_formatted = ucfirst($row->agent_first_name) . " ". ucfirst($row->agent_last_name);
				$row->agent_name_short = substr(ucfirst($row->agent_first_name), 0, 1) . ". ". ucfirst($row->agent_last_name);
				$comments[] = $row;
			}

			return $comments;
		}
	}

?>
