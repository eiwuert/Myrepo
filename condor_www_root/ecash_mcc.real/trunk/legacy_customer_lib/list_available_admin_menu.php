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
function list_available_admin_menu($user_acl_sub_names) {
	
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
	
	if (isset($user_acl_sub_names['dda'])) {
		$available_items = list_available_admin_menu_merg_helper($available_items,
			array(
				'DDA' => array(
					'background-color' => '#fbcd57',
					'class' => 'dda',
					'submenu' => array(
						'Application Status/React' => '/?mode=dda&dda_resource=application',
						'Scheduled Transactions' => '/?mode=dda&dda_resource=schedule',
						'Account Adjustments' => '/?mode=dda&dda_resource=adjustments',
						'Queue Contents' => '/?mode=dda&dda_resource=queues&subsection=none',
						'Change Controlling Collections Agent' => '/?mode=dda&dda_resource=controlling_agent',
						'Reassign Agent Applications' => '/?mode=dda&dda_resource=reaffiliate',
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
					//	'Printing Manager' => '/?mode=docs_config&view=printing_queue',
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
