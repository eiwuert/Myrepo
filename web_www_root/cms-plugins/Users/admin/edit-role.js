	function initPlugin()
	{		
		$("#edit_form").validationEngine({
			onValidationComplete: function(form, r)
			{
				if (r)
					lum_submitForm($("#edit_form"), handleResponse);
			}
		});
	}
	
	function handleResponse(o)
	{
		if (o.success)
		{
			if (!checkForTimeout(o))
			{
				window.location.href = TOOLS_PATH+"/Users/list-roles";
			}
		}
		else
		{
			$.jGrowl(o.errors, {sticky: true});
		}
	}
