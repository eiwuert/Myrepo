<div class="legal-page">
	<table class="legal-100pctw" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td class="norm2 sh-align-center" width="38%">
				Applicant: <b><u><span class="med">
				<?php echo $condor_content["data"]["name_first"]." ".$condor_content["data"]["name_last"]; ?></span></u></b><br />
				Loan ID: <b>
				<?php
					$property_short = $condor_content["config"]->property_short;
					echo $property_short ."-".$condor_content["application_id"];
				?>
					</b>
			</td>
			<td class="bigboldu sh-align-center" width="24%">Application</td>
			<td class="med sh-align-center" width="38%">
				Date :
				<?php echo isset($condor_content["data"]["doc_date"])?date("m/d/Y",strtotime($condor_content["data"]["doc_date"])):date("m/d/Y"); ?><br />
				src: 
				<?php echo $condor_content["config"]->site_name ." : ". $condor_content["config"]->promo_id; ?>
			</td>
		</tr>
	</table> 
			<table id="wf-legal-maininfo" class="legal-100pctw legal-boxed" cellspacing="0" cellpadding="1">
			<tr>
				<td colspan="2" class="norm">
					<b>Personal Information</b>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Applicant Name: <u><span class="med">
					<?php echo $condor_content["data"]["name_first"]." ".$condor_content["data"]["name_last"]; ?>
					</span></u></b>
				</td>
				<td class="norm2" rowspan="3">
					<b>Applicants Address:</b><br />
					<?php echo $condor_content["data"]["home_street"]." ".$condor_content["data"]["home_unit"]; ?>
					<br />
					<?php echo $condor_content["data"]["home_city"]; ?>,
					<?php echo $condor_content["data"]["home_state"]; ?> &nbsp;
					<?php echo $condor_content["data"]["home_zip"]; ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>DOB:</b>
					<?php echo $condor_content["data"]["dob"]; ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>SS#</b>:
					<?php echo $condor_content["data"]["ssn_part_1"]."-".$condor_content["data"]["ssn_part_2"]."-".$condor_content["data"]["ssn_part_3"] ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Home Phone #:</b>
					<?php echo $this->Display ("phone", $condor_content['data']['phone_home']); ?>
				</td>
				<td class="norm2">
					<b>Length at address:</b>
					<?php 
					if( isset($condor_content["data"]["residence_length"]) )
						echo floor ($condor_content["data"]["residence_length"]/ 12)." yrs ".($condor_content["data"]["length_of_residence"] % 12)." mnths"; 
					else 
						echo "NA";
					?>
					<br />
					<?php
					if( isset($condor_content["data"]["residence_type"]) )
					{
						echo "I am the ";
							if ($condor_content["data"]["residence_type"] == "RENT")
							{
								echo "renter"; 
							}
							else
							{
								echo "owner"; 
							}
						
						echo " of the residence";
					} 
					else 
					{
						echo "NA";
					}
					?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Fax Number:</b>
					<?php if ($condor_content['data']["phone_fax"]) echo $this->Display ("phone", $condor_content['data']["phone_fax"]); ?>
				</td>
				<td class="norm2">
					<b>E-Mail address:</b>
					<?php echo ($condor_content['data']["email_primary"]); ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Cell Number:</b>
					<?php if ($condor_content['data']["phone_cell"]) echo $this->Display ("phone", $condor_content['data']["phone_cell"]); ?>
				</td>
				<td class="norm2">
					<b>Drivers License:</b>
					<?php echo ($condor_content['data']["state_id_number"]); ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="norm">
					<b><span class="norm">Employment / Income Information</b>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Employer:</b>
					<?php echo $condor_content['data']["employer_name"]; ?>
				</td>
				<td class="norm2">
					<b>Income comes from?</b>
					<?php
						if (strtoupper($condor_content['data']["income_type"]) == "BENEFITS")
						{
							echo "benefits"; 
						}
						else
						{
							echo "job"; 
						}
					?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Your work phone:</b>
					<?php echo $this->Display ("phone",  $condor_content['data']["phone_work"]); ?>
				</td>
				<td width="50%" class="norm2">&nbsp;
					
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Length of Employment:</b>
					<?php
						echo "0 Yrs&nbsp;&nbsp;&nbsp;&nbsp;";
						echo "3+ Mths&nbsp;&nbsp;&nbsp;&nbsp;";
					?>
				</td>
				<td class="norm2">
					<b>Monthly Take Home pay*:</b> $
					<?php echo $this->Display ("money", $condor_content['data']["income_monthly_net"]); ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Position:</b>
					<?php echo $condor_content['data']["title"]; ?>
				</td>
				<td class="norm2">
					<b>Net pay each pay check*:</b> 
					$<?php echo $this->Display ("money", $condor_content["data"]["qualify_info"]["net_pay"]); ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Shift/Hours:</b>
					<?php echo $condor_content["employment"]["shift"]; ?>
				</td>
				<td class="norm2">
					<b>Next four pay dates: </b>
					<?php echo $this->Display ("date", $condor_content["data"]["paydates"][0]); ?>            &amp;
					<?php echo $this->Display ("date", $condor_content["data"]["paydates"][1]); ?>            &amp;           
					<?php echo $this->Display ("date", $condor_content["data"]["paydates"][2]); ?>            &amp;
					<?php echo $this->Display ("date", $condor_content["data"]["paydates"][3]); ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Direct Deposit?:</b>
					<?php echo ($condor_content["data"]["income_direct_deposit"] == 'TRUE') ? "Yes" : "No"; ?>
				</td>
				<td class="norm2">
					<b>Paid how often:</b>
					<?php print preg_replace ("/_/", "-", strtolower ($condor_content['data']['paydate_model']["income_frequency"])); ?>           
				</td>
			</tr>
			<tr>
				<td colspan="2" class="norm">
					<b>Checking Account Information</b>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>BANK NAME:</b>
					<?php print ($condor_content['data']["bank_name"]); ?>
				</td>
				<td class="norm2">
					<b>ABA/ROUTING: </b>
					<?php print ($condor_content['data']["bank_aba"]); ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>ACCOUNT NUMBER:</b>
					<?php print ($condor_content['data']["bank_account"]); ?>
				</td>
				<td class="norm2">
					<b>NEXT CHECK NUMBER:</b>
					<?php print ($condor_content['data']["check_number"]); ?>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="norm">
					<b>Personal References</b>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Ref #1 name:</b>
					<?php echo $condor_content['data']["ref_01_name_full"]; ?>
				</td>
				<td class="norm2">
					<b>Ref #2 name:</b>
					<?php echo $condor_content['data']["ref_02_name_full"]; ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Ref #1 phone:</b>
					<?php echo $this->Display ("phone", $condor_content['data']["ref_01_phone_home"]); ?>
				</td>
				<td class="norm2">
					<b>Ref #2 phone:</b>
					<?php echo $this->Display ("phone", $condor_content['data']['ref_02_phone_home']); ?>
				</td>
			</tr>
			<tr>
				<td width="50%" class="norm2">
					<b>Ref #1 relationship:</b>
					<?php echo $condor_content['data']["ref_01_relationship"]; ?>
				</td>
				<td class="norm2">
					<b>Ref #2 relationship: </b>
					<?php echo $condor_content['data']["ref_02_relationship"]; ?>
				</td>
			</tr>
			</table>
			<div class="small sh-align-left">
			*or other source of income periodically deposited to your account. 
			However, alimony, child support, or separate maintenance income 
			need not be revealed if you do not wish to have it considered as 
			a basis for repaying this obligation.

			<br />
			<u>NOTICE: We adhere to the Patriot Act and we are required by law to 
			adopt procedures to request and retain in our records information 
			necessary to verify your identity.</u> 
			<!--sw replaced text here 10/8/04
			NOTICE: We are required by law to adopt procedures to 
			request and retain in our records information necessary to verify your identity.-->
						
				<u>Agreement to Arbitrate All Disputes</u>: By signing below or electronically signing 
				and to induce us, <?php echo $condor_content["config"]->legal_entity; ?>, to process your application for a loan, you and 
				we agree that any and all claims, disputes or controversies that we or our servicers 
				or agents have against you or that you have against us, our servicers, agents, 
				directors, officers and employees, that arise out of your application for one or 
				more loans, the Loan Agreements that govern your repayment obligations, the loan 
				for which you are applying or any other loan we previously made or later make to you, 
				this Agreement To Arbitrate All Disputes, collection of the loan or loans, or alleging 
				fraud or misrepresentation, whether under the common law or  pursuant to federal or 
				state statute or regulation, or otherwise,  including disputes as to the matters subject 
				to arbitration, shall be resolved by binding individual (and not class) arbitration by 
				and under the Code of Procedure of the National Arbitration Forum (&quot;NAF&quot;) in effect at 
				the time the claim is filed.  This agreement to arbitrate all disputes shall apply no 
				matter by whom or against whom the claim is filed.  Rules and forms of the NAF may be 
				obtained and all claims shall be filed at any NAF office, on the World Wide Web at 
				<u>www.arb-forum.com</u>, or at &quot;National Arbitration Forum, P.O. Box 50191, Minneapolis, 
				Minnesota 55405.&quot;  If you are unable to pay the costs of arbitration, your arbitration 
				fees may be waived by the NAF.  The cost of a participatory hearing, if one is held at 
				your or our request, will be paid for solely by us if the amount of the claim is $15,000 
				or less.  Unless otherwise ordered by the arbitrator, you and we agree to equally share 
				the costs of a participatory hearing of the claim is for more than $15,000 or less than 
				$75,000.  Any participatory hearing will take place at a location near your residence.  
				This arbitration agreement is made pursuant to a transaction involving interstate commerce.  
				It shall be governed by the Federal Arbitration Act, 9 U.S.C. Sections 1-16.  Judgment 
				upon the award may be entered by any party in any court having jurisdiction. This 
				Agreement to Arbitrate All Disputes is an independent agreement and shall survive the 
				closing, funding, repayment and/or default of the loan for which you are applying. 
	
				<br />
				NOTICE: YOU AND WE WOULD HAVE HAD A RIGHT OR OPPORTUNITY TO 
				LITIGATE DISPUTES THROUGH A COURT AND HAVE A JUDGE OR JURY  
				DECIDE THE DISPUTES BUT HAVE AGREED INSTEAD TO RESOLVE DISPUTES 
				THROUGH BINDING ARBITRATION.
				<!--sw replaced text here 10/8/04
				NOTICE: YOU AND WE WOULD HAVE HAD A RIGHT OR OPPORTUNITY TO
				LITIGATE DISPUTES THROUGH A COURT AND HAVE A JUDGE OR JURY
				DECIDE THE DISPUTES BUT HAVE AGREED INSTEAD TO RESOLVE DISPUTES
				THROUGH BINDING ARBITRATION.-->
				<br />
				<u>Agreement Not To Bring, Join Or Participate In Class Actions</u>: 
				To the extent permitted by law, by signing below or electronically 
				signing you agree that you will not bring, join or participate in 
				any class action as to any claim, dispute or controversy you may 
				have against us or our agents, servicers, directors, officers and 
				employees.  You agree to the entry of injunctive relief to stop 
				such a lawsuit or to remove you as a participant in the suit.  
				You agree to pay the costs we incur, including our court costs 
				and attorney's fees, in seeking such relief.  This agreement is 
				not a waiver of any of your rights and remedies to pursue a claim 
				individually and not as a class action in binding arbitration as 
				provided above. This agreement not to bring, join or participate 
				in class action suites is an independent agreement and shall survive 
				the closing, funding, repayment, and/or default of the loan for 
				which you are applying.	
			
				<br />
				<b>Borrower's Electronic Signature to the above Agreements Appears Below</b>
				<br />
				By signing below or electronically signing this Application you certify 
				that all of the information provided above is true, complete and correct 
				and provided to us, <?php echo $condor_content["config"]->legal_entity; ?>, for the purpose of inducing us to make 
				the loan for which you are applying.  You also agree to the Agreement to 
				Arbitrate All Disputes and the Agreement Not To Bring, Join Or Participate 
				in Class Actions.  You authorize <?php echo $condor_content["config"]->legal_entity; ?> to verify all information 
				that you have provided and acknowledge that this information may be used 
				to verify certain past and/or current credit or payment history information 
				from third party source(s). <?php echo $condor_content["config"]->legal_entity; ?> may utilize Check Loan Verification 
				or other similar consumer-reporting agency for these purposes.  We may 
				disclose all or some of the nonpublic personal information about you that 
				we collect to financial service providers that perform services on our behalf, 
				such as the servicer of your short term loan, and to financial institutions 
				with which we have joint marketing arrangements.  Such disclosures are made 
				as necessary to effect, administer and enforce the loan you request or authorize 
				and any loan you may request or authorize with other financial institutions 
				with regard to the processing, funding, servicing, repayment and collection 
				of your loan. <b>(This Application will be deemed incomplete and will not be 
				processed by us unless signed by you below.)</b>
			</p>
			</div>
			<br />
		<table id="wf-legal-cancelauth" width="100%" border="0" cellspacing="0" cellpadding="4">
			<tr>
				<td class="norm sh-align-left">
					<b>(X) </b><u><span class="med"><b>
					<?php 
						if (isset($condor_content["esignature"])) 
							echo ($condor_content['data']["name_first"]." ".$condor_content['data']["name_last"]); 				
					?>
					</b></span></u>
				</td>
				<td class="norm sh-align-left">
					<b>(X) </b><u><span class="med"><b>
					<?php print ($condor_content['data']["name_first"]." ".$condor_content['data']["name_last"]); ?>
					</b></span></u>
				</td>
				<td class="norm sh-align-left">
					<b>(X) <u><span class="med">
						<?php echo isset($condor_content["data"]["doc_date"])?date("m/d/Y",strtotime($condor_content["data"]["doc_date"])):date("m/d/Y"); ?>
					</span></u></b>
				</td>
			</tr>
			<tr>
				<td class="norm">&nbsp;Electronic Signature of Applicant</td>
				<td class="norm">&nbsp;Printed Name of Applicant</td>
				<td class="norm">&nbsp;Date</td>
			</tr>
		</table>
		<p class="small"><b>
	SHORT TERM LOANS PROVIDE THE CASH NEEDED TO MEET IMMEDIATE SHORT-TERM CASH
	FLOW PROBLEMS. THEY ARE NOT A SOLUTION FOR LONGER TERM FINANCIAL PROBLEMS
	FOR WHICH OTHER KINDS OF FINANCING MAY BE MORE APPROPRIATE. YOU MAY WANT
	TO DISCUSS YOUR FINANCIAL SITUATION WITH A NONPROFIT FINANCIAL COUNSELING
	SERVICE.</b>
	</p>

