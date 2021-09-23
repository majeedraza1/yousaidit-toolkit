import {createPopper} from '@popperjs/core'
import "./index.scss";

class Tooltip {

	/**
	 * Register automatically
	 */
	static register(selectors = "[data-tooltip-target], [data-tooltip]") {
		let elements = document.querySelectorAll(selectors);
		if (elements.length) {
			elements.forEach(element => new Tooltip(element));
		}
	}

	/**
	 * @param {HTMLElement} element
	 * @param {object} options
	 */
	constructor(element, options = {}) {
		this.content = element.getAttribute('data-tooltip') || element.getAttribute('title') || options.content;
		this.uuid = this.createUUID();
		this.forElement = this.updateTooltipTargetElement(element);
		this.element = null;
		this.options = Object.assign({
			theme: 'dark',
			placement: 'auto',
			content: '',
			html: true,
			container: 'body',
			mainClass: 'shapla-tooltip',
			activeClass: 'is-active',
			removeOnClose: false,
			showEvents: ['mouseenter', 'focus'],
			hideEvents: ['mouseleave', 'blur'],
		}, options);

		this.popperInstance = null;

		// Initialize instance.
		this.init();
	}

	/**
	 * Initialize element.
	 */
	init() {
		if (!this.content) {
			return;
		}
		this.options.showEvents.forEach(event => this.forElement.addEventListener(event, () => this.show()));
		this.options.hideEvents.forEach(event => this.forElement.addEventListener(event, () => this.hide()));
	}

	/**
	 * Show tooltip
	 */
	show() {
		this.createTooltipElementIfNotExists();

		this.element.classList.add(this.options.activeClass);

		this.popperInstance = createPopper(this.forElement, this.element, {
			placement: this.options.placement,
			modifiers: [
				{
					name: 'offset',
					options: {
						offset: [0, 8],
					},
				},
				{
					name: 'arrow',
					options: {
						element: `.${this.options.mainClass}__arrow`
					}
				},
			],
		});

		// Enable the event listeners
		this.popperInstance.setOptions((options) => ({
			...options,
			modifiers: [
				...options.modifiers,
				{name: 'eventListeners', enabled: true},
			],
		}));

		this.popperInstance.update();
	}

	/**
	 * Hide tooltip
	 */
	hide() {
		this.element.classList.remove(this.options.activeClass);

		// Disable the event listeners
		if (this.popperInstance) {
			this.popperInstance.setOptions((options) => ({
				...options,
				modifiers: [
					...options.modifiers,
					{name: 'eventListeners', enabled: false},
				],
			}));
		}

		if (this.options.removeOnClose) {
			setTimeout(() => this.element.remove(), 10);
		}
	}

	/**
	 * Validate tooltip and tooltip for elements
	 */
	createTooltipElementIfNotExists() {
		this.element = document.querySelector(`[data-tooltip-for="${this.uuid}"]`)

		if (!this.element) {
			this.element = this.createTooltipElement(this.content);
		}
	}

	/**
	 * Create tooltip element
	 *
	 * @param {string} content
	 * @returns {HTMLDivElement}
	 */
	createTooltipElement(content) {
		// Create arrow element, <div class="tooltip__arrow"></div>
		let arrowElement = document.createElement("div");
		arrowElement.classList.add(this.options.mainClass + '__arrow');

		// Create arrow element, <div class="tooltip__inner"></div>
		let innerElement = document.createElement("div");
		innerElement.classList.add(this.options.mainClass + '__body');
		if (this.options.html) {
			innerElement.innerHTML = content;
		} else {
			innerElement.innerText = content;
		}

		// Create main element, <div class="tooltip"></div>
		let mainElement = document.createElement("div");
		mainElement.classList.add(this.options.mainClass);
		mainElement.classList.add(`is-theme-${this.options.theme}`);
		mainElement.setAttribute('data-tooltip-for', this.uuid);
		mainElement.setAttribute('role', 'tooltip');
		mainElement.appendChild(arrowElement);
		mainElement.appendChild(innerElement);

		let containerElement = document.querySelector(this.options.container);
		containerElement.appendChild(mainElement);

		return mainElement;
	}

	/**
	 * Update tooltip for element
	 *
	 * @param {HTMLDivElement} targetElement
	 * @returns {HTMLDivElement}
	 */
	updateTooltipTargetElement(targetElement) {
		targetElement.setAttribute('aria-describedby', 'tooltip');
		targetElement.setAttribute('data-tooltip-target', this.uuid);
		targetElement.setAttribute('data-tooltip', this.content);
		if (targetElement.hasAttribute('title')) {
			targetElement.removeAttribute('title');
		}
		return targetElement
	}

	/**
	 * Create UUID
	 *
	 * @returns {string}
	 */
	createUUID() {
		const pattern = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
		return pattern.replace(/[xy]/g, (c) => {
			const r = (Math.random() * 16) | 0;
			const v = c === 'x' ? r : ((r & 0x3) | 0x8);
			return v.toString(16);
		});
	}
}

export {Tooltip}
export default Tooltip;
