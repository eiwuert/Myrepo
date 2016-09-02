<?php 
/*
Template: Payday Widget
Description: Widget to assist user input pay day information
*/

	function breakoutPaydateInfo ($application){
		
		$result = array();
		$rtn = array();
		$paydate_model = $application['paydate_model'];
		$frequency = $application['income_frequency'];
		
		switch (strtoupper($frequency)) {
		    case "WEEKLY":
			$result['paydate']['weekly_day'] = strtoupper($application['day_of_week']);
			$rtn[$application['day_of_week']] = true;
			break;
		    case "BI_WEEKLY":
			$result['paydate']['biweekly_day'] = strtoupper($application['day_of_week']);
			$init_date = strtotime($application['last_paydate']);
			$diff = time()-$init_date;
			$num_biweeks = floor($diff / (60*60*24*14));
			$next_date = $init_date + $num_biweeks * (60*60*24*14);
			$result['paydate']['biweekly_date'] = date("m/d/Y",$next_date);
			$rtn[$application['day_of_week']] = true;
			$rtn[$result['paydate']['biweekly_date']] = true;
			break;
		    case "TWICE_MONTHLY":
			switch ($paydate_model) {
			    case "DMDM":
				$result['paydate']['twicemonthly_type'] = 'date';
				$result['paydate']['twicemonthly_date1'] = $application['day_of_month1'];
				$result['paydate']['twicemonthly_date2'] = $application['day_of_month2'];
				$rtn[$application['day_of_month1']] = true;
				$rtn[$application['day_of_month2']] = true;
				break;
			    case "WWDW":
				$result['paydate']['twicemonthly_type'] = 'week';
				$result['paydate']['twicemonthly_day'] = strtoupper($application['day_of_week']);
				$rtn[$application['day_of_week']] = true;
				$result['paydate']['twicemonthly_week'] = $application['week_1']."-".$application['week_2'];
				$rtn[$application['week_1']."-".$application['week_2']] = true;
				break;
			}
			break;
		    case "MONTHLY":
			switch ($paydate_model) {
			    case "DM":
				$result['paydate']['monthly_type'] = "date";
				$result['paydate']['monthly_date'] = $application['day_of_month1'];
				$rtn["date"] = true;
				$rtn[$application['day_of_month1']] = true;
				break;
			    case "WDW":
				$result['paydate']['monthly_type'] = "day";
				$result['paydate']['monthly_day'] = strtoupper($application['day_of_week']);
				$result['paydate']['monthly_week'] = $application['week_1'];
				$rtn["day"] = true;
				$rtn[$application['day_of_week']] = true;
				$rtn[$application['week_1']] = true;
				break;
			    case "DWDM":
				$paydate_model = "DWDM";
				$result['paydate']['monthly_type'] = "after";
				$result['paydate']['monthly_after_date'] = $application['day_of_month1'];
				$result['paydate']['monthly_after_day'] = strtoupper($application['day_of_week']);
				$rtn[$application['day_of_month1']] = true;
				$rtn[$application['day_of_week']] = true;
				$rtn["after"] = true;
				break;
			}
			break;
		}
		
		return(array($result,$rtn));
	}
	
	define("WEEKS_BACK", 2); # generate paychecks for this many weeks
	define("DAY_MON", "MON");
	define("DAY_TUE", "TUE");
	define("DAY_WED", "WED");
	define("DAY_THU", "THU");
	define("DAY_FRI", "FRI");
	define("DAY_SAT", "SAT");
	define("DAY_SUN", "SUN");
	define("FREQ_WEEKLY", "WEEKLY");
	define("FREQ_BIWEEKLY", "BI_WEEKLY");
	define("FREQ_TWICEMONTHLY", "TWICE_MONTHLY");
	define("FREQ_MONTHLY", "MONTHLY");

	$weekdays = array(
		DAY_MON => 1,
		DAY_TUE => 2,
		DAY_WED => 3,
		DAY_THU => 4,
		DAY_FRI => 5,
		DAY_SAT => 6,
		DAY_SUN => 7
	);
	$weekday = $weekdays[strtoupper(date("D"))];

	$biweekly_dates = array(
		DAY_MON => array(),
		DAY_TUE => array(),
		DAY_WED => array(),
		DAY_THU => array(),
		DAY_FRI => array()
	);
	
	$ts = strtotime(date("m/d/Y") . " -$weekday days");

	while (list($day,$i) = each($weekdays))
	{
		if ($i > $weekdays[DAY_FRI]) # skip weekends
			continue;
		$tmp_ts = strtotime(date("m/d/Y", $ts) . " +$i days");
		$go_back = ($i > $weekday ? WEEKS_BACK : WEEKS_BACK - 1); # don't fully rewind if dates could be in current week
		$tmp_ts = strtotime(date("m/d/Y", $tmp_ts) . " -$go_back weeks"); # go back far
		$c = 0;
		while ($c++ < WEEKS_BACK)
		{
			$biweekly_dates[$day][] = date("m/d/Y", $tmp_ts);
			$tmp_ts = strtotime(date("m/d/Y", $tmp_ts) . " +1 weeks");
		}
	}
	
	// check if user is logged in.  if so set up pdw result.
	if ((isset($_COOKIE[lum_getString("[SESSION_NAME]")])) &&
	    (!empty($_COOKIE[lum_getString("[SESSION_NAME]")])) &&
	    (isset($_SESSION['application']))){
		list($pdo,$rslt) = breakoutPaydateInfo($_SESSION['application']);
	} else {
		$pdo = false;
	}
