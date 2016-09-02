<?php
/*
Template: React Application Form
Description: The form contents for a lead (user) to supply the react application information with defaults filled
*/
	$application = getSession();
?>
	<fieldset>
		<input id="is_react" name="is_react" type="hidden" value="TRUE"/>
		<h2 class="fs-title">[cmstext React Application Form Placeholder]</h2>
		<h3 class="fs-subtitle">[cmstext Please update any information Placeholder]</h3>
		<div>
			<table class="ap_table">
				<tr><td colspan=2><h2 class="fs-title">[cmstext Personal Identification Title Placeholder]</h2></td></tr>
				<tr>
					<td class="label_cell">[cmstext First Name Placeholder]</td>
					<td class="input_cell"><input id="name_first" name="name_first" type="text" class="first validate[required] fieldset text-input" value="<?= $application['name_first']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Last Name Placeholder]</td>
					<td class="input_cell"><input id="name_last" name="name_last" type="text" class="last validate[required] fieldset text-input" value="<?= $application['name_last']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext DOB Placeholder]</td>
					<td class="input_cell"><input id="dob_datepicker" name="date_of_birth" type="text" class="dob validate[required,custom[date]] fieldset datepicker text-input" value="<?= $application['dob']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Legal ID Number Placeholder]</td>
					<td class="input_cell"><input id="legal_id_number" name="legal_id_number" type="text" class="legal_id validate[required] fieldset text-input" value="<?= $application['legal_id_number']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Licence State Placeholder]</td>
					<td class="input_cell">
						<div class="pretty_select state">
							<select id="legal_id_state" name="legal_id_state" class="validate[required] fieldset select-input" type="select-one">
								<?php $select_option = $application['legal_id_state']; include(TEMPLATES_PATH. 'states.inc.php'); ?>								
							</select>
						</div>
					</td>
				</tr>
				<tr><td colspan=2><h2 class="fs-title">[cmstext Supply Contact Information Placeholder]</h2></td></tr>
				<tr>
					<td class="label_cell">[cmstext Email Placeholder]</td>
					<td class="input_cell"><input id="email" name="email_primary" type="text" class="email validate[required,custom[email]] fieldset text-input" value="<?= $application['email']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Home Phone Placeholder]</td>
					<td class="input_cell"><input id="phone_home" name="phone_home" maxlength="12" type="text" class="phone validate[required,custom[phone]] fieldset text-input" value="<?= str_format($application['phone_home'],'phone')?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Cell Phone Placeholder]</td>
					<td class="input_cell"><input id="phone_cell" name="phone_cell" maxlength="12" type="text" class="phone validate[custom[phone]] fieldset text-input" value="<?= str_format($application['phone_cell'],'phone')?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Address Placeholder]</td>
					<td class="input_cell"><input id="address" name="address" maxlength="100" type="text" class="address validate[required] fieldset text-input" value="<?= $application['street']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext City Placeholder]</td>
					<td class="input_cell"><input id="city" name="city" maxlength="100" type="text" class="city validate[required] fieldset text-input" value="<?= $application['city']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext State Placeholder]</td>
					<td class="input_cell">
						<div class="pretty_select state">
							<select id="state" name="state" class="validate[required] fieldset select-input" type="select-one">
								<?php $select_option = $application['state']; include(TEMPLATES_PATH. 'states.inc.php'); ?>								
							</select>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Zip Code Placeholder]</td>
					<td class="input_cell"><input id="zip_code" name="zip_code" maxlength="5" type="text" class="zip_code right_side validate[required,custom[zip]] fieldset text-input" value="<?= $application['zip_code']?>"/></td>
				</tr>
				<tr><td colspan=2><h2 class="fs-title">[cmstext Employer Details Placeholder]</h2></td></tr>
				<tr><td colspan=2 class="merge_cell"><div class="income_source income_source_question merge_cell">[cmstext Income Source Question Placeholder]</div></td></tr>
				<tr><td colspan=2 class="merge_cell">
					<div class="income_source income_source_answer merge_cell">
						<input class="rad validate[required] fieldset" name="income_source" id="income_employment" value="EMPLOYMENT" tabindex="" type="radio" <?= $application['income_source'] == 'employment' ? 'checked':'';?>> [cmstext Employment Placeholder] &nbsp;&nbsp;&nbsp;
						<input class="rad validate[required] fieldset" name="income_source" id="income_benefits" value="BENEFITS" tabindex="" type="radio" <?= $application['income_source'] == 'benefits' ? 'checked':'';?>> [cmstext Benefits Placeholder]
					</div>
				</td></tr>
				<tr>
					<td class="label_cell">[cmstext SSN Placeholder]</td>
					<td class="input_cell"><input id="ssn" name="ssn" maxlength="11" type="text" class="ssn validate[required,custom[ssn]] fieldset text-input" value="<?= str_format($application['ssn'],'ssn')?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Employer Placeholder]</td>
					<td class="input_cell"><input id="employer" name="employer" type="text" class="employer validate[required] fieldset text-input" value="<?= $application['employer_name']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Work Phone Placeholder]</td>
					<td class="input_cell"><input id="phone_work" name="phone_work" maxlength="12" type="text" class="phone validate[required,custom[phone]] fieldset text-input" value="<?= str_format($application['phone_work'],'phone')?>"/></td>
				</tr>
				<tr><td colspan=2 class="merge_cell"><div class="military military_question merge_cell">[cmstext Military Question Placeholder]</div></td></tr>
				<tr><td colspan=2 class="merge_cell">
					<div class="military military_answer merge_cell">
						<input class="rad validate[required] fieldset" name="military" id="military_yes" value="TRUE" tabindex="" type="radio" <?= $application['income_source'] == 'military' ? 'checked':'';?>> [cmstext Yes Placeholder] &nbsp;&nbsp;&nbsp;
						<input class="rad validate[required] fieldset" name="military" id="military_no" value="FALSE" tabindex="" type="radio" <?= $application['income_source'] == 'military' ? '':'checked';?>> [cmstext No Placeholder]
					</div>
				</td></tr>
				<tr><td colspan=2 class="merge_cell"><div class="military military_disclaim merge_cell"><a href="/<?=getCurrentLanguage();?>/military_disclaim" onclick="centeredPopup(this.href,'military_disclaim','1000','600','yes');return false"><small>[cmstext Military Disclaimer Placeholder]</small></a></div></td></tr>
				<tr><td colspan=2><h2 class="fs-title">[cmstext Income Details Placeholder]</h2></td></tr>
				<tr>
					<td class="label_cell">[cmstext Income Placeholder]</td>
					<td class="input_cell"><input id="monthly_income" name="monthly_income" maxlength="" type="text" class="monthly_income validate[required,custom[onlyNumber]] fieldset text-input" value="<?= $application['income_monthly']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Pay Frequency]</td>
					<td class="input_cell">
						<div class="pretty_select pay_frequency">
							<select name="paydate[frequency]" id="pay_frequency" onchange="change_paydate_model(this.value)" tabindex="" class="validate[required] fieldset4 select-input" type="select-one">
								<option value="WEEKLY" <?= strtolower($application['income_frequency']) == 'weekly' ? 'selected':'';?>>[cmstext Every Week]</option>
								<option value="BI_WEEKLY" <?= strtolower($application['income_frequency']) == 'bi_weekly' ? 'selected':'';?>>[cmstext Every Other Week]</option>
								<option value="TWICE_MONTHLY" <?= strtolower($application['income_frequency']) == 'twice_weekly' ? 'selected':'';?>>[cmstext Twice Per Month]</option>
								<option value="MONTHLY" <?= strtolower($application['income_frequency']) == 'monthly' ? 'selected':'';?>>[cmstext Once Per Month]</option>
							</select>
						</div>
					</td>
				</tr>
				<tr><td colspan=2 class='paydate_widget_react'><div class='paydate_widget_react'>[cmsinclude Payday Widget]</div></td></tr>
				<tr><td colspan=2><h2 class="fs-title">[cmstext Banking Details Placeholder]</h2></td></tr>
				<tr>
					<td class="label_cell">[cmstext Bank Name Placeholder]</td>
					<td class="input_cell"><input id="bank_name" name="bank_name" type="text" maxlength="40" class="bank_name validate[required] fieldset text-input" value="<?= $application['bank_name']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Bank ABA Placeholder]</td>
					<td class="input_cell"><input id="bank_aba" name="bank_aba" type="text" maxlength="9"class="bank_aba validate[required,custom[aba]] fieldset text-input" value="<?= $application['bank_aba']?>"/></td>
				</tr>
				<tr>
					<td class="label_cell">[cmstext Bank Account Placeholder]</td>
					<td class="input_cell"><input id="bank_account" name="bank_account" type="text" maxlength="20" class="bank_account validate[required,custom[onlyNumber],length[7,17]] fieldset5 text-input" value="<?= $application['bank_account']?>"/></td>
				</tr>
				<tr><td colspan=2 class="merge_cell"><div class="bank_type bank_type_question merge_cell">[cmstext Bank Type Question Placeholder]</div></td></tr>
				<tr><td colspan=2 class="merge_cell">
					<div class="bank_type bank_type_answer merge_cell">
						<input class="rad validate[required] fieldset" name="bank_type" id="bank_type_check" value="CHECKING" tabindex="" type="radio" <?= $application['bank_account_type'] == 'checking' ? 'checked':'';?> > [cmstext Checking Placeholder] &nbsp;&nbsp;&nbsp;
						<input class="rad validate[required] fieldset" name="bank_type" id="bank_type_save" value="SAVINGS" tabindex="" type="radio" <?= $application['bank_account_type'] == 'checking' ? '':'checked';?> > [cmstext Savings Placeholder]
					</div>
				</td></tr>
				<tr><td colspan=2 class="merge_cell"><div class="direct_deposit direct_deposit_question merge_cell">[cmstext Direct Diposit Question Placeholder]</div></td></tr>
				<tr><td colspan=2 class="merge_cell">
					<div class="direct_deposit direct_deposit_answer merge_cell">
						<input class="rad validate[required] fieldset" name="direct_deposit" id="direct_deposit_yes" value="TRUE" tabindex="" type="radio" <?= $application['income_direct_deposit'] == 'no' ? '':'checked';?> > [cmstext Yes1 Placeholder] &nbsp;&nbsp;&nbsp;
						<input class="rad validate[required] fieldset" name="direct_deposit" id="direct_deposit_no" value="FALSE" tabindex="" type="radio" <?= $application['income_direct_deposit'] == 'no' ? 'checked':'';?> > [cmstext No1 Placeholder]
					</div>
				</td></tr>
				<tr><td colspan=2><div class="center_button"><button type="submit" name="submit" class="submit action-button center_side" value="Submit" >[cmstext Submit Placeholder]</button></div></td></tr>
			</table>
		</div>
	</fieldset>