import {css, html, LitElement} from "lit";
import {customElement, property} from 'lit/decorators.js'
import {DynamicCardItemInterface} from "./interfaces";

@customElement('dynamic-card-layer')
export class DynamicCardLayer extends LitElement {

  @property({type: String})
  active = false;

  @property({type: Object})
  section: DynamicCardItemInterface = {
    label: '', section_type: 'static-text', position: {left: 0, top: 0}, text: '', placeholder: '',
  };

  @property({type: String, attribute: 'show-edit-icon'})
  showEditIcon = 'no';

  @property({type: Number, attribute: 'index'})
  index = -1;

  @property({type: Number, attribute: 'card-width-mm'})
  cardWidthMM = 0;

  @property({type: Number, attribute: 'card-height-mm'})
  cardHeightMM = 0;

  @property({type: Number, attribute: 'element-width-mm'})
  elementWidthMM = 0;

  @property({type: Number, attribute: 'element-height-mm'})
  elementHeightMM = 0;

  @property({type: Number, attribute: 'element-width-px'})
  elementWidthPX = 0;

  @property({type: Number, attribute: 'element-height-px'})
  elementHeightPX = 0;

  private fontFamilies: { fontUrl: string; key: string; label: string }[] = [];

  constructor() {
    super();
    this.fontFamilies = window.StackonetToolkit.fonts || window.DesignerProfile.fonts;
  }

  sectionStyle() {
    // when card width 150mm, then element width 40mm
    // when card width 1mm, then element width 40mm/150mm
    const {userOptions} = this.section;
    let top = parseFloat(this.section.position.top.toString());
    if (userOptions && userOptions.position.top) {
      top += parseFloat(userOptions.position.top.toString())
    }
    let left = parseFloat(this.section.position.left.toString());
    if (userOptions && userOptions.position.left) {
      left += parseFloat(userOptions.position.left.toString());
    }
    let styles = [];
    // _top = Math.round(100 / this.elementHeightMM * this.section.position.top),
    // _left = Math.round(100 / this.elementWidthMM * this.section.position.left);
    // _top = Math.round(100 * (top / this.cardHeightMM)),
    // _left = Math.round(100 * (left / this.cardWidthMM));

    styles.push(`--card-width-mm: ${this.cardWidthMM}`);
    styles.push(`--card-height-mm: ${this.cardHeightMM}`);
    styles.push(`--element-width-mm: ${this.elementWidthMM}`);
    styles.push(`--element-height-mm: ${this.elementHeightMM}`);
    styles.push(`--element-width-px: ${this.elementWidthPX}`);
    styles.push(`--element-height-px: ${this.elementHeightPX}`);
    styles.push(`--from-left-mm: ${left}`);
    styles.push(`--from-top-mm: ${top}`);
    styles.push(`--scaling-factor: ${(this.elementWidthMM / this.cardWidthMM).toFixed(3)}`);

    if (userOptions) {
      styles.push(`--rotate: ${userOptions.rotate}deg`);
      if (userOptions.zoom > 0) {
        let zoom = 1 + (userOptions.zoom / 100)
        styles.push(`--zoom: ${zoom}`);
      } else if (userOptions.zoom < 0) {
        let zoom = 1 + (userOptions.zoom / 100)
        styles.push(`--zoom: ${zoom}`);
      }
    }

    if (-1 !== ['static-text', 'input-text'].indexOf(this.section.section_type) && this.section.textOptions) {
      const fontFamily = this.fontFamilies.find(_font => {
        if (this.section.textOptions) {
          return _font.key === this.section.textOptions.fontFamily
        }
        return false;
      });

      if (fontFamily) {
        styles.push(`font-family: ${fontFamily.label}`);
      }
      let fontSize = parseFloat(this.section.textOptions.size.toString());
      styles.push(`font-size: calc(${fontSize}px * var(--scaling-factor))`);
      styles.push(`text-align: ${this.section.textOptions.align}`);
      styles.push(`color: ${this.section.textOptions.color}`);

      if (this.section.textOptions.rotation) {
        styles.push(`--rotate: ${this.section.textOptions.rotation}deg`);
      }

      if (this.section.textOptions.spacing) {
        styles.push(`--spacing: ${this.section.textOptions.spacing}pt`);
      }

      if (['center', 'right'].indexOf(this.section.textOptions.align) !== -1) {
        styles.push('width: 100%');
        styles.push('left: 0%');
      }
    }

    if (-1 !== ['static-image', 'input-image'].indexOf(this.section.section_type)) {
      styles = styles.concat(this.sectionImageStyle())
      if (left > 0) {
        styles.push('right: calc(-1 * 100% * (var(--from-left-mm) / var(--card-width-mm)))');
        styles.push('left: auto')
      }
    }

    return styles.join(';');
  }

