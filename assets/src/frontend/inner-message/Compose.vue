<template>
	<div class="yousaidit-inner-message-compose flex flex-col h-full -m-4 p-4 sm:bg-gray-100">
		<div class="h-full flex flex-wrap justify-center">
			<div class="sm:w-full flex items-center justify-center flex-grow" id="editable-content-container">
				<editable-content
					v-if="active"
					class="shadow-lg sm:mb-4 sm:bg-white md:ml-auto md:mr-auto"
					style="max-width: 400px;"
					placeholder="Please click here to write your message"
					:font-family="font_family"
					:font-size="font_size"
					:text-align="alignment"
					:color="color"
					v-model="message"
					:card-size="cardSize"
				/>
				<div v-if="showLengthError" class="has-error p-4 my-4">
					Oops... your message is too long, please keep inside the box.
				</div>
			</div>
			<div class="sm:w-full sm:mt-4 sm:mb-4">
				<div class="flex flex-col h-full bg-gray-100 w-80 ml-auto">
					<editor-controls v-model="editor_control_data" @change="onChangeEditorControls"/>
					<div class="flex-grow"></div>
					<div class="flex space-x-2 p-4 mt-4">
						<shapla-button theme="primary" outline @click="$emit('close')" class="flex-grow">Cancel
						</shapla-button>
						<shapla-button theme="primary" @click="submit" class="flex-grow">{{ btnText }}</shapla-button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import EditableContent from "./EditableContent";
import fontFamilies from "./font-family";
import colors from "./colors";
import {shaplaButton} from 'shapla-vue-components';
import EmojiPicker from "./EmojiPicker";
import EditorControls from "./EditorControls";

export default {
	name: "Compose",
	components: {EditorControls, EmojiPicker, EditableContent, shaplaButton},
	props: {
		cardSize: {type: String},
		active: {type: Boolean, default: false},
		innerMessage: {type: Object},
		btnText: {type: String, default: 'Add to Basket'}
	},
	data() {
		return {
			message: '',
			font_family: "'Indie Flower', cursive",
			font_size: '18',
			alignment: 'center',
			color: '#1D1D1B',
			showLengthError: false,
		}
	},
	computed: {
		editor_control_data: {
			get() {
				return this._data;
			},
			set() {

			}
		},
		font_families() {
			return fontFamilies;
		},
		colors() {
			return colors;
		},
		font_sizes() {
			return ['12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40']
		},
		alignments() {
			return [
				{label: 'Left', value: 'left'},
				{label: 'Center', value: 'center'},
				{label: 'Right', value: 'right'},
			];
		}
	},
	methods: {
		onChangeEditorControls(args) {
			if ('font-family' === args.key) {
				this.font_family = args.payload.fontFamily;
			}
			if ('color' === args.key) {
				this.color = args.payload;
			}
			if ('emoji' === args.key) {
				document.execCommand("insertHtml", false, args.payload);
			}
		},
		submit() {
			this.$emit('submit', this._data);
		}
	},
	watch: {
		innerMessage: {
			deep: true,
			handler(newValue) {
				this.message = newValue.content;
				this.font_family = newValue.font;
				this.font_size = newValue.size;
				this.alignment = newValue.align;
				this.color = newValue.color;
			}
		},
		message() {
			let content = this.$el.querySelector('.editable-content'),
				editor = content ? content.querySelector('.editable-content__editor') : null;

			if (editor && content) {
				this.showLengthError = editor.offsetHeight > (0.95 * content.offsetHeight);
			}
		},
		active(newValue) {
			if (newValue) {
				let container = this.$el.querySelector('#editable-content-container');
			}
		}
	},
	mounted() {
	}
}
</script>

<style lang="scss">
@import "~shapla-css/src/colors";
@import url('https://fonts.googleapis.com/css2?family=Amatic+SC&family=Caveat&family=Cedarville+Cursive&family=Fontdiner+Swanky&family=Handlee&family=Indie+Flower&family=Josefin+Slab&family=Kranky&family=Lovers+Quarrel&family=Mountains+of+Christmas&family=Prata&family=Sacramento&display=swap');

.inner-message-font-families {
	max-height: 64vh;
	overflow-y: auto;
}

.inner-message-font-family {
	padding: 10px;
	font-size: 16px;
	line-height: 24px;

	&:not(.is-selected):hover {
		background-color: rgba(#000, 0.04);
	}

	&.is-selected {
		background-color: var(--shapla-primary-alpha, rgba(0, 0, 0, 0.04));
	}
}

.inner-message-colors,
.inner-message-font-sizes,
.inner-message-text-alignments {
	margin: -5px;
}

.inner-message-color,
.inner-message-font-size,
.inner-message-text-alignment {
	padding: 5px;
}

.color-box {
	text-indent: -999999px;
	cursor: pointer;
	height: 48px;
	width: 48px;

	&.is-active {
		border-radius: 99px;
	}
}

.emoji-picker {
	width: 100% !important;
}

.container-emoji {
	height: 310px !important;
}

.has-error {
	background-color: $error;
	color: $on-error;
}

@media screen and (max-width: 768px) {
	.is-hidden-mobile {
		display: none !important;
	}
}
</style>
