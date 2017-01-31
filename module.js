function colourupdate(jscolor) {
	var colour = "#" + jscolor,
		picker = jscolor.styleElement,
		textfieldid = picker.id.replace("colourbtn_", ""),
		textfield = document.getElementById(textfieldid),
		parts = textfieldid.split("_"),
		colourtype = parts[1],
		envbarid = parts[2];

	textfield.value = colour;

	var repeatenv = false;
	if (colourtype.match(/^repeat.*/)) {
		repeatenv = true;
		colourtype = colourtype.replace("repeat", "");
	}
	envbarupdate(envbarid, colourtype, colour, repeatenv);
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

function envbarupdate(id, colourtype, colour, repeatenv) {
	var envbarclass = (repeatenv === false) ? "envbar env" + id : "envbar envpreview repeatenv" + id ,
	 	envbars = document.getElementsByClassName(envbarclass);

	for (var i = 0; i < envbars.length; i++) {
		var links = envbars[i].getElementsByTagName('a');
		if (colourtype == "colourtext") {
			envbars[i].style.color = colour;
			for (var ii = 0; ii < links.length; ii++) {
				links[ii].style.color = colour;
			}
		} else if (colourtype == "colourbg") {
			envbars[i].style.background = colour;
			for (var ii = 0; ii < links.length; ii++) {
				links[ii].style.background = colour;
			}
		}
	}
}

function colourinputhandler() {
	var colour = this.value,
		parts = this.id.split("_"),
		colourbuttonid = parts[0] + "_" + parts[1] + "_colourbtn_" + parts[2],
		colourbutton = document.getElementById(colourbuttonid);
		colourtype = parts[1],
		envbarid = parts[2];

	if (!colour.match(/^#.{6}/)) {
		//we're dealing with a non hex colour
		colour = colourtohex(colour);
	}

	if (colour) {
		var repeatenv = false;
		if (colourtype.match(/^repeat.*/)) {
			repeatenv = true;
			colourtype = colourtype.replace("repeat", "");
		}
		colourbutton.jscolor.fromString(colour);
		envbarupdate(envbarid, colourtype, colour, repeatenv);
	}
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

	var previewbars = document.getElementsByClassName('envpreview');
	for (var i = 0; i < previewbars.length; i++) {
		previewbars[i].className += " repeatenv" + i;
	}

	var colourinputs = document.querySelectorAll('input[id*="colourtext"],input[id*="colourbg"]');
	for (var i = 0; i < colourinputs.length; i++) {
		colourinputs[i].addEventListener("keyup", colourinputhandler);
		colourinputs[i].addEventListener("change", colourinputhandler);
	}
})