	var t;
	var data_index = 'user_id';
	var plugin = 'Users';
	
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
				sort:'username',
				dir:'ASC'
			},
			columns: {
				last_name: {'title':'Last', width: '125px', renderer: renderName},
				first_name: {'title':'First', width: '125px'},
				username: {'title':'Username', width: '125px'},
				role: {'title':'Master Site Role', width: '100px'},
				status: {'title':'Can Login', width: '50px', renderer: renderStatus}
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
			doSearch(t);
		});
		
		$('.reset_search').click(function() {
			// just do the basic search
			$('#search').val('');
			$('.do_search').trigger('click');
		});		
	}

	function doSearch(t)
	{
		if ($("#cregion").length && $("#cregion").val() != '')
			t.config.params['region'] = $("#cregion").val();
		else
			delete(t.config.params['region']);			
			
		if ($("#search").length)		
			t.config.params['query'] = $("#search").val();
			
		t.config.params['start'] = 0;
		t.load();
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
		
		if (lum_hasPermission('Users\\Accounts\\Edit'))
			str += '<br/><a href="'+TOOLS_PATH+'/Users/edit?user_id='+record.user_id+'">Edit</a>&nbsp;&nbsp;';
		
		if (lum_hasPermission('Users\\Accounts\\Delete'))
			str += '<a href="#" onclick="lum_delete(\''+record.username+'\', '+record.user_id+'); return false;">Delete</a>&nbsp;&nbsp;';
		
		if (lum_hasPermission('Users\\Accounts\\Edit'))
		{
			if (record.status == 0)
				str += '<a href="#" onclick="lum_changeStatus('+record.user_id+', 1); return false;">Activate</a>';
			else
				str += '<a href="#" onclick="lum_changeStatus('+record.user_id+', 0); return false;">Deactivate</a>';
		}		
		return str;
	}
