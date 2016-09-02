<?php
/**
 * Functions to facilitate the sql operations needed for IMPACT tagging.
 */

	class ECash_Application_Tags extends ECash_Application_Component
	{
		const INVESTOR_GROUP_TAG_PREFIX = 'IG_';
		
		protected function create($tag_name, $name)
		{
			//Creating new tag detail
			$tag_detail = new ECash_TagDetails($this->db);
			$tag_detail->add($tag_name, $name);
		}
 

		/**
		 * Sets the given tag for the given application.
		 * 
		 * Returns false if anything other than numbers is passed to the function.
		 *
		 * @param int $application_id
		 * @param int $tag_id
		 * @return bool 
		 */
		public function add($tag_id) 
		{
			if (!ctype_digit((string)$tag_id)) 
			{
				return false;
			}
			
			$new_tag = ECash::getFactory()->getModel('ApplicationTags');

			$new_tag->created_date = time();
			$new_tag->tag_id = $tag_id;
			$new_tag->application_id = $this->application_id;
			$new_tag->save();

			return $new_tag;
		}

		/**
		 * Removes all tags for a given application.
		 */
		public function removeAll()
		{
			ECash::getFactory()->getData('Application')->removeTags(INVESTOR_GROUP_TAG_PREFIX, $this->application_id);

		}

		/**
		 * Loads all tag details.
		 *
		 * @return array
		 */
		public function getAll() 
		{
			$tag_details = new ECash_TagDetails($this->db);
			$details = $tag_details->getAll();
			return $details;
		}

		/**
		 * Sets the weights for all given tags. The tags and weights are passed as an 
		 * associative array using the tag_id as the key and the weight as a value. 
		 * This function will not save the data if the passed weights do not add to 
		 * 100 or if any of the passed tag_ids could not be found.
		 * 
		 * In both of those cases an exception is thrown and should be dealt with a 
		 * catch in the code.
		 *
		 * @param array $tag_weights
		 */
		public function updateTagWeights($tag_weights) 
		{
			$tag_details = new ECash_TagDetails($this->db);
			$tag_details->setTagWeights($tag_weights);
		}

		/**
		 * Returns a tag weight map to map wieghts to tag_ids. This will NOT return 
		 * any tags whoses weights are 0.
		 *
		 * @return array
		 */
		public function loadWeightMap() 
		{
			$tag_details = new ECash_TagDetails($this->db);
			return $tag_details->getWeightMap();
		}
		
		/**
		 * Creates an array for use by the tagging system to keep track of 
		 * distribution in the currenent tagging session. (Each Batch)
		 *
		 * @param unknown_type $tag_weights
		 * @return unknown
		 */
		public function createDistributionArray($tag_weights) 
		{
			return array_combine(array_keys($tag_weights), array_fill(0, count($tag_weights), 0));
		}

		/**
		 * Adds tag to current application
		 *
		 * @param double $loan_amount
		 * @param array $current_distribution
		 */
		public function addTag($loan_amount, &$current_distribution) 
		{
			$deltas = array();
			$tag_weights = $this->loadWeightMap();
			
			$total_distribution = array_sum($current_distribution);
			$percentage_distribution = array();
			foreach ($current_distribution as $tag_id => $amount) 
			{
				$percentage_distribution[$tag_id] = $total_distribution ? (100 * ($amount / $total_distribution)) : 0;
			}
			
			foreach ($tag_weights as $tag_id => $target_weight) 
			{
				$deltas[$tag_id] = $target_weight - $percentage_distribution[$tag_id];
			}
			
			//reverse sort the deltas (greatest first) and pull the key for the 
			//first value.
			arsort($deltas, SORT_NUMERIC);
			reset($deltas);
			list($new_tag, $junk) = each($deltas);
			
			//Tag the application
			$this->addTag($new_tag);
			
			//Update the distribution
			$current_distribution[$new_tag] += $loan_amount;
			
			return $new_tag;
		}	
}
	
class Tagging_BadWeights_Exception extends Exception {}
?>