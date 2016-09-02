	var t;
	var data_index = 'gallery_id';
	var plugin = 'Gallery';
	
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
				gallery_id: {'title':'ID', width: '80px'},
				name: {'title':'Name', width: '200px', renderer: renderName}
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
	
	//=== form code for later
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
		var name = value;
		name = name.replace("\\", "");
		
		var str = name;
		if (lum_hasPermission('Gallery\\Edit'))
			str += '<br/><a href="'+TOOLS_PATH+'/Gallery/edit?gallery_id='+record.gallery_id+'">Edit</a>&nbsp;&nbsp;';
		
		if (lum_hasPermission('Gallery\\Delete'))
			str += '<a href="#" onclick="lum_delete(\''+record.name+'\', \''+record.gallery_id+'\'); return false;">Delete</a>&nbsp;&nbsp;';
			
		if (lum_hasPermission('Gallery\\Edit'))
		{
			if (record.status == 0)
				str += '<a href="#" onclick="lum_changeStatus(\''+record.gallery_id+'\', 1); return false;">Activate</a>';
			else	
				str += '<a href="#" onclick="lum_changeStatus(\''+record.gallery_id+'\', 0); return false;">Deactivate</a>';
		}
		
		return str;
	}