</div><!-- end of legal-page div -->

<br class="breakhere" />
<div class="legal-page"><a name="privacy_policy"></a>

	<div class="legal-boxed norm2"><br />
		<div class="bigbold sh-align-center">
			<u>Privacy Policy</u>
		</div>
		<p class="norm2 sh-align-left">
		<br />
		<b>PRIVACY POLICY:</b> To view our Privacy Policy please <a href="http://
		<?php echo $condor_content["config"]->site_name; ?>
		/?page=info_privacy" target="_blank">
		click here</a>. The Privacy Policy can be viewed at <?php echo "http://" .$condor_content["config"]->site_name . "/?page=info_privacy";?>.
		<br /></p>
		
		<p class="norm2 sh-align-left"><b>RIGHT TO CANCEL: YOU MAY CANCEL THIS 
		LOAN WITHOUT COST OR FURTHER OBLIGATION TO US, IF YOU DO SO BY THE END OF 
		BUSINESS ON THE BUSINESS DAY AFTER THE LOAN PROCEEDS ARE DEPOSITED INTO 
		YOUR CHECKING ACCOUNT.</b><br> To submit your cancellation form <a href="http://
		<?php echo $condor_content["config"]->site_name; ?>
		/?page=docs_cancellation" target="_blank">
		click here</a>. The cancellation form can be viewed at <?php echo "http://" .$condor_content["config"]->site_name . "/?page=docs_cancellation";?>.
		</p>
		
	</div> <!-- end box div -->
	<br /><br />

	
