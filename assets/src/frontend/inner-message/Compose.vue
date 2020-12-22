<template>
	<div class="yousaidit-inner-message-compose">
		<columns multiline>
			<column :tablet="6">
				<editable-content
						placeholder="Please click here to write your message"
						:font-family="font_family"
						:font-size="font_size"
						:text-align="alignment"
						:color="color"
						v-model="message"
				/>
				<div v-if="showLengthError" class="has-error p-4 my-4">
					Don't write more text.
				</div>
			</column>
			<column :tablet="6">
				<div>
					<tabs>
						<tab name="Font" selected>
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
							<div class="inner-message-font-sizes flex flex-wrap justify-center">
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
							<div class="inner-message-text-alignments flex flex-wrap">
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
							<div class="inner-message-colors flex flex-wrap justify-center">
								<div v-for="_color in colors" :key="_color.hex" class="inner-message-color p-3">
									<div @click="setFontColor(_color.hex)" class="color-box" :title="_color.label"
											 :style="`background:${_color.hex}`">{{ _color }}
									</div>
								</div>
							</div>
						</tab>
						<tab name="Emoji" nav-item-class="is-hidden-mobile">
							<emoji-picker @select="selectEmoji"/>
						</tab>
					</tabs>
				</div>
			</column>
		</columns>
		<div class="yousaidit-inner-message__actions">
			<shapla-button theme="default" @click="$emit('close')">Cancel</shapla-button>
			<shapla-button theme="primary" @click="submit">Confirm</shapla-button>
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
			return ['10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40']
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
		message(newValue) {
			let content = this.$el.querySelector('.editable-content'),
					editor = content.querySelector('.editable-content__editor');

			console.log(content.offsetHeight);
			console.log(editor.offsetHeight);
			this.showLengthError = editor.offsetHeight > (0.6 * content.offsetHeight);
		}
	},
	mounted() {

	}
}
</script>

<style lang="scss">
@import "~shapla-color-system/src/variables";
@import "~shapla-css/src/flexbox/display";
@import "~shapla-css/src/flexbox/flex-wrap";
@import "~shapla-css/src/flexbox/justify-content";
@import "~shapla-css/src/flexbox/flex-grow";
@import "~shapla-css/src/spacing/padding";
@import "~shapla-css/src/spacing/margin";
@import url('https://fonts.googleapis.com/css2?family=Amatic+SC&family=Caveat&family=Cedarville+Cursive&family=Fontdiner+Swanky&family=Handlee&family=Indie+Flower&family=Josefin+Slab&family=Kranky&family=Lovers+Quarrel&family=Mountains+of+Christmas&family=Prata&family=Sacramento&display=swap');

.inner-message-font-families {
	max-height: 300px;
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
