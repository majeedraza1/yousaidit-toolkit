import Tooltip from "@/shapla/tooltip";
import "./index.scss";

class Popover extends Tooltip {
	getDefaultOptions() {
		return {
			theme: 'light',
			placement: 'auto',
			title: '',
			content: '',
			html: true,
			container: 'body',
			mainClass: 'shapla-popover',
			activeClass: 'is-active',
			uuidAttr: 'data-popover-for',
			removeOnClose: false,
			showEvents: ['click'],
			hideEvents: ['blur'],
		};
	}
}

export {Popover}
export default Popover;
