;(function($) {
	$("input[name=ContentSource]").livequery(function() {
		$(this)
			.change(function() {
				$("#SourcePageID").toggle(this.value == "page");
				$("#Content").toggle(this.value == "content");
			})
			.filter(":checked")
			.trigger("change");
	});
})(jQuery);