?>

<div class="paydate_widget">
	<!-- weekly -->
	<div id="paydate_div_weekly" class="paydate_div">
		<div class="all_space" id="label_weekly_day">[cmstext Which day 1 Placeholder]
			<span class="pw_pretty_span">
				<select name="paydate[weekly_day]" id="paydate_weekly_day" tabindex="" class="select-input pw_pretty_select weekly_day qrtr_space" type="select-one">
					<option value="">[cmstext choose a day 1 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['weekly_day'])) $_SESSION['select_option'] = $pdo['paydate']['weekly_day'];
?>
					[cmsinclude Business Days 1]
				</select>
			</span>
		</div >
	</div>
	<!-- biweekly -->
	<div id="paydate_div_bi_weekly" class="paydate_div">
		<div class="" id="label_biweekly_day">
			[cmstext I get paid 24 Placeholder]
			<span class="pw_pretty_span">
				<select name="paydate[biweekly_day]" id="paydate_biweekly_day" onchange="funcBiweeklyDay();funcShowDayWeek(this.value);" tabindex="" class="select-input pw_pretty_select biweekly_date qrth_space" type="select-one" >
					<option value="">[cmstext choose a day 6 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['biweekly_day'])) $_SESSION['select_option'] = $pdo['paydate']['biweekly_day'];
?>
					[cmsinclude Business Days 2]							
				</select>
			</span>
		</div>
<?php
if ($pdo && isset($pdo['paydate']['biweekly_date'])) $hide = '';
else $hide = "hide";
?>
		<div id="div_biweekly_once_date" class="<?= $hide;?> div_biweekly_once_date">
			[cmstext last paydate 25 Placeholder]
			<div class="">
<?php

if ($pdo && isset($pdo['paydate']['biweekly_date'])) $select_option = $pdo['paydate']['biweekly_date'];
else $select_option = '';

$html = '';
foreach ($weekdays as $day => $key){
	
	if ($pdo && isset($pdo['paydate']['biweekly_day']) && ($pdo['paydate']['biweekly_day'] == $day)) $hide = '';
	else $hide = "hide";
	
	if ($biweekly_dates[$day][0] == $select_option) $checked_0 = "checked";
	else $checked_0 = "";
	if ($biweekly_dates[$day][1] == $select_option) $checked_1 = "checked";
	else $checked_1 = "";
	$html .= "
				<div id='paydate_biweekly_day_div_".$day."' class='".$hide."'>
					<input type='radio' id='paydate_".$day."_0' name='paydate[biweekly_date]' value='".$biweekly_dates[$day][0]."'  tabindex='' ".$checked_0."/> ".$biweekly_dates[$day][0]."<br />
					<input type='radio' id='paydate_".$day."_1' name='paydate[biweekly_date]' value='".$biweekly_dates[$day][1]."'  tabindex='' ".$checked_1."/> ".$biweekly_dates[$day][1]."<br />
				</div>
	";
}
echo $html;
?>
			</div>
			<span class="hint">
			[cmstext last paycheck Placeholder]
			</span>
		</div>
	</div>
	<!-- twicemonthly -->
	<div id="paydate_div_twice_monthly" class="paydate_div">
		<span id="label_twicemonthly_type">
			[cmstext Select Pay Schedule 2 Placeholder]
		</span>
		<ul style="list-style:none; padding-left:1em; padding-right:1em">
			<li>
