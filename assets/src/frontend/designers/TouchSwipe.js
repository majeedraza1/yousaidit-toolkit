const TouchSwipe = {
	data() {
		return {
			startClientX: null,
			startClientY: null,
		}
	},

	mounted() {
		document.addEventListener('touchstart', this.handleTouchStart, false);
		document.addEventListener('touchmove', this.handleTouchMove, false);
		document.addEventListener('touchend', this.handleTouchEnd, false);
	},

	methods: {

		/**
		 * @param event {TouchEvent}
		 * @param startX {Number}
		 * @param startY {Number}
		 * @private
		 */
		_getDirection(event, startX, startY) {
			let changedTouche = event.changedTouches[0];
			let xDiff = startX - changedTouche.clientX;
			let yDiff = startY - changedTouche.clientY;

			if (Math.abs(xDiff) < 1 && Math.abs(yDiff) < 1) {
				return false;
			}

			let change = 0, direction = undefined;

			if (Math.abs(xDiff) > Math.abs(yDiff)) {/*most significant*/
				change = Math.abs(xDiff);
				if (xDiff > 0) {
					direction = 'to-left';
				} else {
					direction = 'to-right';
				}
			} else {
				change = Math.abs(yDiff);
				if (yDiff > 0) {
					direction = 'to-up';
				} else {
					direction = 'to-down';
				}
			}

			return {
				direction: direction,
				change: change,
				startX: startX,
				startY: startY,
				endX: changedTouche.clientX,
				endY: changedTouche.clientY,
				changeX: Math.abs(xDiff),
				changeY: Math.abs(yDiff),
			};
		},

		/**
		 * Handle touch start
		 *
		 * @param event
		 */
		handleTouchStart(event) {
			let changedTouche = event.changedTouches[0];
			this.startClientX = changedTouche.clientX;
			this.startClientY = changedTouche.clientY;

			// this.browserWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
			// this.browserHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		},

		/**
		 * Handle touch move
		 *
		 * @param event
		 */
		handleTouchMove(event) {
			let swipe = this._getDirection(event, this.startClientX, this.startClientY);

			if (false !== swipe) {
				this.$emit('swipe:move', swipe['direction'], swipe);
			}
		},

		/**
		 * Handle touch end
		 *
		 * @param event {TouchEvent}
		 */
		handleTouchEnd(event) {
			if (!this.startClientX || !this.startClientY) {
				return;
			}

			let swipe = this._getDirection(event, this.startClientX, this.startClientY);

			if (false !== swipe) {
				this.$emit('swipe:end', swipe['direction'], swipe);
			}

			/* reset values */
			this.startClientX = null;
			this.startClientY = null;
		}
	}
};

export {TouchSwipe}
export default TouchSwipe;
