import {createPopper} from '@popperjs/core';
import "./index.scss";

/**
 * Create element for tooltip or popover
 *
 * @param {String} uuid
 * @param {String} content
 * @param {Object} args
 *
 * @returns {HTMLDivElement}
 */
const createElement = (uuid, content, args = {}) => {
	const defaultArgs = {
		mainClass: 'shapla-tooltip',
		title: '',
		theme: 'dark',
		container: 'body',
		uuidAttr: 'data-tooltip-for',
		role: 'tooltip',
		headerTagName: 'h3'
	};
	const options = Object.assign(defaultArgs, args);

	// Create arrow element, <div class="tooltip__arrow"></div>
	let arrowElement = document.createElement("div");
	arrowElement.classList.add(options.mainClass + '__arrow');

	// Create arrow element, <div class="tooltip__body"></div>
	let bodyElement = document.createElement("div");
	bodyElement.classList.add(options.mainClass + '__body');
	bodyElement.innerHTML = content;

	// Create main element, <div class="tooltip"></div>
	let mainElement = document.createElement("div");
	mainElement.classList.add(options.mainClass);
	mainElement.classList.add(`${options.mainClass}--${options.theme}`);
	mainElement.setAttribute(options.uuidAttr, uuid);
	mainElement.setAttribute('role', options.role);

	mainElement.appendChild(arrowElement);

	// Create header element, <h3 class="tooltip__header"></h3>
	if (options.title) {
		let headerElement = document.createElement(options.headerTagName);
		headerElement.classList.add(options.mainClass + '__header');
		headerElement.innerHTML = options.title;
		mainElement.appendChild(headerElement);
	}

	mainElement.appendChild(bodyElement);

	let containerElement = document.querySelector(options.container);
	containerElement.appendChild(mainElement);

	return mainElement;
}

/**
 * Create UUID
 *
 * @returns {string}
 */
const createUUID = () => {
	const pattern = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
	return pattern.replace(/[xy]/g, (c) => {
		const r = (Math.random() * 16) | 0;
		const v = c === 'x' ? r : ((r & 0x3) | 0x8);
		return v.toString(16);
	});
}


/**
 * Register automatically
 */
const autoRegister = (selectors = "[aria-describedby='tooltip'], [data-tooltip-target], [data-tooltip]") => {
	let elements = document.querySelectorAll(selectors);
	if (elements.length) {
		elements.forEach(element => new Tooltip(element));
	}
}

class Tooltip {

	/**
	 * @param {HTMLElement} element
	 * @param {object} options
	 */
	constructor(element, options = {}) {
		this.title = options.title || element.getAttribute('data-tooltip') ||
			element.getAttribute('title') || element.getAttribute('data-title');
		this.content = options.content || element.getAttribute('data-content') || this.title;

		this.uuid = createUUID();
		this.targetElement = this.updateTooltipTargetElement(element);
		this.options = Object.assign(this.getDefaultOptions(), options);
		this.popperInstance = null;
		this.element = null;

		// Initialize instance.
		this.init();
	}

	/**
	 * Initialize element.
	 */
	init() {
		this.options.showEvents.forEach(event => this.targetElement.addEventListener(event, () => this.show()));
		this.options.hideEvents.forEach(event => this.targetElement.addEventListener(event, () => this.hide()));
	}

	getDefaultOptions() {
		return {
			theme: 'dark',
			placement: 'auto',
			content: '',
			html: true,
			container: 'body',
			mainClass: 'shapla-tooltip',
			activeClass: 'is-active',
			uuidAttr: 'data-tooltip-for',
			removeOnClose: false,
			showEvents: ['mouseenter', 'focus'],
			hideEvents: ['mouseleave', 'blur'],
		};
	}

	/**
	 * Show tooltip
	 */
	show() {
		if (!this.content) {
			return;
		}

		this.createTooltipElementIfNotExists();

		this.element.classList.add(this.options.activeClass);

		this.popperInstance = createPopper(this.targetElement, this.element, {
			placement: this.options.placement,
			modifiers: [
				{name: 'offset', options: {offset: [0, 8],},},
				{name: 'arrow', options: {element: `.${this.options.mainClass}__arrow`}},
				{
					name: 'onChange', enabled: true, phase: 'afterWrite',
					fn: data => this._handlePopperPlacementChange(data)
				}
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
	 * Handle popper placement change
	 *
	 * @param {Object} popperData
	 * @private
	 */
	_handlePopperPlacementChange(popperData) {
		const {state} = popperData

		if (state) {
			this.element.classList.add(`is-placement-${state.placement}`);
		}
	}

	/**
	 * Validate tooltip and tooltip for elements
	 */
	createTooltipElementIfNotExists() {
		this.element = document.querySelector(`[${this.options.uuidAttr}="${this.uuid}"]`)

		if (!this.element) {
			this.element = createElement(this.uuid, this.content, {
				mainClass: this.options.mainClass,
				title: this.title,
				theme: this.options.theme,
				container: this.options.container,
				uuidAttr: this.options.uuidAttr,
			});
		}
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
		if (this.title) {
			targetElement.setAttribute('data-title', this.title);
		}
		targetElement.setAttribute('data-content', this.content);
		if (targetElement.hasAttribute('title')) {
			targetElement.removeAttribute('title');
		}
		return targetElement
	}
}

export {Tooltip, createElement, createUUID, autoRegister}
export default Tooltip;
