	var t;
	var data_index = 'lang_id';
	var plugin = 'Languages';
	
	function initPlugin()
	{
		t = $.jGrid({
			id: 'grid',
			rpc_url: RPC_URL,
			method: 'getList',
			plugin: plugin,
			params: {
				start:0,
				limit:15,
				sort:'language',
				dir:'ASC'
			},
			columns: {
				lang_code: {'title':'Code', width: '40%', renderer: renderName},
				language: {'title':'Language', width: '40%'},
				status: {'title':'Status', width: '20%', renderer: renderStatus}
			},
			data_index: data_index
		});

		t.load();

		$('.do_bulk_action').click(function(){
			
			var value = $('#bulk_action').val();
			if (value != '')
			{
				var temp = value.split('::');			
				lum_bulkAction(t, temp[0], temp[2], temp[1]);
			}
		})
		
		$('.do_search').click(function() {
			// just do the basic search
			lum_doSearch(t);
		});
		
		$('.reset_search').click(function() {
			// just do the basic search
			$('#search').val('');
			$('.do_search').trigger('click');
		});		
	}
	
	function setDefault(name, id)
	{
		var buttons = {
				"Set Default": function()
					{
						$( this ).dialog( "close" );
						$.jGrowl("Changing default language...");
						jRpc.send(handleResponse, {plugin: 'Languages', method: 'setDefault', params: {lang_id: id}});
					},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}

		lum_confirm("Change Default Language?", "Are you sure you want to change the default language to '"+name+"'?", buttons);
	}	

	// this must be defined
	function handleResponse(o)
	{
		// but you can use the generic handleResonse if you want
		lum_handleResponse(o);
	}
	
	//=== RENDERERS
	function renderStatus(value, record, meta)
	{
		if (value == 1)
		{
			return '<img src="../../cms-admin/images/flag_green.png"/>';
		}	
		return '<img src="../../cms-admin/images/flag_red.png"/>';
	}
	
	function renderName(value, record, css)
	{
		var str = value;
		
		if (record.def == "1")
			str +=' (Default)';
			
		str += '<br/><a href="'+TOOLS_PATH+'/Languages/edit?lang_id='+record.lang_id+'">Edit</a>\
		&nbsp;&nbsp;\
		<a href="#" onclick="lum_delete(\''+record.language+'\', '+record.lang_id+', \' WARNING: This will also DELETE ALL CONTENT for this language\'); return false;">Delete</a>\
		&nbsp;&nbsp;';
		
		if (record.status == "0")
			str += '<a href="#" onclick="lum_changeStatus('+record.lang_id+', 1); return false;">Activate</a>';
		else
			str += '<a href="#" onclick="lum_changeStatus('+record.lang_id+', 0); return false;">Deactivate</a>';
		
		if (record.def == "0")
			str += '&nbsp;&nbsp;<a href="#" onclick="setDefault(\''+record.language+'\', '+record.lang_id+'); return false;">Set as Default</a>';
		
		return str;
	}
