M.local_envbar = {};

M.local_envbar.init_colourpicker = function(Y, cmid, modulename) {
	var pickers = document.querySelectorAll("input[type=color]");
	for (var i = 0; i < pickers.length; i++) {
		pickers[i].addEventListener("change", function() {
			var hex = this.value,
				parts = this.id.split("_"),
				type = parts[1],
				id = parts[2];

			if (!type.match(/repeat.*/)) {
				var envbars = document.getElementsByClassName("envbar env"+id);
				for (var i = 0; i < envbars.length; i++) {
					if (type == "colourtext") {
						envbars[i].style.color = hex;
					} else if (type == "colourbg") {
						envbars[i].style.background = hex;
					}
				}
			}			
		});
	}
}