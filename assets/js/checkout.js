jQuery(document).ready(function() {
	window.setInterval(function() {
		jQuery('#payment_currency').change(function()
		{
			jQuery('body').trigger('update_checkout');
		}); 
	}, 1000)
});