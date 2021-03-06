var chartid = 0;
$(document).ready(function() {
	$(".add-all").click(function() {
		$(".add-domain").click();
	});
	$(".add-domain").click(function() {
		var that = this;
		var panel = $(".template").clone();
		panel.removeClass("template");
		panel.attr("id", "chart" + chartid++);
		panel.appendTo("#panel-container");
		panel.show();
		panel.find(".panel-title > span").text($(this).data("domain"));
		panel.find(".rrd-id").attr("id", "rrd-" + panel.attr("id"));
		panel.find("button.close").click(function() {
			$(this).parent().parent().parent().remove();
		});
		$.ajax({
			url: "?page=stats",
			dataType: "json",
			data: {"ajax-pie": $(this).data("domain")}
		}).done(function(data) {
			panel.find(".since").text("Since " + new Date(data.since * 1000).toDateString());
			$.plot(panel.find(".pie"), data.flot, {
				series: {
					pie: {
						show: true,
						innerRadius: 0.5,
					}
				},
				legend: {
					show: true,
					labelFormatter: function(label, series) {
						var num = parseInt(series.data[0][1], 10);
						return "&nbsp;" + label + " " + Math.round(series.percent) + "% (" + num + ")";
					}
				}
			});
		});
		$.ajax({
			url: "?page=stats",
			dataType: "json",
			data: {"ajax-rrd": $(this).data("domain")}
		}).done(function(data) {
			var binary = new Array();
			for (i = 0; i < data.length; i++)
				binary.push(new RRDFile(new BinaryFile(atob(data[i]))));
			var rrd = new RRDFileSum(binary);
			// Setup lines and colors
			var dss = rrd.getDSNames();
			var ds_opt = new Array();
			for (i = 0; i < dss.length; i++) {
				ds_opt[dss[i]] = {
					stack: true,
					lines: { show: true, fill: 1, lineWidth: 0 }
				};
				var color = null;
				if (dss[i] == "quarantine") color = "#e96";
				if (dss[i] == "deliver") color = "#7d6";
				if (dss[i] == "delete") color = "#666";
				if (dss[i] == "reject") color = "#d44";
				if (dss[i] == "allow") color = "#9cf";
				if (dss[i] == "block") color = "#622";
				if (dss[i] == "defer") color = "#ed4";
				if (color) ds_opt[dss[i]].color = color;
			}
			var flot_opts = {
				grid: { borderWidth: 1 },
				series: { stack: true },
				yaxis: { tickFormatter: function(v) { return Math.round(v * 3600) + " /h"; }},
				legend: {
					labelFormatter: function(label, series) {
						var hasValue = false;
						for (var i = 0; i < series.data.length; ++i) {
							if (series.data[i][1] != 0) {
								hasValue = true;
								break;
							}
						}
						return hasValue ? label : null;
					}
				}
			};
			var rrd_opts = {
				checked_DSs: ["reject", "deliver", "quarantine", "defer"],
				use_checked_DSs: true,
				graph_width: "100%",
				graph_height: "150px",
				scale_width: "70%",
				scale_height: "50px"
			};
			var rrdid = "rrd-" + panel.attr("id");
			new rrdFlot(rrdid, rrd, flot_opts, ds_opt, rrd_opts);

			// Move the elements that we like to a div
			$("#" + rrdid + "_graph").appendTo(panel.find(".realrrd"));
			$("#" + rrdid + "_scale").appendTo(panel.find(".realrrd")).css("width", "70%").css("float", "right");
			$("#" + rrdid + "_res").appendTo(panel.find(".realrrd")).addClass("form-control").css("width", "30%").css("float", "left");
			$("#" + rrdid + "_res").trigger("change"); // We need to .draw() the flot again to make axis fit, etc
		});
	});
	if ($(".add-domain").length < 6) $(".add-domain").click();

});
