(function($) {
	$(document).ready(function() {
		function generate_key() {
			var text = "";
			var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+={[}],.?/:;";
			for( var i=0; i < 40; i++ )
				text += possible.charAt(Math.floor(Math.random() * possible.length));
			$("#secret").val(text);
			$("#secretgeneratorbutton").hide();
		}

		$("#autoadd").click(function() {
			$("#role").val([]); // clear selections
			$("#role").prop('disabled', !this.checked);
			$("#clearall").prop('disabled', !this.checked);
			$("#setall").prop('disabled', !this.checked);
		});


		$( "#settings" ).on( "submit", function( event ) {
			let ok = true;
			if ($("#disclaimer").length && !$("#disclaimer").is(":checked")) {
				alert( "To use this plugin you must check the confirmation box to\nconfirm you have read and accept the disclaimer\nat the bottom of the page." );
				event.preventDefault();
				ok = false;
			}
			if ($("#autoadd").is(":checked") && $("#role").find("option:selected").length == 0) {
				alert( "You must select at least one role." );
				event.preventDefault();
				ok = false;
			}
			if (ok) {
				$("#disclaimer").prop('disabled',false);	// allow value to be saved when disabled
			}
	
		});

		$( "#setall" ).on( "click", function( event ) {
			$("#role option").prop("selected", true);
		});
		
		$( "#clearall" ).on( "click", function( event ) {
			$("#role option").prop("selected", false);
		});
		
	});
})(jQuery);
