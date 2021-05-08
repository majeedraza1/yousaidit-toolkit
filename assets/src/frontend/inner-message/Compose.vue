<template>
	<div class="yousaidit-inner-message-compose flex flex-col h-full">
		<div class="h-full flex flex-wrap">
			<div class="flex items-center justify-center flex-grow" id="editable-content-container">
				<editable-content
					class="shadow-lg sm:mb-4"
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
			<div>
				<div class="flex flex-col h-full bg-gray-100 w-80 ml-auto">
					<tabs>
						<tab name="Font" selected>
							<div class="font-normal px-4 text-center">Choose Font Family</div>
							<div class="inner-message-font-families">
								<div
									class="inner-message-font-family"
									v-for="_font in font_families"
									:class="{'is-selected':font_family === _font.fontFamily}"
									:style="`font-family:${_font.fontFamily}`"
									@click="setFontFamily(_font)"
								>{{ _font.label }}
								</div>
							</div>
						</tab>
						<tab name="Size">
							<div class="font-normal px-4 text-center">Choose Font Size</div>
							<div class="inner-message-font-sizes flex flex-wrap justify-center p-4">
								<div class="inner-message-font-size" v-for="_size in font_sizes" :key="_size">
									<radio-button
										:label="_size"
										:value="_size"
										v-model="font_size"
										:theme="font_size === _size?'primary':'default'"/>
								</div>
							</div>
						</tab>
						<tab name="Align">
							<template v-slot:name>
								<span class="w-6 h-6 inline-flex justify-center items-center">
									<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24"
										 width="24px" fill="#000000">
										<path d="M0 0h24v24H0z" fill="none"/>
										<path
											d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/>
									</svg>
								</span>
							</template>
							<div class="font-normal px-4 text-center">Choose Text Alignment</div>
							<div class="inner-message-text-alignments flex flex-wrap p-4">
								<div class="inner-message-text-alignment flex-grow" v-for="_alignment in alignments"
									 :key="_alignment.value">
									<radio-button
										fullwidth
										:label="_alignment.label"
										:value="_alignment.value"
										v-model="alignment"
										:theme="alignment === _alignment.value?'primary':'default'"/>
								</div>
							</div>
						</tab>
						<tab name="Color">
							<template v-slot:name>
								<span class="inline-flex w-6 h-6 bg-black"/>
							</template>
							<div class="font-normal px-4 text-center">Choose Text Color</div>
							<div class="inner-message-colors flex flex-wrap justify-center p-4">
								<div v-for="_color in colors" :key="_color.hex" class="inner-message-color p-3">
									<div @click="setFontColor(_color.hex)" class="color-box" :title="_color.label"
										 :style="`background:${_color.hex}`">{{ _color }}
									</div>
								</div>
							</div>
						</tab>
						<tab name="Emoji" nav-item-class="is-hidden-mobile">
							<template v-slot:name>
								<span class="inline-flex w-6 h-6 text-xl justify-center items-center">😁</span>
							</template>
							<div class="font-normal px-4 pb-4 text-center">Choose Emoji</div>
							<emoji-picker @select="selectEmoji"/>
						</tab>
					</tabs>
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
import selectField from 'shapla-select-field'
import {tab, tabs} from 'shapla-tabs';
import {column, columns} from 'shapla-columns';
import radioButton from 'shapla-radio-button';
import shaplaButton from 'shapla-button';
import EmojiPicker from "./EmojiPicker";

export default {
	name: "Compose",
	components: {
		EmojiPicker, EditableContent, selectField, tabs, tab, columns, column, radioButton, shaplaButton
	},
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
		setFontFamily(font) {
			this.font_family = font.fontFamily;
		},
		selectEmoji(emoji) {
			document.execCommand("insertHtml", false, emoji);
		},
		setFontColor(color) {
			this.color = color;
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
				editor = content.querySelector('.editable-content__editor');

			this.showLengthError = editor.offsetHeight > (0.95 * content.offsetHeight);
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
@import "~shapla-color-system/src/variables";
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
