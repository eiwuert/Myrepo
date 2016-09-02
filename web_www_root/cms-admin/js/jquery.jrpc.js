(function($){
	
	/**
	 * Create a New Template
	 */
	$.jRpc = function(config) {
		return new $.jRpc.instance(config);
	};

	/**
	 * Template constructor - Creates a new template instance.
	 *
	 * @param 	options An object of configurable options.  Currently
	 * 			you can toggle compile as a boolean value and set a custom
	 *          template regular expression on the property regx by
	 *          specifying the key of the regx to use from the regx object.
	 */
	$.jRpc.instance = function(config) {
		this.config = $.extend({
			rpc_url: 		null
		}, config || {});
	};

	/**
	 * Template Instance Methods
	 */
	$.extend( $.jRpc.instance.prototype, {
		
		/**
		 * Apply Values to a Template
		 *
		 * This is the macro-work horse of the library, it receives an object
		 * and the properties of that objects are assigned to the template, where
		 * the variables in the template represent keys within the object itself.
		 *
		 * @param 	values 	An object of properties mapped to template variables
		 */
		send: function(callback, config) {
			if (config)
			{
				this.config = $.extend(this.config, config);			
			}
			
			var data = {
				params: this.config.params,
				plugin: this.config.plugin,
				method: this.config.method
			}
			var str = jQuery.jSONToString(data);
			jQuery.post( this.config.rpc_url, str, callback, "json") ;			
		}
	});
	
})(jQuery);