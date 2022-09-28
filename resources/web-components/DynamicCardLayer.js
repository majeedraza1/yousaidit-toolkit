import {css, html, LitElement} from "lit";
import {convertMMtoPX} from "@/utils/helper";

export class DynamicCardLayer extends LitElement {
	// Styles are applied to the shadow root and scoped to this element
	// :host { position: absolute; width: 100%; height: 100%; }
	static styles = css`
    :host, .section, .section *, .section *:before, .section *:after{ box-sizing: border-box }
    .section { position: absolute; line-height: 1; }
    .section-edit.is-image-edit { border: 1px dotted rgba(0,0,0, 0.12); position: relative;
    	transition: 300ms all ease-in-out;
    }
    .section-edit.is-image-edit:hover,
    .section-edit.is-image-edit.is-active {
    	background-color: var(--shapla-primary-alpha);
    }
    .section-edit-icon {
    	background-color: white; position: absolute; top: 0; left: 0; width: 32px; height: 32px; overflow: hidden;
		display: flex; justify-content: center; align-items: center; border: 1px solid var(--shapla-primary);
		cursor: pointer; transition: 300ms all ease-in-out;
    }
    .section-edit-icon:hover {border-radius: 16px;}
    .section-edit-icon svg {display: block; fill: currentColor;}
  `;

	// Creates a reactive property that triggers rendering
	static  properties = {
		active: {type: String},
		showEditIcon: {type: String, attribute: 'show-edit-icon'},
		index: {type: Number},
		section: {type: Object},
		cardWidthMM: {type: Number, attribute: 'card-width-mm'},
		cardHeightMM: {type: Number, attribute: 'card-height-mm'},
		elementWidthMM: {type: Number, attribute: 'element-width-mm'},
		elementHeightMM: {type: Number, attribute: 'element-height-mm'},
		fontFamilies: {type: Array, attribute: 'font-families'},
	}

	constructor() {
		super();
		this.fontFamilies = window.StackonetToolkit.fonts || window.DesignerProfile.fonts;
	}

	sectionStyle() {
		// when card width 150mm, then from top 10mm
		// when card width 15mm, then from top 10mm/150mm * 15
		let styles = [],
			// _top = Math.round(100 / this.elementHeightMM * this.section.position.top),
			// _left = Math.round(100 / this.elementWidthMM * this.section.position.left);
			_top = Math.round(this.elementHeightMM * (this.section.position.top / this.cardHeightMM)),
			_left = Math.round(this.elementWidthMM * (this.section.position.left / this.cardWidthMM));

		styles.push(`left: ${convertMMtoPX(_left)}px`);
		styles.push(`top: ${convertMMtoPX(_top)}px`);

		if (-1 !== ['static-text', 'input-text'].indexOf(this.section.section_type)) {
			let fontSize = Math.round((this.section.textOptions.size / this.cardWidthMM) * this.elementWidthMM),
				fontFamily = this.fontFamilies.find(_font => _font.key === this.section.textOptions.fontFamily);

			styles.push(`font-family: ${fontFamily.label}`);
			styles.push(`font-size: ${fontSize}pt`);
			styles.push(`text-align: ${this.section.textOptions.align}`);
			styles.push(`color: ${this.section.textOptions.color}`);

			if (['center', 'right'].indexOf(this.section.textOptions.align) !== -1) {
				styles.push('width: 100%');
				styles.push('left: 0%');
			}
		}

		if (-1 !== ['static-image', 'input-image'].indexOf(this.section.section_type)) {
			styles = styles.concat(this.sectionImageStyle())
		}

		return styles.join(';');
	}

	sectionImageStyle() {
		let styles = []
		if (['center', 'right'].indexOf(this.section.imageOptions.align) !== -1) {
			styles.push('width: 100%');
			styles.push('left: 0%');
		}
		if ('center' === this.section.imageOptions.align) {
			styles.push('width: 100%');
			styles.push('display: flex');
			styles.push('justify-content: center');
		}
		if ('right' === this.section.imageOptions.align) {
			styles.push('width: 100%');
			styles.push('display: flex');
			styles.push('justify-content: flex-end');
		}
		return styles;
	}

	imageStyle() {
		// when card width 150mm, then width 101mm
		// when card width 15mm, then width 101mm/150mm * 15mm
		let styles = [],
			width = Math.round((this.section.imageOptions.width / this.cardWidthMM) * this.elementWidthMM);
		styles.push(`width: ${convertMMtoPX(width)}px`);
		return styles.join(';');
	}

	onClickEditSection() {
		this.dispatchEvent(new CustomEvent('edit', {detail: {index: this.index, section: this.section}}))
	}

