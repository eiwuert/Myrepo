<?php
/*
Template: Customer Privacy Settings Request Page
Description: Page that customers in good status land on after the login submission
*/
?>
<?php
	include(TEMPLATES_PATH.'customer.func.php'); 

	$application = getSession();
	
	$field_settings = getApplicationFields($application['application_id']);
	
	$fields_list = array('phone_home', 'phone_cell', 'phone_work', 'customer_email', 'ref_phone_1', 'ref_phone_2', 'ref_phone_3', 'ref_phone_4', 'ref_phone_5', 'ref_phone_6', 'street');
	$field_values = array('phone_home' => (empty($application['phone_home'])? FALSE: (substr($application['phone_home'],0,3).'-'.substr($application['phone_home'],3,3).'-'.substr($application['phone_home'],6))),
		'phone_cell' => (empty($application['phone_cell'])? FALSE: (substr($application['phone_cell'],0,3).'-'.substr($application['phone_cell'],3,3).'-'.substr($application['phone_cell'],6))),
		'phone_work' => (empty($application['phone_work'])? FALSE: (substr($application['phone_work'],0,3).'-'.substr($application['phone_work'],3,3).'-'.substr($application['phone_work'],6))),
		'customer_email' => strtolower($application['email']),
		'ref_phone_1' => (!isset($application['personal_reference'][0])? FALSE: (substr($application['personal_reference'][0]['phone_home'],0,3).'-'.substr($application['personal_reference'][0]['phone_home'],3,3).'-'.substr($application['personal_reference'][0]['phone_home'],6))),
		'ref_phone_2' => (!isset($application['personal_reference'][1])? FALSE: (substr($application['personal_reference'][1]['phone_home'],0,3).'-'.substr($application['personal_reference'][1]['phone_home'],3,3).'-'.substr($application['personal_reference'][1]['phone_home'],6))),
		'ref_phone_3' => (!isset($application['personal_reference'][2])? FALSE: (substr($application['personal_reference'][2]['phone_home'],0,3).'-'.substr($application['personal_reference'][2]['phone_home'],3,3).'-'.substr($application['personal_reference'][2]['phone_home'],6))),
		'ref_phone_4' => (!isset($application['personal_reference'][3])? FALSE: (substr($application['personal_reference'][3]['phone_home'],0,3).'-'.substr($application['personal_reference'][3]['phone_home'],3,3).'-'.substr($application['personal_reference'][3]['phone_home'],6))),
		'ref_phone_5' => (!isset($application['personal_reference'][4])? FALSE: (substr($application['personal_reference'][4]['phone_home'],0,3).'-'.substr($application['personal_reference'][4]['phone_home'],3,3).'-'.substr($application['personal_reference'][4]['phone_home'],6))),
		'ref_phone_6' => (!isset($application['personal_reference'][5])? FALSE: (substr($application['personal_reference'][5]['phone_home'],0,3).'-'.substr($application['personal_reference'][5]['phone_home'],3,3).'-'.substr($application['personal_reference'][5]['phone_home'],6))),
		'street' => str_replace(' ,',',',(ucwords(strtolower($application['street'].' '.$application['unit'].', '.$application['city'])).' '.$application['state'].' '.$application['zip_code']))
		);
	$field_labels = array('phone_home' => '[cmstext Home Phone]',
		'phone_cell' => '[cmstext Cell Phone]',
		'phone_work' => '[cmstext Work Phone]',
		'customer_email' => '[cmstext Email]',
		'ref_phone_1' => (!isset($application['personal_reference'][0])? '': (ucwords(strtolower($application['personal_reference'][0]['relationship'].', '.$application['personal_reference'][0]['name_full'])))),
		'ref_phone_2' => (!isset($application['personal_reference'][1])? '': (ucwords(strtolower($application['personal_reference'][1]['relationship'].', '.$application['personal_reference'][1]['name_full'])))),
		'ref_phone_3' => (!isset($application['personal_reference'][2])? '': (ucwords(strtolower($application['personal_reference'][2]['relationship'].', '.$application['personal_reference'][2]['name_full'])))),
		'ref_phone_4' => (!isset($application['personal_reference'][3])? '': (ucwords(strtolower($application['personal_reference'][3]['relationship'].', '.$application['personal_reference'][3]['name_full'])))),
		'ref_phone_5' => (!isset($application['personal_reference'][4])? '': (ucwords(strtolower($application['personal_reference'][4]['relationship'].', '.$application['personal_reference'][4]['name_full'])))),
		'ref_phone_6' => (!isset($application['personal_reference'][5])? '': (ucwords(strtolower($application['personal_reference'][5]['relationship'].', '.$application['personal_reference'][5]['name_full'])))),
		'street' => '[cmstext Mailing Address]'
		);
	$field_check = array('phone_home' => false,
		'phone_cell' => false,
		'phone_work' => false,
		'customer_email' => false,
		'ref_phone_1' => (!isset($application['personal_reference'][0])? false: (($application['personal_reference'][0]['contact_pref'] == 'do not contact')? true: false)),
		'ref_phone_2' => (!isset($application['personal_reference'][1])? false: (($application['personal_reference'][1]['contact_pref'] == 'do not contact')? true: false)),
		'ref_phone_3' => (!isset($application['personal_reference'][2])? false: (($application['personal_reference'][2]['contact_pref'] == 'do not contact')? true: false)),
		'ref_phone_4' => (!isset($application['personal_reference'][3])? false: (($application['personal_reference'][3]['contact_pref'] == 'do not contact')? true: false)),
		'ref_phone_5' => (!isset($application['personal_reference'][4])? false: (($application['personal_reference'][4]['contact_pref'] == 'do not contact')? true: false)),
		'ref_phone_6' => (!isset($application['personal_reference'][5])? false: (($application['personal_reference'][5]['contact_pref'] == 'do not contact')? true: false)),
		'street' => false
		);
	$attrib_list = array(1 => 'bad_info', 2 => 'do_not_contact', 3 => 'best_contact', 4 => 'do_not_market');
	$attrib_label = array(1 => '[cmstext Bad Information]', 2 => '[cmstext Do Not Contact]', 3 => '[cmstext Prefered Contact Method]', 4 => '[cmstext Do Not Market]');
	
	$left_heading = "<p>[cmstext How do you want us to contact you?]  &nbsp; &nbsp; [cmstext Here is your list of contact choices.]</p>\n";
	$left_column = "<div class='contact_area'>";
	$right_heading = "<div class='tabs'>\n";
	$right_column = "";
	$bad_info_text = "";
					
	$bad_info = false;
	$attrib_idx = 2;
	$bad_info_aray = array();
	$dnc_aray = array();
	$fields_string = "";
	$best_set = false;
	
	foreach($fields_list as $name){
		$class_one = "";
		if ($field_settings[$name]['bad_info']) {
			$bad_info = true;
			$class_one = 'status_bad';
			$attrib_idx = 1;
			$bad_info_aray[] = $name;
			$bad_info_text = "<div class='status_bad'>[cmstext you have bad info, contact customer support]</div>\n";
		}
		if ($field_settings[$name]['best_contact']) {
			$best_set = true;
		}
		if ($field_values[$name]) {
			$left_column .= "<div class='contact_list ".$class_one."'>".$field_labels[$name].":  ".$field_values[$name]."</div>\n";
			$fields_string .= "'".$name."',";
		}
	}

	$tab_idx_list = array();
	
	$style = "style='display:block'";
	while ($attrib_idx <= count($attrib_list)){
		
		$class_one = "";
		$tab_idx_list[] = $attrib_idx;
		$attrib = $attrib_list[$attrib_idx];
		$right_column .= "<div id='tab".$attrib_idx."' class='tabContent' ".$style.">\n<div class='common_area'></div><div class='right_sliver'>";
		$style = "";
		
		
		foreach($fields_list as $name){
			$disable = "";
			if ($field_values[$name]){
				$class_two = "";
				$checked = "";
				if ($field_settings[$name][$attrib]){
					$checked = "checked";
					if ($attrib == 'bad_info') {
						$class_one = 'status_bad';
						$class_two = 'status_bad';
						$disable = 'disabled';
					}
				}
				if (($attrib == 'bad_info') || (in_array($name,$bad_info_aray))) $disable = 'disabled';
				if ((in_array($attrib,array('best_contact', 'do_not_market'))) && (in_array($name,$dnc_aray))) $disable = 'disabled';
				if (($field_check[$name]) && (in_array($attrib,array('do_not_contact', 'do_not_market')))) $checked = "checked";
				if (($checked == "checked") && ($attrib == 'do_not_contact')) $dnc_aray[] = $name;
				if (($best_set) && ($attrib == 'best_contact') && ($checked != "checked")) $disable = 'disabled';
				$right_column .= "<div class='contact_check ".$class_two."'><input type='checkbox' class='".$class_two."' id='".$name."_".$attrib."' name='".$name."_".$attrib."' value='".$name."_".$attrib."' ".$disable." ".$checked."  onchange='checkBoxChecked(this,\"".$name."\",\"".$attrib."\")'/></div>\n";
			}
		}
		$right_heading .= "<span class='tab_head_".$attrib_idx." ".$class_one."' id='tab_head_".$attrib_idx."'><a class='tab ".$class_one."' onclick='tabs.show(\"tab".$attrib_idx."\")'>".$attrib_label[$attrib_idx]."</a></span>\n";
		$attrib_idx ++;
		$right_column .= "</div></div>\n";
	}
	$right_column .= "\n";
	$left_column .= "</div>\n";
	$right_heading .= "</div>\n";
	
	$tab_str = '';
	foreach ($tab_idx_list as $val){
		$tab_str .= "'tab".$val."',";
		$tab_hd_str .= "'tab_head_".$val."',";
	}
	$tab_str = substr($tab_str,0,-1);
	$tab_hd_str = substr($tab_hd_str,0,-1);
	$fields_string = substr($fields_string,0,-1);

