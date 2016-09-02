	var t;
	var data_index = 'option_id';
	var plugin = 'Options';
	
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
				dir:'ASC'
			},
			columns: {
				name: {'title':'Option', width: '50%', renderer: renderText},
				value:  {'title':'Value', width: '50%', renderer: renderValue}
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
	function renderValue(value, record, meta)
	{
		if (record.type == 'int' || record.type == 'string')
			return value;
		
		if (record.type == 'bool')
		{
			if (value == '0')
				return 'No';
			if (value == '1')
				return 'Yes';
			
		}
		return '';
	}
	
	function renderText(value, record, meta)
	{
		var str = value+'<br/>';
		str += '<a href="'+TOOLS_PATH+'/Options/edit?option_id='+record.option_id+'">Edit</a>';
		str += '&nbsp;&nbsp;';
		str +='<a href="#" onclick="lum_delete(\''+record.name+'\', '+record.option_id+'); return false;">Delete</a>';
		return str;
	}
