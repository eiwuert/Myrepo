	var t;
	var data_index = 'request_id';
	var plugin = 'Newsletter';
	
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
				sort:'request_date',
				dir:'DESC',
				status: 1
			},
			columns: {
				request_id: {'title':'ID', width: '10%'},
				request_date:  {'title':'Request Date', width: '15%'},
				email: {'title':'Email', width: '20%', renderer: renderEmail},
				status:  {'title':'Status', width: '10%', renderer: renderStatus}
			},
			data_index: data_index
		});
		
		t.load();

		$('.do_search').click(function() {
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
		if ($("#status").length && $("#status").val() != '')
			t.config.params['status'] = $("#status").val();
		else
			delete(t.config.params['status']);
			
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
	function renderWebId(value, record, meta)
	{
		return 'WS'+value;
	}
	
	function renderStatus(value, record, meta)
	{
		if (value == 1)
		{
			return 'NEW';
		}	
		return 'ARCHIVED';
	}
	
	function renderEmail(value, record, meta)
	{
		if (record.verified == '0')
		{
			return 'Waiting for Verification';		
		}	
		
		var str = value+'<br/>';
		
		if (lum_hasPermission('Newsletter\\View'))
		{
			str += '<a href="'+TOOLS_PATH+'/Newsletter/edit?request_id='+record.request_id+'">View</a>';
		}
		
		if (lum_hasPermission('Newsletter\\Archive'))
		{
			if (record.status == 1)
				str += '&nbsp;&nbsp;<a href="#" onclick="lum_changeStatus('+record.request_id+', 0); return false;">Archive</a>';
		}
			
		return str;
	}
	