<?php
if ($pdo && isset($pdo['paydate']['twicemonthly_type']) && ($pdo['paydate']['twicemonthly_type'] == "biweekly")) $checked = 'checked';
else $checked = '';
?>
				<div class="suboption">
					<input type="radio" name="paydate[twicemonthly_type]" id="paydate_twicemonthly_1" value="biweekly" onclick="div_twicemonthly_show('biweekly',2)"  tabindex="" <?= $checked;?>/>
					<span class="b">
						[cmstext I get paid 3 Placeholder] 
						<u><em>[cmstext every two weeks 4 Placeholder]</em></u> 
						[cmstext same day of the week 5 Placeholder]
					</span><br />
<?php
if ($pdo && isset($pdo['paydate']['twicemonthly_type']) && ($pdo['paydate']['twicemonthly_type'] == "biweekly")) {
	$checked = 'checked';
	$hide = '';
} else {
	$checked = '';
	$hide = 'hide';
}
?>
					<div id="div_twicemonthly_biweekly" class="<?= $hide;?>">
						<div class="">
							[cmstext I get paid on 6 Placeholder] 
							<span class="pw_pretty_span">
								<select name="paydate[biweekly_day_mnth]" id="paydate_biweekly_day" onchange="funcBiweeklyDayLabel();funcShowDayWeek(this.value);" tabindex="" class="select-input pw_pretty_select biweekly_date qrth_space" type="select-one" >
									<option value="">[cmstext choose a day 7 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['biweekly_day'])) $_SESSION['select_option'] = $pdo['paydate']['biweekly_day'];
?>
									[cmsinclude Business Days 3]							
								</select>
							</span>
						</div>
						<div class="<?= $hide;?> div_biweekly_twice_date" id="div_biweekly_twice_date">
							[cmstext last paydate 7 Placeholder] 
							<br /><span class="">
							[cmstext last paycheck 8 Placeholder] 
							</span>
<?php
if ($pdo && isset($pdo['paydate']['twicemonthly_type']) && ($pdo['paydate']['twicemonthly_type'] == "biweekly")) $select_option = $pdo['paydate']['biweekly_date'];
else $select_option = '';

$html = '';
foreach ($weekdays as $day => $key){
	
	if ($pdo && isset($pdo['paydate']['twicemonthly_type']) && ($pdo['paydate']['twicemonthly_type'] == "biweekly") && ($pdo['paydate']['twicemonthly_day'] == $day)) $hide = '';
	else $hide = "hide";
	
	if ($biweekly_dates[$day][0] == $select_option) $checked_0 = "checked";
	else $checked_0 = "";
	if ($biweekly_dates[$day][1] == $select_option) $checked_1 = "checked";
	else $checked_1 = "";

	$html .= "
							<div id='paydate_twice_biweekly_day_div_".$day."' class='".$hide."'>
								<input type='radio' id='paydate_".$day."_0' name='paydate[biweekly_date_mnth]' value='".$biweekly_dates[$day][0]."'  tabindex='' /> ".$biweekly_dates[$day][0]."<br />
								<input type='radio' id='paydate_".$day."_1' name='paydate[biweekly_date_mnth]' value='".$biweekly_dates[$day][1]."'  tabindex='' /> ".$biweekly_dates[$day][1]."<br />
							</div>
	";
}		
echo $html;
?>
						</div>
					</div>
					<span class="example">
						[cmstext every two weeks example 9 Placeholder]
					</span>
				</div>
			</li>
			<li>
					<div class="or">[cmstext or 50 Placeholder]</div>
			</li>
			<li>
