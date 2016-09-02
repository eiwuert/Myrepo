	var content;
	var cmsselect_arr = {};
	var editors = [];
	var content_title = {};
	var plugin = 'Pages';
	
	function initPlugin()
	{		
		$("#edit_form").validationEngine({
			onValidationComplete: function(form, r)
			{
				if (r)
					lum_submitForm($("#edit_form"), handleResponse);
			}
		});
		
		updateSelects();
		
		$('.update_targets').click(confirmLoadContent);
		
		$('#all_display_targets').click(function()
		{
			checked = $(this).attr('checked');
			$('.display_target').each(function()
			{
				$(this).attr('checked', checked);					
			});
		});
		
		$('#cmeta_title').keyup(function(){
			var chars = $('#cmeta_title').val().length;
			if (chars > 70)
				chars = '<span style="color: #ff0000;">'+chars+'</span>';
			else
				chars = '<span style="color: #000000;">'+chars+'</span>';
			$('#meta_title_count').html(chars);
		});
		
		$('#cmeta_description').keyup(function(){
			var chars = $('#cmeta_description').val().length;
			if (chars > 150)
				chars = '<span style="color: #ff0000;">'+chars+'</span>';
			else
				chars = '<span style="color: #000000;">'+chars+'</span>';

			$('#meta_description_count').html(chars);
		});		

		$('#cmeta_keywords').keyup(function(){
			$('#meta_keywords_count').html($('#cmeta_keywords').val().length);
		});		
		
		$('#meta_title_count').html($('#cmeta_title').val().length);
		$('#meta_description_count').html($('#cmeta_description').val().length);
		$('#meta_keywords_count').html($('#cmeta_keywords').val().length);
		
		
		
		lum_setupLocalization();
		setupContentLocalization();
		$("#ctemplate").change(confirmLoadContent);

	}
	
	function setupContentLocalization()
	{
		if ($('.lang_code').length)
		{
			$('.lang_code').each(function(){
				var lang_code = $(this).attr('id');
				if (lang_code != def_lang)
				{
					$('#content-'+lang_code).append('<br/><fieldset><legend>Page Content</legend><div id="content_area_'+lang_code+'"></div></fieldset>');
				}
			});
		}
		
	}
	
	function removeBaseUrl(o)
	{
		var str = $(this).attr('lum_id');
		$('#'+str).val(str_replace(BASE_URL+'/', '', str_replace(SSL_BASE_URL+'/', '', o.files[0].url)));
	}

	function handleResponse(o)
	{
		if (o.success)
		{
			if ($("#cpreview").val() == 1)
			{
				window.open (BASE_URL+"/cms-htmlcache/preview.html","preview");
				return;
			}

			if ($("#cpreview").val() == 0)
			{			
				window.location.href = TOOLS_PATH+"/Pages";
			}
		}
		else
		{
			$.jGrowl(o.errors, {sticky: true});
		}
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
	
	/*function loadTemplate()
	{
		var targets = [];
		$('.display_target').each(function(){
			if ($(this).attr('checked'))
				targets.push($(this).val());
		});
		
		targets = targets.join(',');
		
		$.jGrowl("Loading page content...");
		if ($("#page_id").val() != '')
			jRpc.send(loadContentComplete, {plugin: plugin, method: 'getPageContent', params: {page_id: $("#page_id").val(), display_targets: targets}});
		else
			jRpc.send(createContentArea, {plugin: plugin, method: 'getTemplate', params: {template: $("#template_old").val(), display_targets: targets}});		
	}*/

	function confirmLoadContent()
	{
		var buttons = {
				"Reload Content Area": function()
					{
						$( this ).dialog( "close" );
						$('#template_old').val($("#ctemplate").val());
						loadContent();
					},
				Cancel: function() {
					$( this ).dialog( "close" );
					$('#ctemplate').val($("#template_old").val());
				}
			}
	
		lum_confirm("Reload content area?", "Warning. If you change the template or display targets you will lose any changes you may have made below. Also, if the template is completely different, your content may not reload properly. Are you sure you want to continue?", buttons);
	}
	
	function loadContent()
	{
		var targets = [];
		$('.display_target').each(function(){
			if ($(this).attr('checked'))
				targets.push($(this).val());
		});
		
		targets = targets.join(',');
		
		$.jGrowl("Loading page content...");
		jRpc.send(loadContentComplete, {plugin: plugin, method: 'getPageContent', params: {template: $("#template_old").val(), page_id: $("#page_id").val(), display_targets: targets}});
	}
	
	function loadTemplates()
	{
		var targets = [];
		$('.display_target').each(function(){
			if ($(this).attr('checked'))
				targets.push($(this).val());
		});
		
		targets = targets.join(',');
		
		$.jGrowl("Loading templates...");
		jRpc.send(loadTemplatesComplete, {plugin: plugin, method: 'getTemplateList', params: {template: $("#template_old").val(), display_targets: targets}});
	}
	
	function loadTemplatesComplete(o)
	{
		$('#ctemplate').html(o.rows);
	}
	
	function buildUrlName()
	{
		var is_include = $('#cis_include').val();
	
		var name = $('#cname').val();
		name = name.replace(/[^a-zA-Z0-9\s\-]/g,'');
		name = name.replace(/^\s+|\s+$/g,'');
		name = name.toLowerCase(name);
		name = name.replace(/ /g,'-');
		name = name.replace(',','');
		name = name.replace('.','');
		name = name.replace('$','');
		if (is_include == '1')
			name = '__'+name;
		$('#cseo_name').val(name);
	}
	
	function loadContentComplete(o)
	{
		if (o.success == true)	
		{
			if (!checkForTimeout(o))
			{
				$.jGrowl("Loading page template...");
				if (o.rows)
				{
					for (lang_code in o.rows.content)
					{
						$('#content_area_'+lang_code).html('');
						for (target in o.rows.content[lang_code])
						{
							$('#content_area_'+lang_code).append('<p><b>Display Target: '+o.rows.target_desc[target]+'</b></hr></p>');
							$('#content_area_'+lang_code).append(o.rows.content[lang_code][target]);
						}
					}
					lum_loadTinyMce();
				}
			}
		}
		else
		{
			$.jGrowl(o.errors, {sticky: true});
		}
	}
