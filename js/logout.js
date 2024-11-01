//SCRIPTDATA is put before this by wp_add_inline_script
(function($) {
	$(document).ready(function() {
		jQuery(function() {
			$.post(SCRIPTDATA.url + "/logout.php?noredirect");
//			console.log("Logging out");
		});
	});
})(jQuery);

