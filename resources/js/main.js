$(document).ready(function() { 
	
	$(".apply-filter-button").click(function() {
		jobs = $("#jobs").children();
		$(".apply-filter-button").not($(this)).attr("disabled", "disabled");
		$("#reset-filters-button").css("opacity", 1).css("pointer-events", "all");
		$("span.filter-highlight").removeClass("highlighted");
		
		var filter;
		
		if($(this).data("filter") == "public-transit-commute-time-less-than-1-hour") {
			filter = "filter-public-transit-commute-time-seconds";
			jobs.each(function() {
				if($(this).data(filter) >= 3600) {
					$(this).slideUp();
				}
			});
		} else if($(this).data("filter") == "public-transit-commute-time-less-than-30-mins") {
			filter = "filter-public-transit-commute-time-seconds";
			jobs.each(function() {
				if($(this).data(filter) >= 1800) {
					$(this).slideUp();
				}
			});
		} else if($(this).data("filter") == "public-transit-fare-price-less-than-5-dollars") {
			filter = "filter-public-transit-fare-value";
			jobs.each(function() {
				if($(this).data(filter) >= 5) {
					$(this).slideUp();
				}
			});
		} else if($(this).data("filter") == "public-transit-steps-less-or-equal-to-3") {
			filter = "filter-public-transit-commute-number-of-steps";
			jobs.each(function() {
				if($(this).data(filter) > 3) {
					$(this).slideUp();
				}
			});
		} else if($(this).data("filter") == "driving-monthly-gas-cost-under-50") {
			filter = "filter-driving-monthly-gas-cost";
			jobs.each(function() {
				if($(this).data(filter) >= 50) {
					$(this).slideUp();
				}
			});
		} else if($(this).data("filter") == "driving-monthly-gas-cost-under-100") {
			filter = "filter-driving-monthly-gas-cost";
			jobs.each(function() {
				if($(this).data(filter) >= 100) {
					$(this).slideUp();
				}
			});
		} else if($(this).data("filter") == "driving-daily-commute-time-under-15-mins") {
			filter = "filter-driving-daily-commute-time-seconds";
			jobs.each(function() {
				if($(this).data(filter) >= 900) {
					$(this).slideUp();
				}
			});
		} else if($(this).data("filter") == "driving-daily-commute-time-under-30-mins") {
			filter = "filter-driving-daily-commute-time-seconds";
			jobs.each(function() {
				if($(this).data(filter) >= 1800) {
					$(this).slideUp();
				}
			});
		} else if($(this).data("filter") == "driving-daily-commute-time-under-60-mins") {
			filter = "filter-driving-daily-commute-time-seconds";
			jobs.each(function() {
				if($(this).data(filter) >= 3600) {
					$(this).slideUp();
				}
			});
		}
		
		setTimeout(function(){
			$("span.filter-highlight[data-filter-highlight='"+filter+"']").addClass("highlighted");
		}, 250);
	});
	
	$("#reset-filters-button").click(function() {
		$(this).css("opacity", 0).css("pointer-events", "none");
		$(".apply-filter-button").removeAttr("disabled");
		$("#jobs").children().slideDown();
		$("span.filter-highlight").removeClass("highlighted");
	});

});