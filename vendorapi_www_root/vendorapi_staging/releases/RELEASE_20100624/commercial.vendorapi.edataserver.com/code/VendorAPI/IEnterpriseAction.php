<?php

/**
 * An interface to identify enterprise actions.
 *
 * This is to protect against mistakenly calling company actions when a
 * company is not "truely" defined.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
interface VendorAPI_IEnterpriseAction extends VendorAPI_IAction
{

}

?>