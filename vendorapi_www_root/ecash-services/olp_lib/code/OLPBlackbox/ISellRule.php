<?php
/**
 * Designates a rule as a "sell rule" which is a rule which can post to a lender.
 * 
 * This rule is special because it must be run last. It will be assigned using the
 * {@see OLPBlackbox_Target::setSellRule()} method.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 */
interface OLPBlackbox_ISellRule extends OLPBlackbox_IPickTargetRule {}
?>
