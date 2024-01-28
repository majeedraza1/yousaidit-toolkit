import {css, html, LitElement} from "lit";
import {DynamicCardPayloadInterface} from "./interfaces";

export class DynamicCardCanvas extends LitElement {
  // Styles are applied to the shadow root and scoped to this element
  static styles = css`
    :host, .canvas, .canvas *, .canvas *:before, .canvas *:after {
      box-sizing: border-box
    }

    .canvas {
      background-color: white;
      border: 1px dotted rgba(0, 0, 0, 0.12);
      display: flex;
      position: relative;
      height: var(--item-height, 100%);
      width: var(--item-width, 100%);
      flex-shrink: 0;
      overflow: hidden;
    }
  `

  // Creates a reactive property that triggers rendering
  static properties = {
    options: {type: Object},
    height: {type: String},
    width: {type: String},
    showEditIcon: {type: Boolean, attribute: 'show-edit-icon'},
    activeSectionIndex: {type: Number, attribute: 'active-section-index'},
    cardWidthMM: {type: Number, attribute: 'card-width-mm'},
    cardHeightMM: {type: Number, attribute: 'card-height-mm'},
    elementWidthMM: {type: Number, attribute: 'element-width-mm'},
    elementHeightMM: {type: Number, attribute: 'element-height-mm'},
  }

  constructor() {
    super();
    this.options = {card_size: '', card_bg_type: '', card_bg_color: '', card_background: {}, card_items: []};
    this.elementWidthMM = 0;
    this.elementHeightMM = 0;
  }

  onClickCardLayer(event) {
    this.dispatchEvent(new CustomEvent('edit:layer', {detail: event.detail}));
  }

  // Render the component's DOM by returning a Lit template
  render() {
    const options: DynamicCardPayloadInterface = this.options;
    return html`
        <div class="canvas">
            <dynamic-card-background
                    background-type="${options.card_bg_type}"
                    background-color="${options.card_bg_color}"
                    background-image='${JSON.stringify(options.card_background)}'
            ></dynamic-card-background>
            ${this.options.card_items.map((section, index) =>
                    html`
                        <dynamic-card-layer
                                index="${index}"
                                active="${index === this.activeSectionIndex}"
                                section='${JSON.stringify(section)}'
                                show-edit-icon="${this.showEditIcon}"
                                card-width-mm="${this.cardWidthMM}"
                                card-height-mm="${this.cardHeightMM}"
                                element-width-mm="${this.elementWidthMM}"
                                element-height-mm="${this.elementHeightMM}"
                                @edit="${this.onClickCardLayer}"
                        ></dynamic-card-layer>`
            )}
        </div>`
  }
}

customElements.define('dynamic-card-canvas', DynamicCardCanvas);