</div><!-- end of legal-page div -->

<br class="breakhere" />
<div class="legal-page"><a name="loan_note_and_disclosure"></a>
	<table border="0" cellspacing="0" cellpadding="2" width="100%">
		<tr> 
			<td class="sh-align-left norm sh-bold" width="45%">
				LOAN NOTE AND DISCLOSURE
			</td>
			<td class="sh-align-right sh-bold norm" width="55%">
				<?php echo $condor_content["config"]->site_name; ?>&nbsp;
			</td>
		</tr>
		<tr> 
			<td class="sh-align-left sh-bold norm2" width="50%">
				Borrower's Name:
				<?php print ($condor_content['data']["name_first"]." ".$condor_content['data']["name_last"]); ?>
			</td>
			<td align="right" class="norm2" width="50%">
				Date: 
				<?php echo isset($condor_content["data"]["doc_date"])?date("m/d/Y",strtotime($condor_content["data"]["doc_date"])):date("m/d/Y"); ?>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				ID#:
				<b>
				<?php print ($property_short."-".$condor_content["application_id"]); ?>
				</b>
			</td>
		</tr>
	</table>
	<div class="med sh-align-left">
		<u>Parties:</u>  In this Loan Note and Disclosure (&quot;Note&quot;) you are the person named 
		as Borrower above.  &quot;We&quot; <?php echo $condor_content["config"]->legal_entity; ?> are the lender 
		(the &quot;Lender&quot;).  <br />
		All references to &quot;we&quot;, &quot;us&quot; or &quot;ourselves&quot; means the Lender.  
		Unless this Note specifies otherwise or unless we notify you to the contrary in writing, 
		all notices and documents you are to provide to us shall be provided to <?php echo $condor_content["config"]->legal_entity; ?> 
		at the fax number and address specified in this Note and in your other loan documents.  
		<br />
		<u>The Account</u>:  You have deposit account, No. <?php echo $condor_content['data']["bank_account"]; ?> (&quot;Account&quot;), 
		at <?php echo $condor_content['data']["bank_name"]; ?> (&quot;Bank&quot;).  You authorize us to effect a credit 
		entry to deposit the proceeds of the Loan (the Amount Financed indicated below) to your Account 
		at the Bank.  
	
		<!--sw replaced below 10/8/04
		<u>Parties:</u> In this Loan Note and Disclosure (&quot;Note&quot;) 
		you are the person named as Borrower above. We are 
		<?php echo $condor_content["config"]->legal_entity; ?>.<br />
		<u>The Account:</u> You have deposit account, No.
		<?php echo $condor_content['data']["bank_account"]; ?> (&quot;Account&quot;), with us or, if the following space is completed, 
		at 
		<?php echo $condor_content['data']["bank_name"]; ?> (&quot;Bank&quot;). You authorize us to effect a credit entry to deposit 
		the proceeds of the Loan (the Amount Financed indicated below) to 
		your Account at the Bank. -->
		
		<br />
		DISCLOSURE OF CREDIT TERMS: The information in the following box is 
		part of this Note.
	</div>
	<table class="legal-boxed" cellspacing="0" cellpadding="0">
		<tr> 
			<td class="norm2 sh-align-left" width="25%" style="border: 3px solid #000000">
				<b>ANNUAL PERCENTAGE RATE</b><br />
				The cost of your credit as a yearly rate (e)<br />
				<div class="sh-align-center">
					<b>
					<?php echo round($condor_content["data"]["qualify_info"]["apr"],2); ?>%
					</b>
				</div>
			</td>
			<td class="norm2 sh-align-left" width="25%" style="border: 3px solid #000000">
				<b>FINANCE CHARGE</b><br />
				The dollar amount the credit will cost you.<br />
				<div align="center">
					<b>
					$<?php echo $this->Display ("money", $condor_content["data"]["qualify_info"]["finance_charge"]); ?>
					</b>
				</div>
			</td>
			<td class="norm2 sh-align-left" width="25%">
				<b>Amount Financed</b><br />
				The amount of credit provided to you or on your 
				behalf.
				<div class="sh-align-center">
					<b>
					$<?php print ($this->Display ("money", $condor_content["data"]["qualify_info"]["fund_amount"])); ?>
					</b>
				</div>
			</td>
			<td class="norm2 sh-align-left" width="25%">
				<b>Total of Payments</b><br />
				The amount you will have paid after you have made the scheduled payment.<br />
				<div class="sh-align-center">
					<b>
					$<?php print ($this->Display ("money", $condor_content["data"]["qualify_info"]["finance_charge"] + $condor_content["data"]["qualify_info"]["fund_amount"])); ?>
					</b>
				</div>
			</td>
		</tr>
		<tr> 
			<td colspan="4" class="med sh-align-left">
				Your <b>Payment Schedule</b> will be:
				1 payment of <b>
				$<?php echo $this->Display ("money", $condor_content["data"]["qualify_info"]["finance_charge"] + $condor_content["data"]["qualify_info"]["fund_amount"]); ?>
				</b> due on <b>
				<?php print ($this->Display ("date", $condor_content["data"]["qualify_info"]["payoff_date"])); ?>
				</b>,
				if you decline* the option of renewing your loan. 
				If renewal is accepted you will pay the finance charge of 
				$ <?php print ($this->Display ("money", $condor_content["data"]["qualify_info"]["finance_charge"])); ?>
				 only, on <?php print ($this->Display ("date", $condor_content["data"]["qualify_info"]["payoff_date"])); ?>.  
				You will accrue new finance charges with every renewal of your loan. If
				your pay date falls on a weekend or holiday and you have direct deposit,
				your account will be debited the business day prior to your normal pay date.
				On the due date resulting from a fourth renewal and every renewal due date 
				thereafter, your loan must be paid down by $50.00. This means your Account 
				will be debited the finance charge plus $50.00 on the due date.  This will 
				continue until your loan is paid in full.<br />
				 *To decline the option of renewal you must sign the Account Summary page 
				 and fax it back to our office at least three Business Days before your loan is due.
				
				<br />
				<b>Security:</b> The loan is unsecured.<br />
				
				<b>Prepayment</b>: <u>You may prepay your loan only in increments of $50.00.</u>  
				If you prepay your loan in advance, you will not receive a refund of any Finance Charge.(e) 
				The Annual Percentage Rate is estimated based on the anticipated date the proceeds will 
				be deposited to or paid on your account, which is <?php print ($this->Display ("date", $condor_content["data"]["qualify_info"]["fund_date"])); ?>.
				
				<br />
				See below and your other contract documents for any additional information about prepayment, nonpayment and default.
			</td>
		</tr>
	</table>
	<div class="med sh-align-left">
		<b><u>Itemization Of Amount Financed of $<?php print ($this->Display ("money", $condor_content["data"]["qualify_info"]["fund_amount"])); ?></u>;
		Given to you directly: <u>
		$<?php print ($this->Display ("money", $condor_content["data"]["qualify_info"]["fund_amount"])); ?></u>;
		Paid on your account <u>$0</u></b><br />
		
		<b><u>Promise To Pay:</u></b> You promise to pay to us or to our order and our assignees, 
		on the date indicated in the Payment Schedule, the Total of Payments, unless this Note is 
		renewed.  If this Note is renewed, then on the Due Date, you will pay the Finance Charge 
		shown above.  This Note will be renewed on the Due Date unless at least three Business Days 
		Before the Due Date either you tell us you do not want to renew the Note or we tell you that 
		the Note will not be renewed.  Information regarding the renewal of your loan will be sent to 
		you prior to any renewal showing the new due date, finance charge and all other disclosures.  
		As used in the Note, the term &quot;Business Day&quot; means a day other than Saturday, Sunday or legal 
		holiday, that <?php echo $condor_content["config"]->legal_entity; ?> is open for business.
		This Note may be renewed four times without having to make any principle payments on the Note.  
		If this Note is renewed more than four times, then on the due date resulting from your fourth 
		renewal, and on the due date resulting from each and every subsequent renewal, you must pay the 
		finance charge required to be paid on that due date and make a principle payment of $50.00.
		Any payment due on the Note shall be made by us effecting one or more ACH debit entries to your 
		Account at the Bank.  You authorize us to effect this payment by these ACH debit entries.  You 
		may revoke this authorization at any time up to three Business Days prior to the date any payment 
		becomes due on this Note.  However, if you timely revoke this authorization, you authorize us 
		to prepare and submit a check drawn on your Account to repay your loan when it comes due.  If 
		there are insufficient funds on deposit in your Account to effect the ACH debit entry or to pay 
		the check or otherwise cover the loan payment on the due date, you promise to pay us all sums 
		you owe by submitting your credit card information or mailing a Money Order payable to: <?php echo $condor_content["config"]->legal_entity; ?>. We do not accept personal checks, however, if you send us a check, you authorize us to peform an ACH debit on that account in the amount specified. 
		<br />
		<u><b>Return Item Fee</b></u>: If sufficient funds are not available in the Account on the 
		due date to cover the ACH debit entry or check, you agree to pay us a Return Item Fee of $30.
		
	    <br />
	    <b><u>Prepayment</u></b>: The Finance Charge consists solely of a loan fee that is earned in full at the time 
	    the loan is funded.  Although you may pay all or part of your loan in advance without penalty, 
	    you will not receive a refund or credit of any part or all of the Finance Charge.
	    	    
	    <br />
	    <u><b>Governing Law</b></u>: Both parties agree that this Note and your account shall be
		governed by all applicable federal laws and all laws of the jurisdiction in
		which the Lender is located, regardless of which state you may reside, and by
		signing below or by your electronic signature, you hereby contractually consent to the exclusive
		exercise of regulatory and adjudicatory authority by the jurisdiction in
		which the Lenders is located over all matters related to this Note and your
		account, forsaking any other jurisdiction which either party may claim by
		virtue of residency.
	    <br />
	     
	    <u><b>Arbitration of All Disputes</b></u>: <b>You and we agree that any and all claims, disputes or controversies 
	    between you and us, any claim by either of us against the other (or the employees, officers, directors, agents, 
	    servicers or assigns of the other) and any claim arising from or relating to your application for this loan, 
	    regarding this loan or any other loan you previously or may later obtain from us, this Note, this agreement to 
	    arbitrate all disputes, your agreement not to bring, join or participate in class actions, regarding collection 
	    of the loan, alleging fraud or misrepresentation, whether under common law or pursuant to federal, state or local 
	    statute, regulation or ordinance, including disputes regarding the matters subject to arbitration, or otherwise, 
	    shall be resolved by binding individual (and not joint) arbitration by and under  the Code of Procedure  of the 
	    National Arbitration Forum (&quot;NAF&quot;)  in effect at the time the  claim is filed.  No class arbitration.  All disputes 
	    including any Representative Claims against us and/or related third parties shall be resolved by binding arbitration 
	    only on an individual basis with you.  THEREFORE, THE ARBITRATOR SHALL NOT CONDUCT CLASS ARBITRATION; THAT IS, THE 
	    ARBITRATOR SHALL NOT ALLOW YOU TO SERVE AS A REPRESENTATIVE, AS A PRIVATE ATTORNEY GENERAL, OR IN ANY OTHER 
	    REPRESENTATIVE CAPACITY FOR OTHERS IN THE ARBITRATION.  This agreement to arbitrate all disputes shall apply no matter 
	    by whom or against whom the claim is filed.  Rules and forms of the NAF may be obtained and all claims shall be filed at 
	    any NAF office, on the World Wide Web at  <u>www.arb-forum.com</u>, by telephone at 800-474-2371, or at &quot;National Arbitration 
	    Forum, P.O. Box 50191, Minneapolis, Minnesota 55405.&quot;  Your arbitration fees will be waived by the NAF in the event you 
	    cannot afford to pay them. The cost of any participatory, documentary or telephone hearing, if one is held at your or our 
	    request, will be paid for solely by us as provided in the NAF Rules and, if a participatory hearing is requested, it will 
	    take place at a location near your residence.  This arbitration agreement is made pursuant to a transaction involving 
	    interstate commerce.  It shall be governed by the Federal Arbitration Act, 9 U.S.C. Sections 1-16.  Judgment upon the award 
	    may be entered by any party in any court having jurisdiction.</b>
	     
        <br />
		<span class="small">
		    <b>NOTICE:</b> YOU AND WE WOULD HAVE HAD A RIGHT OR OPPORTUNITY TO LITIGATE DISPUTES THROUGH A 
		    COURT AND HAVE A JUDGE OR JURY DECIDE THE DISPUTES BUT HAVE AGREED INSTEAD TO RESOLVE DISPUTES 
		    THROUGH BINDING ARBITRATION</td>

		</span>		
			
		<b><u>Agreement Not To Bring, Join Or Participate In Class Actions:</u></b> To the extent permitted by 
		law, you agree that you will not bring, join or participate in any class action as to any claim, 
		dispute or controversy you may have against us, our employees, officers, directors, servicers and 
		assigns.  You agree to the entry of injunctive relief to stop such a lawsuit or to remove you as a 
		participant in the suit.  You agree to pay the attorney's fees and court costs we incur in seeking 
		such relief.  This agreement does not constitute a waiver of any of your rights and remedies to pursue 
		a claim individually and not as a class action in binding arbitration as provided above.
		<br />		
		<b><u>Survival:</u></b> The provisions of this Loan Note And Disclosure dealing with the Agreement To Arbitrate All 
		Disputes and the Agreement Not To Bring, Join Or Participate In Class Actions shall survive repayment 
		in full and/or default of this Note.
		<br />
		
		<!--sw replaced 10/08/04
		<b><u>Agreement not to Bring, Join Or Participate 
		In Class Actions:</u> To the extent permitted by 
        law, you agree that you will not bring, join or participate 
        in any class action as to any claim, dispute or controversy 
        you may have against us, our employees, officers, directors, 
        servicers and assigns. You agree to the entry of injunctive 
        relief to stop such a lawsuit or to remove you as a participant 
        in the suit. You agree to pay the attorney&#8217;s fees and 
        court costs we incur in seeking such relief. This agreement 
        does not constitute a waiver of any of your rights and remedies 
        to pursue a claim individually and not as a class action in 
        binding arbitration as provided above.</b><br />
        <b><u>Survival:</u></b> The provisions of this Loan Note and 
        Disclosure dealing with the Agreement To Arbitrate All Disputes 
        and the Agreement Not To Bring, Join Or Participate In Class 
        Actions shall survive repayment in full and/or default of 
        this Note.<br /> -->
        
        <b><u>No Bankruptcy:</u></b> By signing below or electronically signing you represent that you 
        have not recently filed for bankruptcy and you do not plan to do so.
        
        <!--sw replaced 10/08/04
        <b><u>No Bankruptcy:</u></b> By electrinically signing below you represent 
        that you have not recently filed for bankruptcy and you do 
        not plan to do so.-->
        <br />
        
        	<b><u>NOTICE: We adhere to the Patriot Act and we are required by law to adopt procedures to request 
        	and retain in our records information necessary to verify your identity.</u></b>
        	<br />
		By signing or electronically signing this Loan Note you certify that all of the information provided 
		above is true, complete and correct and provided to us, <?php echo $condor_content["config"]->legal_entity; ?>, for the purpose of inducing us 
		to make the loan for which you are applying. By signing below or electronically signing you also agree 
		to the Agreement to Arbitrate All Disputes and the Agreement Not To Bring, Join Or Participate in Class 
		Actions. By signing or electronically signing this application you authorize <?php echo $condor_content["config"]->legal_entity; ?> to verify 
		all information that you have provided and acknowledge that this information may be used to verify certain 
		past and/or current credit or payment history information from third party source(s).  <?php echo $condor_content["config"]->legal_entity; ?> may 
		utilize Check Loan Verification or other similar consumer-reporting agency for these purposes.  We may 
		disclose all or some of the nonpublic personal information about you that we collect to financial service 
		providers that perform services on our behalf, such as the servicer of your short term loan, and to 
		financial institutions with which we have joint marketing arrangements.  Such disclosures are made as 
		necessary to effect, administer and enforce the loan you request or authorize and any loan you may request 
		or authorize with other financial institutions with regard to the processing, funding, servicing, repayment 
		and collection of your loan.  (This Application will be deemed incomplete and will not be processed by us 
		unless signed by you below.)

		<!--<u>BY ELECTRONICALLY SIGNING BELOW, YOU AGREE TO ALL THE TERMS OF THIS NOTE, 
		INCLUDING THE AGREEMENT TO ARBITRATE ALL DISPUTES AND THE 
		AGREEMENT NOT TO BRING, JOIN OR PARTICIPATE IN CLASS ACTIONS.</u>-->
		<br />
		<br />
	</div>
	<table border="0" cellspacing="2" cellpadding="0">
		<tr> 
			<td><br />
				<table width="100%" cellspacing="0" cellpadding="1">
					<tr> 
						<td class="med legal-underline sh-align-left"><strong>(X) 
						<?php 
						if (isset($condor_content["esignature"])) 
							echo ($condor_content['data']["name_first"]." ".$condor_content['data']["name_last"]); 				
						?>
						</strong></td>
						<td class="med legal-underline sh-align-center" width="30%"> 
							<?php echo isset($condor_content["data"]["doc_date"])?date("m/d/Y",strtotime($condor_content["data"]["doc_date"])):date("m/d/Y"); ?>
						</td>
					</tr>
					<tr> 
						<td class="med sh-align-left">
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Electronic Signature
						</td>
						<td class="med sh-align-center" width="30%">
							Date
						</td>
					</tr>
					<tr> 
						<td class="med legal-underline sh-align-left" colspan="2">
							<br />
							&nbsp;&nbsp;&nbsp;&nbsp;
							<?php print ($condor_content['data']["name_first"]." ".$condor_content['data']["name_last"]); ?>
						</td>
					</tr>
					<tr> 
						<td class="med sh-align-left" colspan="2">
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Print Name
						</td>
					</tr>
				</table>
			</td>
			<td width="30%" class="small legal-boxed">
				<b>INSTRUCTIONS: YOU WILL BE ADVISED OF YOUR APPROVAL VIA PHONE OR EMAIL.</b>
				<br /><br />
				<p>v.1.1.27</p>
			</td>
		</tr>
	</table>
	
