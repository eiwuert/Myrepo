<?php

/**
 * Designates that a rule is a pick target rule.
 * 
 * Pick target rules are run when a blackbox target's pickTarget() method is
 * called. These are usually things that either should only happen when a target
 * is designated as a winner (such as hit stats for being chosen) or possibly
 * things like WithheldTargets that can only be determined after previous picks
 * have been rejected.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 */
interface OLPBlackbox_IPickTargetRule extends Blackbox_IRule {}

?>
