<template>
	<div class="editable-content-container">
		<div class="editable-content" :class="`card-size--${cardSize}`" :style="containerStyle">
			<div class="editable-content__editor"
				 :style="editorStyle"
				 contenteditable="true"
				 @focus="handleFocusEvent"
				 @input="handleInputEvent"
			>{{ placeholder }}
			</div>
		</div>
	</div>
</template>

<script>
import {imageContainer} from 'shapla-vue-components'

export default {
	name: "EditableContent",
	components: {imageContainer},
	props: {
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
		}
	},
	computed: {
		textLength() {
			return this.text.length;
		},
		paddingTop() {
			if ('a4' === this.cardSize) {
				return (100 / (426 / 2) * 303) + '%';
			}
			if ('a5' === this.cardSize) {
				return (100 / (303 / 2) * 216) + '%';
			}
			if ('a6' === this.cardSize) {
				return (100 / (216 / 2) * 154) + '%';
			}
			if ('square' === this.cardSize) {
				return (100 / (300 / 2) * 150) + '%';
			}
			return '100%';
		},
		containerStyle() {
			let styles = [];
			styles.push({'--padding-top': `${this.paddingTop}`});
			return styles;
		},
		editorStyle() {
			let styles = [];
			if (this.fontFamily) {
				styles.push({'--font-family': this.fontFamily});
			}
			if (this.fontSize) {
				styles.push({'--font-size': `${this.fontSize}px`});
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
			this.$el.querySelector('.editable-content__editor').innerHTML = newValue;
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
			this.$emit('input', this.text);
		}
	},
	mounted() {
		document.execCommand("defaultParagraphSeparator", false, "div");
		this.text = this.value;
	}
}
</script>

<style lang="scss">
.editable-content {
	border: 1px solid rgba(#000, 0.12);
	min-height: 300px;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	padding-top: var(--padding-top, 100%);

	&-container {
		position: relative;
		max-height: 90vh;

		width: 300px;
		margin: 0 auto;
		border: 1px solid rgba(#000, 0.12);
		padding: 15px;
	}

	&.card-size--square {
	}

	&.card-size--a4 {
	}

	&__editor {
		border: 1px dashed rgba(#000, 0.12);
		border-left-width: 0;
		border-right-width: 0;
		padding: 10px 30px;
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
