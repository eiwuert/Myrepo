<?php

	class ECash_Application_Comments extends ECash_Application_Component
	{
		const TYPE_STANDARD = 'standard';
		const TYPE_WITHDRAW = 'withdraw';
		const TYPE_DENY = 'deny';
		const TYPE_FOLLOWUP = 'followup';
		const TYPE_REVERIFY = 'reverify';
		const TYPE_TRANSACTION = 'transaction';
		const TYPE_COLLECTION = 'collection';
		const TYPE_NOTES = 'notes';
		const TYPE_ROW = 'row';
		const TYPE_DECLINED = 'declined';
		const TYPE_ACH_CORRECTION = 'ach_correction';
		const TYPE_DNL = 'dnl';

		const SOURCE_LOAN_AGENT = 'loan agent';
		const SOURCE_CALL_CENTER_AGENT = 'call center agent';
		const SOURCE_SYSTEM = 'system';
		const SOURCE_CUSTOMER = 'customer';

		public function add($comment_body, $agent_id, $type = self::TYPE_STANDARD, $source = self::SOURCE_LOAN_AGENT, $related_key = NULL)
		{
			$comment = ECash::getFactory()->getModel('Comment');
			$comment->date_created = time();
			$comment->company_id = $this->application->getCompanyId();
			$comment->application_id = $this->application->getId();
			$comment->source = $source;
			$comment->type = $type;
			$comment->agent_id = $agent_id;
			$comment->comment = $comment_body;
			if(!is_null($related_key))
				$comment->related_key = $related_key;
			$comment->save();

			return $comment->comment_id;
		}

		public function getAll()
		{
			$commentList = ECash::getFactory()->getModel('CommentList');
			$commentList->loadBy(array('company_id' => $this->application->getCompanyId(), 'application_id' => $this->application->getId()));
			return $commentList;
		}
	}

?>