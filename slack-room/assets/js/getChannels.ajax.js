jQuery(document).ready(function($) {
    alert("AJI");
	var data = {
		'action': 'my_action',
		'whatever': ajax_object.we_value      // We pass php values differently!
	};
	// We can also pass the url value separately from ajaxurl for front end AJAX implementations
	jQuery.post(ajax_object.ajax_url, data, function(response) {
		alert('Got wthis from the server: ' + response); 
	});

	function getChannels () {
		alert("hola");
	}
});