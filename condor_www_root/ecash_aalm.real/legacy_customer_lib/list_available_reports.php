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
	
	return array(
		'agent_reports' => array(
			'name' => 'Agent Reports',
			'background-color' => 'lightsteelblue',
			'reports' => array(
				
				'performance' => array(
											'name' 	=> 'Agent Actions',
											'link'  => '/?module=reporting&mode=performance',
											'desc'	=> Gen_Report_Desc("Displays agents queue performance.",
														array("Start Date", "End Date", "Loan Type","Show (All Loans,Only Reacts, Only New Loans)"),
														array("Company", "Verify Queue (New)", "Verify Queue (React)", "UW Queue (New)", 
															  "UW Queue (React)", "Received Verify (New)", "Received Verify (React)", 
															  "Approved (React)", "Received UW (New)", "Received UW (React)", 
															  "Reverified (New)", "Reverified (React)", "Funded (New)", "Funded (Dupl)", 
															  "Funded (React)", "Withdrawn (New)", "Withdrawn (React)", "Denied (New)", 
															  "Denied (React)", "Followup (React)"))
									),
				'agent_internal_recovery' => array(
											'name' 	=> 'Agent Internal Recovery',
											'link'  => '/?module=reporting&mode=agent_internal_recovery',
											'desc'	=> Gen_Report_Desc("To view recovery information by agent.",
														array(	"Start Date", "End Date","Agent","Company"),
														array(	"Agent","Paid","Paid &#37;","Paid Amount",
																"Paid Amount &#37;","Failed","Failed &#37;",
																"Failed Amount","Failed Amount &#37",
																"Total Scheduled"))
									),										
				'agent_tracking' => array(
											'name' 	=> 'Agent Tracking',
											'link'  => '/?module=reporting&mode=agent_tracking',
											'desc'	=> Gen_Report_Desc("To track application statuses by agent.",
														array(	"Company", "Start Date", "End Date"),
														array(	"Agent",
														"React Button",
														"React Offer Button",
														"Search Apps",
														"Verification (react)",
																"Verification (non-react)",
																"Underwriting (react)",
																"Underwriting (non-react)", 
																"Underwriting",
																"Watch",
																"Collections New",
																"Collections Returned QC",
																"Collections General"))
									),										
				'collections_performance' => array(
											'name' 	=> 'Collection Agent Action',
											'link'  => '/?module=reporting&mode=collections_performance',
											'desc'	=> Gen_Report_Desc("To track collections actions by agent.",
														array(	"Start Date", "End Date","Loan Type","Company"),
														array(	"Company", "Agent","Inactive Agent","Collections New",
																"Collections Returned QC","Collections General",
																"Received Collections","Follow Up",
																"Bankruptcy Notified", "Bankruptcy Verified",
																"Made Arrangements", "QC Ready",
																"Personal Queue Count"))
									),		
				'controlling_agent' => array(
											'name' 	=> 'Controlling Collection Agent',
											'link'  => '/?module=reporting&mode=controlling_agent',
											'desc'	=> Gen_Report_Desc("To view the status of collections per agent.",
														array(	"Agent","Company"),
														array(	"Company", "Application ID", "Last Name", "First Name",
																"Contact Arranged","Owning Agent"))
									),	
				'follow_up' => array(
											'name' 	=> 'Follow Up',
											'link'  => '/?module=reporting&mode=follow_up',
											'desc'	=> Gen_Report_Desc("To view all of the follow ups outstanding.",
														array(	"Start Date", "End Date","Loan Type","Type", "Company", "Queue (All, Underwriting, Verification, Collections)"),
														array(	"Application ID","Agent","Comment","Queue",
															"Created On","Follow Up"))
									),
				'fraud' => array(
											'name' 	=> 'Fraud Performance',
											'link'  => '/?module=reporting&mode=fraud_performance',
											'desc'	=> Gen_Report_Desc("To view actions related to fraud by agent.",
														array(	"Start Date", "End Date","Queue Type","Company"),
														array(	"Agent","Pulled","Released","Follow Up","Withdrawn",
																"Denied",))
									),
				'manual_payment' => array(
											'name' 	=> 'Manual Payments',
											'link'  => '/?module=reporting&mode=manual_payment',
											'desc'	=> Gen_Report_Desc("To view manual payments.",
														array(	"Start Date", "End Date","Loan Type","Payment Type","Company"),
														array(	"Company", "Agent Name","Controlling Agent","Application ID",
																"Customer Name","Payment Type","Payment Date",
																"Amount",))
									),
				'my_apps' => array(
											'name' 	=> 'My Queue',
											'link'  => '/?module=reporting&mode=my_apps',
											'desc'	=> Gen_Report_Desc("Shows what applications are in the agents personal queue.",
														array(	"Company", "Agent"),
														array(	"Application ID","First Name","Last Name","SSN",
																"Expiration Date","Follow-up Date","Location","Type",
																"Controlling Agent"))
									),	
				'payment_arrangements' => array(
											'name' 	=> 'Payment Arrangements',
											'link'  => '/?module=reporting&mode=payment_arrangements',
											'desc'	=> Gen_Report_Desc("To review automatic payment arrangements.",
														array(	"Start Date", "End Date","Loan Type","Date Search","Company",),
														array(	"Created Date","Agent Name","Application ID",
																"Customer Name","Return Reason","Payment Date",
																"Amount","Method","Status"))
									),	
				'reminder_queue' => array(
											'name' 	=> 'Reminder Queue',
											'link'  => '/?module=reporting&mode=reminder_queue',
											'desc'	=> Gen_Report_Desc("To report application contact information by agent.",
														array("Company","Agent"),
														array(	"Company", "Application ID","Last Name","First Name",
																"Transaction Scheduled Date",
																"Contact Arranged","Owning Agent"))											
									),										
				'reverification' => array(
											'name' 	=> 'Reverification',
											'link'  => '/?module=reporting&mode=reverification',
											'desc'	=> Gen_Report_Desc("To view the number of applications going back to verification.",
														array(	"Start Date", "End Date","Loan Type","Company"),
														array(	"Company", "Agent","Application ID","Reason"))
									),										
				'verification_performance' => array(
											'name' 	=> 'Verification Performance',
											'link'  => '/?module=reporting&mode=verification_performance',
											'desc'	=> Gen_Report_Desc("To review the status of loan applications that have been through the verification step.",
														array(	"Date Range","Loan Type","Company",),
														array(	"Company", "Agent","Approved","Received UW","Funded",
																"Withdrawn","Denied","Reverified"))
									),
				'agent_email_queue' => array(
											'name' 	=> 'Agent Email Queue',
											'link'  => '/?module=reporting&mode=agent_email_queue',
											'desc'	=> Gen_Report_Desc("To view email queue actions by agent.",
														array(	"Start Date", "End Date", "Agent","Company",),
														array(	'Agent','Received','Associated',
 				                                                'Responded','Follow Ups','Filed',
				                                                'Queue Change','Canned Responses','Removed'))
									),

			)
		),
		'applicant_reports' => array(
			'name' => 'Applicant Reports',
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
														array("Company","Specific Date"),
														array('Company','Application ID','Last Name',
		                                   'First Name','Status','Previous Status','Fund Date','Funded Age',
		                                   'Collection Age','Status Age','Payoff Amount','Principal Pending',
		                                   'Principal Failed','Service Charge Pending','Service Charge Failed',
		                                   'Fees Pending','Fees Failed','NSF Ratio'))
									),
				'payments_due' => array(
											'name' 	=> 'ACH Payments Due',
											'link'  => '/?module=reporting&mode=payments_due',
											'desc'	=> Gen_Report_Desc("To view all ACH payments due on a given date.",
														array(	"Start Date","End Date","Loan Type","Company"),
														array(	"Company", "Payment Date", "Application ID","Last Name","First Name",
																"Status","Pay Period","DD","Principal",
																"Fees","Interest","Total Due",
																"Next Scheduled","Loan Type","First Time Due",	
																"Pay Out","Special Arrangements"))
									),			
				'applicant' => array(
											'name' 	=> 'Applicant',
											'link'  => '/?module=reporting&mode=applicant',
											'desc'	=> Gen_Report_Desc("Shows how many applicants went through the loan process.",
														array(	"Start Date","End Date","Loan Type (All, Payday Loan)"),
														array(	"Company", "Application ID","Received Verify","Received UW",
																"Funded","Approved","Withdrawn","Denied","Reverified"))
									),			

				'cc_payments_due' => array(
											'name' 	=> 'C.C. Payments Due',
											'link'  => '/?module=reporting&mode=cc_payments_due',
											'desc'	=> Gen_Report_Desc("Display credit card payments made.",
														array(	"Company", "Date","Agent",),
														array(	"Company", "Agent","Application ID","Customer Name",
																"Principal Amt.","Fee Amt.","Total Due"))									
									),	
				'chargeback' => array(
											'name' 	=> 'Chargeback Report',
											'link'  => '/?module=reporting&mode=chargeback',
											'desc'	=> Gen_Report_Desc("Displays Chargebacks and Chargeback Reversals.",
														array(	"Company", "Start Date","End Date","Chargeback Types (All, Chargebacks, Chargebacks Reverals)"),
														array(	"Company", "Date","Application ID","Name","Chargeback Type","Amount"))
									),		
				'dnl_override' => array(
											'name' 	=> 'DNL Override Report',
											'link'  => '/?module=reporting&mode=dnl_override',
											'desc'	=> Gen_Report_Desc("Reports on applications that override Do Not Loan.",
														array(	"Start Date","End Date"),
														array(	"App ID","Company","First Name","Last Name","SSN",
																"No. of Set DNL","Co of Recent DNL","Override Date",
																"Current Status","Agent"))
									),																					
				'dnl' => array(
											'name' 	=> 'DNL Report',
											'link'  => '/?module=reporting&mode=dnl',
											'desc'	=> Gen_Report_Desc(">Display applications with Do No Loan enable with reason.",
														array(	"Company", "Start Date","End Date"),
														array(	"App ID","Company (Owner)","Firat Name","Last Name",
																"SSN","DNL Category","DNL Explanation","DNL Set Date",
																"Current Status","Agent"))
									),		
				'fraud_balance' => array(
											'name' 	=> 'Fraud Balance',
											'link'  => '/?module=reporting&mode=fraud_balance',
											'desc'	=> Gen_Report_Desc("Applications added and removed from the Fraud and High Risk queue.",
														array(	"Company", "Start Date","End Date"),
														array(	"Underwriting","Verification",
																"Withdrawn","Denied","Total"))
									),
				'fraud_deny' => array(
											'name' 	=> 'Fraud Denied',
											'link'  => '/?module=reporting&mode=fraud_deny',
											'desc'	=> Gen_Report_Desc("Applications denied in eCash/OLP by confirmed fraud rules",
														array("Company", "Start Date","End Date"),
														array("Rule Name", "Rule Description", "# Denied"))
									),
				'fraud_proposition' => array(
											'name' 	=> 'Fraud Proposition',
											'link'  => '/?module=reporting&mode=fraud_proposition',
											'desc'	=> Gen_Report_Desc("Show all rules with propositions and their matched applications and outcomes.",
														array("Company"),
														array("Rule Name", "Proposition #", "Application ID", "Hours", "Outcome"))
									),
				'fraud' => array(
											'name' 	=> 'Fraud Report',
											'link'  => '/?module=reporting&mode=fraud',
											'desc'	=> Gen_Report_Desc("Detailed list of all funded applications for fraud research purposes.",
														array(	"Company", "Start Date","End Date","Loan Type",),
														array(	"Application ID","Last Name","First Name",
																"Home Street","Home City","Home State",
																"Home Zip Code","SSN","DOB","Home Phone",
																"Employer","Employer Phone","Income",
																"Pay Period","Bank Name","Bank Account Number",
																"Bank ABA","Principal Amount","First Due Date",
																"Email Address","IP Address","Timestamp"))
									),						
				'fraud_full_queue' => array(
											'name' 	=> 'High Risk/Fraud Full Queue',
											'link'  => '/?module=reporting&mode=fraud_full_queue',
											'desc'	=> Gen_Report_Desc("Detailed list of all applications in the Fraud and High Risk queues.",
														array(	"Company", "Queue"),
														array(	"Company", "Queue", "App ID", "Last Name", "First Name", 
																"Home Street", "Home City", "Home State", "Home Zip Code", 
																"SSN", "Home Phone", "Employer", "Employer Phone", "Income", 
																"Pay Period", "Bank Name", "Bank Account Number", "Bank ABA", 
																"Principal Amount", "First Due Date", "Email Address", 
																"IP Address", "Timestamp"))
									),						
				'fraud_queue' => array(
											'name' 	=> 'High Risk/Fraud Queue',
											'link'  => '/?module=reporting&mode=fraud_queue',
											'desc'	=> Gen_Report_Desc("Applications in High Risk and Fraud queues.",
														array(	"Company", "Queue"),
														array(	"Hours", "Application ID", "Matching Rules"))
									),						
				'inactive_paid_status' => array(
											'name' 	=> 'Inactive Paid Status',
											'link'  => '/?module=reporting&mode=inactive_paid_status',
											'desc'	=> Gen_Report_Desc("Display applications that have become Inactive Paid.",
														array(	"Company", "Start Date","End Date"),
														array(	"Application ID","Last Name","First Name",
																"SSN","Paid Off Date","Fund Amount"))
									),
				'internal_recovery' => array(
											'name' 	=> 'Internal Recovery Report',
											'link'  => '/?module=reporting&mode=internal_recovery',
											'desc'	=> Gen_Report_Desc("Reports on collections paid versus failed payments.",
														array(	"Company", "Start Date","End Date"),
														array(	"Company", "Date","Paid","Paid %","Paid Amt.","Paid Amt. %","Failed",
																"Failed %","Failed Amt.","Failed Amt. %","Total Sched.",
																"Total Sched. Amt."))
									),																
                'loan_activity' => array(
                                            'name'  => 'Loan Activity',
                                            'link'  => '/?module=reporting&mode=loan_activity',
                                            'desc'  => Gen_Report_Desc("Displays loan activity between a selected date range.",
                                                        array(  "Date Range","Loan Type","Company", "Date Type"),
                                                        array(  'Company', 'Payment Date',
                                                                'Fund Date','Application ID',
                                                                'Last Name', 'First Name',
                                                                'Transaction ID', 'Original Loan Amount',
                                                                'Payoff Amount', 'Transaction Amount',
                                                                'Transaction Type', 'Credit/Debit',
                                                                'Current Status', 'Agent Name',
                                                                'New/React', 'Application Status'))
									),
				'nonach_payments_due' => array(
											'name' 	=> 'Non ACH Payments Due',
											'link'  => '/?module=reporting&mode=nonach_payments_due',
											'desc'	=> Gen_Report_Desc("To view all Non-ACH payments due on a given date.",
														array(	"Specific Date","Loan Type","Company"),
														array(	"Application ID","Last Name","First Name",
																"Status","Pay Period","DD","Principal",
																"Fees","Interest","Total Due",
																"Next Scheduled","Loan Type","First Time Due",
																"Pay Out","Special Arrangements"))
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
// Preacts will not be enabled till a later phase [BrianR]
//				'preact_pending' => array(
//											'name' 	=> 'Preact Pending',
//											'link'  => '/?module=reporting&mode=preact_pending',
//											'desc'	=> Gen_Report_Desc("Displays Preacts and parent application.",
//														array(	"Start Date","End Date",),
//														array(	"Date Created","Parent App ID",
//																"Preact App ID","Preact Est. Fund Date"))
//									),	
				'queue_summary' => array(
											'name' 	=> 'Queue Summary',
											'link'  => '/?module=reporting&mode=queue',
											'desc'	=> Gen_Report_Desc("Shows overview of applications in a given queue.",
														array(	"Company", "Queue"),
														array(	"# (Order)","Hours","Application ID",
																"First Name","Last Name","SSN", "Address",
																"City","State","Balance","Status"))
									),																			
				'queue_overview' => array(
											'name' 	=> 'Queue History Overview',
											'link'  => '/?module=reporting&mode=queue_overview',
											'desc'	=> Gen_Report_Desc("Displays applications in specified queue for date selected.",
														array(	"Company", "Start Date","End Date","Queue"),
														array(	"Company", "Application ID","Queue","Pulling Agent",
																"Date Inserted",
																"Date Unavailable", "Date Removed"))
									),
				'reattempts_detailed' => array(
											'name' 	=> 'Reattempts Detailed',
											'link'  => '/?module=reporting&mode=reattempts_detailed',
											'desc'	=> Gen_Report_Desc("Shows overview of Reattempt details made during selected time period.",
														array(	"Company", "Start Date","End Date"),
														array(	"Application ID","Status","Schedule ID",
																"New Principal","New Svc. Chg","New Fees",  	
																"Re. Principal","Re. Svc. Chg.","Re. Fees"))
									),
				'reattempts' => array(
											'name' 	=> 'Reattempts Report',
											'link'  => '/?module=reporting&mode=reattempts',
											'desc'	=> Gen_Report_Desc("Shows overview of Reattempts made during selected time period.",
														array(	"Company", "Start Date","End Date"),
														array(	"New Principal","New Svc. Chg","New Fees",
																"Pre Principal","New Principal"))
									),	
				
/*				'returned_quickchecks' => array(
											'name' 	=> 'Returned QC Report',
											'link'  => '/?module=reporting&mode=returned_quickchecks',
											'desc'	=> Gen_Report_Desc("Displays applications with returned Quickchecks",
														array(	"Start Date","End Date"),
														array(	"Date",	"Application ID","Name","Reason Code","Reason Description",
														  		"Return Count",	"Amount"))
									),																		
*/
			/*	'score' => array(
											'name' 	=> 'Score',
											'link'  => '/?module=reporting&mode=score',
											'desc'	=> Gen_Report_Desc("Used to review individual applicant\'s scores.",
														array(	"Start Date","End Date","Loan Type (Standard, Card, ALL)"),
														array(	"Application ID","Last Name","First Name","Fund Amount",
																"SSN","Score","Date Funded"))
									),
			*/
				'status_history' => array(
											'name' 	=> 'Status History',
											'link'  => '/?module=reporting&mode=status_history',
											'desc'	=> Gen_Report_Desc("Display status history for applications on a given date and queue.",
														array("Start Date", "End Date"),
														array("Application ID", "Time Modified", "Previous",
														"New Status","Agent ID","Agent Name"))
									),									

				'transaction_history' => array(
											'name' 	=> 'Transaction History',
											'link'  => '/?module=reporting&mode=transaction_history',
											'desc'	=> Gen_Report_Desc("Displays a breakdown of transactions for the selected day.",
														array(	"Company", "Date", "Loan (All,Standard,Card"),
														array(	"Application ID", "Time Modified", "Transaction ID", "Transaction Type", "Previous", 
																"New Status", "Amount", "Agent Name"))								
									),	
				'delayed_pulled' => array(
											'name' 	=> 'Unpulled Audit Report',
											'link'  => '/?module=reporting&mode=delayed_pulled',
											'desc'	=> Gen_Report_Desc("This report shows applications that are still in the Agree status and shows the date the application was created and when the Agree status was set.",
														array(	"Company", "Start Date","End Date"),
														array(	"Company", "Date Created", "Date Agreed", "Application ID" ,"Name" ,"Type", "Loan Type"))
									),	
				'loan_actions' => array(
											'name' 	=> 'Verification Triggers',
											'link'  => '/?module=reporting&mode=loan_actions',
											'desc'	=> Gen_Report_Desc("To review scores for individual applicants.",
														array(	"Start Date","End Date","Loan Type","Company"),
														array(	"Application ID","Last Name","First Name",
																"Fund Amount","SSN","Score","Date Funded"))
									),	
				'web_queue' => array(
											'name' 	=> 'Web Queue',
											'link'  => '/?module=reporting&mode=web_queue',
											'desc'	=> Gen_Report_Desc("Displays applications currently in the Web Queue.",
														array("Company"),
														array(	"#","Hours","Application ID","First Name","Last Name",
		                                   						"SSN","Address","City","State","Balance","Status",
										   						"Event","Amount"))
									),									
				'withdrawn_deny_loan_actions' => array(
											'name' 	=> 'Withdrawn / Denied',
											'link'  => '/?module=reporting&mode=withdrawn_deny_loan_actions',
											'desc'	=> Gen_Report_Desc("Shows withdrawn or denied applications with associated dispositions.",
														array(	"Company", "Start Date","End Date"),
														array(	"Company","App ID","Name", "SSN", "Current Status","Disposition",
																"Agent","Status","Date"))
									),									
			)
		),
		'batch_reports' => array(
			'name' => 'Batch Reports',
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
				'batch_review' => array(
											'name' 	=> 'Batch Review',
											'link'  => '/?module=reporting&mode=batch_review',
											'desc'	=> Gen_Report_Desc("To review batch transactions.",
														array("Company"),
														array(	"Application ID","Customer Name","ABA &#35;",
															"Account &#35;","Account Type","Amount","ACH Type"))									
									),				
				'corrections' => array(
											'name' 	=> 'Corrections',
											'link'  => '/?module=reporting&mode=corrections',
											'desc'	=> Gen_Report_Desc("To view corrections from your ACH processor.",
														array(	"Start Date", "End Date", "Loan Type"),
														array(	"Application ID","Correction"))
									),										
				'loan_posting' => array(
											'name' 	=> 'Loan Posting',
											'link'  => '/?module=reporting&mode=loan_posting',
											'desc'	=> Gen_Report_Desc("Displays applications that have posted a loan payment.",
														array(	"Date"),
														array(	"Application ID","Last Name","First Name","ABA #","Account #",
																"Card #","Amount","Current Due Date","Loan Type"))
									),			
				'return_item_summary' => array(
											'name' 	=> 'Return Item Summary',
											'link'  => '/?module=reporting&mode=return_item_summary',
											'desc'	=> Gen_Report_Desc("Shows a summary of bank return items during the selected time period.",
														array(	"Company","Start Date", "End Date","Loan Type"),
														array(	"Application ID","Last Name","First Name","Date Sent",
																"Reason and Code","Routing &#35; / Account &#35;",
																"Debits","Credits","Notes","Is Reattempt",
																"Fatal Return")),			
									),
				'external_transactions' => array(
											'name' 	=> 'External Transactions',
											'link'  => '/?module=reporting&mode=external_transactions',
											'desc'	=> Gen_Report_Desc("To view All Non-ACH transactions.",
														array(	"Start Date", "End Date","Loan Type","Payment Type","Company"),
														array(	"Company","Agent Name","Controlling Agent","Application ID",
																"Customer Name","Transaction Type","Transaction Date",
																"Status", "Amount",))
									),
			)
		),
		'monitor' => array(
			'name' => 'Monitor',
			'background-color' => '#ffbb88',
			'reports' => array(
				'monitor' => array(
											'name' 	=> 'Monitor Report',
											'link'  => '/?module=reporting&mode=monitor',
											'desc'	=> Gen_Report_Desc("To monitor agent statistics.",
														array(	"Performance Options","Agent"),
														array(	"<i>Graph</i>"))
									),			
				)
		),
		'overview_reports' => array(
			'name' => 'Overview Reports',
			'background-color' => '#B4DCAF',
			'reports' => array(

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
				'payment_type_success' => array(
											'name' 	=> 'Payment Type Success',
											'link'  => '/?module=reporting&mode=payment_type_success',
											'desc'	=> Gen_Report_Desc("Displays positive and negative payment totals for different payment types.",
														array(	"Company", "Start Date", "End Date"),
														array(	"Payment Type", "Completed", "Completed Amount", "Returned", "Returned Amount", 
																"Total Payments Attempted", "Total Amount Attempted"))
									),
				/*'process_status' => array(
											'name' 	=> 'Process Status Report',
											'link'  => '/?module=reporting&mode=process_status',
											'desc'	=> Gen_Report_Desc("Displays a summery of daily processes.",
														array(	"Start Date", "End Date"),
														array(	"Run Day",  "Process Log ID",  "Process Step Name",  "Status", "Start","End", "Duration"))
									),
				*/
				'status_overview' => array(
											'name' 	=> 'Status Overview',
											'link'  => '/?module=reporting&mode=status_overview',
											'desc'	=> Gen_Report_Desc("Displays application balance by balance type and application status.",
														array(	"Company", "Balance Type", "Status"),
														array(	"Application ID", "First Name", "Last Name", "SSN",  "Street",
														  		"City", "State", "Principal Balance"))
									),
				'status_group_overview' => array(
											'name' 	=> 'Status Group Overview',
											'link'  => '/?module=reporting&mode=status_group_overview',
											'desc'	=> Gen_Report_Desc("Displays application balance by balance type and application status group.",
														array(	"Company", "Balance Type", "Status Group"),
														array(	"Company", "Application ID", "First Name", "Last Name", "SSN",  "Street",
														  		"City", "State", "Principal Balance", "Total Balance"))
									),

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
			)
		),
		
	);
}

?>
