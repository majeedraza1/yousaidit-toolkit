<template>
	<div class="editable-content" :class="`card-size--${cardSize}`">
		<div class="editable-content__editor"
			 :style="editorStyle"
			 contenteditable="true"
			 @focus="handleFocusEvent"
			 @input="handleInputEvent"
		>{{ placeholder }}
		</div>
	</div>
</template>

<script>
export default {
	name: "EditableContent",
	props: {
		value: {type: String},
		placeholder: {type: String, default: ''},
		cardSize: {
			type: String,
			default: 'square',
			validator: value => ['square', 'a4', 'a5', 'a6'].indexOf(value) !== -1
		},
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
	padding-top: 100%;

	&.card-size--square {
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
