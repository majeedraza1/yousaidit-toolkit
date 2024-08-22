import {css, html, LitElement} from "lit";

export class DynamicCardBackground extends LitElement {

  static properties = {
    backgroundType: {type: String, attribute: 'background-type'},
    backgroundColor: {type: String, attribute: 'background-color'},
    backgroundImage: {type: Object, attribute: 'background-image'},
  }

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

customElements.define('dynamic-card-background', DynamicCardBackground);
