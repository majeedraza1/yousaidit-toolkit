const convertPXtoMM = (px: number) => px * 0.2645833333
const convertMMtoPX = (mm: number) => mm * 3.7795275591
const convertPTtoMM = (points: number) => points * 0.352778
const convertMMtoPT = (mm: number) => mm * 2.835

/**
 *
 * @param {Number} cardWidthInMM
 * @param {Number} screenWidthInPX
 * @param {Number} fontSizeInPT
 */
const calculateFontSizeScale = (cardWidthInMM: number, screenWidthInPX: number, fontSizeInPT: number | string) => {
  let screenWidthInMM = convertPXtoMM(screenWidthInPX);
  let fontSize = (parseFloat(fontSizeInPT.toString()) / cardWidthInMM) * screenWidthInMM;
  return Math.round(fontSize);
}

/**
 * Get card size from card name
 */
const cardSizeFromName = (name: string): number[] => {
  const sizes = window.StackonetToolkit.pdfSizes;
  return sizes[name] ?? [0, 0];
}

/**
 *
 * @param sizeString
 * @param {HTMLElement} element
 */
const calculateElementHeight = (sizeString: string, element: HTMLElement): number => {
  let size = cardSizeFromName(sizeString);
  let cardWidth = (size[0] / 2), cardHeight = size[1];
  let elementWidth = element.offsetWidth;
  return Math.round((cardHeight / cardWidth) * elementWidth);
}

const calculateElementPadding = (cardWidthInMM: number, screenWidthInPX: number, paddingInMM: number = 8) => {
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
  convertMMtoPT,
  calculateFontSizeScale,
  cardSizeFromName,
  calculateElementHeight,
  calculateElementPadding
}
