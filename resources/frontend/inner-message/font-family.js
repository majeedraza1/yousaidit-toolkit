const fontFamilies = window.YousaiditFontsList || [{label: "Open Sans", fontFamily: "Open Sans"}];

const createFontFaceCss = () => {
    const el = document.querySelector('#yousaidit-inline-font-face-css');
    if (el) {
        return;
    }
    const styleTag = document.createElement('style');
    styleTag.setAttribute('id', 'yousaidit-inline-font-face-css');
    styleTag.setAttribute('type', 'text/css');

    let style = '';
    fontFamilies.forEach(font => {
        style += `@font-face {font-family: '${font.fontFamily}'; font-style: normal; font-weight: 400;font-display: swap; src: url(${font.url}) format('truetype');}\n`
    })
    styleTag.innerHTML = style;

    document.body.append(styleTag);
}

window.addEventListener("load", () => {
    // createFontFaceCss();
});
export {createFontFaceCss}
export default fontFamilies;
