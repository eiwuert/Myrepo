<?php
if (lum_requirePermission('Gallery\Edit', false) || lum_requirePermission('Gallery\Edit Photos', false)) :

	list($langs, $count) = lum_call('Languages', 'getList', array('status'=>1, 'sort'=>'def desc, language', 'dir'=>'asc'));
	$def_lang = lum_call('Languages', 'getDefaultLanguage');
	
	$obj = null;
	$render_form = true;
	$new = 1;
	
	$_SESSION['upload_folder'] = com_create_guid();
	
	$localized = false;
	$parents = array();
	
	if (isset($_GET['gallery_id']))
	{
		$new = 0;
		$obj = lum_call('Gallery', 'get', array('gallery_id'=>$_GET['gallery_id']));
		$localized = lum_call('Gallery', 'getLocalized',  array('gallery_id'=>$_GET['gallery_id']));

		if (!$obj)
		{
			echo "<p>The gallery could not be found in the database.</p>";
			$render_form = false;
		}
		else
		{
			$_SESSION['upload_folder'] = $_GET['gallery_id'];
		}
	
	}

	if ($render_form) :
	
?>
<?php if (lum_requirePermission('Gallery\Edit Photos', false) && !lum_requirePermission('Gallery\Edit', false)) : ?>
<style type="text/css" media="all">
.photolistitem fieldset {
	display: none;
}
</style>
<?php endif; ?>
<script type="text/javascript" src="../../../cms-admin/tiny_mce/jquery.tinymce.js"></script>
<div id="plugin-header">
	<script type="text/javascript">
		var id = '<?php echo $_SESSION['upload_folder'];?>';
		var def_lang = '<?=$def_lang?>';
		<?php if ($obj) : ?>
		var aspect = <?php
			$aspect = round($obj['width_large'] / $obj['height_large'], 2);
			echo $aspect;
		?>;
		<?php endif; ?>
	</script>	
	<h1><?php echo ($new ? 'Add a Gallery' : 'Edit Gallery');?></h1>
	<form id="edit_form" name="edit_form" method="post" action="">
		<input type="hidden" name="plugin" value="Gallery"/>
		<input type="hidden" name="method" value="update"/>
		<input type="hidden" name="new" id="cnew" value="<?php echo $new; ?>"/>
        	<input type="hidden" id="cpreview" name="preview" value="0"/>
           	<input type="hidden" id="gallery_id" name="gallery_id" value="<?=$_GET['gallery_id']?>"/>
		<input type="hidden" name="lang_code" value="<?=$def_lang?>"/>
		<div id="tabs">
			<?php if (!$new) :?>
			<ul>
				<?php
					foreach ($langs as $lang)
					{
						echo '<li><a href="#tabs-'.$lang['lang_code'].'">'.$lang['language'].'</a></li>';
					}
				?>
			</ul>
			<?php endif; ?>
				<!--
						*** IMPORTANT ***
						for localized plugins you must have this exact html structure. Row, td with the label, and td with the editable field.
						jquery is used to find localized fields and insert them into the various tabs for each language. It depends on his
						html structure to do that.
				-->
			
			<div id="tabs-<?php echo $def_lang; ?>">
				<fieldset>
					<legend class="collapsible">Gallery Details</legend>
					<table class="data_details">
						<?php if (!lum_requirePermission('Gallery\Edit', false)) : ?>
						<tr>
							<td>
								Name
							</td>
							<td>
								<?php echo ($obj ? $obj['name'] : '');?>
							</td>
						</tr>
						<input id="cname" name="name" type="hidden" value="<?php echo ($obj ? $obj['name'] : '');?>"/>
						<input id="cwidth_large" name="width_large" type="hidden" value="<?php echo ($obj ? $obj['width_large'] : GalleryDefines::WIDTH_LARGE);?>"/>
						<input id="cheight_large" name="height_large" type="hidden" minlength="2" value="<?php echo ($obj ? $obj['height_large'] : GalleryDefines::HEIGHT_LARGE);?>"/>
						<input id="cwidth_large" name="width_thumb" type="hidden" minlength="1" value="<?php echo ($obj ? $obj['width_thumb'] : GalleryDefines::WIDTH_THUMB);?>"/>
						<input id="cheight_thumb" name="height_thumb" type="hidden" minlength="1" value="<?php echo ($obj ? $obj['height_thumb'] : GalleryDefines::HEIGHT_THUMB);?>"/>
						<?php else : ?>
						<tr>
							<td>
								Name
							</td>
							<td>
								<input id="cname" name="name" class="validate[required] text-input" minlength="3" value="<?php echo ($obj ? $obj['name'] : '');?>"/>
							</td>
						</tr>
						<tr>
							<td>
								Large Photo Width
							</td>
							<td>
								<input id="cwidth_large" name="width_large" class="validate[required] text-input" minlength="2" value="<?php echo ($obj ? $obj['width_large'] : GalleryDefines::WIDTH_LARGE);?>"/>
							</td>
						</tr>						
						<tr>
							<td>
								Large Photo Height
							</td>
							<td>
								<input id="cheight_large" name="height_large" class="validate[required] text-input" minlength="2" value="<?php echo ($obj ? $obj['height_large'] : GalleryDefines::HEIGHT_LARGE);?>"/>
							</td>
						</tr>
						<tr>
							<td>
								Thumb Photo Width
							</td>
							<td>
								<input id="cwidth_large" name="width_thumb" class="validate[required] text-input" minlength="1" value="<?php echo ($obj ? $obj['width_thumb'] : GalleryDefines::WIDTH_THUMB);?>"/>
							</td>
						</tr>						
						<tr>
							<td>
								Thumb Photo Height
							</td>
							<td>
								<input id="cheight_thumb" name="height_thumb" class="validate[required] text-input" minlength="1" value="<?php echo ($obj ? $obj['height_thumb'] : GalleryDefines::HEIGHT_THUMB);?>"/>
							</td>
						</tr>
						<?php endif; ?>
						<tr class="localize">
							<td>&nbsp;</td>
							<td>
								<input type="hidden" id="cphotos" name="photos" class="photos" value=""/>
							</td>
						</tr>
						<?php if ($new) :?>
						<tr>
							<td>&nbsp;</td>
							<td>
								<input class="submit" type="submit" value="&raquo;Next" onclick="doNext();"/>
							</td>
						</tr>
						<?php else: ?>
						<tr>
							<td colspan="2">
								<input class="submit" type="submit" value="Save and Close" onclick="doSave();"/>
								<?php if (!$new) : ?>
								<input class="submit" type="submit" value="Save and Continue" onclick="doSaveAndContinue();"/>
								<?php endif; ?>					
								<input type="button" value="Cancel" onclick="window.location.href = TOOLS_PATH+'/Gallery/list';"/>
							</td>
						</tr>						
						<?php endif; ?>
					</table>
				</fieldset>
				<?php if (!$new) :?>
				<br/>
				<fieldset id="thumbnails">
					<legend>Thumbnails</legend>
					<p>Instructions</p>
					<ul>
						<li><b>Changing Order</b><br/>
						To change the order of images drag and drop them wherever they need to go. Use the image as a handle for dragging.
						</li>
						<li><b>Editing Title, Captions & Link</b><br/>
						To change or add a title, caption or link simply click in the necessary box, make your changes and Save. To makes changes for other languages, click on the language tab at the top of this page then Save on the default language tab.
						</li>
						<li><b>Delete a Photo</b><br/>
						Click the 'Delete' link beneath a photo. Make sure to save your changes after deleting a photo.
						</li>
						<li><b>Saving Changes</b><br/>Click on 'Save & Close' or 'Save & Continue' when you're done changing things</li>
					</ul>
					<p><input type="button" value="Toggle Thumbnail Size" id="thumbnail_size"/></p>
					<?php if (lum_requirePermission('Gallery\Edit', false)) : ?>
					<p><input type="button" value="Toggle Slideshow Options" id="slideshow_options"/></p>
					<?php endif; ?>
					<!-- We double div to fix an IE problem with sorting and jQuery -->
					<div id="divPhotoList">
						<div id="thumbnail_box">
						</div>
					</div>
				</fieldset>				
				</form>				
				<br/>
				
				<fieldset>
					<legend>Add Images</legend>
					<p>To add more images to the gallery click on the Browse button below and select files to upload.</p>
					<div id="fileQueue"></div>
					<input type="file" name="uploadify" id="uploadify" />
					<p><a href="javascript:jQuery('#uploadify').uploadifyClearQueue()">Cancel All Uploads</a></p>
				</fieldset>
				<br/>				
				<fieldset>
					<legend class="collapsible">Unprocessed Photos</legend>
					<form id="raw_image_form" method="POST" action="">
					<input type="hidden" name="plugin" value="Gallery"/>
					<input type="hidden" name="folder" id="folder" value="<?php echo $_SESSION['upload_folder'];?>"/>
					<input type="hidden" name="method" value="processRawPhotos"/> 
					<input type="button" value="Process Photos" class="process_raw"/>
					<p>Any images listed below need to be resized. If you want to crop to a certain part of the image drag a box using your mouse over the area you want to keep. After you're done click on 'Process Raw Images' and all of the necessary files will be created automatically.</p>
					<p>Note: If you do not set a crop area the center of the image will be used.</p>
					<div id="raw_images">
					</div>
					<input type="button" value="Process Photos" class="process_raw"/> <input type="button" value="Delete ALL Unprocessed Photos" class="delete_raw"/>
					</form> 
				</fieldset>			

				<?php endif; ?>
			</div>
		</div>
	  
	<script>
		function updateSelects()
		{
		}
	</script>	
</div>
<?php echo lum_embedLocalizedContentForEdit($localized, $langs); ?>
<?php
	endif;
endif;
?>
