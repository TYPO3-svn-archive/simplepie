var nextItem = 0;

jQuery(document).ready(function(){
	// click on next/prev link
	jQuery(".simplepie_ajax_next").click(function(){
		getItem();
	});
});

function getItem() {
	//jQuery(".simplepie_ajax_content").text("jQuery dyn Test");
	
	nextItem = nextItem + 1;
	
	jQuery.ajax({
		url: "index.php",
		processData: "false",
		data: "id=96&type=4711&item=" + nextItem + "&no_cache=1&tx_simplepie_pi1[action]=ajax&tx_simplepie_pi1[controller]=Feed",
		dataType: "json",
		success: function(ret){
			// place new content
			jQuery(".simplepie_ajax_content").html(ret.content);
		}
	});
}