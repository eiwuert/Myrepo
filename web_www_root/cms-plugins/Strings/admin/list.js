	var t;
	var data_index = 'string_id';
	var plugin = 'Strings';
	
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
				sort:'string_code',
				dir:'ASC',
				lang_code: $("#lang_code").val()
			},
			columns: {
				string_code: {'title':'String Code', width: '20%'},
				text:  {'title':'Text', width: '80%', editor: 'textBox', renderer: renderText}
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

	// this must be defined
	function handleResponse(o)
	{
		// but you can use the generic handleResonse if you want
		lum_handleResponse(o);
	}
	
	//=== RENDERERS
	function renderText(value, record, meta)
	{
		var str = value+'<br/>';
		if (lum_hasPermission('Strings\\Edit'))
			str += '<a href="'+TOOLS_PATH+'/Strings/edit?string_id='+record.string_id+'">Edit</a>&nbsp;&nbsp;';
			
		if (lum_hasPermission('Strings\\Delete'))
			str +='<a href="#" onclick="lum_delete(\''+record.string_code+'\', '+record.string_id+'); return false;">Delete</a>';
		return str;
	}