</div>
<br class="breakhere" />

<div class="legal-page"><a name="auth_agreement"></a>
	<div class="sh-align-right bigboldu">
		<?php echo $condor_content["config"]->legal_entity; ?>
	</div>
	
<div class="norm sh-align-left">
		<ol class="norm">
			<li><b>BY SIGNING OR ELECTRONICALLY SIGNING BELOW YOU VERIFY BANK, RESIDENCE, AND EMPLOYMENT INFORMATION as printed in item 5 and 6.</b></li>
			<li><b>UNLESS the authorization in item 6 below  is properly and timely revoked, THERE WILL BE A $30.00 FEE ON ANY ACH DEBIT ENTRY ITEMS THAT ARE RETURNED AT TIME OF COLLECTION.</b></li>
			<li><b>YOU AUTHORIZE US to contact you at your place of employment or residence at any time up to 9:00 p.m., your local time.</b></li>
			<li><b>YOU REPRESENT that you have NOT RECENTLY FILED FOR BANKRUPTCY and you DO
NOT PLAN TO DO SO.</b></li>
			<li><b>YOU REPRESENT that your employer remains:	<?php echo $condor_content['data']["employer_name"]; ?> <br />
					and your residence remains:  <?php echo $condor_content["data"]["home_street"]." ".$condor_content["data"]["home_unit"]; ?> &nbsp;&nbsp;
					<?php echo $condor_content["data"]["home_city"]; ?>,<?php echo $condor_content["data"]["home_state"]; ?> &nbsp;<?php echo $condor_content["data"]["home_zip"]; ?></b></li>
			<li><b>You authorize us</b>, 
				<?php echo $condor_content["config"]->legal_entity; ?>, 
				or our servicer, agent, or affiliate to
				initiate one or more ACH debit entries (for example, at our option, one debit
				entry may be for the principle of the loan and another for the finance
				charge) to your Deposit Account indicated below for payments that come due
				each pay period and/or each due date concerning every renewal, with regard to
				the loan for which you are applying.  If your pay date falls on a weekend or
				holiday and you have direct deposit, your account will be debited the
				business day prior to your normal pay date.  You REPRESENT that your
				Depository Institution named below, called BANK, which will receive and debit
				such entry to your Bank Account, remains:
				<table class="legal-boxed legal-90pctw" cellspacing="0" cellpadding="3">
					<tr> 
						<td width="33%" class="norm"> 
							<b><u>Bank Name</u></b>
							<div class="sh-align-center">
								<b> 
								<?php echo $condor_content["data"]["bank_name"]; ?>
								</b>
							</div>
						</td>
						<td width="33%" class="norm"> 
							<b><u>Routing/ABA No.</u></b>
							<div class="sh-align-center">
								<b>
								<?php echo $condor_content["data"]["bank_aba"]; ?>
								</b>
							</div>
						</td>
						<td width="33%" class="norm"> 
							<b><u>Account No.</u></b>
							<div class="sh-align-center">
								<b>
								<?php echo $condor_content["data"]["bank_account"]; ?>
								</b>
							</div>
						</td>
					</tr>
				</table>
				<div class="sh-align-center norm">Please See Item 7, below, if any Information has changed.</div><br /><br />
				<div class="sh-align-left med">
					This Authorization becomes effective at the time we make you the loan for which you are applying and 
					will remain in full force and effect until we have received notice of revocation from you.  This authorizes 
					us to make debit entries with regard to any other loan you may have received with us. You may revoke this 
					authorization to effect an ACH debit entry to your Account(s) by giving written notice of revocation to us, 
					which must be received no later than 3 business days prior to the due date of you loan.  However, if you 
					timely revoke this authorization to effect ACH debit entries before the loan(s) is paid in full, you authorize 
					us to prepare and submit one or more checks drawn on your Account(s) on or after the due date of your loan.  
					This authorization to prepare and submit a check on your behalf may not be revoked by you until such time as 
					the loan(s) is paid in full.
				</div>
			</li>
			<li><b>If there is any change in your Bank Information in item 6 above, you MUST PROVIDE US WITH A NEW BLANK CHECK FROM 
				YOUR CHECKING ACCOUNT MARKED &quot;VOID&quot;.  You authorize us to correct any missing or erroneous information that you provide 
				by calling the bank or capturing the necessary information from that check.
				You must provide us with a blank check from your checking account marked &quot;VOID&quot;. You authorized us to correct any 
				missing or erroneous information that you provide by calling the bank or capturing the necessary information from that check.</b>
			</li>
			<li style="border: 2px solid #000000">
				<b>Payment Options:</b>
					<ol type="a" class="norm2">
						<li>Renewal.  Your loan will be renewed on every* due date unless you notify us of your desire to pay in 
							full or to pay down your principle amount borrowed.  You will accrue a new fee every time your loan is renewed.  
							Any fees accrued will not go toward the principle amount owed.
							<br />
							*On your fifth renewal and every renewal thereafter, your loan will be paid down by $50.00.  
							This means your account will be debited for the finance charge plus $50.00, this will continue 
							until your loan is paid in full.
						</li>
						<li>Pay Down.  You can pay down your principle amount by increments of $50.00.  Paying down will decrease 
							the fee charge for renewal. To accept this option you must notify us of your request in writing via fax 
							at <?php print $this->Display ("phone", $condor_content["config"]->support_fax); ?>, at least three full business days before your loan is due.
						</li>
						<li>Pay Out.  You can payout your full balance, the principle plus the fee for that period.  To accept this 
							option you must notify us of your request in writing via fax at <?php print $this->Display ("phone", $condor_content["config"]->support_fax); ?>.  
							The request must be received at least three full business days before your loan is due.
						</li>
					</ol>
			</li>
			<li>BY SIGNING OR ELECTRONICALLY SIGNING BELOW, YOU ACKNOWLEDGE READING AND AGREEING TO THE STATEMENTS IN ITEMS 2, 3, 4, 5, THE AUTHORIZATIONS IN ITEMS 6 AND 7, THE PAYMENT OPTIONS IN ITEM 8, THE PRIVACY POLICY LOCATED AT <?php echo "http://" .$condor_content["config"]->site_name . "/?page=info_privacy";?>, AND
			THE TERMS OF USE LOCATED AT <?php echo "http://" .$condor_content["config"]->site_name . "/?page=info_terms";?>.
			</li>
			<li>Agreement to be Contacted for Reactivation - As a convenience for our customers, once you have paid off your initial loan with us,
 				we make obtaining reactivations easier. You acknowledge and agree that reactivations are subject to the terms contained herein
				 and that by providing your electronic signature below you accept all reactivations on the terms contained herein. You acknowledge
				 and agree that we may contact you via SMS text-message at the cellular number you have provided after you have paid off your
				 initial loan to inquire as to your interest in obtaining a reactivation. You acknowledge and agree that any charges incurred for
				 receipt of messages sent via SMS text-messaging or requiring the use of web browser via cellular phone to receive are solely
				 your responsibility. Reactivations offered through this process will contain the same terms and conditions as the original loan.
				 Should you desire a reactivation, you will be required to input your electronic signature into your cellular telephone which
				 shall constitute your agreement to the statements in items 2, 3, 4, 5, the authorizations in items 6 and 7, the payment options
				 in item 8, the privacy policy located at <?php echo "http://" .$condor_content["config"]->site_name . "/?page=info_privacy";?> ,
				 and the terms of use located at <?php echo "http://" .$condor_content["config"]->site_name . "/?page=info_terms";?> and
				 your agreement to all other terms contained herein.
			</li>
		</ol>
