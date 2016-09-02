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
function CreateList($title, $listarr)
{
	$report_desc = "";
	if(is_array($listarr))
	{			
		$report_desc .= "<b>{$title}:</b>";
		$report_desc .= "<UL>";
		for($i=0; $i<count($listarr); $i++)
			$report_desc .= "<LI>".$listarr[$i];
		$report_desc .= "</UL>";
	}
	else 
	{
		$report_desc .= $listarr;
	}	
	return $report_desc;
}

function Gen_Report_Desc($title, $search_arr,$col_arr)
{
	$report_desc = "<dl><dt><b>Overview:</b></dt><dd>{$title}</dl>";	
	$report_desc .= CreateList("Search Criteria",$search_arr);
	$report_desc .= CreateList("Columns",$col_arr);
	return str_replace("\n","",$report_desc);
}

function list_available_reports() {
	
	$return =  array(
		'agent_reports' => array(
			'background-color' => 'lightsteelblue',
			'reports' => array(
				'performance' => array(
											'name' 	=> 'Agent Actions',
											'link'  => '/?module=reporting&mode=performance',
											'desc'	=> Gen_Report_Desc("Displays agents queue performance.",
														array("Start Date", "End Date", "Loan Type","Show (All Loans,Only Reacts, Only New Loans)"),
														array("Agent","(Queues)"))
														
									),
				'collections_performance' => array(
											'name' 	=> 'Collection Agent Action',
											'link'  => '/?module=reporting&mode=collections_performance',
											'desc'	=> Gen_Report_Desc("To track collections actions by agent.",
														array(	"Date Range","Loan Type","Company"),
														array(	"Agent","Inactive Agent","Collections New",
																"Collections Returned QC","Collections General",
																"Recieved Collections","Follow Up",
																"Bankruptcy Notified", "Bankruptcy Verified",
																"Made Arrangements", "QC Ready",
																"Personal Queue Count"))
									),					
				'verification_performance' => array(
											'name' 	=> 'Verification Performance',
											'link'  => '/?module=reporting&mode=verification_performance',
											'desc'	=> Gen_Report_Desc("To review the status of loan applications that have been through the verification step.",
														array(	"Date Range","Loan Type","Company",),
														array(	"Agent","Approved","Received UW","Funded",
																"Withdrawn","Denied","Reverified"))
									),					
				'reverification' => array(
											'name' 	=> 'Reverification',
											'link'  => '/?module=reporting&mode=reverification',
											'desc'	=> Gen_Report_Desc("To view the number of applications going back to verification.",
														array(	"Date Range","Loan Type","Company"),
														array(	"Agent","Application ID","Reason"))
									),
				'follow_up' => array(
											'name' 	=> 'Follow Up',
											'link'  => '/?module=reporting&mode=follow_up',
											'desc'	=> Gen_Report_Desc("To view all of the follow ups outstanding.",
														array(	"Date Range","Loan Type","Queue", "Company"),
														array(	"Application ID","Agent","Comment","Queue",
															"Created On","Follow Up"))
									),
				'reminder_queue' => array(
											'name' 	=> 'Reminder Queue',
											'link'  => '/?module=reporting&mode=reminder_queue',
											'desc'	=> Gen_Report_Desc("To report application contact information by agent.",
														array("Agent"),
														array(	"Application ID","Last Name","First Name",
																"Transaction Scheduled Date",
																"Contact Arranged","Owning Agent"))									
									),	
				'payment_arrangements' => array(
											'name' 	=> 'Payment Arrangements',
											'link'  => '/?module=reporting&mode=payment_arrangements',
											'desc'	=> Gen_Report_Desc("To review automatic payment arrangements.",
														array(	"Date Range","Loan Type","Date Search","Company",),
														array(	"Created Date","Agent Name","Application ID",
																"Customer Name","Return Reason","Payment Date",
																"Amount","Method","Status"))
									),	
				'manual_payment' => array(
											'name' 	=> 'Manual Payments',
											'link'  => '/?module=reporting&mode=manual_payment',
											'desc'	=> Gen_Report_Desc("To view manual payments.",
														array(	"Date Range","Loan Type","Payment Type","Company"),
														array(	"Agent Name","Controlling Agent","Application ID",
																"Customer Name","Payment Type","Payment Date",
																"Amount",))
									),
			)
		),
		'applicant_reports' => array(
			'background-color' => '#ff9999',
			'reports' => array(
				'current_weekly_summary' => array(
											'name'  => 'Current Weekly Summary Report',
											'link'  => '/?module=reporting&mode=current_weekly_summary',
											'desc'  => Gen_Report_Desc("To view the status of leads entered into eCash.",
														array(	"Date Range", "Company"	),
														array(	"Company", "Week", "Leads Bought", "Funded",
																"Funded Amount", "Deposits", "Disbursements"	))
									),
				'current_lead_status' => array(
											'name'  => 'Current Lead Status Report',
											'link'  => '/?module=reporting&mode=current_lead_status',
											'desc'  => Gen_Report_Desc("To view the status of leads entered into eCash.",
														array(	"Date Range", "Company"	),
														array(	"Date Lead Bought", "Bought", "Unsigned", "Expired",
																"I agree", "Pending", "Withdrawn", "Denied", "Funded",
																"Funding Failed"	))
									),
			'leads_by_campaign' => array(
											'name'  => 'Leads By Campaign Report',
											'link'  => '/?module=reporting&mode=leads_by_campaign',
											'desc'  => Gen_Report_Desc("To view the status of leads entered into eCash by camapign.",
														array(	"Date Range", "Company"	),
														array(	"Company Name", "Campaign Name", "Bought", 
																"Agree",  "Funded"	))
									),
			'accounts_recievable' => array(
											'name' 	=> 'AR Report',
											'link'  => '/?module=reporting&mode=accounts_recievable',
											'desc'	=> Gen_Report_Desc("Displays AR Report.",
														array("Specific Date"),
														array('Company','Application ID','Last Name',
		                                   'First Name','Status','Previous Status','Fund Date','Funded Age',
		                                   'Collection Age','Status Age','Payoff Amount','Principal Pending',
		                                   'Principal Failed','Service Charge Pending','Service Charge Failed',
		                                   'Fees Pending','Fees Failed','NSF Ratio'))
									),
				'withdrawn_deny_loan_actions' => array(
											'name' 	=> 'Withdrawn / Denied',
											'link'  => '/?module=reporting&mode=withdrawn_deny_loan_actions',
											'desc'	=> Gen_Report_Desc("Shows withdrawn or denied applications with associated dispositions.",
														array(	"Start Date","End Date"),
														array(	"App ID","Current Status","Disposition",
																"Agent","Status","Date"))
									),	
				'applicant' => array(
											'name' 	=> 'Applicant',
											'link'  => '/?module=reporting&mode=applicant',
											'desc'	=> Gen_Report_Desc("Shows how many applicants went through the loan process.",
														array(	"Start Date","End Date","Loan Type (Standard, Card, ALL)"),
														array(	"Application ID","Received Verify","Received UW",
																"Funded","Approved","Withdrawn","Denied","Reverified"))
									),	
				'payments_due' => array(
											'name' 	=> 'ACH Payments Due',
											'link'  => '/?module=reporting&mode=payments_due',
											'desc'	=> Gen_Report_Desc("To view all ACH payments due on a given date.",
														array(	"Specific Date","Loan Type","Company"),
														array(	"Application ID","Last Name","First Name",
																"Status","Pay Period","DD","Principal",
																"Fees","Interest","Total Due",
																"Next Scheduled","Loan Type","First Time Due",	
																"Pay Out","Special Arrangements"))
									),
				'cc_payments_due' => array(
											'name' 	=> 'C.C. Payments Due',
											'link'  => '/?module=reporting&mode=cc_payments_due',
											'desc'	=> Gen_Report_Desc("Display credit card payments made.",
														array(	"Date","Agent",),
														array(	"Agent","Application ID","Customer Name",
																"Principal Amt.","Fee Amt."))
									),
				'loan_actions' => array(
											'name' 	=> 'Verification Triggers',
											'link'  => '/?module=reporting&mode=loan_actions',
											'desc'	=> Gen_Report_Desc("To review scores for individual applicants.",
														array(	"Date Range","Loan Type","Company"),
														array(	"Application ID","Last Name","First Name",
																"Fund Amount","SSN","Score","Date Funded"))
									),	
				'loan_activity' => array(
                                            'name'  => 'Loan Activity',
                                            'link'  => '/?module=reporting&mode=loan_activity',
                                            'desc'  => Gen_Report_Desc("Displays loan activity between a selected date range.",
                                                        array(	"Date Range","Loan Type","Company", "Date Type"),
                                                        array(	'Company', 'Payment Date',
																'Fund Date','Application ID',
																'Last Name', 'First Name',
																'SSN', 'Customer ID',
																'Transaction ID', 'Original Loan Amount',
                                           						'Payoff Amount', 'Transaction Amount',
                                           						'Transaction Type', 'Credit/Debit',
                                           						'Current Status', 'Agent Name',
                                           						'New/React', 'Application Status'))
									),

				'transaction_history' => array(
											'name' 	=> 'Transaction History',
											'link'  => '/?module=reporting&mode=transaction_history',
											'desc'	=> Gen_Report_Desc("Displays a breakdown of transactions for the selected day.",
														array(	"Date", "Loan (All,Standard,Card"),
														array(	"Application ID", "Time Modified", "Transaction ID", "Transaction Type", "Previous", 
																"New Status", "Amount", "Agent Name"))
									),	
				'status_history' => array(
											'name' 	=> 'Status History',
											'link'  => '/?module=reporting&mode=status_history',
											'desc'	=> Gen_Report_Desc("Display status history for applications on a given date and queue.",
														array("Date Range"),
														array("Application ID", "Time Modified", "Previous",
														"New Status","Agent ID","Agent Name"))
									),	
				'queue_summary' => array(
											'name' 	=> 'Queue Summary',
											'link'  => '/?module=reporting&mode=queue',
											'desc'	=> Gen_Report_Desc("Shows overview of applications in a given queue.",
														array(	"Queue"),
														array(	"# (Order)","Hours","Application ID",
																"First Name","Last Name","SSN", "Address",
																"City","State","Balance","Status"))
									),										
				'my_queue'      => array(
											'name'	=> 'My Queue',
											'link'  => '/?module=reporting&mode=my_apps',
											'desc'  => Gen_Report_Desc( "Displays applications in My Queue.",
														array( "Agent"),
														array( "Application ID", "First Name", "Last Name",
															   "SSN", "Expiration Date", "Follow-up Date",
															   "Location", "Type", "Controlling Agent"))
									),
				'inactive_paid_status' => array(
											'name' 	=> 'Inactive Paid Status',
											'link'  => '/?module=reporting&mode=inactive_paid_status',
											'desc'	=> Gen_Report_Desc("Display applications that have become Inactive Paid.",
														array(	"Start Date","End Date"),
														array(	"Application ID","Last Name","First Name",
																"SSN","Paid Off Date","Fund Amount", "Reactivated"))
									),
				'reactivation_marketing' => array(
											'name' 	=> 'Reactivation Marketing',
											'link'  => '/?module=reporting&mode=reactivation_marketing',
											'desc'	=> Gen_Report_Desc("Display applications that have become Inactive Paid for determining marketing.",
														array(	"Start Date","End Date"),
														array(	"Application ID","Last Name","First Name",
																"SSN","Paid Off Date","Fund Amount", "Reactivated", "DoNotLoan", "DoNotMarket"))
									),
				'rouge_signed_docs' => array(
											'name' 	=> 'Tiff Funded Report',
											'link'  => '/?module=reporting&mode=rouge_signed_docs',
											'desc'	=> Gen_Report_Desc("Lists applications that have been tiffed but have not been funded.",
														array("Start Date","Status"),
														array("Application ID","Application Created","Current Status"))
									),
				'nsf' => array(
											'name' 	=> 'NSF Report',
											'link'  => '/?module=reporting&mode=nsf',
											'desc'	=> Gen_Report_Desc("Shows batch comparisons Reports and Non Report Debit Amounts.",
														array(	"Start Date","End Date Date",
																"Show: Reported/Non Reported",
																"Show: Reported/Non Reported (New Loans)",
																"Show: Reported/Non Reported (Reacts)",
																"Show: Reported/Non Reported by Status Type",
																"Ach Type: (Debit/Credit)"),
														"<table width=100% border=1><tr><td valign=top style=text-align:left;>
														<b>Columns:</b>
														<UL>														
															<LI>Batch ID
															<LI>Batch Created
															<LI>Non Reported Debit
															<LI>Reported Debit
															<LI>Total Debit
															<LI>Non Debit Amount
															<LI>Rep Debit Amount
															<LI>Total Debit Amount
															<LI>Reported Debit Percent
															<LI>Amount Debit Percent
														</UL>
														</td><td valign=top style=text-align:left;>
														<b>Columns (by Status Type):</b>
														<UL>
															<LI>Status
															<LI>Non Reported Debit
															<LI>Reported Debit
															<LI>Total Debit
															<LI>Non Debit Amount
															<LI>Rep Debit Amount
															<LI>Total Debit Amount
															<LI>Reported Debit Percent
															<LI>Amount Debit Percent
														</UL>
														</td></tr></table>")
									),
			)
		),
		'batch_reports' => array(
			'background-color' => '#ffbb88',
			'reports' => array(

				'achbatch' => array(
											'name' 	=> 'ACH Batch Report',
											'link'  => '/?module=reporting&mode=achbatch',
											'desc'	=> Gen_Report_Desc("Displays ACH Batch report for a date range.",
														array(	"Date Range"),
														array( 'Date','# Credits','$ Credits', '# Debits','$ Debits','# Net','$ Net', '# Returned','$ Returned', '$ Net after returns', '# Unauthorized Returns',
																 '$ Unauthorized Returns'))
									),
				'loan_posting' => array(
											'name' 	=> 'Loan Posting',
											'link'  => '/?module=reporting&mode=loan_posting',
											'desc'	=> Gen_Report_Desc("Displays applications that have posted a loan payment.",
														array(	"Date"),
														array(	"Application ID","Last Name","First Name","ABA #","Account #",
																"Card #","Amount","Current Due Date","Loan Typen"))
									),
				'return_item_summary' => array(
											'name' 	=> 'Return Item Summary',
											'link'  => '/?module=reporting&mode=return_item_summary',
											'desc'	=> Gen_Report_Desc("To view application status statistics.",
														array(	"Date Range","Loan Type"),
														array(	"Application ID","Last Name","First Name","Date Sent",
																"Reason and Code","Routing &#35; / Account &#35;",
																"Debits","Credits","Notes","Is Reattempt",
																"Fatal Return")),			
									),	
				'corrections' => array(
											'name' 	=> 'Corrections',
											'link'  => '/?module=reporting&mode=corrections',
											'desc'	=> Gen_Report_Desc("To view corrections from your ACH processor.",
														array(	"Date Range","Loan Type"),
														array(	"Application ID","Correction"))
									),		
				'batch_review' => array(
											'name' 	=> 'Batch Review',
											'link'  => '/?module=reporting&mode=batch_review',
											'desc'	=> Gen_Report_Desc("To review batch transactions.",
														"",
														array(	"Application ID","Customer Name","ABA &#35;",
															"Account &#35;","Account Type","Amount","ACH Type"))									
									),
			)
		),
		'monitor' => array(
			'background-color' => '#ffbb88',
			'reports' => array(
				'monitor' => array(
											'name' 	=> 'Monitor Report',
											'link'  => '/?module=reporting&mode=monitor',
											'desc'	=> Gen_Report_Desc("To monitor agent statistics.",
														array(	"Performance Options","Agent"),
														array(	"<i>Graph</i>"))
									),
				),
		),
		'overview_reports' => array(
			'background-color' => '#B4DCAF',
			'reports' => array(
				'open_advances' => array(
											'name' 	=> 'Open Advances',
											'link'  => '/?module=reporting&mode=open_advances',
											'desc'	=> Gen_Report_Desc("To view the amount of monies outstanding for each loan status.",
														array(	"Specific Date","Loan Type","Company"),
														array(	"Status","Count Pos","&#36; Pos","Count Neg",
																"&#36; Neg","Count Total","Total"))
									),	
				'open_advances_detail' => array(
											'name' 	=> 'Open Advances Detailed',
											'link'  => '/?module=reporting&mode=open_advances_detail',
											'desc'	=> Gen_Report_Desc("To view detailed information on the amount of monies outstanding on the specified date for each loan status.",
														array(	"Specific Date","Company"),
														array(	"Status","Count Pos","&#36; Pos","Count Neg",
																"&#36; Neg","Count Total","Total"))
								),
				'loan_status' => array(
											'name'  => 'Loan Status Report',
											'link'  => '/?module=reporting&mode=loan_status',
											'desc'  => Gen_Report_Desc("To view the status of loans in eCash.",
														array(	"Date Range", "Company"	),
														array(	"Company", "Application ID", "Current Loan Status"))
									),
				'collections_projected' => array(
											'name' 	=> 'Collections Projected',
											'link'  => '/?module=reporting&mode=collections_projected',
											'desc'	=> Gen_Report_Desc("To view the amount of monies projected to be collected.",
														array(	),
														array(	'amount_delinquient_first_returns',          
																'amount_delinquent_all_else',
																'total_delinquent',
																'delinquent_first_returns_attempted',
																'delinquent_all_other_arranged_today',
																'delinquent_previously_arranged',
																'total',
																'projected_cleared'))
									),	
				'collections_summary' => array(
							'name' 	=> 'Collections Summary',
							'link'  => '/?module=reporting&mode=collections_summary',
							'desc'	=> Gen_Report_Desc("To view the amount and number of collections scheduled for a date range.",
										array("Date Range","Company"	),
										array(	'Date' ,
										   '# of Scheduled' , 
		                                   '$ of Scheduled',
		                                   '# of Returns',
		                                   '$ of Returns',
		                                   'Today\'s # of Future Scheduled Accounts',
		                                   'Today\'s $ value of Future Scheduled Accounts'))
									),
				'status_overview' => array(
											'name' 	=> 'Status Overview',
											'link'  => '/?module=reporting&mode=status_overview',
											'desc'	=> Gen_Report_Desc("Displays application balance by balance type and application status.",
														array(	"Balance Type", "Status"),
														array(	"Application ID", "First Name", "Last Name", "SSN",  "Street",
														  		"City", "State", "Principal Balance"))
									),
				'flash' => array(
											'name' 	=> 'Flash',
											'link'  => '/?module=reporting&mode=flash',
											'desc'	=> Gen_Report_Desc("To view the number of customers by status per pay period.",
														array(	"Specific Date","Loan Type","Company"),
														array(	"Pay Period","Status","Customer Count"))
									),
			)
		),
		'admin_reports' => array(
			'name' => 'Admin Reports',
			'background-color' => '#ffbb88',
			'reports' => array(
				/*
				Example Report
				'daily_cash' => array(
											'name' 	=> 'Daily Cash Report',
											'link'  => '/?module=reporting&mode=daily_cash',
											'desc'	=> Gen_Report_Desc(
																		"Report Overview",
																		array("Date Range"),
																		array("Column - Description"))
									),		
				*/
											
				)
		),		

	);
	if(ECash::getCompany()->name_short == 'icf')
	{
		$return['applicant_reports']['reports']['cfs_loan_activity'] = array(
                                            'name'  => 'CSHF Loan Activity',
                                            'link'  => '/?module=reporting&mode=cfs_loan_activity',
                                            'desc'  => Gen_Report_Desc("Displays CSHF loan activity between a selected date range.",
                                                        array(	"Date Range","Loan Type","Company", "Date Type"),
                                                        array(	'Company', 'Payment Date',
																'Fund Date','Application ID',
																'Last Name', 'First Name',
																'SSN', 'Customer ID',
																'Transaction ID', 'Original Loan Amount',
                                           						'Payoff Amount', 'Transaction Amount',
                                           						'Transaction Type', 'Credit/Debit',
                                           						'Current Status', 'Agent Name',
                                           						'New/React', 'Application Status'))
									);
	}
	return $return;
}

?>
