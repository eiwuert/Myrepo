(function($){
	
	/**
	 * Create a New Template
	 */
	$.jGrid = function(config) {
		var obj = new $.jGrid.instance(config);
		obj.create();
		return obj;
	};

	/**
	 * Template constructor - Creates a new template instance.
	 *
	 * @param 	options An object of configurable options.  Currently
	 * 			you can toggle compile as a boolean value and set a custom
	 *          template regular expression on the property regx by
	 *          specifying the key of the regx to use from the regx object.
	 */
	$.jGrid.instance = function(config) {
		this.config = $.extend({
			useDblClick: false
		}, config || {});
	};

	/**
	 * Template Instance Methods
	 */
	$.extend( $.jGrid.instance.prototype, {
		
		/**
		 * Apply Values to a Template
		 *
		 * This is the macro-work horse of the library, it receives an object
		 * and the properties of that objects are assigned to the template, where
		 * the variables in the template represent keys within the object itself.
		 *
		 * @param 	values 	An object of properties mapped to template variables
		 */
		create: function(){

						
			var html = '<table class="grid_data" id="table_grid_'+this.config.id+'">';
			html += this.createColumnHeaders();
			html += '</table>';
			
			if (this.config.group_by)
			{
				this.last_group = null;
			}
			
			this.repeat_header_count = 0;
			
			$('#'+this.config.id).append(html);	   

			$('#selected_all').click(createObjectCallback(this, this.toggleChecks))
		},
		
		createColumnHeaders: function()
		{
			if (this.config.light_grid)
				return '';
			
			var bulk = '<input type="checkbox" name="selected_all" id="selected_all"/>';
			if (this.config.auto_num || this.config.skip_bulk)
			{
				bulk = '&nbsp;';
			}
			
			html = '<tr class="nodrag lum_admin_header"><th width="2%">'+bulk+'</th>';
			for (n in this.config.columns)
			{
				if (this.config.auto_columns)
				{
					if (this.config.columns[n]['render'] == false)
						continue;
				}
				
				var width = '';
				if (this.config.columns[n]['width'] != undefined)
					width = 'width="'+this.config.columns[n]['width']+'"';

				var title = 'Unknown';
				if (this.config.columns[n]['title'] != undefined)
					title = this.config.columns[n]['title'];
					
				html += '<th '+width+'>'+title+'</th>';
			}
			html += '</tr>';
			return html;
		},
		
		createRow: function(row, row_num, last_num){
			var id = row[this.config.data_index];
			
			var bulk = '<input type="checkbox" id="selected_'+id+'" name="selected_pages" class="select_box"/>';
			if (this.config.auto_num)
			{
				bulk = row_num;
			}
			if (this.config.skip_bulk)
				bulk = '';
			
			var html = '';
			
			if (this.config.repeat_header && row_num > 1)
			{
				this.repeat_header_count++;
				var repeat_header = false
				if (this.config.repeat_header_every != undefined)
				{
					if (this.repeat_header_count == this.config.repeat_header_every)
					{
						repeat_header = true;
						this.repeat_header_count = 0;
					}
				}
				else
				{
					repeat_header = true;
				}
				
				if (repeat_header)
					html += this.createColumnHeaders();
			}
			
			alt_class = '';
			if (row_num % 2 == 0)
				alt_class = ' alt';
				
			group_class='';
			if (this.config.group_by)
			{
				group_class=value = ' '+row[this.config.group_by];
				
				if (this.last_group == null || this.last_group != row[this.config.group_by])
				{
					var span = 1;
					for (n in this.config.columns)
					{
						span++;
					}
					html += '<tr id="'+this.config.data_index+'__'+id+'" class="non_edit group_by'+alt_class+'"><td colspan="'+span+'"><b>'+this.config.columns[this.config.group_by]['title']+': '+row[this.config.group_by]+'</b></td></tr>';
					this.last_group = row[this.config.group_by];
				}
				
			}
			
			html += '<tr id="'+this.config.data_index+'__'+id+'" class="non_edit'+group_class+alt_class+'">';
			
			if (this.config.light_grid == undefined)
				html += '<td>'+bulk+'</td>';
			
			var value;
			var meta = {
				css: '',
				disable_select: false
			}
			
			var changes = [];
			var change = false;
			var dbls = [];
			var count = 1;
			var last_row = [];
			for (n in this.config.columns)
			{

				if (this.config.auto_columns)
				{
					if (lum_isDefined(row[n]))
					{
						value = row[n]['value'];
						if (row[n]['last_row'] != undefined)
						{
							last_row.push(row[n]);
							continue;
						}
					}
					else
					{
						value = 0;
					}
				}
				else
					value = row[n];
					
				meta.css = '';
				if (this.config.columns[n] != undefined)
				{
					if (this.config.columns[n]['renderer'] != undefined)
					{
						if (this.config.auto_columns)
						{
							var func = eval(this.config.columns[n]['renderer']);
							value = func(value, row, meta);
						}
						else
							value = this.config.columns[n]['renderer'](value, row, meta);
					}
					
					if (this.config.columns[n]['editor'] != undefined)
					{
						if (this.config.columns[n]['editor'] == 'textBox')
						{
							this.config.useDblClick = true;
							dbls.push(n+'_'+id);
							meta.css = "textBox";
						}
					}
				}		
				
				if (meta.css != '')	
				{
					meta.css = 'class="'+meta.css+'"';
				}
				
				if (meta.disable_select)
				{
					change = true;
				}
						
				html += '<td id="'+n+'_'+id+'" '+meta.css+'>'+value+'</td>';
				count++;
			}
			
			html +='</tr>';
			if (last_row.length > 0)
			{
				for (z=0;z<last_row.length;z++)
				{
					value = last_row[z]['value'];
					if (last_row[z]['renderer'] != undefined)
					{
						
						var func = eval(last_row[z]['renderer']);
							value = func(value, row, meta);
					}
					html += '<tr class="non_edit lum_admin_paging"><td><img src="../../cms-admin/images/page-next.gif" class="open_close"/></td><td colspan="'+(count-1)+'"><b>'+last_row[z]['title']+'</b><div class="collapse_row">'+value+'</div></td></tr>';
				}
			}
			var num_cols = this.numColumns()+1;
			
			$('#'+this.config.id +' .grid_data').append(html);	

			if (last_row.length > 0 && row_num == last_num)
			{
				$('#table_grid_'+this.config.id+' .collapse_row').hide();
				$('#table_grid_'+this.config.id+' .open_close').click(function(){
						$(this).parent().next().children('.collapse_row').slideToggle();
				});
			}
			
			if (change)
				$('#selected_'+id).attr('disabled' , true);
				
		},		
		
		numColumns : function(){
			cols = 0;	
			for (n in this.config.columns)
			{
				cols++;
			}			
			return cols;
		},
		
		createNavBar: function(){
			var span_cols = this.numColumns() + 1;
			var html = '<tr class="nodrag lum_admin_paging"><th colspan="'+span_cols+'" style="font-size 11px; height: 20px; line-height: 20px; padding: 0px;">'+this.getNavigation()+'</th></tr>';
			$('#'+this.config.id +' .grid_data').append(html);	 

			if (this.config.skip_navigation != undefined && this.config.skip_navigation)
				return;
			
			//$('.load_next').unbind('click');
			$('.load_next').click(createObjectCallback(this, this.nextPage));
			//$('.load_prev').unbind('click');
			$('.load_prev').click(createObjectCallback(this, this.prevPage));
			//$('.load_first').unbind('click');
			$('.load_first').click(createObjectCallback(this, this.firstPage));
			//$('.load_last').unbind('click');
			$('.load_last').click(createObjectCallback(this, this.lastPage));
			//$("#page_num").unbind('keyup');
			$("#page_num").keyup(createObjectCallback(this, this.goToPage));
			
		},			
		
		goToPage : function(event)
		{
			var page = $('#page_num').val();
			if (page > this.num_pages)
				page = this.num_pages;
				
			if (page < 1)
				page = 1;
				
			if (event.keyCode == 13)
			{
				var new_start = (page * this.config.params.limit) - this.config.params.limit;;  // zero based index
				this.config.params.start = new_start;
				this.reset();
				this.create();
				this.load();
			}
		},
		
		firstPage : function()
		{
			var new_start = 0;
			this.config.params.start = new_start;
			this.reset();
			this.create();
			this.load();
		},			

		lastPage : function()
		{
			var last_page = this.num_pages;
			var new_start = (last_page * this.config.params.limit) - this.config.params.limit;;  // zero based index
			this.config.params.start = new_start;
			this.reset();
			this.create();
			this.load();
		},			
		
		prevPage : function()
		{
			var prev_page = this.current_page - 1;
			var new_start = (prev_page * this.config.params.limit) - this.config.params.limit;;  // zero based index
			this.config.params.start = new_start;
			this.reset();
			this.create();
			this.load();
		},		
		
		nextPage : function()
		{
			var next_page = this.current_page + 1;
			var new_start = (next_page * this.config.params.limit) - this.config.params.limit;  // zero based index
			this.config.params.start = new_start;
			this.reset();
			this.create();
			this.load();
		},
		
		reset : function()
		{
			$('#'+this.config.id).html('');
		},
		
		clear : function()
		{
			$("#table_grid_"+this.config.id+" .non_edit").remove();
		},		
		
		getNavigation: function()
		{
			
			if (this.config.skip_navigation)
				return "";
			var bNext = false;
			var start = this.config.params.start;
			var limit = this.config.params.limit;
			var num_results = this.num_records;
			
			if (num_results > (start + limit))
				bNext = true;

			var bPrev = false;
			if (start > 0)
				bPrev = true;

			// how many pages do we have total?
			var pages = Math.ceil(num_results / limit);

			var lastpage = (pages - 1) * limit;

			var nextstart = start + limit;
			if (nextstart > num_results)
			{
				nextstart = num_results - limit;
			}

			var prevstart = start - limit;
			if (prevstart < 0)
				prevstart = 0;

			this.current_page = (start / limit) + 1;				
				
			var str = '';
			if (bPrev)
			{
				str +='<div style="float: left; margin: 0px; padding: 0px; height: 25px;"><a href="#" class="load_first" style="padding: 0px; margin: 0px;"><img src="../../cms-admin/images/page-first.gif" border="0" style="padding: 2px; margin: 0px;"/></a>';
				str +='<a href="#" class="load_prev" style="padding: 0px; margin: 0px;"><img src="../../cms-admin/images/page-prev.gif" border="0" style="padding: 2px; margin: 0px;"/></a></div>';
			}
			else
			{
				str +='<div style="float: left; margin: 0px; padding: 0px; height: 25px;"><a href="#" class="load_first" style="padding: 0px; margin: 0px;"><img src="../../cms-admin/images/page-first-disabled.gif" border="0" style="padding: 2px; margin: 0px;"/></a>';
				str +='<a href="#" class="load_prev" style="padding: 0px; margin: 0px;"><img src="../../cms-admin/images/page-prev-disabled.gif" border="0" style="padding: 2px; margin: 0px;"/></a></div>';
			}
				
			if (this.config.light_grid == undefined)
				str += ' <p style="float: left; margin: 0px; padding: 0px; height: 25px; line-height: 25px;">Page <input type="text" id="page_num" style="padding: 0px; height: 18px; width: 30px; font-size: 12px;" value="'+this.current_page+'"/> of '+this.num_pages+'&nbsp;</p>'
			
			if (bNext)
			{
				str +='<div style="float: left; margin: 0px; padding: 0px; height: 25px;"><a href="#" class="load_next" style="padding: 0px; margin: 0px;"><img src="../../cms-admin/images/page-next.gif" border="0" style="padding: 2px; margin: 0px;"/></a>';
				str +='<a href="#" class="load_last" style="padding: 0px; margin: 0px;"><img src="../../cms-admin/images/page-last.gif" border="0" style="padding: 2px; margin: 0px;"/></a></div>';
			}
			else
			{
				str +='<div style="float: left; margin: 0px; padding: 0px; height: 25px;"><a href="#" class="load_next" style="padding: 0px; margin: 0px;"><img src="../../cms-admin/images/page-next-disabled.gif" border="0" style="padding: 2px; margin: 0px;"/></a>';
				str +='<a href="#" class="load_last" style="padding: 0px; margin: 0px;"><img src="../../cms-admin/images/page-last-disabled.gif" border="0" style="padding: 2px; margin: 0px;"/></a></div>';
			}

			//str += ' - Current Page: '+this.current_page+' Total Records: '+this.num_records+' Num Pages: '+this.num_pages+'';
			
			
			return str;
		},
		
		render: function(data) {
			var id = this.config.id;
			this.repeat_header_count = 0;
			if (this.config.auto_columns)
			{
				if (data.rows)
				{
					$("#table_grid_"+this.config.id).remove();
					this.config.columns = {};
					for (n in data.rows[0])
					{
						if (this.config.debug)
							alert(n);
						this.config.columns[n] = {'title':data.rows[0][n]['title'], width: data.rows[0][n]['width'], renderer: data.rows[0][n]['renderer']};
						if (data.rows[0][n]['last_row'] != undefined)
						{
							this.config.columns[n]['render'] = false;
						}
						else
						{
							this.config.columns[n]['render'] = true;
						}
					}
					this.create();
				}				
			}
			
			
			$("#"+id).find("tr:gt(0)").remove();

			this.num_records = data.num_records;
			this.num_pages = Math.ceil(data.num_records / this.config.params.limit);
			this.createNavBar();
			
			if (data.rows)
			{
		  		for (i=0;i<data.rows.length;i++)
		  		{
		  			var row_num = i + 1;
					this.createRow(data.rows[i], row_num, data.rows.length);
					var data_id = this.config.data_index+'__'+data.rows[i][this.config.data_index];
					/*$("#"+data_id).click(function(event) {
						var $tgt = $(event.target);
						if ($tgt.is('td')) {
							var arr = this.id.split("__");
							var id = parseInt(arr[1]);
							if (!isNaN(id))
							{
								$('#selected_'+id).attr('checked', !$('#selected_'+id).is(':checked'));
							}
						}
					});*/
					
					//if (this.config.useDblClick)
						//$("#"+data_id).dblclick(createObjectCallback(this, this.onDblClick));
					
		  		}
			}	  		
//			$(".grid_data .non_edit").mouseover(function() {$(this).addClass("over");}).mouseout(function() {$(this).removeClass("over");});
	
			
			
			this.num_records = data.num_records;
			this.num_pages = Math.ceil(data.num_records / this.config.params.limit);
			//this.createNavBar();
			
			this.onRender();
		},
		
		onRender: function(){
		},
		
		onDblClick: function (event) {
			if (this.editing)
				return;
				
			if ($(event.target).hasClass('edit_value') || $(event.target).hasClass('edit_row'))
			{
				this.saved_id = event.target.parentNode.id;
			}
			else
			{
				this.saved_id = event.target.id;
			}
			
			this.saved_value = $('#'+this.saved_id+' .edit_value').text();
			$('#'+this.saved_id+' .edit_value').html('<input type="text" value="'+this.saved_value+'" id="current_editor"/>');
			$('#current_editor').keyup(createObjectCallback(this, this.handleEdit));
			//$('#current_editor').blur(createObjectCallback(this, this.cancelEdit));
			this.editing = true;
		},
		
		onHandleEdit: function(event)
		{		
		},
		
		handleEdit: function(event)
		{
			if (event.keyCode == 13)
			{
				this.saveEdit();
			}
			if (event.keyCode == 27)
			{
				this.cancelEdit();
			}
			this.editing = false;
		},
		cancelEdit : function()
		{
			$('#'+this.saved_id+' .edit_value').html('');
			$('#'+this.saved_id+' .edit_value').text(this.saved_value);				
		},
		saveEdit : function()
		{
			value = $('#current_editor').val();
			$('#'+this.saved_id+' .edit_value').html('');
			$('#'+this.saved_id+' .edit_value').text(value);
			this.onHandleEdit();			
		},
		
		load: function() {
			$.jGrowl("Loading data...");
			var data = {
				params: this.config.params,
				plugin: this.config.plugin,
				method: this.config.method
			}
			var str = jQuery.jSONToString(data);
		
			jQuery.post( this.config.rpc_url, str, createObjectCallback(this, this.onLoad), "json") ;			
		},
		
		onLoad : function(data, result)
		{
			if (data.success == true && !data['session_timeout'])
			{
				$.jGrowl("Loading complete.");
				this.render(data);
			}
			else if (data.success == true && data.session_timeout == true)
			{
				$.jGrowl("Your session has timed out. Please reload the page to log back in.", { sticky: true });
			}
			else
			{
				$.jGrowl("There was an error retrieving the data. The error returned was<br/><br/>"+data.errors, { sticky: true });
			}
		},
		
		getSelected: function(){
			var ids = [];
			$(".grid_data .select_box").each(
				function (){
					if ($(this).is(':checked'))
					{
						arr = this.id.split('_');
						ids.push(arr[1]);
					}
				});		
			return ids;			
		},
		
		toggleChecks: function(obj) {
			this.checked != this.checked;
			$(".grid_data .select_box").each(
				function (){
					if (!$(this).attr('disabled'))
						$(this).attr('checked', $('#selected_all').is(':checked'));
				});
		}		
	});

})(jQuery);
