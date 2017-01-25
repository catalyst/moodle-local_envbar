function colourupdate(jscolor) {
	var colour = "#" + jscolor,
		picker = jscolor.styleElement,
		textfieldid = picker.id.replace("colourbtn_", ""),
		textfield = document.getElementById(textfieldid),
		parts = textfieldid.split("_"),
		colourtype = parts[1],
		envbarclass = "envbar env" + parts[2];

	textfield.value = colour;

	var envbars = document.getElementsByClassName(envbarclass);
	for (var i = 0; i < envbars.length; i++) {
		if (colourtype == "colourtext") {
			envbars[i].style.color = colour;
		} else if (colourtype == "colourbg") {
			envbars[i].style.background = colour;
		}
	}
}

function colourtohex(colour) {
	var colours = {
		"black":"#000000","white":"#ffffff","red":"#ff0000","green":"#008000","seagreen":"#2e8b57","yellow":"#ffff00","brown":"#a52a2a",
		"blue":"#0000ff","slateblue":"#6a5acd","chocolate":"#d2691e","crimson":"#dc143c","orange":"#ffa500","darkorange":"#ff8c00"
	};

	if (typeof colours[colour.toLowerCase()] != 'undefined')
        return colours[colour.toLowerCase()];

    return false;
}

document.addEventListener("DOMContentLoaded", function(event) {
	var colourbuttons = document.querySelectorAll('input[id*="colourbtn"],button[id*="colourbtn"]');
	for (var i = 0; i < colourbuttons.length; i++) {
		var textfieldid = colourbuttons[i].id.replace("colourbtn_", ""),
			textfield = document.getElementById(textfieldid),
			colour = textfield.value;

		if (!colour.match(/^#.{6}/)) {
			//we're dealing with a non hex colour
			colour = colourtohex(colour);
		}

		colourbuttons[i].className += " jscolor {valueElement:null,value:'" + colour + "',onFineChange:'colourupdate(this)'}";
	}
})