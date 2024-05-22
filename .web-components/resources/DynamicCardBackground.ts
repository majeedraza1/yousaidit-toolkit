import {css, html, LitElement} from "lit";
import {customElement, property} from 'lit/decorators.js'
import {ImageDataInterface} from "./interfaces";

@customElement('dynamic-card-background')
export class DynamicCardBackground extends LitElement {

  @property({type: String, attribute: 'background-type'})
  backgroundType = 'color'

  @property({type: String, attribute: 'background-color'})
  backgroundColor = '#ffffff'

  @property({type: Object, attribute: 'background-image'})
  backgroundImage?: ImageDataInterface;

  // Styles are applied to the shadow root and scoped to this element
  static styles = css`
      :host, .card-canvas__background, .card-canvas__background *, .card-canvas__background *:before,
      .card-canvas__background *:after {
          box-sizing: border-box
      }

      .card-canvas__background {
          position: absolute;
          width: 100%;
          height: 100%;
      }
  `

  // Render the component's DOM by returning a Lit template
  render() {
    if ('color' === this.backgroundType) {
      return html`
          <div class="card-canvas__background is-type-color"
               style="background-color:${this.backgroundColor}"></div>`
    }

    if (typeof this.backgroundImage === 'object' && Object.keys(this.backgroundImage).length) {
      return html`<img class="card-canvas__background" src="${this.backgroundImage.src}" alt="">`
    }
  }
}


declare global {
  interface HTMLElementTagNameMap {
    'dynamic-card-background': DynamicCardBackground
  }
}
