
	var jRpc = $.jRpc({
		rpc_url: RPC_URL
	});

	var t;
	
	function initPlugin()
	{
		t = $.jGrid({
			id: 'grid',
			rpc_url: RPC_URL,
			method: 'getRoleList',
			plugin: 'Users',
			params: {
				start:0,
				limit:15,
				sort:'name',
				dir:'ASC'
			},
			columns: {
				role_id: {'title':'Role ID', width: '60px'},
				name: {'title':'Role', width: '125px', renderer: renderName}
			},
			data_index: 'role_id'
		});

		t.load();	
	}
	
	function deleteRole(name, role_id)
	{
		var buttons = {
				"Delete": function()
					{
						$( this ).dialog( "close" );
						$.jGrowl("Deleting role...");
						jRpc.send(handleResponse, {plugin: 'Users', method: 'deleteRole', params: {role_id: role_id}});
					},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}

		lum_confirm("Delete Role?", "Are you sure you want to delete '"+name+"'?", buttons);
	}	

	function doBulkAction()
	{
		// we need to find all of the rows that are checked and grab the page ids
		// then we need to build the ajax reqest and send it!
		var value = $('#bulk_action').val();
		var arr = value.split('::');
		
		var method = arr[0];
		var growl_text = arr[1];
		var ids = t.getSelected();
		if (ids.length == 0)
		{
			$.jGrowl("Nothing was selected.");
			return;
		}		
		
		var buttons = {
				"Do it": function()
					{
						$(this).dialog( "close" );
						$.jGrowl(growl_text);
						jRpc.send(handleResponse, {plugin: 'Users', method: method, params: {ids: ids}});
					},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}

		if (arr[2] == '1')
		{
			lum_confirm("Delete Role?", "Are you sure you want to delete '"+name+"'?", buttons);
			return;
		}

		jRpc.send(handleResponse, {plugin: 'Users', method: method, params: {ids: ids}});
	}
	
	function doSearch()
	{
		var query = $("#search").val();
		t.config.params['query'] = query;
		t.load();
	}
	
	function handleResponse(o)
	{
		if (o.success)
		{
			if (!checkForTimeout(o))
			{
				$.jGrowl("The action was a success");
				t.load();
			}
		}
		else
		{
			$.jGrowl(o.errors);
		}
	}
	
	//=== renderers
	function renderName(value, record, css)
	{
		var str = value;
		
		if (lum_hasPermission('Users\\Roles\\Edit'))
			str += '<br/><a href="'+TOOLS_PATH+'/Users/edit-role?role_id='+record.role_id+'">Edit</a>&nbsp;&nbsp;';
		
		if (lum_hasPermission('Users\\Roles\\Delete'))
			str += '<a href="#" onclick="deleteRole(\''+record.name+'\', '+record.role_id+'); return false;">Delete</a>&nbsp;&nbsp;';
		return str;
	}
