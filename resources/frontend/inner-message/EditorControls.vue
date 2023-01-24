<template>
	<tabs centred>
		<tab name="Font" selected>
			<div class="font-normal px-4 text-center">Choose Font Family</div>
			<div class="inner-message-font-families">
				<div
					class="inner-message-font-family"
					v-for="_font in font_families"
					:class="{'is-selected':options.font_family === _font.fontFamily}"
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
					<radio-button :label="_size" :value="_size" v-model="options.font_size"
								  :theme="options.font_size === _size?'primary':'default'"/>
				</div>
			</div>
		</tab>
		<tab name="Align">
			<template v-slot:name>
				<span class="w-6 h-6 inline-flex justify-center items-center">
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
						<path d="M0 0h24v24H0z" fill="none"/>
						<path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/>
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
						v-model="options.alignment"
						:theme="options.alignment === _alignment.value?'primary':'default'"/>
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
					<div @click="setFontColor(_color.hex)" :title="_color.label" :style="`background:${_color.hex}`"
						 class="color-box" :class="{'is-active':options.color === _color.hex}">{{ _color }}
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
</template>

<script>
import {tabs, tab, radioButton} from 'shapla-vue-components'
import EmojiPicker from "./EmojiPicker";
import fontFamilies from "./font-family";
import colors from "./colors";

export default {
	name: "EditorControls",
	components: {tabs, tab, radioButton, EmojiPicker},
	props: {
		value: {
			type: Object, default: () => {
				return {
					font_family: '',
					font_size: '',
					alignment: '',
					color: '',
				}
			}
		}
	},
	data() {
		return {
			options: {
				font_family: '',
				font_size: '',
				alignment: '',
				color: '',
			},
			font_sizes: ['12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40'],
			alignments: [
				{label: 'Left', value: 'left'},
				{label: 'Center', value: 'center'},
				{label: 'Right', value: 'right'},
			]
		}
	},
	computed: {
		font_families() {
			return fontFamilies;
		},
		colors() {
			return colors;
		}
	},
	watch: {
		value: {
			deep: true,
			handler(newValue) {
				this.options = newValue;
			}
		},
		options: {
			deep: true,
			handler(newValue) {
				this.$emit('input', newValue);
			}
		}
	},
	methods: {
		setFontFamily(font) {
			this.options.font_family = font.fontFamily;
			this.emitChange('font-family', font);
		},
		setFontColor(hexColor) {
			this.options.color = hexColor;
			this.emitChange('color', hexColor);
		},
		selectEmoji(emoji) {
			this.emitChange('emoji', emoji);
		},
		emitChange(type, value) {
			this.$emit('change', {key: type, payload: value});
		}
	},
	mounted() {
		this.options = this.value;
	}
}
</script>
