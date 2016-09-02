<?php

/**
 * Produce the Vetting target tree described in gforge 9922.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_Factory_Collection
{
	/**
	 * The Rule Factory to set up campaigns with rules.
	 *
	 * @var OLPBlackbox_Factory_Legacy_Rule
	 */
	protected $rule_factory = NULL;
	/**
	 * DOMDocument used to parse XML tree for initial version of this class.
	 *
	 * @var DOMDocument
	 */
	protected $doc = NULL;
	
	/**
	 * Rows from the database containing rule information about the campaigns we'll be assmbling.
	 *
	 * @var ArrayObject
	 */
	protected $campaign_rows = NULL;
	
	/**
	 * Copy of the debugging class.
	 *
	 * @var OLBlackbox_Config
	 */
	protected $config = NULL;
	
	/**
	 * Reflection class for OLPBlackbox_DebugConf
	 *
	 * Used for verifying debug constants in the array that lays out the 
	 * blueprint for the collection this factory produces.
	 * 
	 * @var ReflectionClass
	 */
	protected $debug_reflect = NULL;
	
	/**
	 * File location of the XML to parse to make a collection.
	 * 
	 * This is public because we want the factory to be able to set this on each
	 * new object before it asks for a collection. In "phase 3" we'll need around
	 * three collections of this type that will be loaded into an ordered
	 * collection at the top level.
	 * 
	 * This isn't set in the constructor since assembling this collection from
	 * an XML file is "temporary" and will eventually be in the database. 
	 * (Supposedly.)
	 *
	 * @var string
	 */
	public $xml_file = '/virtualhosts/olp_blackbox/OLPBlackbox/Vetting/Factory/tree.xml';
	
	/**
	 * Construct an OLPBlackbox_Vetting_Factory_Collection object.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->config = OLPBlackbox_Config::getInstance();
		$this->debug_reflect = new ReflectionClass('OLPBlackbox_DebugConf');
	}
	
	/**
	 * Query the database for the campaigns this factory is interested in.
	 * 
	 * @return ArrayObject
	 */
	protected function buildCampaignRows()
	{
		// collect the names of the campaigns from the DOMDocument, because 
		// it's easier than parsing an arrayobject tree.
		$campaign_names = array();
		foreach ($this->getDOMDocument()->getElementsByTagName('campaign') as $campaign)
		{
			$campaign_names[] = $campaign->getAttribute('name');
		}
		
		// {@see OLPBlackbox_Factory_Legacy_OLPBlackbox::getCampaignQuery}
		$clause = sprintf(
			"AND target.name IN ('%s')", implode("', '", $campaign_names)
		);
		
		$query = OLPBlackbox_Factory_Legacy_OLPBlackbox::getCampaignQuery($clause);
		
		$key = sprintf('olpblackbox/vetting/factory/query/%s', md5($query));

		try 
		{
			$rows = Cache_OLPMemcache::getInstance()->get($key);
		}
		catch (Exception $e)
		{
			OLPBlackbox_Config::getInstance()->applog->Write(
				$e->getMessage()
			);
		}
		
		if (!$rows)
		{
			$db = $this->getDB();
			$info = $this->getDBInfo();
			try
			{
				$rows = new ArrayObject();
				$result = $db->Query($info['db'], $query);
				
				while ($row = $db->Fetch_Array_Row($result))
				{
					$rows->append($row);
				}
				
				if (!$rows)
				{
					throw new Exception('no targets were returned');
				}
				
				try 
				{
					Cache_OLPMemcache::getInstance()->add($key, $rows);
				}
				catch (Exception $e)
				{
					OLPBlackbox_Config::getInstance()->applog->Write(
						$e->getMessage()
					);
				}
			}
			catch (Exception $e)
			{
				throw new Blackbox_Exception(sprintf(
					'unable to build collection object: %s',
					$e->getMessage())
				);
			}
		}

		return $rows;
	}
	
	/**
	 * Checks to see if a debugging option string is set in config.
	 *
	 * @param string $option the string option
	 * 
	 * @return bool TRUE means skip this rule, FALSE means do not skip
	 */
	protected function debugSkip($option = NULL)
	{
		if ($option && !$this->debug_reflect->hasConstant($option))
		{
			throw new InvalidArgumentException(sprintf(
				'DebugConf does not have constant %s',
				strval($option))
			);
		}
		
		return $this->config->debug->debugSkipRule(
			$this->debug_reflect->getConstant($option)
		);
	}
	
	/**
	 * Converts an XML tree (in an expected format) into an array structure.
	 *
	 * @param DOMElement $element Element to start translating with.
	 * 
	 * @return ArrayObject
	 */
	protected function domToArray(DOMElement $element)
	{
		/**
		 * This could be a class member, but the XML stuff is "temporary" and 
		 * "soon" database stuff will take it's place. Also, this isn't something
		 * that a factory manipulating this object will need to change (like
		 * the xml file name). [4/15/08] [DanO]
		 */
		static $properties = FALSE;
		if (!$properties)
		{
			$properties = array('name', 'class', 'weight', 'id');
		}
		
		$obj = new ArrayObject();
		$obj['type'] = $element->tagName;
		
		foreach ($properties as $prop)
		{
			if ($element->hasAttribute($prop))
			{
				$obj[$prop] = $element->getAttribute($prop);
			}
		}
		
		// determine the children that the current node is allowed to have
		// and loop through and build the children.
		$children = array();
		$allowed_children = array();
		if ($element->tagName == 'collection')
		{
			$allowed_children = array('campaign', 'target', 'collection');
		}
		elseif ($element->tagName == 'campaign')
		{
			$allowed_children = array('target', 'collection');
		}
		
		$obj['children'] = new ArrayObject();
		foreach ($element->childNodes as $child)
		{
			if (!$child instanceof DOMElement) continue;
			
			if (in_array($child->tagName, $allowed_children))
			{
				// "children" are simply elements that are assembled recursively
				$children[] = $this->domToArray($child);
			}
			else
			{
				// if this entry is not a "child" it should be handled as
				// a property of the current object.
				$this->parseDomProperty($child, $obj);
			}
		}
		foreach ($children as $child)
		{
			$obj['children']->append($child);
		}
		
		return $obj;
	}
	
	/**
	 * Return an array containing the information needed to set up an ITarget tree.
	 *
	 * @return array
	 */
	protected function fetchTree()
	{
		// for now, we'll just parse an XML file and turn it into an array.
		// however, eventually this information should come out of the DB
		$this->doc = $this->getDOMDocument();
		return $this->domToArray($this->doc->childNodes->item(0));
	}
	
	/**
	 * Returns the campaign rules, building them if needed.
	 *
	 * @return ArrayObject
	 */
	protected function getCampaignRows()
	{
		if (!$this->campaign_rows instanceof ArrayObject)
		{
			$this->campaign_rows = $this->buildCampaignRows();
		}
		return $this->campaign_rows;
	}
	
	/**
	 * Returns standard rules for a campaign (by campaign_name).
	 *
	 * @param string $campaign_name Name of the campaign. (property_short in db)
	 * 
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getRulesForCampaign($campaign_name)
	{
		if (!$this->rule_factory instanceof OLPBlackbox_Factory_Legacy_Rule)
		{
			$this->rule_factory = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance($campaign_name);
		}
		
		$campaign_row = array();
		
		foreach ($this->getCampaignRows() as $row)
		{
			if (strcasecmp($row['property_short'], $campaign_name) !== FALSE)
			{
				$campaign_row = $row;
				break;
			}
		}
		
		return $this->rule_factory->getRuleCollection($campaign_row);
	}
	
	/**
	 * Returns a collection specific for Vetting.
	 *
	 * TODO: When the "new" database structure goes in place (the structure that
	 * will accompany Blackbox 3.0), this collection could be pulled from the db
	 * instead of XML. Furthermore, the parseTree part of this class should be
	 * able to be mostly reused, hopefully.
	 * 
	 * @return OLPBlackbox_TargetCollection
	 */
	public function getCollection()
	{
		// the layout for how the resulting collection should look
		$tree = $this->fetchTree();
		
		/*
		 * TODO: Ideally, we could have a class like "OLPBlackbox_CollectionParser"
		 * that could take an array or something and parse it into a collection
		 * tree. However, at this point, as nebulous as the requirements are for
		 * this gforge ticket, I'm just going to do it all in this class.
		 */
		
		// translate the array into a collection
		return $this->parseTree($tree);
	}
	
	/**
	 * Return the olp database object, separated into an object for mocking.
	 *
	 * @return MySQL_Wrapper object. 
	 */
	protected function getDB()
	{
		return OLPBlackbox_Config::getInstance()->olp_db;
	}
	
	/**
	 * Return the database information, mostly for mocking purposes.
	 *
	 * Returns an associative array with information. The most interesting entry
	 * is 'db' which is the name of the db to connect to.
	 * 
	 * @return array
	 */
	protected function getDBInfo()
	{
		return OLPBlackbox_Config::getInstance()->olp_db->db_info;
	}
	
	/**
	 * This method is designed to be overwritten by a PHPUnit test.
	 * 
	 * @return DOMDocument object
	 */
	protected function getDOMDocument()
	{
		if (!$this->doc instanceof DOMDocument)
		{
			$doc = new DOMDocument();
			if (!file_exists($this->xml_file) || !is_readable($this->xml_file))
			{
				throw new Blackbox_Exception(sprintf(
					'xml tree file (%s) missing/unreadable.',
					$this->file)
				);
			}
			
			$loaded = $doc->load($this->xml_file);
			if (!$loaded)
			{
				throw new Blackbox_Exception('could not load xml tree');
			}
			$this->doc = $doc;
		}
		
		return $this->doc;
	}

	/**
	 * Produce a Campaign object instantiation from a representational array.
	 *
	 * @param ArrayObject $node Representation of how the Campaign should look.
	 * 
	 * @return Blackbox_ITarget Campaign object.
	 */
	protected function parseCampaign(ArrayObject $node)
	{
		// one of the few required elements
		if (empty($node['name']) || empty($node['weight']) || !is_numeric($node['weight']))
		{
			throw new UnexpectedValueException(sprintf(
				'campaign node should have name and int weight, got node: %s',
				var_export($node, TRUE))
			);
		}
		
		// rules for the campaign specified in the tree
		$rules = $this->getRulesForCampaign($node['name']);
		if (!method_exists($rules, 'addRule'))
		{
			// make sure the rules for this object are a collection
			// this is important for the rest of the factory to be able to add
			// custom rules not in the DB
			$t = new OLPBlackbox_RuleCollection();
			$t->addRule($rules);
			$rules = $t;
		}
						
		return new OLPBlackbox_Campaign($node['name'], 
			$node['id'],
			intval($node['weight']),
			NULL, 
			($rules instanceof Blackbox_IRule) ? $rules : NULL
		);
	}
	
	/**
	 * Translate the representation of a Collection object into an instantiation.
	 *
	 * @param ArrayObject $node Representation of how the collection should look.
	 * 
	 * @return Blackbox_ITarget Collection object
	 */
	protected function parseCollection(ArrayObject $node)
	{
		if (empty($node['name']))
		{
			throw new UnexpectedValueException(sprintf(
				'node did not have a name: %s',
				var_export($node, TRUE))
			);
		}
		
		if (empty($node['type']))
		{
			$object = new OLPBlackbox_TargetCollection($node['name']);
		}
		elseif (class_exists($node['class']))
		{
			$object = new $node['class']($node['name']);
		}
		else
		{
			throw new Blackbox_Exception(sprintf(
				'could not instantiate class from node: %s',
				var_export($node, TRUE))
			);
		}
		
		if (!empty($node['id']))
		{
			$object->setID($node['id']);
		}
		
		if (!empty($node['picker']))
		{
			if (!method_exists($object, 'setPicker'))
			{
				throw new UnexpectedValueException(sprintf(
					'collection node cannot setPicker, but picker was provided: %s',
					var_export($node, TRUE))
				);
			}
			
			if (!class_exists($node['picker']['class']))
			{
				throw new UnexpectedValueException(sprintf(
					'picker class requested when parsing node does not exist: %s',
					$node['picker'])
				);
			}
			
			$object->setPicker(new $node['picker']['class']());
		}
		
		return $object;
	}
	
	/**
	 * Parses things such as pickers and rules that are "properties" of targets.
	 *
	 * @param DOMElement $property The item we'd like to parse, like "rules."
	 * @param ArrayObject $parent The obj to store the results of the parse in.
	 * 
	 * @return void
	 */
	protected function parseDomProperty(DOMElement $property, ArrayObject $parent)
	{
		// this function will be called a lot, but eventually hopefully we will
		// not be pulling the collection from XML, which is why this is static
		// and not a class variable
		static $allowed_types = FALSE;
		if (!$allowed_types)
		{
			$allowed_types = array('collection', 'campaign', 'target');
		}
		
		
		if (empty($parent['type']) || !in_array($parent['type'], $allowed_types))
		{
			throw new UnexpectedValueException(sprintf(
				'object type must be collection, campaign or target, not "%s"',
				strval($parent['type']))
			);
		}
		if ($parent['type'] == 'collection')
		{
			if ($property->tagName == 'picker' && $property->hasAttribute('class'))
			{
				$parent[$property->tagName] = new ArrayObject(
					array('class' => $property->getAttribute('class'))
				);
			}
		}
		
		if ($property->tagName == 'rules')
		{
			// all allowed types can have rules (or pick target rules)
			// TODO: Add support for pickTarget rules
			$rule_nodes = $property->getElementsByTagName('rule');
			
			if (!isset($parent['children']))
			{
				$parent['children'] = new ArrayObject();
			}
			
			foreach ($rule_nodes as $rule)
			{
				$parent['children']->append($this->parseDomRule($rule));
			}
		}
	}
	
	/**
	 * Parse a rule from a DOMElement which represents it.
	 *
	 * @param DOMElement $rule Representation of the rule in a DOMElement
	 * 
	 * @return ArrayObject
	 */
	protected function parseDomRule(DOMElement $rule)
	{
		// to complete vetting, we assume each rule must have a class.
		// it's possible, of course, that later this should be changed
		// so that valid rules have either a class or key+value.
		if (!$rule->getAttribute('class'))
		{
			throw new UnexpectedValueException(
				'rule in XML must have a "class" attribute.'
			);
		}
		$rule_obj = new ArrayObject();
		$rule_obj['class'] = $rule->getAttribute('class');
		$rule_obj['type'] = 'rule';

		if ($rule->hasAttribute('debug_opt'))
		{
			$rule_obj['debug_opt'] = $rule->getAttribute('debug_opt');
		}
		
		if ($rule->hasAttribute('event_name'))
		{
			$rule_obj['event_name'] = $rule->getAttribute('event_name');
		}

		$arg_list = $rule->getElementsByTagName('arg');
		
		if ($arg_list->length > 0)
		{
			$rule_obj['args'] = new ArrayObject();
			foreach ($arg_list as $arg)
			{
				$rule_obj['args']->append($arg->nodeValue);
			}
		}
		
		return $rule_obj;
	}
	
	/**
	 * Interpret an array type node into an actual Blackbox_IRule instance.
	 *
	 * @param mixed $node array or ArrayObject
	 * 
	 * 
	 * @return Blackbox_IRule
	 */
	protected function parseRule($node)
	{
		if ($this->debugSkip(empty($node['debug_opt']) ? NULL : $node['debug_opt']))
		{
			$object = new OLPBlackbox_DebugRule();
		}
		else 
		{
			if (empty($node['class']) || !class_exists($node['class']))
			{
				throw new UnexpectedValueException(sprintf(
					'class absent or not declared, cannot construct node %s',
					var_export($node, TRUE))
				);
			}
			
			if (!empty($node['args']))
			{
				$reflection = new ReflectionClass($node['class']);
				$object = $reflection->newInstanceArgs(
					$node['args']->getArrayCopy()
				);
			}
			else
			{
				$object = new $node['class']();
			}
		}
		
		if (!empty($node['event_name']) 
			&& method_exists($object, 'setEventName')
			&& method_exists($object, 'getEventName')
			&& !$object->getEventName($node['event_name']))
		{
			$object->setEventName($node['event_name']);
		}
		
		return $object;
	}
	
	/**
	 * Assemble a target from an ArrayObject representation.
	 *
	 * @param ArrayObject $node representation of the Target object
	 * 
	 * @return Blackbox_ITarget object.
	 */
	protected function parseTarget(ArrayObject $node)
	{
		if (empty($node['class']) || !class_exists($node['class']))
		{
			$class = 'OLPBlackbox_Target';
		}
		else
		{
			$class = $node['class'];
		}
		
		if (empty($node['name'])
			|| empty($node['id'])
			|| !is_numeric(strval($node['id']))	// rc/live have no ctype_digit
			|| !is_string($node['name']))
		{
			throw new UnexpectedValueException(sprintf(
				'unable to assemble target from node: %s',
				var_export($node, TRUE))
			);
		}
		
		return new $class($node['name'], $node['id']);
	}
	
	/**
	 * Parse a multi-dimentional array into a Blackbox Collection.
	 *
	 * @param mixed $node ArrayObject or array
	 * 
	 * @return object Blackbox collection of some sort.
	 */
	protected function parseTree($node)
	{
		if (!$node['type'])
		{
			throw new UnexpectedValueException(sprintf(
				'could not parse node: %s',
				var_export($node, TRUE))
			);
		}
		
		// this may be unused, or even overwritten.
		$rules = NULL;
		
		// translate the current node into an object.
		/**
		 * For now, shortcuts have been taken since for Vetting (GForge 9922)
		 * none of the targets will have rules, so we don't have to parse them
		 * for rules and thus targets can be created by parseCampaign() safely.
		 */
		if ($node['type'] == 'collection')
		{
			$object = $this->parseCollection($node);
			$rules = $object->getRules();
		}
		elseif ($node['type'] == 'campaign')
		{
			if (empty($node['name']))
			{
				throw new UnexpectedValueException(sprintf(
					'campaign node is required to have a name, got: %s',
					var_export($node, TRUE))
				);
			}
			$object = $this->parseCampaign($node);
			$rules = $object->getRules();
		}
		elseif ($node['type'] == 'target')
		{
			$object = $this->parseTarget($node);
		}
		elseif ($node['type'] == 'rule')
		{
			$object = $this->parseRule($node);
		}
		else 
		{
			throw new UnexpectedValueException(sprintf(
				'unknown node type %s',
				$node['type'])
			);
		}
				
		if (isset($node['children']))
		{
			foreach ($node['children'] as $child)
			{				
				$child = $this->parseTree($child);
				if ($child instanceof Blackbox_ITarget)
				{
					$method = NULL;
					if (method_exists($object, 'addTarget'))
					{
						$method = 'addTarget';
					}
					elseif (method_exists($object, 'setTarget')) 
					{
						$method = 'setTarget';
					}
					else 
					{
						throw new UnexpectedValueException(sprintf(
							'node %s cannot add targets, but has target children.',
							var_export($node, TRUE))
						);
					}
					
					$object->$method($child);
				}
				elseif ($child instanceof Blackbox_IRule)
				{
					if (is_null($rules))
					{
						$rules = new OLPBlackbox_RuleCollection();
					}
					$rules->addRule($child);
				}
				else 
				{
					throw new UnexpectedValueException(sprintf(
						'node parsed (%s) did not result in ITarget or IRule: %s',
						is_object($child) ? get_class($child) : 'non-object',
						var_export($node, TRUE))
					);
				}
			}
	
			if (count($rules))
			{
				if (!method_exists($object, 'setRules'))
				{
					throw new UnexpectedValueException(sprtinf(
						'node cannot accept rules but has rule children: %s',
						var_export($node, TRUE))
					);
				}
				$object->setRules($rules);
			}
		}

		return $object;
	}
}

?>
