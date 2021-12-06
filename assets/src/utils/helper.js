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
	let scaleRatio = cardWidthInMM / convertPXtoMM(screenWidthInPX);
	return Math.round(fontSizeInPT / convertMMtoPT(scaleRatio));
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

export {
	convertPXtoMM,
	convertPTtoMM,
	convertMMtoPX,
	calculateFontSizeScale,
	cardSizeFromName,
	calculateElementHeight
}
