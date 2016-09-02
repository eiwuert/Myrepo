	var content;
	var cmsselect_arr = {};
	var editors = [];
	var content_title = {};
	var plugin = 'Gallery';
	
	function initPlugin()
	{		
		$("#edit_form").validationEngine({
			onValidationComplete: function(form, r)
			{
				if (r)
					lum_submitForm($("#edit_form"), handleResponse);
			}
		});
		
		if ($('#cnew').val() == '0')
		{
			updateSelects();
			lum_setupLocalization();
			$(':text').addClass('width300');
			$('.process_raw').click(processRaw);
			$('.delete_raw').click(purgeRaw);
			setupPhotos();
		}
		
		$('#thumbnail_size').click(function()
		{
			if ($('#thumb_0').hasClass('small_thumbs'))
				$('.photolistitem').removeClass('small_thumbs');
			else
				$('.photolistitem').addClass('small_thumbs');
		});
		
		$('#slideshow_options').click(function()
		{
			if ($('#thumb_0').hasClass('hide_slideshow'))
				$('.photolistitem').removeClass('hide_slideshow');
			else
				$('.photolistitem').addClass('hide_slideshow');
		});		
	}

	function processRaw()
	{
		lum_submitForm($("#raw_image_form"), handleProcessRawImages);
	}

	function purgeRaw()
	{
		jRpc.send(handleRawImages, {plugin: plugin, method: 'purgeRawImages', params: {folder: id}});
	}

	function handleProcessRawImages(o)
	{
		if (o.success == true)	
		{
			if (!checkForTimeout(o))
			{
				loadRawImages();
				loadThumbnails();	
				//makeEditable();
			}
		}
		else
		{
			$.jGrowl(o.errors, {sticky: true});
		}
	}

	function setupPhotos()
	{
		if ($("#uploadify").length > 0)
		{
			$('#uploadify').uploadify({
				'uploader'       : 'cms-plugins/Gallery/admin/images/uploadify.swf',
				'script'         : 'cms-plugins/Gallery/admin/uploadify.php',
				'cancelImg'      : 'cms-plugins/Gallery/admin/images/cancel.png',
				'multi'          : true,
				'auto'           : true,
				'fileExt'        : '*.jpg;*.gif;*.png',
				'fileDesc'       : 'Image Files (.JPG, .GIF, .PNG)',
				'queueSizeLimit' : 25,
				'simUploadLimit' : 3,
				'removeCompleted': false,
				'scriptData': {'id': id},
				'onError' : function (r) {
					if (r.indexOf('error|') > -1)
					{
						var temp = r.split('|');
						$.jGrowl(temp[1], {sticky: true});
						return false;
					}
					return true;
				},				
				'onAllComplete'  : function(event,data) {
				    loadRawImages();
				  }
			      });
			
			// set up our save buttons in the thumbnails sectio
			//$('#thumbnails .save_thumbs').click(savePhotos);
		}
			
		// load any raw images
		loadRawImages();
		
		// load our thumbnails
		loadThumbnails();					
	}
	
	function loadRawImages()
	{
		// id is a global var in edit.php
		jRpc.send(handleRawImages, {plugin: plugin, method: 'loadRawImages', params: {folder: id}});
	}		


	/**
	 * handleRawImages()
	 * The callback function used to render the Raw Images section
	 *  
	 **/		
	function handleRawImages(o)
	{
		if (o.success == true)	
		{
			if (!checkForTimeout(o))
			{
				$('#raw_images').html();
				$.jGrowl("Loading unprocessed images...");
				$('#raw_images').html(o.rows);
				makeCroppable();
			}
		}
		else
		{
			$.jGrowl(o.errors, {sticky: true});
		}
	}	
		

	function handleResponse(o)
	{
		if (o.success)
		{
			savePhotos();
			if ($("#cpreview").val() == 1)
			{
				window.open (BASE_URL+"/cms-htmlcache/preview.html","preview");
				return;
			}

			if ($("#cpreview").val() == 3)
			{			
				window.location.href = TOOLS_PATH+"/"+plugin+"/edit?gallery_id="+o.rows.gallery_id;
			}

			if ($("#cpreview").val() == 0)
			{			
				window.location.href = TOOLS_PATH+"/"+plugin;
			}
		}
		else
		{
			$.jGrowl(o.errors, {sticky: true});
		}
	}

	function doNext()
	{
		$("#cpreview").val(3);
	}

	function doSave()
	{
		$("#cpreview").val(0);
	}	

	function doSaveAndContinue()
	{
		$("#cpreview").val(2);
	}	
	
	
	function doPreview()
	{
		$("#cpreview").val(1);
		$("#edit_form").submit();
	}
	
	function makeCroppable()
	{
		$('#raw_images img.cropme').Jcrop({
			aspectRatio:aspect, // global var set in edit.php
			onChange: getCoords,
			onSelect: getCoords
		});
	}
	
	function getCoords(c,eid)
	{
		$('#'+eid+'_x').val(c.x);
		$('#'+eid+'_y').val(c.y);
		$('#'+eid+'_x2').val(c.x2);
		$('#'+eid+'_y2').val(c.y2);
		$('#'+eid+'_w').val(c.w);
		$('#'+eid+'_h').val(c.h);
	};
	
	/**
	 * loadThumbnails()
	 * Makes a call to the RPC loadThumbnails to fill in the Thumbnails section
	 *  
	 **/		
	function loadThumbnails()
	{
		var gallery_id = $("#gallery_id").val();
		jRpc.send(handleThumbnails, {plugin: plugin, method: 'loadThumbnails', params: {gallery_id: gallery_id}});
	}		
	
	/**
	 * handleThumbnails()
	 * The callback function used to render the Thumbnails section
	 *  
	 **/		
	function handleThumbnails(o)
	{
		if (o.success == true)	
		{
			if (!checkForTimeout(o))
			{
				$('#thumbnail_box').html();
				$.jGrowl("Showing thumbnails...");
				$('#thumbnail_box').html("<ul id='ulThumbnailList'>"+decodeURIComponent(o.rows[def_lang] + ''));

				$('#ulThumbnailList').append('<li style="clear: both; list-style-type: none;"></li></ul>');
				
				$('#localized_content').children().each(function()
				{
					var lang_code = $(this).attr('id');
		
					if (lang_code != def_lang)
					{					
						if ($($('#content-'+lang_code).children().get(1)).find('ul').length)
							$($('#content-'+lang_code).children().get(1)).find('ul').html('');
							
						var content = o.rows[def_lang];
						if (lum_isDefined(o.rows[lang_code]) && o.rows[lang_code] != '')
							content = o.rows[lang_code];
							
						$($('#content-'+lang_code).children().get(1)).append("<ul>"+decodeURIComponent(content + '')+"</ul>");
					}
				});
				
				 makeEditable();
			}
		}
		else
		{
			$.jGrowl(o.errors, {sticky: true});
		}
	}
	
	function makeEditable() {
	    $("#ulThumbnailList").sortable(
	        {  
	           opacity: 0.7, 
	           revert: true, 
	           scroll: true ,
	           handle: $(".imagecontainer")
	        });                                      
 
	    $(".deletethumbnail").bind("click",deletePhoto);                   
	                                                                          
	}
	
	function savePhotos()
	{
		var photos = {};

		photos[def_lang] = [];
	
		// let's get the default language first
		$("#ulThumbnailList .photolistitem").each(function() {
			photos[def_lang].push(getPhoto(this));
		});

		// now other languages
		$('#localized_content').children().each(function(){

			var lang_code = $(this).attr('id');
			if (lang_code != def_lang)
			{				
				photos[lang_code] = [];
				$("#content-"+lang_code+" .photolistitem").each(function() {
					photos[lang_code].push(getPhoto(this));
				});
			}
		});
	
		// do ajax stuff here	
		var gallery_id = $("#gallery_id").val();
		jRpc.send(handleThumbnails, {plugin: plugin, method: 'savePhotos', params: {gallery_id: gallery_id, photos: photos}});
	}
	
	function getPhoto(obj)
	{
		var photo = {};
		photo.id = $(obj).attr('id');
		photo.url = $(obj).find('img').attr('title');
		photo.title = encodeURI( $(obj).find('.edit_title').val());
		photo.caption = encodeURI( $(obj).find('.edit_caption').val());
		photo.link = encodeURI( $(obj).find('.edit_link').val());
		return photo;
	}

	function deletePhoto()
	{
		var file = $(this).attr('title');

		var buttons = {
				"Delete": function()
					{
						$( this ).dialog( "close" );
						$.jGrowl("Deleting...");
						$('.photolistitem').each(function(){
							if ($(this).find('img').attr('title') == file)
							{
								$(this).remove();
							}
						});
					},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
	
		lum_confirm("Delete this?", "Are you sure you want to delete this photo?", buttons);
	}	
