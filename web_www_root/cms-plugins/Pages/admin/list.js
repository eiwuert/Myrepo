	var t;
	var data_index = 'page_id';
	var plugin = 'Pages';
	
	function initPlugin()
	{		
		t = $.jGrid({
			id: 'grid',
			rpc_url: RPC_URL,
			plugin: plugin,
			method: 'getList',
			params: {
				start:0,
				limit:25,
				sort:'name',
				dir:'ASC',
				lang_code: $("#lang_code").val()
			},
			columns: {
				name: {'title':'Name', width: '200px', renderer: renderName},
				parent_name: {'title':'Parent Page', width: '150px', renderer: renderParent},
				template: {'title':'Template', width: '150px', renderer: renderTemplate},
				status: {'title':'Active', width: '50px', renderer: renderStatus}
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
	
	function doBulkAction()
	{
		// we need to find all of the rows that are checked and grab the page ids
		// then we need to build the ajax reqest and send it!
		var value = $('#bulk_action').val();
		var arr = value.split('::');
		if (arr[2] == '1')
		{
			if (!confirm("Are you sure you want to perform this action?"))
				return;
		}
		
		var method = arr[0];
		var growl_text = arr[1];
		var ids = t.getSelected();
		if (ids.length == 0)
		{
			$.jGrowl("Nothing was selected.");
			return;
		}
		$.jGrowl(growl_text);
		
		jRpc.send(handleResponse, {method: method, params: {ids: ids}});
	}
	
	// this must be defined
	function handleResponse(o)
	{
		// but you can use the generic handleResonse if you want
		lum_handleResponse(o);
	}
	
	function copyPage(id, name, seo_name)
	{
		var buttons = {
				"Copy": function()
					{
						$( this ).dialog( "close" );
						$.jGrowl("Copying page...");
						jRpc.send(handleResponse, {plugin: plugin, method: 'copyPage', params: {page_id: id}});
					},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
	
		lum_confirm("Copy this?", "Are you sure you want to copy the page '"+name+"'?", buttons);	
	}
	
	//=== form code for later
	function renderStatus(value, record, meta)
	{
		if (value == 1)
		{
			return '<img src="../../cms-admin/images/flag_green.png"/>';
		}	
		return '<img src="../../cms-admin/images/flag_red.png"/>';
	}
	
	function renderParent(value, record, css)
	{
		if (value == null)
			return 'N/A';
	
		return value;
	}	
	
	function renderTemplate(value, record, css)
	{
		return value;
		value = value.substring(0, -4);
		return value;
	}		
	
	function renderName(value, record, css)
	{
		var name = value;
		name = name.replace("\\", "");
		
		var str = name;
		
		if (lum_hasPermission('Pages\\Edit'))
		{		
			str += '<br/><a href="'+TOOLS_PATH+'/Pages/edit?page_id='+record.page_id+'">Edit</a>&nbsp;&nbsp;';
		}		
		
		
		
		if (record.page_id != 1)
		{
			if (lum_hasPermission('Pages\\Delete'))
				str += '<a href="#" onclick="lum_delete(\''+record.name+'\', '+record.page_id+'); return false;">Delete</a>&nbsp;&nbsp;';
				
			if (lum_hasPermission('Pages\\Edit'))
			{
				if (record.status == 0)
					str += '<a href="#" onclick="lum_changeStatus('+record.page_id+', 1); return false;">Activate</a>';
				else	
					str += '<a href="#" onclick="lum_changeStatus('+record.page_id+', 0); return false;">Deactivate</a>';
					
				str += '&nbsp;&nbsp;<a href="#" onclick="copyPage('+record.page_id+', \''+record.name+'\'); return false;">Copy</a>';
			}
		}
		return str;
	}
