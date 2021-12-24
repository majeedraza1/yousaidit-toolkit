import {LitElement, html, css} from "lit";

export class DynamicCardBackground extends LitElement {
	// Styles are applied to the shadow root and scoped to this element
	static styles = css`
	.card-canvas__background { position: absolute; width: 100%; height: 100%; }
	`

	// Creates a reactive property that triggers rendering
	static  properties = {
		options: {type: Object}
	}

	// Render the component's DOM by returning a Lit template
	render() {
		if (this.options.card_bg_type === 'color') {
			return html`
				<div class="card-canvas__background is-type-color"
					 style="background-color:${this.options.card_bg_color}"></div>`
		}

		if (Object.keys(this.options.card_background).length) {
			return html`<img class="card-canvas__background" src="${this.options.card_background.src}" alt="">`
		}
	}
}

customElements.define('dynamic-card-background', DynamicCardBackground);