<?php
if ($pdo && isset($pdo['paydate']['twicemonthly_type']) && ($pdo['paydate']['twicemonthly_type'] == "date")) {
	$checked = 'checked';
	$hide = '';
} else {
	$checked = '';
	$hide = 'hide';
}
?>
				<div class="suboption ">
					<input type="radio" name="paydate[twicemonthly_type]" id="paydate_twicemonthly_2" value="date" onclick="div_twicemonthly_show('date',3)" tabindex="" <?= $checked;?>/>
					<span class="b" id="label_twicemonthly_order">
						[cmstext I am paid based 10 Placeholder] 
						<u><em>[cmstext date 11 Placeholder]</em></u>
					</span><br />
					<div id="div_twicemonthly_date" class="<?= $hide;?>">
						[cmstext I get paid 12 Placeholder] 
						<span class="pw_pretty_span">
							<select name="paydate[twicemonthly_date1]" id="paydate_twicemonthly_date1" tabindex="" class="select-input pw_pretty_select twicemonthly_date1 eith_space" type="select-one" >
								<option value="">[cmstext choose a date 2 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['twicemonthly_date1'])) $select_option = $pdo['paydate']['twicemonthly_date1'];
	else $select_option = '';
	include(TEMPLATES_PATH. 'day_of_month.inc.php');
	if (($pdo && isset($pdo['paydate']['twicemonthly_date1']) && ($pdo['paydate']['twicemonthly_date1'] ==32))) $selected = 'selected';
?>								
								<option value="32"  <?= $selected;?>>[cmstext Last Day 13 Placeholder]</option>
							</select>
						</span>
						[cmstext and Placeholder]
						<span class="pw_pretty_span">
							<select name="paydate[twicemonthly_date2]" id="paydate_twicemonthly_date2" tabindex="" class="select-input pw_pretty_select twicemonthly_date2 eith_space" type="select-one" >
								<option value="">[cmstext choose a date 3 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['twicemonthly_date2'])) $select_option = $pdo['paydate']['twicemonthly_date2'];
	else $select_option = '';
	include(TEMPLATES_PATH. 'day_of_month.inc.php');
	if (($pdo && isset($pdo['paydate']['twicemonthly_date2']) && ($pdo['paydate']['twicemonthly_date2'] ==32))) $selected = 'selected';
?>								
								<option value="32" <?= $selected?>>[cmstext Last Day 14 Placeholder]</option>
							</select>
						</span>
						[cmstext of every month 15 Placeholder]
					</div>
					<span class="example">
						[cmstext twice monthly example 16 Placeholder]
					</span>
				</div>
			</li>
			<li>
					<div class="or">[cmstext or 51 Placeholder]</div>
			</li>
			<li>
<?php
if ($pdo && isset($pdo['paydate']['twicemonthly_type']) && ($pdo['paydate']['twicemonthly_type'] == "week")) {
	$checked = 'checked';
	$hide = '';
} else {
	$checked = '';
	$hide = 'hide';
}
?>
				<div class="suboption ">
					<input type="radio" name="paydate[twicemonthly_type]" value="week" id="paydate_twicemonthly_3" onclick="div_twicemonthly_show('day',3)" tabindex="" <?= $checked;?>/>
					<span class="b" id="label_twicemonthly_dw">
						[cmstext same day 18 Placeholder] 
						<u><em>[cmstext twice monthly 19 Placeholder]</em></u>
					</span><br />
					<div id="div_twicemonthly_day" class="<?= $hide;?>">
						[cmstext I get paid 20 Placeholder] 
						<span class="pw_pretty_span">
<?php
	if ($pdo && (isset($pdo['paydate']['twicemonthly_week'])) && ($pdo['paydate']['twicemonthly_week'] == "1-3")) $selected1 = 'selected';
	else $selected1 = '';
	if ($pdo && (isset($pdo['paydate']['twicemonthly_week'])) && ($pdo['paydate']['twicemonthly_week'] == "2-4")) $selected2 = 'selected';
	else $selected2 = '';