?>
<?php include(TEMPLATES_PATH.'confirmation_header_html.php'); ?>
<div id="normal_page">
[cmsinclude Header]
	<div class="content">
		<div class="wrapper">
			<div class="home_bottom clearfix ">
				<br/>
				<h3>[cmstext Customer Page Placeholder]</h3>
				<h2 class="fs-title">[cmstext Customer Privacy Settings Page Placeholder]</h2>
				[cmstext Hello Placeholder] <?= ucwords(strtolower($application['name_first']." ".$application['name_last']));?>.<br/>
				[cmstext from this page change your privacy settings]
				<?=$right_heading;?>
				<?=$left_heading;?>
				<div id="common_stuff" class="left_column common_stuff">
					<?=$left_column;?>
					<br/>
				</div>

				<form name='customer_privacy' class='validate_form' id='application_form' method='post' action='/<?=getCurrentLanguage()?>/privacy_submit'>
					<?=$right_column;?>
					<div class="center_button">
						<button type='submit' name='submit' class='submit action-button center_side' value='Submit' >[cmstext Submit Placeholder]</button>
					</div>
				</form>
			</div>
			<?=$bad_info_text;?>
		</div>
	</div>
	<br/>&nbsp;
[cmsinclude Footer]
</div>
<table id="waiting_page_fbl"><tbody><tr><td id="waiting_page_td">
	<div class="waiting_page hide">
		<p class="waiting_text">[cmstext Please Wait]</p>
	</div>
