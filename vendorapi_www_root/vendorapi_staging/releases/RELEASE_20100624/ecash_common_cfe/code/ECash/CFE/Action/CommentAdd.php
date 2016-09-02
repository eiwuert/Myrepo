<?php

	/**
	 * An action that creates a comment for an application.
	 *
	 * @author Will! Parker
	 */
	class ECash_CFE_Action_CommentAdd extends ECash_CFE_Base_BaseAction 
	{
		
		public function getParameters()
		{
			return array(
				new ECash_CFE_API_VariableDef('comment', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB()),
				new ECash_CFE_API_VariableDef('type', ECash_CFE_API_VariableDef::TYPE_STRING, ECash::getFactory()->getDB())
				
			);
		}
		
		
		public function getReferenceData($param_name) {
			$retval = array();
			switch($param_name) {
				case "type":
					foreach(ECash::getFactory()->getModel('Comment')->getCommentTypes() as $comment_type)
					{
						$retval[] = array($comment_type, $comment_type, 0);
					}
					break;
			}
			return $retval;
		}
		
		public function getType()
		{
			return 'CommentAdd';
		}

		/**
		 * Inserts a comment
		 *
		 * @param CFE_IContext $c
		 */
		public function execute(ECash_CFE_IContext $c)
		{
			// evaluate any expression parameters
			$params = $this->evalParameters($c);

			
			//Create the comment to add.  We may want to make more of these configurable at a later date.
			$comment = ECash::getFactory()->getModel('Comment');
			$comment->comment = $params['comment'];
			$comment->date_created = date('Y-m-d H:i:s');
			$comment->company_id = $c->getAttribute('company_id');
			$comment->application_id = $c->getAttribute('application_id');
			$comment->source = 'system';
			//I don't like getting the agent_id from the session, but that's currently how we do it.
			$comment->agent_id = ECash::getAgent()->getAgentId();
			$comment->type = $params['type']?$params['type']:'standard';
			
			//Insert the comment!
			$comment->insert();
		}
	}

?>
