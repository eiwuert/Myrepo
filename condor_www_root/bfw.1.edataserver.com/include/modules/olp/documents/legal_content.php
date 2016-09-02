<?
	$document .= "\n<div class=\"wf-legal-block\">".
	"\n<a name=\"note_and_disclosures\"></a>".
	"\n<div class=\"wf-legal-title\" >LOAN NOTE AND DISCLOSURE</div>".
	"\n<div class=\"wf-legal-copy\">".
	"\n<p>".
	"\n<strong><u>Parties:</u></strong> In this Loan Note and Disclosure (&quot;Note&quot;), &nbsp;".$condor_content["data"]["name_first"]." ".$condor_content["data"]["name_last"].", ". "[Social Security Number: ".$condor_content["data"]["ssn_part_1"]."-".$condor_content["data"]["ssn_part_2"]."-".$condor_content["data"]["ssn_part_3"]."] are the person named as the Borrower.  &quot;We&quot; <strong>".$condor_content["config"]->legal_entity."</strong> are the lender (the &quot;Lender&quot;).  ".
	"\n All references to &quot;we&quot;, &quot;us&quot; or &quot;ourselves&quot; means the Lender.  Unless this Note specifies otherwise or unless we notify you to the contrary in writing, all notices and documents you are to provide to us shall be provided to <strong>".$condor_content["config"]->legal_entity."</strong> at the fax number and address specified in this Note and in your other loan documents.  </p>".
	"\n<p>".
	"\n<strong><u>The Account:</u></strong> You have deposit account, No. ".$condor_content["data"]["bank_account"]." (&quot;Account&quot;), at ".$condor_content["data"]["bank_name"]."(&quot;Bank&quot;). You authorize us to effect a credit entry to deposit the proceeds of the Loan (the Amount Financed indicated below) to your Account at the Bank. ".
	"\n<strong>DISCLOSURE OF CREDIT TERMS:</strong> The information in the following box is part of this Note.\n</p>".

	// Terms and Schedule Table [OPEN]
	// **********************************
	"\n<table bgcolor=\"#000000\"  width=\"100%\" id=\"esig-table\">".
	"\n<tr>".
	// ANNUAL PERCENTAGE RATE
	// **********************************
	"\n<td class=\"wf-legal-table-cell-terms\">".
	"\n<strong>ANNUAL PERCENTAGE RATE (APR): ".$condor_content["data"]["qualify_info"]["apr"]."%</strong><br />The cost of your credit as a yearly rate (e)".
	"\n</td>".
	// Payment Schedule
	// **********************************
	"\n<td rowspan=\"4\" class=\"wf-legal-table-cell-schedule\">".
	"\n<strong>Payment Schedule</strong>".
	"\n<br />".
	"\nYou must make one payment of <strong>\$".$condor_content["data"]["qualify_info"]["total_payments"]."</strong> ".
	"on <strong>".date("m/d/Y", strtotime($condor_content["data"]["qualify_info"]["payoff_date"])).",</strong> ".
	"if you decline* the option of renewing your loan. If renewal is accepted you will pay ".
	"the finance charge of <strong> \$".$condor_content["data"]["qualify_info"]["finance_charge"]."</strong> only, ".
	"on <strong>".date("m/d/Y", strtotime($condor_content["data"]["qualify_info"]["payoff_date"])).".</strong> ".
	"You will accrue new finance charges with every renewal of your loan.  On the due date resulting from a fourth ".
	"renewal and every renewal due date thereafter, your loan must be paid down by $50.00. This means your Account ".
	"will be debited the finance charge plus $50.00 on the due date. This will continue until your loan is paid in full.".
	"\n<br /><br />".
	"\n* To decline the option of renewal you must sign the Account summary page and fax it back to ".
	"our office at least three Business Days before your loan is due.".
	"\n<br /><br />".
	"\n<strong>Security:</strong> The loan is unsecured. ".
	"\n<br /><br />".
	"\n<strong>Prepayment.</strong> <strong><u>You may prepay your loan only in increments of $50.00</u></strong>.  If you prepay your loan in advance, ".
	"you will not receive a refund of any Finance Charge. ".
	"(e) The Annual Percentage Rate is estimated based on the anticipated ".
	"date the proceeds will be deposited to or paid on your account, which ".
	"is <strong>".date("m/d/Y", strtotime($condor_content["data"]["qualify_info"]["fund_date"]))."</strong>. ".
	"See below and your other contract documents for any additional information about prepayment, nonpayment and default.".
	"\n</td>".
	"\n</tr>".
	// FINANCE CHARGE
	// **********************************
	"\n<tr>".
	"\n<td class=\"wf-legal-table-cell-terms\">".
	"\n<strong>FINANCE CHARGE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; \$".$condor_content["data"]["qualify_info"]["finance_charge"]."</strong><br />The dollar amount the loan will cost you. ".
	"\n</td>".
	"\n</tr>".
	// AMOUNT FINANCED
	// **********************************
	"\n<tr>".
	"\n<td class=\"wf-legal-table-cell-terms\">".
	"\n<strong>AMOUNT FINANCED &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; \$".$condor_content["data"]["qualify_info"]["fund_amount"]."</strong><br />The amount of credit provided to you or on your behalf. ".
	"\n</td>".
	"\n</tr>".
	// TOTAL OF PAYMENTS
	// **********************************
	"\n<tr>".
	"\n<td class=\"wf-legal-table-cell-terms\">".
	"\n<strong>TOTAL OF PAYMENTS &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; \$".$condor_content["data"]["qualify_info"]["total_payments"]."</strong><br />The amount you will have paid after you have<br />made the scheduled payment. ".
	"\n</td>".
	"\n</tr>".
	"<tr>".
	"<td class=\"wf-legal-table-cell-terms\"><strong><u>Itemization Of Amount Financed of \$".$condor_content["data"]["qualify_info"]["fund_amount"]."</u></strong></td>".
	"<td class=\"wf-legal-table-cell-terms\">Given to you directly:<strong> \$".$condor_content["data"]["qualify_info"]["fund_amount"]."</strong> Paid on your account \$0</td>".
	"</tr>".
	"\n</table>".

	"\n<br />".

	// REMAINING TERMS
	// **********************************
	"<table width=\"100%\" border=\"0\" valign=\"top\" id=\"esig-table\">".
	"<tr>".
	"<td width=\"48%\">".
	"<p>".
	"<strong><u>Promise To Pay:</u> </strong>".
	"You promise to pay to us or to our order and our assignees, on the date indicated in the Payment Schedule,".
	" the Total of Payments, unless this Note is renewed.  If this Note is renewed, then on the Due Date, you ".
	" will pay the Finance Charge shown above.  This Note will be renewed on the Due Date unless at least three ".
	" Business Days Before the Due Date either you tell us you do not want to renew the Note or we tell you that ".
	" the Note will not be renewed.  Information regarding the renewal of your loan will be sent to you prior to ".
	" any renewal showing the new due date, finance charge and all other disclosures.  As used in the Note, the ".
	" term &quot;Business Day&quot; means a day other than Saturday, Sunday or legal holiday, that <strong>".$condor_content["config"]->legal_entity."</strong> is open for business.".
     " This Note may be renewed four times without having to make any principal payments on the Note.  If this ".
     " Note is renewed more than four times, then on the due date resulting from your fourth renewal, and on the ".
     " due date resulting from each and every subsequent renewal, you must pay the finance charge required to be ".
     " paid on that due date and make a principal payment of \$50.00.".
     " Any payment due on the Note shall be made by us effecting one or more ACH debit entries to your Account at ".
     " the Bank.  You authorize us to effect this payment by these ACH debit entries.  You may revoke this ".
     " authorization at any time up to three Business Days prior to the date any payment becomes due on this Note.  ".
     " However, if you timely revoke this authorization, you authorize us to prepare and submit a check drawn on ".
     " your Account to repay your loan when it comes due.  If there are insufficient funds on deposit in your Account ".
     " to effect the ACH debit entry or to pay the check or otherwise cover the loan payment on the due date, you ".
     " promise to pay us all sums you owe by by submitting your credit card information or mailing a Money Order payable to: <strong>".$condor_content["config"]->legal_entity."</strong>".
	"</p>".
	"<p>".
	"<strong><u>Return Item Fee:</u> </strong>".
	"If sufficient funds are not available in the Account on the due date to cover the ACH debit ".
	"entry or check, you agree to pay us a Return Item Fee of \$30.".
	"</p>".
	"<p>".
	"<strong><u>Prepayment:</u> </strong>".
	"The finance Charge consists solely ".
	"of a loan fee that is earned in full at the time the loan is funded. ".
	"Although you may pay all or part of your loan in advance without ".
	"penalty, you will not receive a refund or credit of any part or ".
	"all of the Finance Charge. ".
	"</p>".
	"<p>".
	"<strong><u>Arbitration of All Disputes:</u> </strong>".
	"<strong>You and we agree that any and all claims, disputes or controversies between".
	" you and us, any claim by either of us against the other (or the employees,".
	" officers, directors, agents, servicers or assigns of the other) and any claim".
	" arising from or relating to your application for this loan, regarding this loan".
	" or any other loan you previously or may later obtain from us, this Note, this".
	" agreement to arbitrate all disputes, your agreement not to bring, join or".
	" participate in class actions, regarding collection of the loan, alleging".
	" fraud or misrepresentation, whether under common law or pursuant to federal,".
	" state or local statute, regulation or ordinance, including disputes regarding".
	" the matters subject to arbitration, or <i>otherwise</i>, shall be resolved by binding".
	" individual (and not joint) arbitration by and under the Code of Procedure of".
	" the National Arbitration Forum (&quot;NAF&quot;) in effect at the time the".
	" claim is filed. No class arbitration. All disputes including any Representative".
	" Claims against us and/or related third parties shall be resolved by binding".
	" arbitration only on an individual basis with you.  THEREFORE, THE ARBITRATOR".
	" SHALL NOT CONDUCT CLASS ARBITRATION; THAT IS, THE ARBITRATOR SHALL NOT ALLOW".
	" YOU TO SERVE AS A REPRESENTATIVE, AS A PRIVATE ATTORNEY GENERAL, OR IN ANY".
	" OTHER REPRESENTATIVE CAPACITY FOR OTHERS IN THE ARBITRATION.</strong>".
	"</p>".
	"</td>".
	"<td width=\"4%\">&nbsp;</td>".
	"<td width=\"48%\">".
	"<p>".
	"<strong>This agreement to arbitrate all disputes shall apply no matter by whom or".
	" against whom the claim is filed. Rules and forms of the NAF may be obtained".
	" and all claims shall be filed at any NAF office, on the World Wide Web at".
	" www.arb-forum.com, by telephone at 800-474-2371, or at &quot;National".
	" Arbitration Forum, P.O. Box 50191, Minneapolis, Minnesota 55405.&quot;".
	" <i>Your arbitration fees will be waived by the NAF in the event you cannot".
	" afford to pay them</i>. The cost of any participatory, documentary or telephone hearing, if one is held at".
	" your or our request, will be paid for solely by us as provided in the NAF Rules and, if a participatory".
	" hearing is requested, it will take place at a location near your residence.  ".
	" This arbitration agreement is made pursuant to a transaction involving interstate commerce. ".
	" It shall be governed by the Federal Arbitration Act, 9 U.S.C. Sections 1-16. Judgment upon the award".
	" may be entered by any party in any court having jurisdiction.</strong>".
	"</p>".
	"<p>".
	"<strong>NOTICE: YOU AND WE WOULD HAVE HAD A RIGHT OR OPPORTUNITY TO LITIGATE".
	" DISPUTES THROUGH A COURT AND HAVE A JUDGE OR JURY DECIDE THE DISPUTES".
	" BUT HAVE AGREED INSTEAD TO RESOLVE DISPUTES THROUGH BINDING ARBITRATION.</strong>".
	"</p>".
	"<p>".
	"<strong><u>Agreement Not To Bring, Join Or Participate in Class Actions:</u> </strong>".
	"<strong>To the extent permitted by law, you agree that you will not bring,".
	" join or participate in any class action as to any claim, dispute".
	" or controversy you may have against us, our employees, officers,".
	" directors, servicers and assigns. You agree to the entry of".
	" injunctive relief to stop such a lawsuit or to remove you as a".
	" participant in the suit. You agree to pay the attorney's fees".
	" and court costs we incur in seeking such relief. This agreement".
	" does not constitute a waiver of any of your rights and remedies".
	" to pursue a claim individually and not as a class action in binding".
	" arbitration as provided above.</strong>".
	"</p>".
	"<p>".
	"<strong><u>Survival:</u> </strong>".
	"The provisions of this Loan Note And Disclosure dealing with the Agreement".
	" To Arbitrate All Disputes and the Agreement Not To Bring, Join Or Participate".
	" In Class Actions shall survive repayment in full and /or default of this Note.".
	"</p>".
	"<p>".
	"<strong><u>No Bankruptcy:</u> </strong>".
	"By electronically signing above you represent that you have not recently filed for bankruptcy".
	" and you do not plan to do so.".
	"</p>".
	"<p>".
	"<strong><u>NOTICE: ".
	"We adhere to the Patriot Act and we are required by law to adopt procedures to request and retain".
	" in our records information necessary to verify your identity.</u> </strong>".
	"</p>".
	"<p>".
	"By signing or electronically signing this Loan Note you certify that all of the information provided".
	" above is true, complete and correct and provided to us, <strong>".$condor_content["config"]->legal_entity."</strong>, for the purpose of inducing us to".
	" make the loan for which you are applying. By signing below or electronically signing you also agree".
	" to the Agreement to Arbitrate All Disputes and the Agreement Not To Bring, Join Or Participate in Class".
	" Actions. By signing or electronically signing this application you authorize <strong>".$condor_content["config"]->legal_entity."</strong> to verify all".
	" information that you have provided and acknowledge that this information may be used to verify certain".
	" past and/or current credit or payment history information from third party source(s).  <strong>".$condor_content["config"]->legal_entity."</strong> may".
	" utilize Check Loan Verification or other similar consumer-reporting agency for these purposes.  We may".
	" disclose all or some of the nonpublic personal information about you that we collect to financial service".
	" providers that perform services on our behalf, such as the servicer of your short term loan, and to".
	" financial institutions with which we have joint marketing arrangements.  Such disclosures are made as".
	" necessary to effect, administer and enforce the loan you request or authorize and any loan you may request".
	" or authorize with other financial institutions with regard to the processing, funding, servicing, repayment".
	" and collection of your loan.  <strong>(This Application will be deemed incomplete and will not be processed by us".
	" unless electronically signed by you above.)</strong>	".
	"</p>".
	"<br /><br />".
	"</td>".
	"</tr>".
	"</table>".
	"\n</div>";
?>