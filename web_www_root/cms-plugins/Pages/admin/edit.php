<?php
if (lum_requirePermission('Pages\Edit')) :

	list($langs, $count) = lum_call('Languages', 'getList', array('status'=>1, 'sort'=>'def desc, language', 'dir'=>'asc'));
	$def_lang = lum_call('Languages', 'getDefaultLanguage');
	
	$obj = null;
	$render_form = true;
	$new = 1;
	$localized = false;
	$parents = array();
	
	if (isset($_GET['page_id']))
	{
		$new = 0;
		$obj = lum_call('Pages', 'get', array('page_id'=>$_GET['page_id']));
		$localized = lum_call('Pages', 'getLocalized',  array('page_id'=>$_GET['page_id']));

		if (!$obj)
		{
			echo "<p>The page could not be found in the database.</p>";
			$render_form = false;
		}
	}

	list($parents, $count) = lum_call('Pages', 'getNavList', array("show_on_menu"=>'all'));
	if ($count == WEB_SERVICE_ERROR)
	{
		echo $parents;
		$render_form = false;
	}

	list($templates, $count) = lum_call('Pages', 'getTemplateList');
	if ($count == WEB_SERVICE_ERROR)
	{
		echo $templates;
		$render_form = false;
	}

	if ($render_form) :
	
	$_SESSION['mc_rootpath'] = lum_getSitePath()."userfiles/images";
	//$_SESSION['mc_rootpath'] = IMAGE_PATH;
	
