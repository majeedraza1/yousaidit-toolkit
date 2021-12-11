const convertPXtoMM = px => px * 0.2645833333
const convertMMtoPX = mm => mm * 3.7795275591
const convertPTtoMM = points => points * 0.352778
const convertMMtoPT = mm => mm * 2.835

/**
 *
 * @param {Number} cardWidthInMM
 * @param {Number} screenWidthInPX
 * @param {Number} fontSizeInPT
 */
const calculateFontSizeScale = (cardWidthInMM, screenWidthInPX, fontSizeInPT) => {
	let screenWidthInMM = convertPXtoMM(screenWidthInPX);
	let fontSize = (fontSizeInPT / cardWidthInMM) * screenWidthInMM;
	window.console.log({cardWidthInMM, screenWidthInPX, screenWidthInMM, fontSizeInPT, fontSize})
	return Math.round(fontSize);
}

/**
 * Get card size from card name
 *
 * @param name
 * @return {*|number[]}
 */
const cardSizeFromName = name => {
	const sizes = {
		a4: [426, 303],
		a5: [303, 216],
		a6: [216, 154],
		square: [300, 150],
	};
	return sizes[name] ?? [0, 0];
}

/**
 *
 * @param sizeString
 * @param {HTMLElement} element
 */
const calculateElementHeight = (sizeString, element) => {
	let size = cardSizeFromName(sizeString);
	let cardWidth = (size[0] / 2), cardHeight = size[1];
	let elementWidth = element.offsetWidth;
	return Math.round((cardHeight / cardWidth) * elementWidth);
}

const calculateElementPadding = (cardWidthInMM, screenWidthInPX, paddingInMM = 8) => {
	let returnPaddingInMM = (paddingInMM / cardWidthInMM) * convertPXtoMM(screenWidthInPX)
	// If card size 150mm, then padding 8mm
	// If card size 1mm, then padding 8mm/150mm
	// Convert element width from px to mm
	// If element size is 200mm, then padding is {(8mm/150mm) * 200mm}
	// Convert mm to px
	return Math.round(convertMMtoPX(returnPaddingInMM));
}

export {
	convertPXtoMM,
	convertPTtoMM,
	convertMMtoPX,
	calculateFontSizeScale,
	cardSizeFromName,
	calculateElementHeight,
	calculateElementPadding
}
