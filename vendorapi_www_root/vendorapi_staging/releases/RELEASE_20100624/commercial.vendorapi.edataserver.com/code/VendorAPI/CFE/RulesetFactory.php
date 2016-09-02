<?php

/**
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_CFE_RulesetFactory implements VendorAPI_CFE_IRulesetFactory
{
	/**
	 * @var ECash_CFE_IFactory
	 */
	protected $factory;

	public function __construct(VendorAPI_CFE_IFactory $factory)
	{
		$this->factory = $factory;
	}

	/**
	 * Builds the ruleset from the given XML
	 *
	 * Rulesets are currently arrays containing arrays of rules
	 * indexed by event name: array(event => array(rule, ...), ...)
	 *
	 * @return array
	 */
	public function getRuleset(DOMDocument $doc)
	{
		$xpath = $this->getXPath($doc);

		$root = $xpath->query('/ruleset');
		if ($root->length === 0)
		{
			throw new Exception('Could not find root element');
		}

		$ruleset = $this->buildTree($xpath, $root->item(0));
		return $ruleset;
	}

	protected function buildTree(DOMXPath $xpath, DOMNode $root)
	{
		$nodes = array();
		$ruleset = array();

		$q = $xpath->query('node', $root);
		foreach ($q as $node)
		{
			$node_id = $node->getAttribute('id');
			if (!isset($nodes[$node_id]))
			{
				$n = $nodes[$node_id] = new VendorAPI_CFE_Node();
			}
			else
			{
				$n = $nodes[$node_id];
			}

			foreach ($xpath->query('expression', $node) as $e)
			{
				$name = $e->hasAttribute('name')
					? $e->getAttribute('name')
					: '';

				$expr = $this->factory->getExpression(
					$e->getAttribute('type'),
					$this->getParameters($xpath, $e)
				);
				$n->addExpression($expr, $name);
			}

			foreach ($xpath->query('transition', $node) as $t)
			{
				$target_id = $t->getAttribute('to');
				if (!isset($nodes[$target_id]))
				{
					$target = $nodes[$target_id] = new VendorAPI_CFE_Node();
				}
				else
				{
					$target = $nodes[$target_id];
				}

				$cond = null;
				if ($t->hasAttribute('when'))
				{
					$cond = new VendorAPI_CFE_TransitionCondition($t->getAttribute('when'));
				}

				$transition = new VendorAPI_CFE_Transition(
					$target,
					$cond
				);
				$n->addTransition($transition);
			}

			// the CFE engine wants an array of rules per event
			$ruleset[$node_id] = array($n);
		}

		return $ruleset;
	}

	/**
	 * Return the XPath object
	 * @param DOMDocument $doc
	 * @return DOMXPath
	 */
	protected function getXPath(DOMDocument $doc)
	{
		return new DOMXPath($doc);
	}

	/**
	 * @param $el
	 * @return array
	 */
	protected function getParameters(DOMXPath $xpath, DOMElement $el)
	{
		$q = $xpath->query('param', $el);

		if (!$q->length
		&& $el->localName == 'param'
		&& ($value = trim($el->textContent)))
		{
			return array($value);
		}

		$params = array();

		foreach ($q as $c)
		{
			if ($c->hasAttribute('type')
				&& $c->getAttribute('type') != 'literal')
			{
				$expr = $this->factory->getExpression(
					$c->getAttribute('type'),
					$this->getParameters($xpath, $c)
				);
			}
			else
			{
				$expr = $c->textContent;
			}

			if ($c->hasAttribute('name'))
			{
				$params[$c->getAttribute('name')] = $expr;
			}
			else
			{
				$params[] = $expr;
			}
		}

		return $params;
	}
}