<?php
if (lum_requirePermission('Strings\Edit')) :

	list($langs, $count) = lum_call('Languages', 'getList', array('status'=>1, 'sort'=>'def desc, language', 'dir'=>'asc'));
	$def_lang = lum_call('Languages', 'getDefaultLanguage');
	
	$obj = null;
	$render_form = true;
	$new = 1;

	if (isset($_GET['string_id']))
	{
		$new = 0;
		$obj = lum_call('Strings', 'get', array('string_id'=>$_GET['string_id']));
		$localized = lum_call('Strings', 'getLocalized',  array('string_id'=>$_GET['string_id']));
		
		if (!$obj)
		{
			echo "<p>The string could not be found in the database.</p>";
			$render_form = false;
		}
		else
		{
			$obj['string_code'] = str_replace('[', '&#091;', $obj['string_code']);
			$obj['string_code'] = str_replace(']', '&#093;', $obj['string_code']);
		}
	}

	if ($render_form) :
?>
<div id="plugin-header">
	<script type="text/javascript">
		var def_lang = '<?=$def_lang?>';
	</script>
	<h1><?php echo ($new ? 'Add a String' : 'Edit String');?></h1>
	<form id="edit_form" name="edit_form" method="post" action="" class="cmxform" >
		<input type="hidden" name="plugin" value="Strings"/>
		<input type="hidden" name="method" value="update"/>
		<input type="hidden" name="new" value="<?php echo $new; ?>"/>
		<input type="hidden" id="string_id" name="string_id" value="<?php echo (!$obj ? '' : $obj['string_id']); ?>"/>
		<input type="hidden" name="lang_code" value="<?=$def_lang?>"/>
		<div id="tabs">
			<ul>
				<?php
					foreach ($langs as $lang)
					{
						echo '<li><a href="#tabs-'.$lang['lang_code'].'">'.$lang['language'].'</a></li>';
					}
				?>
			</ul>
			<div id="tabs-<?php echo $def_lang; ?>">
			<fieldset>
				<!--
						*** IMPORTANT ***
						for localized plugins you must have this exact html structure. Row, td with the label, and td with the editable field.
						jquery is used to find localized fields and insert them into the various tabs for each language. It depends on his
						html structure to do that.
				-->
				<legend>String Details</legend>
				<table>
					<tr>
						<td>
							String Code	
						</td>
						<td>
							<input id="cstring_code" name="string_code" class="validate[required] text-input" minlength="3" value="<?php echo (!$obj ? '' : $obj['string_code']); ?>" style="width: 300px;"/>
						</td>
					</tr>
					<tr class="localize">
						<td>
							Text
						</td>
						<td>
							<input id="ctext" name="text" class="validate[required] text-input" value="<?php echo lum_getDefaultLanguageValue($localized, 'text', $def_lang);?>" style="width: 300px;"/>
						</td>
					</tr>
				</table>
			</fieldset>
			</div>
		</div>
		<input class="submit" type="submit" value="Save String"/>
		<input type="button" value="Cancel" onclick="window.location.href = TOOLS_PATH+'/Strings/list';"/>

	  </form>
</div>
<?php echo lum_embedLocalizedContentForEdit($localized, $langs); ?>
<?php
	endif;
endif;
?>
