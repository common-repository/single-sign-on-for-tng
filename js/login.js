//SCRIPTDATA is put before this by wp_add_inline_script
(function($) {
	$(document).ready(function() {
		jQuery(function() {
			if (SCRIPTDATA.tngusername == undefined) {
				window.location.href = SCRIPTDATA.url;
			}
			else {
				var data = {
					tngusername: SCRIPTDATA.tngusername,
					tngpassword: SCRIPTDATA.tngpassword 
					}
				$.post( SCRIPTDATA.url + "/processlogin.php",
				data, function() {
					window.location.href = SCRIPTDATA.url;
				})
			}
		});
	});
})(jQuery);