</td></tr></tbody></table>

<!-- scripts in body are executed on document ready -->
<script type="text/javascript">
  
var tabs = (function () {

    // list the tab divs
    var tabs = [<?=$tab_str?>],
	tab_heads = [<?=$tab_hd_str?>],
        domTabs = [],
        domTabHds = [],
        commonStuff,
        obj,
        cldrn,
        child,
        currentPrefix,
        show,
        i,
        j;

    // Recursively iterate through node children and rename form elements
    function renameNodes(node) {
        var i;
        if (node.length) {
            for (i = 0; i < node.length; i += 1) {
                renameNodes(node[i]);
            }
        } else {
            // rename any form-related elements
            if (typeof node.form !== 'undefined') {
                node.id = currentPrefix + '_' + node.id;
                node.name = currentPrefix + '_' + node.name;

            // Assume that form elements do not have child form elements
            } else if (node.children) {
                renameNodes(node.children);
            }
        }
    }

    // Clone the common stuff dom element and prepend the tabId to the elements
    function getCommonStuff(tabId) {
        var commonClone = commonStuff.cloneNode(true);
        // hack for ie6/7
        if (!!document.all) {
            commonClone.innerHTML = commonStuff.innerHTML;
        }

        currentPrefix = tabId;
        renameNodes(commonClone);
        return commonClone;
    }

    show = function showTab(tab) {
        var i;
	var reg = new RegExp('(\\s|^)'+"selected"+'(\\s|$)');
	
        for (i = 0; i < domTabs.length; i += 1) {
            if (tabs[i] === tab) {
                domTabs[i].style.display = "block";
		domTabHds[i].className += " selected";
            } else {
                domTabs[i].style.display = "none";
                domTabHds[i].className = domTabHds[i].className.replace(reg,"");
            }
        }
    };

    // Let's keep a reference to the dom nodes so we don't have to fish
    for (i = 0; i < tabs.length; i += 1) {
        domTabs.push(document.getElementById(tabs[i]));
        domTabHds.push(document.getElementById(tab_heads[i]));
    }

    commonStuff = document.getElementById("common_stuff");

    // remove the common stuff from the form
    commonStuff.parentNode.removeChild(commonStuff);

    for (i = 0; i < domTabs.length; i += 1) {
        obj = domTabs[i];

        // Find the correct div
        cldrn = obj.childNodes;
        for (j = 0; j < cldrn.length; j += 1) {
            child = cldrn[j];
            if (child.className === "common_area") {
                // Copy the common content over to the tab
                child.appendChild(getCommonStuff(tabs[i]));
                break;
            }
        }
    }

    // show the first tab
    show(tabs[0]);

    return {
        show: show // return the show function
    };

}());

	function checkBoxChecked(elem,name,attrib){
		var checked = elem.checked;
		var fields = [<?=$fields_string;?>];
		if (checked) {
			switch (attrib){
				case 'do_not_contact':
					// first make sure that it isn't the last 'do not contact' contact
					var one_unchecked = false;
					for (i = 0; i < fields.length; i += 1) {
						if (!(document.getElementById(fields[i]+"_do_not_contact").checked) && !(document.getElementById(fields[i]+"_do_not_contact").disabled))  one_unchecked = true;
					}
					if (one_unchecked) {
						// since we do not contact, cant be preferred or marketed
						document.getElementById(name+"_do_not_market").checked = true;
						document.getElementById(name+"_best_contact").checked = false;
						checkBoxChecked(document.getElementById(name+"_best_contact"),name,'best_contact');
						document.getElementById(name+"_do_not_market").disabled = true;
						document.getElementById(name+"_best_contact").disabled = true;
					} else {
						elem.checked = false;
						alert('[cmstext At least one contact method must be good to contact you.]');
					}
					break;
				case 'do_not_market':
					break;
				case 'best_contact':
					// only one best contact allowed so disable the remaining
					for (i = 0; i < fields.length; i += 1) {
						if (fields[i] != name){
							document.getElementById(fields[i]+"_best_contact").checked = false;
							document.getElementById(fields[i]+"_best_contact").disabled = true;
						}
					}
					break;
			}
		} else {
			switch (attrib){
				case 'do_not_contact':
					// undisable the do not market and best contact (if no other are set)
					document.getElementById(name+"_do_not_market").disabled = false;
					var set_disable = true;
					for (i = 0; i < fields.length; i += 1) {
						if (document.getElementById(fields[i]+"_best_contact").checked && (name != fields[i])){
						set_disable = false;
						}
					}
					if (set_disable) {
						document.getElementById(name+"_best_contact").disabled = false;
						checkBoxChecked(document.getElementById(name+"_best_contact"),name,'best_contact');
					}
					break;
				case 'do_not_market':
					break;
				case 'best_contact':
					// since we are turning best contact off, un-disable those that can be selected
					for (i = 0; i < fields.length; i += 1) {
						if (document.getElementById(fields[i]+"_do_not_contact").checked || document.getElementById(fields[i]+"_bad_info").checked){
						// do nothering
						} else {
							document.getElementById(fields[i]+"_best_contact").disabled = false;
						}
					}
					break;
			}
		}
	}
</script><?php include(TEMPLATES_PATH.'footer_html.php');?>