</div>
		<table width="600" border="0" cellspacing="0" cellpadding="0">
		<tr> 
			<td class="norm legal-underline" width="4%"><b>(x)</b></td>
			<td class="norm legal-underline sh-align-left" width="50%"><strong>
			<?php 
				if (isset($condor_content["esignature"]))
					echo ($condor_content['data']["name_first"]." ".$condor_content['data']["name_last"]);
			?>
			
			</strong></td>
			<td class="norm legal-underline" width="45%" class="norm"><?php echo isset($condor_content["data"]["doc_date"])?date("m/d/Y",strtotime($condor_content["data"]["doc_date"])):date("m/d/Y"); ?></td>
		</tr>
		<tr> 
			<td class="norm" width="4%">&nbsp;</td>
			<td class="norm sh-align-left" width="50%"><b>Electronic Signature</b></td>
			<td class="norm" width="45%"><b>Date</b></td>
		</tr>
		<tr> 
			<td class="norm legal-underline" width="4%"><br /><b>(x)</b></td>
			<td class="norm legal-underline sh-align-left" width="50%"><br /><?php echo $condor_content['data']["name_first"]." ".$condor_content['data']["name_last"]; ?></td>
			<td class="norm legal-underline sh-align-left" width="45%" class="norm"><br /> <?php echo $condor_content["data"]["ssn_part_1"]."-".$condor_content["data"]["ssn_part_2"]."-".$condor_content["data"]["ssn_part_3"]; ?></td>
		</tr>
		<tr> 
			<td class="norm" width="4%">&nbsp;</td>
			<td class="norm sh-align-left" width="50%"><b>PRINT NAME</b></td>
			<td class="norm" width="45%"><b>SOCIAL SECURITY NUMBER</b></td>
		</tr>
		</table>
		<br /><br />
		<div class="sh-align-right small">
			site: <?php print ($condor_content["config"]->site_name); ?>
		</div>
</div>
