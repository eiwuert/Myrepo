<?php
	
	/**
	 * Various utilitarian functions for dealing with the output of ACL_2.
	 */
	class Admin_Resources
	{
		
		private $unsorted_master_tree;
		private $display_level;
		private $display_sequence;
		
		protected $nodes;
		protected $children;
		protected $root;
		
		/**
		 *
		 * @params
		 *	$unsorted_master_tree  This is the tree that will be sorted.
		 *	$display_level  This is the first level to display
		 *	$display_sequence  This is the first node of the display sequence
		 */
		public function __construct($unsorted_master_tree, $display_level, $display_sequence)
		{
			
			$this->unsorted_master_tree = $unsorted_master_tree;
			$this->display_level = $display_level;
			$this->display_sequence = $display_sequence;
			
			$this->processTree($this->unsorted_master_tree);
			
		}
		
		/**
		 * Returns the "tree", but sorted -- i.e., parents will be
		 * followed by their children.
		 * @return array
		 */
		public function getSorted()
		{
			
			$sorted = array();
			
			foreach ($this->root as $node_id)
			{
				$this->getChildren($node_id, $sorted);
			}
			
			return $sorted;
			
		}
		
		/**
		 * Returns the entire tree.
		 * @return array
		 */
		public function getTree()
		{
			
			$tree = array();
			
			foreach ($this->root as $node_id)
			{
				$tree[$this->get_tree_node_key($this->nodes[$node_id])] = $this->buildNode($this->nodes[$node_id]);
			}
			
			return $tree;
			
		}

		/**
		 * Returns what key to use for this node in the tree.  This is explicitly abstracted for subclass overrides
		 * @return string
		 */
		protected function get_tree_node_key($node_object)
		{
			return $node_object->description;
		}
		
		
		public function Get_Sorted_Master_Tree()
		{
			return $this->getSorted();
		}
		
		/**
		 * Process the "tree" from ACL_2 and builds several index-ish things.
		 * @param array $tree
		 * @return void
		 */
		protected function processTree($tree)
		{
			
			$root = NULL;
			
			// reset
			$this->nodes = array();
			$this->children = array();
			$levels = array();
			
			foreach ($tree as $key=>$section)
			{
				
				// store this in our nodes with the section ID as the key
				$this->nodes[$section->section_id] = $section;
				
				// make space for our children
				if (!isset($this->children[$section->section_parent_id]))
				{
					$this->children[$section->section_parent_id] = array();
				}
				
				$this->children[$section->section_parent_id][] = $section->section_id;
				
				// keep track of who's on what level
				$levels[$section->section_id] = $section->level;
				
				// find the root node
				if (($section->level >= $this->display_level) && (($root === NULL) || ($section->level < $root)))
				{
					$root = $section->level;
				}
				
			}
			
			$this->root = array_keys($levels, $root);
			
			return;
			
		}
		
		/**
		 * Returns an array of $node_id and all its children IDs.
		 * This is used primarily by getSorted().
		 * @param int $node_id The parent node ID
		 * @param array $children If provided, results will be placed into this
		 * @return mixed
		 */
		protected function getChildren($node_id, &$children = NULL)
		{
			
			if (!is_array($children))
			{
				$children = array();
			}
			
			$children[] = $node_id;
			
			if (isset($this->children[$node_id]))
			{
				
				// add our children to the list
				foreach ($this->children[$node_id] as $child_id)
				{
					$this->getChildren($child_id, $children);
				}
				
			}
			
			if ($children === NULL)
			{
				return $children;
			}
			
			return TRUE;
			
		}
		
		/**
		 * Returns a node in the format expected from getTree().
		 * @param object $node
		 * @return array
		 */
		protected function buildNode($node)
		{
			
			$children = array();
			
			if (isset($this->children[$node->section_id]))
			{
				
				foreach ($this->children[$node->section_id] as $child_id)
				{
					$children[$this->get_tree_node_key($this->nodes[$child_id])] = $this->buildNode($this->nodes[$child_id]);
				}
				
			}
			
			$new = array(
				'name' => $node->name,
				'parent_section_id' => $node->section_parent_id,
				'section_id' => $node->section_id,
				'children' => $children,
			);
			return $new;
			
		}
		
	}
	
?>
