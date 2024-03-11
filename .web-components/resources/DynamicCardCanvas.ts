import {css, html, LitElement} from "lit";
import {customElement, property} from "lit/decorators.js";


@customElement('dynamic-card-canvas')
export class DynamicCardCanvas extends LitElement {

  @property({type: Object, attribute: 'options'})
  options = {
    card_size: '', card_bg_type: '', card_bg_color: '', card_background: {}, card_items: []
  }

  @property({type: Boolean, attribute: 'show-edit-icon'})
  showEditIcon = false;

  @property({type: Number, attribute: 'active-section-index'})
  activeSectionIndex = -1;

  @property({type: Number, attribute: 'card-width-mm'})
  cardWidthMM = 0;

  @property({type: Number, attribute: 'card-height-mm'})
  cardHeightMM = 0;

  @property({type: Number, attribute: 'element-width-mm'})
  elementWidthMM = 0;

  @property({type: Number, attribute: 'element-height-mm'})
  elementHeightMM = 0;

  constructor() {
    super();
    this.options = {card_size: '', card_bg_type: '', card_bg_color: '', card_background: {}, card_items: []};
    this.elementWidthMM = 0;
    this.elementHeightMM = 0;
  }

  onClickCardLayer(event: CustomEvent) {
    this.dispatchEvent(new CustomEvent('edit:layer', {detail: event.detail}));
  }

  // Render the component's DOM by returning a Lit template
  render() {
    return html`
        <div class="canvas">
            <dynamic-card-background
                    background-type="${this.options.card_bg_type}"
                    background-color="${this.options.card_bg_color}"
                    background-image='${JSON.stringify(this.options.card_background)}'
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

  // Styles are applied to the shadow root and scoped to this element
  static get styles() {
    return css`
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
  }
}


declare global {
  interface HTMLElementTagNameMap {
    'dynamic-card-canvas': DynamicCardCanvas
  }
}