	iconTemplate(icon = 'text') {
		const path = html`
			<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
				<rect fill="none" height="24" width="24"/>
				<path
					d="M3,10h11v2H3V10z M3,8h11V6H3V8z M3,16h7v-2H3V16z M18.01,12.87l0.71-0.71c0.39-0.39,1.02-0.39,1.41,0l0.71,0.71 c0.39,0.39,0.39,1.02,0,1.41l-0.71,0.71L18.01,12.87z M17.3,13.58l-5.3,5.3V21h2.12l5.3-5.3L17.3,13.58z"/>
			</svg>`;
		const pathImage = html`
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px" height="24px">
				<rect fill="none" height="24" width="24"/>
				<path
					d="M18.85,10.39l1.06-1.06c0.78-0.78,0.78-2.05,0-2.83L18.5,5.09c-0.78-0.78-2.05-0.78-2.83,0l-1.06,1.06L18.85,10.39z M14.61,11.81L7.41,19H6v-1.41l7.19-7.19L14.61,11.81z M13.19,7.56L4,16.76V21h4.24l9.19-9.19L13.19,7.56L13.19,7.56z M19,17.5 c0,2.19-2.54,3.5-5,3.5c-0.55,0-1-0.45-1-1s0.45-1,1-1c1.54,0,3-0.73,3-1.5c0-0.47-0.48-0.87-1.23-1.2l1.48-1.48 C18.32,15.45,19,16.29,19,17.5z M4.58,13.35C3.61,12.79,3,12.06,3,11c0-1.8,1.89-2.63,3.56-3.36C7.59,7.18,9,6.56,9,6 c0-0.41-0.78-1-2-1C5.74,5,5.2,5.61,5.17,5.64C4.82,6.05,4.19,6.1,3.77,5.76C3.36,5.42,3.28,4.81,3.62,4.38C3.73,4.24,4.76,3,7,3 c2.24,0,4,1.32,4,3c0,1.87-1.93,2.72-3.64,3.47C6.42,9.88,5,10.5,5,11c0,0.31,0.43,0.6,1.07,0.86L4.58,13.35z"/>
			</svg>`;
		return html`
			<div class="section-edit-icon" @click="${this.onClickEditSection}">
				${'image' === icon ? pathImage : path}
			</div>`;
	}

	imageTemplate() {
		if (-1 === ['static-image', 'input-image'].indexOf(this.section.section_type)) {
			return;
		}
		if (this.section.section_type === 'static-image') {
			return html`<img src="${this.section.imageOptions.img.src}" alt="" style="${this.imageStyle()}">`
		}
		let showEditIcon = -1 !== [true, 'true', 1, '1', 'yes'].indexOf(this.showEditIcon);
		let classes = ['section-edit'];
		if (-1 !== [true, 'true', 1, '1', 'yes'].indexOf(this.active)) classes.push('is-active');
		if (showEditIcon && 'input-image' === this.section.section_type) classes.push('is-image-edit');
		let imageHtml = ``;
		if (this.section.image && this.section.image.src) {
			imageHtml = html`<img src="${this.section.image.src}" style="${this.imageStyle()}">`
		} else {
			imageHtml = html`<img src="${this.section.imageOptions.img.src}" style="${this.imageStyle()}">`
		}
		return html`
			<div class="${classes.join(' ')}" style="${this.sectionImageStyle().join(';')}">
				${showEditIcon ? this.iconTemplate('image') : ''}
				${imageHtml}
			</div>`
	}

	textTemplate() {
		if (-1 === ['static-text', 'input-text'].indexOf(this.section.section_type)) {
			return;
		}
		if (this.section.section_type === 'static-text') {
			return html`${this.replaceInvertedComma(this.section.text)}`
		}
		let showEditIcon = -1 !== [true, 'true', 1, '1', 'yes'].indexOf(this.showEditIcon);
		let classes = ['section-edit'];
		if (-1 !== [true, 'true', 1, '1', 'yes'].indexOf(this.active)) classes.push('is-active');
		if (showEditIcon && 'input-text' === this.section.section_type) classes.push('is-text-edit');
		return html`
			<div class="${classes.join(' ')}">
				${showEditIcon ? this.iconTemplate() : ''}
				${this.section.text ? this.replaceInvertedComma(this.section.text) : this.replaceInvertedComma(this.section.placeholder)}
			</div>`
	}

	// Replace 'DOUBLE_INVERTED_COMMA' with '"' and 'SINGLE_INVERTED_COMMA' with "'"
	replaceInvertedComma(str) {
		// If 'str' is not a string, return it
		if ('string' !== typeof str) {
			return str;
		}
		return str
			.replace("/", '')
			.replace("\\", '')
			.replace(/DOUBLE_INVERTED_COMMA/g, '"')
			.replace(/SINGLE_INVERTED_COMMA/g, "'");
	}

	// Render the component's DOM by returning a Lit template
	render() {
		let classes = [
			'section',
			`section-type--${this.section.section_type}`,
			`section-index--${this.index}`
		]
		return html`
			<div class="${classes.join(' ')}" style="${this.sectionStyle()}">
				${this.textTemplate()}
				${this.imageTemplate()}
			</div>`;
	}
}

customElements.define('dynamic-card-layer', DynamicCardLayer);
