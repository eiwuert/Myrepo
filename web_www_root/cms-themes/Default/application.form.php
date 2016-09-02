<?php
/*
Template: Application Form
Description: The form contents for a lead (user) to supply the application information
*/
?>
<?php 	include 'cms-includes/recaptchalib.php'; ?>
<!-- multistep form
<form class="msform"> -->
	<!-- progressbar -->
	<ul id="progressbar">
		<li class="active">[cmstext Personal Step Placeholder]</li>
		<li>[cmstext Contact Step Placeholder]</li>
		<li>[cmstext Employer Step Placeholder]</li>
		<li>[cmstext Income Step Placeholder]</li>
		<li>[cmstext Bank Step Placeholder]</li>
		<li>[cmstext References Step Placeholder]</li>
		<li>[cmstext Captcha Step Placeholder]</li>
	</ul>
	<!-- fieldsets -->
	<fieldset>
		<h2 class="fs-title">[cmstext Personal Identification Title Placeholder]</h2>
		<h3 class="fs-subtitle">[cmstext supply the information1 Placeholder]</h3>
		<input id="name_first" name="name_first" type="text" placeholder="[cmstext First Name Placeholder]" class="first validate[required] fieldset1 text-input"/>
		<input id="name_last" name="name_last" type="text" placeholder="[cmstext Last Name Placeholder]" class="last validate[required] fieldset1 text-input"/>
		<input id="dob_datepicker" name="date_of_birth" type="text" placeholder="[cmstext DOB Placeholder]" class="dob validate[required,custom[date]] fieldset1 datepicker text-input"/>
		<input id="legal_id_number" name="legal_id_number" type="text" placeholder="[cmstext Legal ID Number Placeholder]" class="legal_id validate[required] fieldset1 text-input"/>
		<div class="pretty_select half_space_select legal_id_state">
			<select id="legal_id_state" name="legal_id_state" class="validate[required] fieldset1 select-input" type="select-one">
				<option value="">[cmstext Licence State Placeholder]</option>
				<?php include(TEMPLATES_PATH. 'states.inc.php'); ?>								
			</select>
		</div>
		<button type="button" name="fieldset1" class="next action-button right_side" value="Next" >[cmstext Next2 Placeholder]</button>
	</fieldset>
	<fieldset>
		<h2 class="fs-title">[cmstext Supply Contact Information Placeholder]</h2>
		<h3 class="fs-subtitle">[cmstext supply the information2 Placeholder]</h3>
		<input id="email" name="email_primary" type="text" placeholder="[cmstext Email Placeholder]" class="email validate[required,custom[email]] fieldset2 text-input"/>
		<input id="phone_home" name="phone_home" maxlength="12" type="text" placeholder="[cmstext Home Phone Placeholder]" class="phone validate[required,custom[phone]] fieldset2 text-input"/>
		<input id="phone_cell" name="phone_cell" maxlength="12" type="text" placeholder="[cmstext Cell Phone Placeholder]" class="phone validate[custom[phone]] fieldset2 text-input"/>
		<input id="address" name="address" maxlength="100" type="text" placeholder="[cmstext Address Placeholder]" class="address validate[required] fieldset2 text-input"/>
		<input id="city" name="city" maxlength="100" type="text" placeholder="[cmstext City Placeholder]" class="city validate[required] fieldset2 text-input"/>
		<div class="pretty_select half_space_select left_side state">
			<select id="state" name="state" class="validate[required] fieldset2 select-input" type="select-one">
				<option value="">[cmstext State Placeholder]</option>
				<?php include(TEMPLATES_PATH. 'states.inc.php'); ?>								
			</select>
		</div>
		<input id="zip_code" name="zip_code" maxlength="5" type="text" placeholder="[cmstext Zip Code Placeholder]" class="zip_code right_side half_space validate[required,custom[zip]] fieldset2 text-input"/>
		<button type="button" name="previous" class="previous action-button left_side" value="Previous" >[cmstext Previous1 Placeholder]</button>
		<button type="button" name="fieldset2" class="next action-button right_side" value="Next" >[cmstext Next3 Placeholder]</button>
	</fieldset>
	<fieldset>
		<h2 class="fs-title">[cmstext Employer Details Placeholder]</h2>
		<h3 class="fs-subtitle">[cmstext supply the information3 Placeholder]</h3>
                <div class="income_source income_source_question">[cmstext Income Source Question Placeholder]</div>
		<div class="income_source income_source_answer">
			<input class="rad validate[required] fieldset3" name="income_source" id="income_employment" value="EMPLOYMENT" tabindex="" type="radio" > [cmstext Employment Placeholder] &nbsp;&nbsp;&nbsp;
			<input class="rad validate[required] fieldset3" name="income_source" id="income_benefits" value="BENEFITS" tabindex="" type="radio" > [cmstext Benefits Placeholder]
		</div>
		<input id="ssn" name="ssn" maxlength="11" type="text" placeholder="[cmstext SSN Placeholder]" class="ssn validate[required,custom[ssn]] fieldset3 text-input"/>
		<input id="employer" name="employer" type="text" placeholder="[cmstext Employer Placeholder]" class="employer validate[required] fieldset3 text-input"/>
		<input id="phone_work" name="phone_work" maxlength="12" type="text" placeholder="[cmstext Work Phone Placeholder]" class="phone validate[required,custom[phone]] fieldset3 text-input"/>
                <div class="military military_question">[cmstext Military Question Placeholder]</div>
		<div class="military military_answer">
			<input class="rad validate[required] fieldset3" name="military" id="military_yes" value="TRUE" tabindex="" type="radio" > [cmstext Yes Placeholder] &nbsp;&nbsp;&nbsp;
			<input class="rad validate[required] fieldset3" name="military" id="military_no" value="FALSE" tabindex="" type="radio" > [cmstext No Placeholder]
		</div>
                <div class="military military_disclaim"><a href="/<?=getCurrentLanguage();?>/military_disclaim" onclick="centeredPopup(this.href,'military_disclaim','1000','600','yes');return false"><small>[cmstext Military Disclaimer Placeholder]</small></a></div>
		<button type="button" name="previous" class="previous action-button left_side" value="Previous" >[cmstext Previous2 Placeholder]</button>
		<button type="button" name="fieldset3" class="next action-button right_side" value="Next" >[cmstext Next4 Placeholder]</button>
	</fieldset>
	<fieldset>
		<h2 class="fs-title">[cmstext Income Details Placeholder]</h2>
		<h3 class="fs-subtitle">[cmstext supply the information4 Placeholder]</h3>
		<input id="monthly_income" name="monthly_income" maxlength="" type="text" placeholder="[cmstext Income Placeholder]" class="monthly_income validate[required,custom[onlyNumber]] fieldset4 text-input"/>
		<div class="pretty_select full_space pay_frequency">
			<select name="paydate[frequency]" id="pay_frequency" onchange="change_paydate_model(this.value)" tabindex="" class="validate[required] fieldset4 select-input" type="select-one">
				<option value="">[cmstext Pay Frequency]</option>
				<option value="WEEKLY">[cmstext Every Week]</option>
				<option value="BI_WEEKLY">[cmstext Every Other Week]</option>
				<option value="TWICE_MONTHLY">[cmstext Twice Per Month]</option>
				<option value="MONTHLY">[cmstext Once Per Month]</option>
			</select>
		</div>
		[cmsinclude Payday Widget]
		<button type="button" name="previous" class="previous action-button left_side" value="Previous" >[cmstext Previous3 Placeholder]</button>
		<button type="button" name="fieldset4" class="next action-button right_side" value="Next" >[cmstext Next5 Placeholder]</button>
	</fieldset>
	<fieldset>
		<h2 class="fs-title">[cmstext Banking Details Placeholder]</h2>
		<h3 class="fs-subtitle">[cmstext supply the information5 Placeholder]</h3>
		<input id="bank_name" name="bank_name" type="text" maxlength="40" placeholder="[cmstext Bank Name Placeholder]" class="bank_name validate[required] fieldset5 text-input"/>
		<input id="bank_aba" name="bank_aba" type="text" maxlength="9" placeholder="[cmstext Bank ABA Placeholder]" class="bank_aba validate[required,custom[aba]] fieldset5 text-input"/>
		<input id="bank_account" name="bank_account" type="text" maxlength="20" placeholder="[cmstext Bank Account Placeholder]" class="bank_account validate[required,custom[onlyNumber],length[7,17]] fieldset5 text-input"/>
                <div class="bank_type bank_type_question">[cmstext Bank Type Question Placeholder]</div>
		<div class="bank_type bank_type_answer">
			<input class="rad validate[required] fieldset5" name="bank_type" id="bank_type_check" value="CHECKING" tabindex="" type="radio" > [cmstext Checking Placeholder] &nbsp;&nbsp;&nbsp;
			<input class="rad validate[required] fieldset5" name="bank_type" id="bank_type_save" value="SAVINGS" tabindex="" type="radio" > [cmstext Savings Placeholder]
		</div>
                <div class="direct_deposit direct_deposit_question">[cmstext Direct Diposit Question Placeholder]</div>
		<div class="direct_deposit direct_deposit_answer">
			<input class="rad validate[required] fieldset5" name="direct_deposit" id="direct_deposit_yes" value="TRUE" tabindex="" type="radio" > [cmstext Yes1 Placeholder] &nbsp;&nbsp;&nbsp;
			<input class="rad validate[required] fieldset5" name="direct_deposit" id="direct_deposit_no" value="FALSE" tabindex="" type="radio" > [cmstext No1 Placeholder]
		</div>
		<img class="check_image" src="[cmsimage check info]" alt="" />
		<button type="button" name="previous" class="previous action-button left_side" value="Previous" >[cmstext Previous4 Placeholder]</button>
		<button type="button" name="fieldset5" class="next action-button right_side" value="Next" >[cmstext Next6 Placeholder]</button>
	</fieldset>
	<fieldset>
		<h2 class="fs-title">[cmstext Reference Details Placeholder]</h2>
		<h3 class="fs-subtitle">[cmstext supply the information6 Placeholder]</h3>
		<div class="reference1">
			<h3 class="reference_title fs-subtitle" >[cmstext personal reference 1 Placeholder]</h3>
			<input id="ref_01_name_full" name="ref_01_name_full" type="text" maxlength="100" placeholder="[cmstext Reference 1 Name Placeholder]" class="ref_name_full text-input reference_input"/>
			<input id="ref_01_phone" name="ref_01_phone" type="text" maxlength="12" placeholder="[cmstext Reference 1 Phone Placeholder]" class="phone text-input reference_input"/>
			<div class="ref_01_relationship full_space pretty_select">
				<select name="ref_01_relationship" id="ref_01_relationship" tabindex="" class="select-input reference_input" type="select-one">
					<option value="">[cmstext Please Select 1 Placeholder]</option>
					<option 'parent'>[cmstext Parent 1 Placeholder]</option>
					<option 'sibling'>[cmstext Sibling 1 Placeholder]</option>
					<option 'friend'>[cmstext Friend 1 Placeholder]</option>
					<option 'Co-Worker'>[cmstext Co Worker 1 Placeholder]</option>
					<option 'extended_family'>[cmstext Extended Family 1 Placeholder]</option>
				</select>';
			</div>
		</div>
		<div class="reference2">
			<h3 class="reference_title fs-subtitle" >[cmstext personal reference 2 Placeholder]</h3>
			<input id="ref_02_name_full" name="ref_02_name_full" type="text" maxlength="100" placeholder="[cmstext Reference 2 Name Placeholder]" class="ref_name_full text-input reference_input"/>
			<input id="ref_02_phone" name="ref_02_phone" type="text" maxlength="12" placeholder="[cmstext Reference 2 Phone Placeholder]" class="phone text-input reference_input"/>
			<div class="ref_02_relationship full_space pretty_select">
				<select name="ref_02_relationship" id="ref_02_relationship" tabindex="" class="select-input reference_input" type="select-one">
					<option value="">[cmstext Please Select 2 Placeholder]</option>
					<option 'parent'>[cmstext Parent 2 Placeholder]</option>
					<option 'sibling'>[cmstext Sibling 2 Placeholder]</option>
					<option 'friend'>[cmstext Friend 2 Placeholder]</option>
					<option 'Co-Worker'>[cmstext Co Worker 2 Placeholder]</option>
					<option 'extended_family'>[cmstext Extended Family 2 Placeholder]</option>
				</select>';
			</div>
		</div>
		<button type="button" name="previous" class="previous action-button left_side" value="Previous" >[cmstext Previous5 Placeholder]</button>
		<button type="button" name="fieldset6" class="next action-button right_side" value="Next" >[cmstext Next7 Placeholder]</button>
	</fieldset>
	<fieldset>
		<h2 class="fs-title">[cmstext Captcha Verification Placeholder]</h2>
		<h3 class="fs-subtitle">[cmstext fill in the captcha Placeholder]</h3>
		<div id="captcha" name="captcha" class="captcha">
			<?= recaptcha_get_html(lum_getString("[CAPTCHA_SITE_KEY]"),null,true); ?>
		</div>
		<button type="button" name="previous" class="previous action-button left_side" value="Previous" >[cmstext Previous6 Placeholder]</button>
		<button type="submit" name="submit" class="submit action-button right_side" value="Submit" >[cmstext Submit Placeholder]</button>
	</fieldset>

<!-- </form>

<!-- jQuery ->
<script src="http://thecodeplayer.com/uploads/js/jquery-1.9.1.min.js" type="text/javascript"></script>
<!-- jQuery easing plugin
<script src="http://thecodeplayer.com/uploads/js/jquery.easing.min.js" type="text/javascript"></script>
 -->