  sectionImageStyle() {
    let styles = []
    styles.push('max-width: calc(100% * var(--zoom, 1))');
    styles.push('max-height: calc(100% * var(--zoom, 1))');
    if (this.section.imageOptions) {
      if (['center', 'right'].indexOf(this.section.imageOptions.align) !== -1) {
        styles.push('width: 100%');
        // styles.push('left: 0%');
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
    }
    return styles;
  }

  imageStyle() {
    // when card width 150mm, then width 101mm
    // when card width 15mm, then width 101mm/150mm * 15mm
    let styles = [];
    if (this.section.imageOptions) {
      let width = Math.round((parseInt(this.section.imageOptions.width.toString()) / this.cardWidthMM) * 100);
      styles.push(`width: ${width}%`);
    }
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
    if (this.section.section_type === 'static-image' && this.section.imageOptions) {
      return html`<img src="${this.section.imageOptions.img.src}" alt="" style="${this.imageStyle()}">`
    }
    let showEditIcon = -1 !== [true, 'true', 1, '1', 'yes'].indexOf(this.showEditIcon);
    let classes = ['section-edit'];
    if (-1 !== [true, 'true', 1, '1', 'yes'].indexOf(this.active)) classes.push('is-active');
    if (showEditIcon && 'input-image' === this.section.section_type) classes.push('is-image-edit');
    let imageHtml = html``;
    if (this.section.image && this.section.image.src) {
      imageHtml = html`<img src="${this.section.image.src}" style="${this.imageStyle()}">`
    } else if (this.section.imageOptions) {
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
            <div style="padding:0 8px;" class="section-text-content">
                ${this.section.text ? this.replaceInvertedComma(this.section.text) : this.replaceInvertedComma(this.section.placeholder)}
            </div>
        </div>`
  }

  // Replace 'DOUBLE_INVERTED_COMMA' with '"' and 'SINGLE_INVERTED_COMMA' with "'"
  replaceInvertedComma(str: any) {
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
            <div class="section-border"></div>
        </div>`;
  }

  // Styles are applied to the shadow root and scoped to this element
  // :host { position: absolute; width: 100%; height: 100%; }
  static styles = css`
      :host, .section, .section *, .section *:before, .section *:after {
          box-sizing: border-box
      }

      .section {
          position: absolute;
          line-height: 1;
          top: calc(100% * (var(--from-top-mm) / var(--card-height-mm)));
          left: calc(100% * (var(--from-left-mm) / var(--card-width-mm)));
      }

      .section-edit.is-image-edit {
          border: 1px dotted rgba(0, 0, 0, 0.12);
          position: relative;
          transition: 300ms all ease-in-out;
      }

      .section-edit.is-image-edit:hover,
      .section-edit.is-image-edit.is-active {
          background-color: var(--shapla-primary-alpha);
      }

      .section-edit.is-image-edit {
          //transform: scale(var(--zoom, 1));
      }

      .section-edit img {
          transform: scale(var(--zoom, 1)) rotate(var(--rotate, 0));
          transform-origin: top left;
      }

      .section-text-content {
          transform: rotate(var(--rotate, 0));
          transform-origin: top;
          letter-spacing: calc(var(--spacing, normal) * var(--scaling-factor, 1));
      }

      .section-edit.is-image-edit.is-active {
          border: 1px dotted rgba(0, 0, 0, 0.12);
      }

      .section-edit-icon {
          background-color: white;
          position: absolute;
          top: 0;
          left: 0;
          width: 32px;
          height: 32px;
          overflow: hidden;
          display: flex;
          justify-content: center;
          align-items: center;
          border: 1px solid var(--shapla-primary);
          cursor: pointer;
          transition: 300ms all ease-in-out;
          z-index: 99;
      }

      .section-edit-icon:hover {
          border-radius: 16px;
      }

      .section-edit-icon svg {
          display: block;
          fill: #323232;
      }

      .section-edit.is-active + .section-border {
          border: 1px dotted rgba(0, 0, 0, 0.12);
          position: absolute;
          width: 100%;
          height: 100%;
          top: 0;
          left: 0;
          z-index: 10;
          transform: scale(var(--zoom, 1)) rotate(var(--rotate, 0));
          transform-origin: top left;
      }
  `;
}

declare global {
  interface HTMLElementTagNameMap {
    'dynamic-card-layer': DynamicCardLayer
  }

  interface Window {
    StackonetToolkit: {
      fonts: { fontUrl: string; key: string; label: string }[];
    };
    DesignerProfile: {
      fonts: { fontUrl: string; key: string; label: string }[];
    }
  }
}
