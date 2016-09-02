<?php 

/**
 * Returns an array containing information for all the available reports for 
 * a given client.
 * 
 * This function is used by Display_Report_parent::Get_Menu_HTML() to display 
 * the report module's menu. The format of the array is as follows:
 * 
 * array(
 *   <token_friendly_title> => array(
 *     'background-color' => <background color for submenu>,
 *     'inline' => <html to display as button (if applicable)>,
 *     'reports' => array(
 *       <human readable title> => <url for report>,
 *       ...
 *     )
 *   ),
 *   ...
 * );
 *
 * @return unknown
 */
function list_available_admin_menu($user_acl_sub_names)
{
	$agent = ECash::getAgent();
	$available_items = array();

	if (isset($user_acl_sub_names['privs'])) {
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Users / Groups' => array(
					'background-color' => 'lightsteelblue',
					'class' => 'user_groups',
					'submenu' => array(
						'Privileges' => '/?mode=privs',
					)
				),
			)
		);
	}
	
	if (isset($user_acl_sub_names['groups'])) {
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Users / Groups' => array(
					'background-color' => 'lightsteelblue',
					'class' => 'user_groups',
					'submenu' => array(
						'Groups' => '/?mode=groups',
					)
				),
			)
		);
	}
	
	if (isset($user_acl_sub_names['profiles'])) {
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Users / Groups' => array(
					'background-color' => 'lightsteelblue',
					'class' => 'user_groups',
					'submenu' => array(
						'Profiles' => '/?mode=profiles',
					)
				),
			)
		);
	}
	
	if (isset($user_acl_sub_names['business_rules'])) {
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Company Data' => array(
					'background-color' => '#B4DCAF',
					'class' => 'company_data',
					'submenu' => array(
						'Business Rules' => '/?mode=rules',
					)
				),
			)
		);

		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Company Data' => array(
					'background-color' => '#B4DCAF',
					'class' => 'company_data',
					'submenu' => array(
						'Flag Types' => '/?mode=flag_type_config',
					)
				),
			)
		);
	}

	/**
	 * This adds the Payment Type Control interface
	 */
	if (isset($user_acl_sub_names['payment_types']) || $agent->getModel()->login == 'ecash_support')
	{
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Company Data' => array(
					'background-color' => '#B4DCAF',
					'class' => 'company_data',
					'submenu' => array(
						'Payment Types' => '/?mode=payment_types',
					)
				),
			)
		);
	}

	if (isset($user_acl_sub_names['tokens'])) 
	{
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Company Data' => array(
					'background-color' => '#B4DCAF',
					'class' => 'company_data',
					'submenu' => array(
						'Tokens' => '/?mode=tokens',
					)
				),
			)
		);
	}	
	if (isset($user_acl_sub_names['holidays'])) {
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Company Data' => array(
					'background-color' => '#B4DCAF',
					'class' => 'company_data',
					'submenu' => array(
						'Holidays' => '/?mode=holidays',
					)
				),
			)
		);
	}
	
	//asm 80
        if (isset($user_acl_sub_names['dda']))
        {
                $available_items = list_available_admin_menu_merg_helper($available_items,
                        array(
                                'Company Data' => array(
                                                        'background-color' => '#B4DCAF',
                                                        'class' => 'company_data',
                                                        'submenu' => array(
                                                        'ACH Providers' => '/?mode=ach_providers',
                                                )
                                ),
                        )
                );
        }

	if (isset($user_acl_sub_names['dda']))
	{
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'DDA' => array(
					'background-color' => '#fbcd57',
					'class' => 'dda',
					'submenu' => array(
						'Application Status/React' => '/?mode=dda&dda_resource=application',
					),
				),
			)
		);
	}

	if (isset($user_acl_sub_names['dda']) && ECash::getAgent()->hasFlag('dda_access'))
	{
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'DDA' => array(
					'background-color' => '#fbcd57',
					'class' => 'dda',
					'submenu' => array(
						'Scheduled Transactions' => '/?mode=dda&dda_resource=schedule',
						'Account Adjustments' => '/?mode=dda&dda_resource=adjustments',
						'Queue Contents' => '/?mode=dda&dda_resource=queues&subsection=none',
						'Change Controlling Collections Agent' => '/?mode=dda&dda_resource=controlling_agent',
						'Reassign Agent Applications' => '/?mode=dda&dda_resource=reaffiliate',
						'Application Flags' => '/?page=DDA_Flags',
					),
				),
			)
		);
	}
	
	if (isset($user_acl_sub_names['queue_config'])) {
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Queue Config' => array(
					'background-color' => '#cccccc',
					'class' => 'queue_config',
					'submenu' => array(
						'Manage Queues' => '/?mode=queue_config&action=new_queue',
						'Timeouts' => '/?mode=queue_config&view=reset_queue_timeouts',
						'Cycle Limits' => '/?mode=queue_config&view=reset_queue_cycle_limits',
						'Recycle Now' => '/?mode=queue_config&view=recycle_queues',
						),
				),
			)
		);
		
	}

	if (isset($user_acl_sub_names['docs_config'])) {
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Document Manager' => array(
					'background-color' => '#ebaeae',
					'class' => 'docs_config',
					'submenu' => array(
						'Document Manager' => '/?mode=docs_config&view=documents',
						'Package Manager' => '/?mode=docs_config&view=packages',
						'Sorting Manager' => '/?mode=docs_config&view=sort_order',
						'Email Manager' => '/?mode=docs_config&view=email_footers',
						'Email Response Manager' => '/?mode=docs_config&view=email_responses'
						),
				),
			)
		);
		
	}
	
		if (isset($user_acl_sub_names['nada_import'])) {
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'NADA Import' => array(
					'background-color' => '#E6B54A',
					'class' => 'nada_config',
					'submenu' => array(
						'Import All' => '/?mode=nada_import'
						),
				),
			)
		);
		
	}

	/**
	 * #34853 - Displays the eCash Data Dictionary
	 */
	if (isset($user_acl_sub_names['data_dictionary'])) 
	{
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Company Data' => array(
					'background-color' => '#B4DCAF',
					'class' => 'company_data',
					'submenu' => array(
						'Data Dictionary' => '/?page=CompanyData_DataDictionary',
					)
				),
			)
		);
	}

	if (isset($user_acl_sub_names['decisioning_rules'])) 
	{
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Company Data' => array(
					'background-color' => '#B4DCAF',
					'class' => 'company_data',
					'submenu' => array(
						'Underwriting Rules' => '/?mode=underwriting_rules',
					)
				),
			)
		);
	}

	if (isset($user_acl_sub_names['decisioning_rules'])) 
	{
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'Company Data' => array(
					'background-color' => '#B4DCAF',
					'class' => 'company_data',
					'submenu' => array(
						'Suppression Rules' => '/?mode=suppression_rules',
					)
				),
			)
		);
	}
	
	//asm 99
        if (isset($user_acl_sub_names['dda']))
        {
                $available_items = list_available_admin_menu_merg_helper($available_items,
                        array(
                                'Company Data' => array(
                                                        'background-color' => '#B4DCAF',
                                                        'class' => 'company_data',
                                                        'submenu' => array(
                                                        'Global Campaign Rules' => '/?mode=global_campaign_rules',
                                                )
                                ),
                        )
                );
        }

	//this is read only, available to all
	$available_items = list_available_admin_menu_merg_helper($available_items,
		array(
			'Company Data' => array(
				'background-color' => '#B4DCAF',
				'class' => 'company_data',
				'submenu' => array(
					'ACH Return Codes' => '/?page=CompanyData_ACHCodes',
				)
			),
		)
	);
	
	return $available_items;
}

/**
 * This is needed because array_merge_recursive will create arrays out of 
 * duplicate data which isn't at all what I want.
 *
 * @param array $array1
 * @param array $array2
 */
function list_available_admin_menu_merg_helper($array1, $array2) {
	if (!is_array($array1) || !is_array($array2)) {
		return;
	}
	$keys = array_unique(array_merge(array_keys($array1), array_keys($array2)));
	foreach ($keys as $key) {
		if ((isset($array1[$key]) && is_array($array1[$key])) || (isset($array2[$key]) && is_array($array2[$key]))) {
			if (!isset($array1[$key])) {
				$array1[$key] = array();
			}
			if (!isset($array2[$key])) {
				$array2[$key] = array();
			}
			$array2[$key] = list_available_admin_menu_merg_helper((array)$array1[$key], (array)$array2[$key]);
		}
	}
	
	return array_merge($array1, $array2);
}

?>