?>								
							<select name="paydate[twicemonthly_week]" id="paydate_twicemonthly_week" tabindex="" class="select-input pw_pretty_select twicemonthly_week thei_space" type="select-one" >
								<option value="">[cmstext choose whick week 4 Placeholder]</option>
								<option value="1-3" >[cmstext first and third 21 Placeholder]</option>
								<option value="2-4">[cmstext second and fourth 22 Placeholder]</option>
							</select>
						</span>
						<span class="pw_pretty_span">
							<select name="paydate[twicemonthly_day]" id="paydate_twicemonthly_day" tabindex="" class="select-input pw_pretty_select twicemonthly_day qrth_space" type="select-one" >
								<option value="">[cmstext choose a day 5 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['twicemonthly_type'])) $_SESSION['select_option'] = $pdo['paydate']['twicemonthly_day'];
?>								
								[cmsinclude Business Days 4] 								
							</select>
						</span>
						[cmstext of every month 4]
					</div>
					<span class="example">
						[cmstext twice monthly day example 23 Placeholder]
					</span>
				</div>
			</li>
		</ul>
	</div>
	<!-- monthly -->
	<div id="paydate_div_monthly" class="paydate_div">
		<div >
			<span  id="label_monthly_type">[cmstext Select Pay Schedule 26 Placeholder]</span>
			<ul style="list-style:none; padding-left:1em; padding-right:1em">
				<li>
<?php
if ($pdo && isset($pdo['paydate']['monthly_type']) && ($pdo['paydate']['monthly_type'] == "date")) {
	$checked = 'checked';
	$hide = '';
} else {
	$checked = '';
	$hide = 'hide';
}
?>
					<div class="suboption">
						<input type="radio" name="paydate[monthly_type]" id="paydate_monthly_type_1" value="date" onclick="div_monthly_show('date')"  tabindex="" <?= $checked;?> />
						<span class="b" id="label_monthly_date">
							[cmstext paid on specific 27 Placeholder] 
							<u><em>[cmstext date 28 Placeholder]</em></u>
						</span><br />
						<div id="div_monthly_date" class="<?= $hide;?>">
							[cmstext paid on 29 Placeholder] 
							<span class="pw_pretty_span">
								<select name="paydate[monthly_date]" id="paydate_monthly_date" tabindex="" class="select-input pw_pretty_select eith_space monthly_date" type="select-one" >
									<option value="">[cmstext choose a date 6 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['monthly_date'])) $select_option = $pdo['paydate']['monthly_date'];
	else $select_option = '';
	include(TEMPLATES_PATH. 'day_of_month.inc.php');
	if (($pdo && isset($pdo['paydate']['monthly_date']) && ($pdo['paydate']['monthly_date'] ==32))) $selected = 'selected';
?>								
									<option value="32" <?= $selected;?>[cmstext Last Day 15 Placeholder]</option>
								</select>
							</span>
							[cmstext every month 30 Placeholder] 
						</div>
						<span class="example">[cmstext date of month example 31 Placeholder]</span>
					</div>
				</li>
				<li>
					<div class="or">
						[cmstext or 32 Placeholder] 
					</div>
				</li>
				<li>
