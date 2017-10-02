function pcpAlternatingCarousel(showSlides) {
	var i;
	var slides = document.getElementsByClassName(showSlides.slidesClassName);
	var lenx2 = slides.length * 2;
	if (showSlides.slideIndex === undefined || showSlides.slideIndex < 0) {showSlides.slideIndex = 0;}
	if (showSlides.firstImageDisplayTime === undefined) {showSlides.firstImageDisplayTime = 4000;} // Change first image after 4 seconds
	if (showSlides.otherImageDisplayTime === undefined) {showSlides.otherImageDisplayTime = 2500;} // Change other images after 2.5 seconds
	if (showSlides.delayAfterLastImage === undefined || showSlides.delayAfterLastImage < 0) {showSlides.delayAfterLastImage = 0;}
	if (showSlides.repeats === undefined) {showSlides.repeats = -1;}
	for (i = 0; i < slides.length; i++) {
		slides[i].style.display = "none";  
	}
	if (showSlides.slideIndex++ > lenx2) {
		showSlides.slideIndex = 3;
		if (showSlides.repeats-- == 0) {
			return;
		}
		if (showSlides.delayAfterLastImage > 0) {
			showSlides.slideIndex = 2;
			setTimeout(pcpAlternatingCarousel, showSlides.delayAfterLastImage, showSlides);
			return;
		}
	}
	if (showSlides.slideIndex % 2) {
		slides[0].style.display = "block";
		setTimeout(pcpAlternatingCarousel, showSlides.firstImageDisplayTime, showSlides); // Change first image
	} else {
		slides[(showSlides.slideIndex/2)-1].style.display = "block";
		setTimeout(pcpAlternatingCarousel, showSlides.otherImageDisplayTime, showSlides); // Change other images
	}
}

function pcpCarousel(showSlides) {
	var i;
	var slides = document.getElementsByClassName(showSlides.slidesClassName);
	if (showSlides.slideIndex === undefined || showSlides.slideIndex < 0) {showSlides.slideIndex = 0;}
	if (showSlides.firstImageDisplayTime === undefined) {showSlides.firstImageDisplayTime = 4000;} // Change first image after 4 seconds
	if (showSlides.imageDisplayTime === undefined) {showSlides.imageDisplayTime = 2500;} // Change other images after 2.5 seconds
	if (showSlides.delayAfterLastImage === undefined || showSlides.delayAfterLastImage < 0) {showSlides.delayAfterLastImage = 0;}
	if (showSlides.repeats === undefined) {showSlides.repeats = -1;}
	for (i = 0; i < slides.length; i++) {
		slides[i].style.display = "none";  
	}
	if (showSlides.slideIndex++ >= slides.length) {
		showSlides.slideIndex = 0;
		if (showSlides.repeats-- == 0) {
			return;
		}
		if (showSlides.delayAfterLastImage > 0) {
			setTimeout(pcpCarousel, showSlides.delayAfterLastImage, showSlides);
			return;
		}
	}
	slides[(showSlides.slideIndex)-1].style.display = "block";
	setTimeout(pcpCarousel, showSlides.imageDisplayTime, showSlides); // Change images
}

function pcpOpenCloseAccordion(accordionId, colorClass, showHideId, showText, hideText) {
	var accordionElement = document.getElementById(accordionId);
	var showHideElement = document.getElementById(showHideId);
	if (accordionElement.className.indexOf("w3-show") == -1) {
		accordionElement.className += " w3-show";
		accordionElement.previousElementSibling.className += " " + colorClass;
		showHideElement.innerHTML = hideText;
	} else {
		accordionElement.className = accordionElement.className.replace(" w3-show", "");
		accordionElement.previousElementSibling.className =
				accordionElement.previousElementSibling.className.replace(" " + colorClass, "");
		showHideElement.innerHTML = showText;
	}
}

<!-- MENU SCRIPTS -->
var pcpLastClickId = "";

function pcpMenuDropdownClick(dropdownId) {
	pcpDropdownClick(dropdownId);
	pcpLastClickId = "";
}

function pcpDropdownClick(dropdownId) {

	var x = document.getElementById(dropdownId);
	if (x.className.indexOf("w3-show") == -1) {
		x.className += " w3-show";
	} else { 
		x.className = x.className.replace(" w3-show", "");
	}
	if( pcpLastClickId != '' && pcpLastClickId != dropdownId ) {
		var y = document.getElementById(pcpLastClickId);
		if (y.className.indexOf("w3-show") != -1) {
			y.className = y.className.replace(" w3-show", "");
		}
	}
	pcpLastClickId = dropdownId;
}
<!-- /MENU SCRIPTS -->

<!-- TICKET PRICE SCRIPTS -->
var pcpLastSelectedOptionId = "";

function pcpShowPrices(selectId) {
	var selectedOptionIndex = document.getElementById(selectId).options.selectedIndex;
	var selectedOptionId = document.getElementById(selectId).options[selectedOptionIndex].value;
	var elem = document.getElementById(selectedOptionId);
 if (elem.className.indexOf("w3-show") == -1) {
		elem.className += " w3-show";
	} else {
		elem.className = x.className.replace(" w3-show", "");
	}
	if( pcpLastSelectedOptionId != '' && pcpLastSelectedOptionId != selectedOptionId ) {
		var y = document.getElementById(pcpLastSelectedOptionId);
		if (y.className.indexOf("w3-show") != -1) {
			y.className = y.className.replace(" w3-show", "");
		}
	}
	pcpLastSelectedOptionId = selectedOptionId;
}
<!-- /TICKET PRICE SCRIPTS -->

<!-- REPLACE PAGE SCRIPT -->
function replacePage( destURL ) {
	location.replace( destURL );
}
<!-- /REPLACE PAGE SCRIPT -->