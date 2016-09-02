<?php

	/**
	 * Interface representing an evaluatable expression; unlike a comparison (which,
	 * arguably, is also an expression), there is no restriction on the return type
	 * of something implementing ECash_CFE_IExpression. This is mainly used for embedding
	 * an evaluatable expression as an Action or Condition parameter.
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	interface ECash_CFE_IExpression
	{
		public function evaluate(ECash_CFE_IContext $c);
	}

?>