<?php
$select_ary = array();
if ($pdo && isset($pdo['paydate']['monthly_type']) && ($pdo['paydate']['monthly_type'] == "day")) {
	$select_ary[$pdo['paydate']['monthly_week']] = true;
	$checked = 'checked';
	$hide = '';
} else {
	$checked = '';
	$hide = 'hide';
}
?>
					<div class="suboption">
						<input type="radio" name="paydate[monthly_type]" id="paydate_monthly_type_2" value="day" onclick="div_monthly_show('day')" tabindex="" <?= $checked;?> />
						<span class="b" id="label_monthly_dw">
							[cmstext paid on 33 Placeholder]
							<u><em>[cmstext cirtain day week 34 Placeholder]</em></u>
						</span><br />
						<div id="div_monthly_day" class="<?= $hide;?>">
							[cmstext paid on 35 Placeholder] 
						<span class="pw_pretty_span">
							<select name="paydate[monthly_week]" id="paydate_monthly_week" tabindex="" class="select-input pw_pretty_select  qrth_space monthly_week" type="select-one" >
								<option value="">[cmstext choose a week 7 Placeholder]</option>
								<option value="1" <?= isset($select_ary[1]) ? 'selected': '';?>>[cmstext first 36 Placeholder]</option>
								<option value="2" <?= isset($select_ary[2]) ? 'selected': '';?>>[cmstext second 37 Placeholder]</option>
								<option value="3" <?= isset($select_ary[3]) ? 'selected': '';?>>[cmstext third 38 Placeholder]</option>
								<option value="4" <?= isset($select_ary[4]) ? 'selected': '';?>>[cmstext fourth 39 Placeholder]</option>
								<option value="5" <?= isset($select_ary[5]) ? 'selected': '';?>>[cmstext last 40 Placeholder]</option>
							</select>
						</span>
						<span class="pw_pretty_span">
							<select name="paydate[monthly_day]" id="paydate_monthly_day" tabindex="" class="select-input pw_pretty_select qrth_space monthly_day" type="select-one" >
								<option value="">[cmstext choose a day 8 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['monthly_day'])) $_SESSION['select_option'] = $pdo['paydate']['monthly_day'];
?>								
								[cmsinclude Business Days 5]
							</select>
						</span>
						[cmstext every month 41 Placeholder] 
						</div>
						<span class="example">
							[cmstext cirtain day week example 42 Placeholder] 
						</span>
					</div>
				</li>
				<li>
					<div class="or">[cmstext or 43 Placeholder]</div>
				</li>
				<li>
<?php
if ($pdo && isset($pdo['paydate']['monthly_type']) && ($pdo['paydate']['monthly_type'] == "after")) {
	$checked = 'checked';
	$hide = '';
} else {
	$checked = '';
	$hide = 'hide';
}
?>
					<div class="suboption">
						
						<input type="radio" name="paydate[monthly_type]" id="paydate_monthly_type_3" value="after" onclick="div_monthly_show('after')"  tabindex="" <?= $checked;?>/>
						<span class="b" id="label_monthly_after">
							[cmstext paid 44 Placeholder] 
							<u><em>[cmstext after date 45 Placeholder]</em></u>
						</span><br />
						<div id="div_monthly_after" class="<?= $hide;?>">
							[cmstext paid on first 46 Placeholder] 
							<span class="pw_pretty_span">
								<select name="paydate[monthly_after_day]" id="paydate_monthly_after_day" tabindex="" class="select-input pw_pretty_select qrth_space monthly_after_day" type="select-one" >
									<option value="">[cmstext choose a day 9 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['monthly_after_day'])) $_SESSION['select_option'] = $pdo['paydate']['monthly_after_day'];
?>
									[cmsinclude Business Days 6]
								</select>
							</span>
							[cmstext after the 47 Placeholder] 
							<span class="pw_pretty_span">
								<select name="paydate[monthly_after_date]" id="paydate_monthly_after_date" tabindex="" class="select-input pw_pretty_select eith_space monthly_after_date" type="select-one" >
								       <option value="">[cmstext choose a date 10 Placeholder]</option>
<?php
	if ($pdo && isset($pdo['paydate']['monthly_after_date'])) $select_option = $pdo['paydate']['monthly_after_date'];
	else $select_option = '';
	include(TEMPLATES_PATH. 'day_of_month.inc.php');
	if (($pdo && isset($pdo['paydate']['monthly_after_date']) && ($pdo['paydate']['monthly_after_date'] ==32))) $selected = 'selected';
?>								
									<option value="32" <?= $selected;?>>[cmstext Last Day 16 Placeholder]</option>
							       </select>
							</span>
							[cmstext of the month 48 Placeholder]
						</div>
						<span class="example">[cmstext after date example 49 Placeholder]</span>
					</div>
				</li>
			</ul>
		</div>
	</div>
</div>
