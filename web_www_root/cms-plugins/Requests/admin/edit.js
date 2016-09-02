	function initPlugin()
	{
		if ($('.tinymce').length)
			lum_loadTinyMce();
                        
                $('.send_reply').click(function(){
                    	var buttons = {
                            "Send": function()
                                    {
                                        $( this ).dialog( "close" );
                                        $('#send_reply').submit();
                                    },
                            Cancel: function() {
                                    $( this ).dialog( "close" );
                            }
                        }
                        lum_confirm("Send reply?", "Are you sure you want to send this reply?", buttons);
                });
	}
