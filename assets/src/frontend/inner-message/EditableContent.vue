<template>
	<div class="editable-content-container" :style="containerStyle">
		<div class="editable-content">
			<div class="editable-content__editor"
			     :style="editorStyle"
			     :contenteditable="isEditable?'true':'false'"
			     @focus="handleFocusEvent"
			     @input="handleInputEvent"
			/>
		</div>
	</div>
</template>

<script>
import {imageContainer} from 'shapla-vue-components'
import {
	calculateElementHeight,
	calculateElementPadding,
	calculateFontSizeScale,
	cardSizeFromName
} from '@/utils/helper.js'

export default {
	name: "EditableContent",
	components: {imageContainer},
	props: {
		editable: {type: Boolean, default: true},
		value: {type: String},
		placeholder: {type: String, default: ''},
		cardSize: {type: String, default: 'square'},
		fontFamily: {type: String,},
		fontSize: {type: [String, Number], default: 12},
		textAlign: {type: String, default: 'center'},
		color: {type: String, default: '#000'}
	},
	data() {
		return {
			text: '',
			canvas_height: 0,
			canvas_width: 0,
			canvas_padding: 0,
			cardSizes: [],
			editableContent: null,
			editableContentEditor: null,
			showLengthError: false,
			isEditable: false,
			showPlaceholder: true,
		}
	},
	computed: {
		textLength() {
			return this.text.length;
		},
		paddingTop() {
			let sizes = cardSizeFromName(this.cardSize);
			if (sizes[0] && sizes[1]) {
				return (100 / (sizes[0] / 2) * sizes[1]) + '%';
			}

			return '100%';
		},
		containerStyle() {
			let styles = [];
			styles.push({'height': `${this.canvas_height}px`});
			return styles;
		},
		editorStyle() {
			let styles = [];
			if (this.fontFamily) {
				styles.push({'--font-family': this.fontFamily});
			}
			if (this.fontSize) {
				// If card size 150mm, then font size 40pt
				// If card size 1mm, then font size 40pt/150mm
				// Convert element width from px to mm
				// If element size is 200mm, then font size is {(40pt/150mm) * 200mm}
				let fontSize = calculateFontSizeScale(this.cardSizes[0] / 2, this.canvas_width, this.fontSize);
				styles.push({'--font-size': `${fontSize}pt`});
			}
			if (this.textAlign) {
				styles.push({'--text-align': this.textAlign});
			}
			if (this.color) {
				styles.push({'--color': this.color});
			}
			return styles;
		}
	},
	watch: {
		value(newValue) {
			this.text = newValue;
		},
		showLengthError(newValue) {
			this.$emit('lengthError', newValue);
		},
		editable(newValue) {
			setTimeout(() => this.isEditable = newValue, 500);
		}
	},
	methods: {
		handleFocusEvent(event) {
			let text = event.target.innerHTML;
			if (text.indexOf(this.placeholder) !== -1) {
				event.target.innerHTML = '';
			}
		},
		handleInputEvent(event) {
			this.text = event.target.innerHTML;
			this.showLengthError = this.editableContentEditor.offsetHeight > (0.90 * this.editableContent.offsetHeight);
			this.$emit('input', this.text);
		},
		calculate_canvas_dimension() {
			this.cardSizes = cardSizeFromName(this.cardSize);
			this.canvas_height = calculateElementHeight(this.cardSize, this.$el);
			this.canvas_width = this.$el.offsetWidth;
		},
		calculate_canvas_edge_padding() {
			// If card size 150mm, then padding 8mm
			// If card size 1mm, then padding 8mm/150mm
			// Convert element width from px to mm
			// If element size is 200mm, then padding is {(8mm/150mm) * 200mm}
			// Convert mm to px
			this.canvas_padding = calculateElementPadding(this.cardSizes[0] / 2, this.canvas_width);
		},
		updateTextAndPlaceholder() {
			const lines = this.value
				// .replace('<div class="editable-content__html">', '')
				.split('<div>')
				.map(_text => _text.replace('</div>', ''))
				.filter(line => typeof line === 'string' && line.length);
			if (lines.length) {
				this.showPlaceholder = false;
				let contentEl = this.$el.querySelector('.editable-content__editor')
				lines.forEach(line => {
					let divEl = document.createElement('div');
					divEl.innerText = line;
					contentEl.append(divEl);
				})
			} else {
				let contentEl = this.$el.querySelector('.editable-content__editor')
				let divEl = document.createElement('div');
				divEl.innerText = this.placeholder;
				contentEl.append(divEl);
			}
		}
	},
	mounted() {
		document.execCommand("defaultParagraphSeparator", false, "div");
		this.text = this.value;
		setTimeout(() => {
			this.calculate_canvas_dimension();
			this.calculate_canvas_edge_padding();

			this.editableContent = this.$el.querySelector('.editable-content');
			this.editableContentEditor = this.$el.querySelector('.editable-content__editor');
			this.isEditable = this.editable;
			this.updateTextAndPlaceholder();
		}, 100);
	}
}
</script>

<style lang="scss">
.editable-content {
	align-items: center;
	border: 1px solid rgba(#000, 0.12);
	display: flex;
	height: 100%;
	justify-content: center;
	position: relative;

	&-container {
		border: 1px solid rgba(#000, 0.12);
		margin: 0 auto;
		padding: var(--container-padding, 15px);
		position: relative;
		width: 100%;
	}

	&__editor {
		border: 1px dashed rgba(#000, 0.12);
		border-left-width: 0;
		border-right-width: 0;
		padding: 0;
		width: 100%;
		position: absolute;
		top: 50%;
		left: 0;
		transform: translateY(-50%);

		&, & * {
			color: var(--color, #000);
			font-family: var(--font-family, 'inherit');
			font-size: var(--font-size, 15px);
			text-align: var(--text-align, center);
			margin: 0;
		}
	}
}
</style>