?>
<script type="text/javascript" src="../../../cms-admin/tiny_mce/plugins/imagemanager/js/mcimagemanager.js"></script>
<script type="text/javascript" src="../../../cms-admin/tiny_mce/jquery.tinymce.js"></script>
<div id="plugin-header">
	<script type="text/javascript">
		var def_lang = '<?=$def_lang?>';
		var BASE_URL = '<?=BASE_URL?>';
		var REAL_BASE_URL = '<?=BASE_URL?>';
		var SSL_BASE_URL = '<?=SSL_BASE_URL?>';
	</script>	
	<h1><?php echo ($new ? 'Add a Page' : 'Edit Page');?></h1>
	<form id="edit_form" name="edit_form" method="post" action="">
		<input type="hidden" name="plugin" value="Pages"/>
		<input type="hidden" name="method" value="update"/>
		<input type="hidden" name="new" value="<?php echo $new; ?>"/>
		<input type="hidden" id="page_id" name="page_id" value="<?php echo (!$obj ? '' : $obj['page_id']); ?>"/>
        	<input type="hidden" id="cpreview" name="preview" value="0"/>
           	<input type="hidden" id="template_old" value="<?php echo (!$obj ? 'content.php' : $obj['template']); ?>"/>
		<input type="hidden" name="lang_code" value="<?=$def_lang?>"/>
		<input class="submit" type="submit" value="Save and Close" onclick="doSave();"/>
		<?php if (!$new) : ?>
		<input class="submit" type="submit" value="Save and Continue" onclick="doSaveAndContinue();"/>
		<?php endif; ?>					
		<input type="button" value="Preview" onclick="doPreview();"/>						
		<input type="button" value="Cancel" onclick="window.location.href = TOOLS_PATH+'/Pages/list';"/>
		<div id="tabs">
			<ul>
				<?php
					foreach ($langs as $lang)
					{
						echo '<li><a href="#tabs-'.$lang['lang_code'].'">'.$lang['language'].'</a></li>';
					}
				?>
			</ul>
			
				<!--
						*** IMPORTANT ***
						for localized plugins you must have this exact html structure. Row, td with the label, and td with the editable field.
						jquery is used to find localized fields and insert them into the various tabs for each language. It depends on his
						html structure to do that.
				-->
			
			<div id="tabs-<?php echo $def_lang; ?>">
				<fieldset>
					<legend class="collapsible">Page Details</legend>
					<table class="data_details">
						<?php if ((isset($_GET['page_id'])) && ($_GET['page_id'] == 1)) : ?>
						<input type="hidden" id="cparent_id" name="parent_id" value="0"/>
						<input type="hidden" id="ctemplate" name="template" value="home.php"/>
						<input type="hidden" id="cstatus" name="status" value="1"/>
						<input type="hidden" id="copen_new_window" name="open_new_window" value="0"/>
						<input type="hidden" id="cis_include" name="is_include" value="0"/>
						<input type="hidden" id="cexternal_url" name="external_url" value="0"/>
						<?php else :?>						
						<input type="hidden" id="cshow_parent_in_url" name="show_parent_in_url" value="0"/>
						<tr>
							<td>
								Active?
							</td>
							<td>
								<select id="cstatus" name="status" class="validate[required]">
									<option value="0">No</option>
									<option value="1">Yes</option>
								</select><br/> <a href="javascript:lum_alert('What\'s This?','Select yes if you want the page to be publicly available on the web site');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<?php
							$targets = unserialize(TARGETS);
							if (count($targets) == 1) :
							
								echo '<input type="hidden" id="cdisplay_targets" name="display_targets[]" value="'.$targets[0].'">';
								
								
							else :
						?>
						<tr>
							<td>
								Display Targets
							</td>
							<td>
								Select a display target to use for this page. Choosing one target will use the same content on all targets or select more than one to have different content for each target. By default the first display target is selected.<br/><br/>			
								<?php
								
									$targets = unserialize(TARGETS);
									$saved_targets = array();
									
									if ($obj)
										$saved_targets = explode(',', $obj['display_targets']);
										
									echo '<input type="checkbox" id="all_display_targets" name="all_display_targets"> All (this will include content areas from all display targets)<hr/>';
									
									$c = 0;
									foreach ($targets as $target)
									{
										if ($obj)
										{
											$checked = '';
											if (in_array($target, $saved_targets))
												$checked = 'checked="checked"';
										}
										else
										{
											if ($c == 0)
												$checked = 'checked="checked"';
											else
												$checked = '';
										}
										
										echo '<input type="checkbox" id="cdisplay_targets" name="display_targets[]" value="'.$target.'"'.$checked.' class="validate[required] display_target"> '.constant($target).'<br/>';
										$c++;
									}
								?>
								<input type="button" name="update_targets" class="update_targets" value="Update Content Area"/><br/><a href="javascript:lum_alert('What\'s This?','If you want to use different content for each target, check the ones you want. Otherwise simply choose one display to target and all targets will use the same content');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td>
								Template
							</td>
							<td>
								<!-- templates are loaded through AJAX because they depend on the display target selections -->
								<select id="ctemplate" name="template" class="validate[required]">
									<?php echo lum_buildSelectOptions($templates, 'file', 'name'); ?>
								</select>
								<br/><a href="javascript:lum_alert('What\'s This?','Select which template should be used to display the page\'s content');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<tr>
							<td>
								Parent
							</td>
							<td>
								<select id="cparent_id" name="parent_id" class="validate[required]">
									<option value="1">Home</option>						
									<?php echo lum_buildSelectOptions($parents, 'page_id', 'name'); ?>
								</select><br/> <a href="javascript:lum_alert('What\'s This?','If this page belongs underneath a specific section of the site choose the page it belongs to');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<?php endif; ?>
						<tr class="localize">
							<td>
								Page Name
							</td>
							<td>
								<input id="cname" name="name" class="validate[required] text-input" title="Page Name" minlength="3" value="<?php echo (!$localized ? '' : lum_getDefaultLanguageValue($localized, 'name', $def_lang));?>"/>
								<br/><a href="javascript:lum_alert('What\'s This?','The name of the page. This will be used on the navigvation menu of the public site and possibly in the page title of the web browser - depending on the search engine optimization settings.');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<tr>
							<td>
								URL Name <input type="button" name="bulid_it" value="Suggest" onclick="buildUrlName();"/>
							</td>
							<td>
								<input id="cseo_name" name="seo_name" class="validate[required] text-input" value="<?php echo (!$obj ? '' : $obj['seo_name']); ?>"/>
								<br/><a href="javascript:lum_alert('What\'s This?','The URL Name is the address by which the page will be accessible.For Example:<br/><br/>For your page to be accessible like this: http://www.yourdomain.com/my-page<br/><br/>You would enter a URL name of \'my-page\'. Use all lower case letters and dashses \'-\' instead of spaces.');" class="whats_this">What's this?</a>
							</td>
						</tr>							
						<?php if ((isset($_GET['page_id'])) && ($_GET['page_id'] != 1)) : ?>
						<tr>
							<td>
								External URL (Optional)
							</td>
							<td>
								<input id="cexternal_url" name="external_url" value="<?php echo (!$obj ? '' : $obj['external_url']); ?>"/>
								<br/><a href="javascript:lum_alert('What\'s This?','If this page is really just a link on the navigation menu meant to send the user to an outside URL, enter the full URL here.');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<tr>
							<td>
								Open in New Window?
							</td>
							<td>
								<select id="copen_new_window" name="open_new_window" class="validate[required]">
									<option value="0">No</option>
									<option value="1">Yes</option>
								</select><br/> <a href="javascript:lum_alert('What\'s This?','Select yes if you want the page to open in a new window when clicked from the navigation menu');" class="whats_this">What's this?</a>
							</td>
						</tr>						
						<?php endif; ?>
						<tr>
							<td>
								Show on Menu?
							</td>
							<td>
								<select id="cshow_on_menu" name="show_on_menu" class="validate[required]">
									<option value="0">No</option>
									<option value="1">Yes</option>
								</select>
								<br/><a href="javascript:lum_alert('What\'s This?','Should show up on the navigation menu or not?');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<tr>
							<td>
								Clickable on Menu?
							</td>
							<td>
								<select id="cmenu_click" name="menu_click" class="validate[required]">
									<option value="0">No</option>
									<option value="1">Yes</option>
								</select>
								<br/><a href="javascript:lum_alert('What\'s This?','You can create navigation menu pages that are simply parents of others pages. This option makes it possible to list a menu item without actually having any content and therefore prevents people from clicking it on the menu.');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<tr>
							<td>
								Menu CSS Class (Optional)
							</td>
							<td>
								<input id="cmenu_class" name="menu_class" value="<?php echo (!$obj ? '' : $obj['menu_class']); ?>"/>
								<br/><a href="javascript:lum_alert('What\'s This?','This will apply the specified class to the menu item.');" class="whats_this">What's this?</a>
							</td>
						</tr>						
					</table>
				</fieldset>
				<br/>
				<fieldset>
					<legend class="collapsible collapsed">Advanced Details</legend>
					<table class="data_details">
						<?php if ((isset($_GET['page_id'])) && ($_GET['page_id'] != 1)) : ?>
						<tr>
							<td>
								Is an Include?	
							</td>
							<td>
								<select id="cis_include" name="is_include" class="validate[required]">
									<option value="0">No</option>
									<option value="1">Yes</option>
								</select><br/><a href="javascript:lum_alert('What\'s This?','Select yes only if this page will be included in other pages. If set to Yes, this page will not be accessible by it\'s URL Name.');" class="whats_this">What's this?</a>
							</td>
						</tr>
						
						<tr>
							<td>
								Show parent in URL?
							</td>
							<td>
								<select id="cshow_parent_in_url" name="show_parent_in_url" class="validate[required]">
									<option value="0">No</option>
									<option value="1">Yes</option>
								</select><br/><a href="javascript:lum_alert('What\'s This?','Select yes if you want the parent page\'s URL name to appear as part of the url for this page');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td>
								Secured?
							</td>
							<td>
								<select id="csecured" name="secured" class="validate[required]">
								<option value="0">No</option>
								<option value="1">Yes</option>
								</select><br/><a href="javascript:lum_alert('What\'s This?','Select yes if you want the page to be shows as a secured HTTPS (SSL) page');" class="whats_this">What's this?</a>
							</td>
						</tr>					
						<tr>
							<td>
								Admin Users Only?
							</td>
							<td>
								<select id="cadmin_only" name="admin_only" class="validate[required]">
								<option value="0">No</option>
								<option value="1">Yes</option>
								</select><br/><a href="javascript:lum_alert('What\'s This?','Select yes if you want the page to be visible to admin users only. Anyone is not signed in as an admin user will be redirected to the home page.');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<tr>
							<td>
								Cache Page?
							</td>
							<td>
								<select id="ccache_page" name="cache_page" class="validate[required]">
								<option value="0">No</option>
								<option value="1">Yes</option>
								</select><br/><a href="javascript:lum_alert('What\'s This?','If set to no, this page will not be cached and will always be dynamically created. Use this option for pages that have specific user content on them.');" class="whats_this">What's this?</a>
							</td>
						</tr>

					</table>
				</fieldset>
				<br/>
				<fieldset>
					<legend class="collapsible collapsed">Search Engine Optimization</legend>
					<table>
		
						<tr class="localize">
							<td>
								Meta Title (Optional)
							</td>
							<td>
								<input id="cmeta_title" name="meta_title" class="validate[required] text-input" title="Meta Title"  value="<?php echo (!$localized ? '&#091;PAGE_TITLE] | &#091;META_TITLE]' : lum_getDefaultLanguageValue($localized, 'meta_title', $def_lang));?>"/>
								<br/><a href="javascript:lum_alert('What\'s This?','The meta title:<br/><br/>-Should provide an Introductional Phrase about what this page is about<br/>-Should contain less than 80 characters<br/>-Should reuse important keywords that are used also in the content on this page<br/>-Should describe what this page is about<br/>-This will show up top left corner of all browsers and in google results');" class="whats_this">What's this?</a> (<span id="meta_title_count"></span> Max 70)
							</td>
						</tr>
						<tr class="localize">
							<td>
								Meta Description (Optional)
							</td>
							<td>
								<input id="cmeta_description" name="meta_description" class="validate[required] text-input" title="Meta Description"  value="<?php echo (!$localized ? '&#091;META_DESCRIPTION]' : lum_getDefaultLanguageValue($localized, 'meta_description', $def_lang));?>"/>
								<br/><a href="javascript:lum_alert('What\'s This?','The meta description should:<br/><br/>-Be a Full Sentence with a Call to Action in it.<br/>\-Be very specific to the page you are working on.<br/>-Contain less than 250 characters<br/>-Reuse important keywords that are used also in the content on this page<br/>');" class="whats_this">What's this?</a> (<span id="meta_description_count"></span> Max 150)
							</td>
						</tr>				
						<tr class="localize">
							<td>
								Meta Keywords (Optional)
							</td>
							<td>
								<input id="cmeta_keywords" name="meta_keywords" class="validate[required] text-input" title="Meta Keywords" value="<?php echo (!$localized ? '&#091;META_KEYWORDS]' : lum_getDefaultLanguageValue($localized, 'meta_keywords', $def_lang));?>"/>
								<br/><a href="javascript:lum_alert('What\'s This?','Some rules of thumb for meta keywords are:<br/><br/>-Use Lower Case individual words or phrases<br/>-After each word or phrase put a comma<br/>-Words should be similar to content on the page<br/>');" class="whats_this">What's this?</a> <span id="meta_keywords_count"></span>
							</td>
						</tr>
						<tr>
							<td>
								Change Frequency (Optional)
							</td>
							<td>
								<select id="cchangefreq" name="changefreq" class="validate[required]">
								<option value="always">Always</option>
								<option value="hourly">Hourly</option>
								<option value="daily">Daily</option>
								<option value="weekly">Weekly</option>
								<option value="monthly">Monthly</option>
								<option value="yearly">Yearly</option>
								<option value="never">Never</option>
								</select><br/><a href="javascript:lum_alert('What\'s This?','The Change Frequency to be set in the sitemap file. Provides a hint about how frequently the page is likely to change.');" class="whats_this">What's this?</a>
							</td>
						</tr>
						<tr>
							<td>
								Priority (Optional)
							</td>
							<td>
								<select id="cpriority" name="priority" class="validate[required]">
								<option value="1.0">1.0 Extremely Important</option>
								<option value="0.9">0.9</option>
								<option value="0.8">0.8</option>
								<option value="0.7">0.7</option>
								<option value="0.6">0.6</option>
								<option value="0.5">0.5</option>
								<option value="0.4">0.4</option>
								<option value="0.3">0.3</option>
								<option value="0.2">0.2</option>
								<option value="0.1">0.1 Not Important</option>
								</select><br/><a href="javascript:lum_alert('What\'s This?','The Priority to be set in the sitemap file. Describes the priority of a page relative to all the other pages on the site.');" class="whats_this">What's this?</a>
							</td>
						</tr>				
					</table>
				</fieldset>
				<br/>
				<fieldset>
					<legend class="collapsible">Content</legend>
					<div id="content_area_<?php echo $def_lang; ?>">
					</div>
				</fieldset>
			</div>
		</div>
		<input class="submit" type="submit" value="Save and Close" onclick="doSave();"/>
		<?php if (!$new) : ?>
		<input class="submit" type="submit" value="Save and Continue" onclick="doSaveAndContinue();"/>
		<?php endif; ?>					
		<input type="button" value="Preview" onclick="doPreview();"/>						
		<input type="button" value="Cancel" onclick="window.location.href = TOOLS_PATH+'/Pages/list';"/>
	</form>
	<script>
		function updateSelects()
		{
			$("#cshow_parent_in_url option[value='<?php echo (!$obj ? '1' : $obj['show_parent_in_url']); ?>']").attr('selected', 'selected');
			$("#cis_include option[value='<?php echo (!$obj ? '0' : $obj['is_include']); ?>']").attr('selected', 'selected');
			$("#cadmin_only option[value='<?php echo (!$obj ? '0' : $obj['admin_only']); ?>']").attr('selected', 'selected');
			$("#cstatus option[value='<?php echo (!$obj ? '1' : $obj['status']); ?>']").attr('selected', 'selected');
			$("#cshow_on_menu option[value='<?php echo (!$obj ? '0' : $obj['show_on_menu']); ?>']").attr('selected', 'selected');
			$("#cmenu_click option[value='<?php echo (!$obj ? '1' : $obj['menu_click']); ?>']").attr('selected', 'selected');
			$("#csecured option[value='<?php echo (!$obj ? '0' : $obj['secured']); ?>']").attr('selected', 'selected');
			$("#ctemplate option[value='<?php echo (!$obj ? 'content.php' : $obj['template']); ?>']").attr('selected', 'selected');
			$("#cparent_id option[value='<?php echo (!$obj ? '0' : $obj['parent_id']); ?>']").attr('selected', 'selected');
			$("#cchangefreq option[value='<?php echo (!$obj ? 'weekly' : $obj['changefreq']); ?>']").attr('selected', 'selected');
			$("#cpriority option[value='<?php echo (!$obj ? '0.5' : isset($obj['priority']) ? $obj['priority'] : '0.5'); ?>']").attr('selected', 'selected');
			$("#ccache_page option[value='<?php echo (!$obj ? '1' : $obj['cache_page']); ?>']").attr('selected', 'selected');
			
			loadContent(); //loadTemplate();
		}
	</script>	
</div>
<?php echo lum_embedLocalizedContentForEdit($localized, $langs); ?>
<?php
	endif;
endif;
?>
