	var t;
	var data_index = 'request_id';
	var plugin = 'Requests';
	
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
				status: 1,
				replied: 0
			},
			columns: {
				request_id: {'title':'ID', width: '10%'},
				request_date:  {'title':'Request Date', width: '15%'},
				agent: {'title':'Agent', width: '15%', renderer: renderAgent},
				name: {'title':'Name', width: '20%', renderer: renderName},
				email: {'title':'Email', width: '20%'},
				replied:  {'title':'Replied', width: '10%', renderer: renderReplied},
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
			
		if ($("#replied").length && $("#replied").val() != '')
			t.config.params['replied'] = $("#replied").val();
		else
			delete(t.config.params['replied']);
			
			
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
	
	function claimRecord(id)
	{
		var params = {};
		params[data_index] = id;
		
		var buttons = {
				"Claim": function()
					{
						$( this ).dialog( "close" );
						$.jGrowl("Claiming...");
						jRpc.send(handleResponse, {plugin: plugin, method: 'claim', params: params});
					},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
	
		lum_confirm("Claim this?", "Are you sure you want to claim this record?", buttons);		
	}		
	
	//=== RENDERERS
	function renderWebId(value, record, meta)
	{
		return 'WS'+value;
	}
	
	function renderReplied(value, record, meta)
	{
		if (value == 1)
		{
			return record.replied_on;
		}	
		return 'NO';
	}
		
	
	function renderStatus(value, record, meta)
	{
		if (value == 1)
		{
			if (record.quote_id > 0)
			{
				return '<a href="'+TOOLS_PATH+'/Quotes/edit?quote_id='+record.quote_id+'">Quote Sent</a>';
			}	
			return 'NEW';
		}	
		return 'ARCHIVED';
	}
	
	function renderAgent(value, record, meta)
	{
		if (record.company != null && record.agent_name == '')
		{
			return 'WHOLESALE ORDER<br/>'+record.company;
		}
		
		return value;
	}	
	
	function renderName(value, record, meta)
	{
		var str = value+'<br/>';
		
		if (lum_hasPermission('Requests\\View'))
		{
			str += '<a href="'+TOOLS_PATH+'/Requests/edit?request_id='+record.request_id+'">View</a>';
		}
		
		if (lum_hasPermission('Requests\\Archive'))
		{
			if (record.status == 1)
				str += '&nbsp;&nbsp;<a href="#" onclick="lum_changeStatus('+record.request_id+', 0); return false;">Archive</a>';
		}
		
		if (record.agent_id == '0')
		{
			if (lum_hasPermission('Requests\\Archive'))
			{
				str += '&nbsp;&nbsp;<a href="#" onclick="claimRecord('+record.request_id+'); return false;">Claim</a>';
			}
			meta.css = 'new-quote';
		}		
			
		return str;
	}
