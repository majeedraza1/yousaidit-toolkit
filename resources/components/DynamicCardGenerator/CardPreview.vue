<template>
	<div :class="`shadow-lg card-canvas card-canvas--${card_size}`" :style="canvas_styles">
		<div class="card-canvas__background is-type-color" v-if="background_type === 'color'"
		     :style="`background-color:${background.color.hex}`"></div>
		<img class="card-canvas__background" v-if="Object.keys(image).length" :src="image.src" alt="">
		<div v-for="(section,index) in sections"
		     class="card-canvas__section"
		     :class="sectionClass(section,index)"
		     :style="sectionStyle(section)"
		>
			<template v-if="section.section_type === 'static-text'">
				{{ section.text }}
			</template>
			<template v-if="section.section_type === 'input-text'">
				{{ section.placeholder }}
			</template>
			<template v-if="section.section_type === 'static-image' || section.section_type === 'input-image'">
				<img :src="section.imageOptions.img.src" alt="" :style="sectionImageStyle(section)">
			</template>
			<template v-else>
				<!--						{{ section }}-->
			</template>
		</div>
	</div>
</template>

<script>
export default {
	name: "CardPreview",
	props: {
		card_size: {type: String},
		card_sizes: {type: Array},
		canvas_width: {type: [String, Number]},
		canvas_scale_ration: {type: Number},
		image: {type: Object},
		background: {type: Object},
		sections: {type: Array},
	},
	computed: {
		font_families() {
			return window.DesignerProfile.fonts;
		},
		canvas_styles() {
			return {
				'width': `${this.canvas_width}px`
			}
		},
		card_size_in_mm() {
			let size = this.card_sizes.find(item => item.value === this.card_size);
			return {width: (size.width / 2) + 1, height: size.height};
		},
		card_width_in_mm() {
			return this.card_size_in_mm.width;
		},
		card_height_in_mm() {
			return this.card_size_in_mm.height;
		},
		background_type() {
			if (!this.background) {
				return 'color';
			}
			return this.background.type;
		}
	},
	watch: {
		sections: {
			deep: true,
			handler(newValue) {
				this.update_fonts_import(newValue);
			}
		}
	},
	methods: {
		mm_to_px(mm) {
			return Math.round(mm * 3.7795275591);
		},
		px_to_mm(px) {
			return Math.round(px * 0.2645833333);
		},
		points_to_mm(points) {
			return Math.round(points * 0.352778);
		},
		sectionClass(section, index) {
			return [`section-type--${section.section_type}`, `section-index--${index}`]
		},
		sectionStyle(section) {
			let styles = [],
				_top = Math.round(100 / this.card_height_in_mm * section.position.top),
				_left = Math.round(100 / this.card_width_in_mm * section.position.left);

			styles.push({left: `${_left}%`});
			styles.push({top: `${_top}%`});

			if (section.section_type === 'static-image' || section.section_type === 'input-image') {
				if (['center', 'right'].indexOf(section.imageOptions.align) !== -1) {
					styles.push({width: '100%', left: '0%'})
				}
				if ('center' === section.imageOptions.align) {
					styles.push({width: '100%', display: 'flex', justifyContent: 'center'})
				}
				if ('right' === section.imageOptions.align) {
					styles.push({width: '100%', display: 'flex', justifyContent: 'flex-end'})
				}
			}
			if (section.section_type === 'static-text' || section.section_type === 'input-text') {
				let fontSize = Math.round((section.textOptions.size / this.canvas_scale_ration) * 2),
					fontFamily = this.font_families.find(_font => _font.key === section.textOptions.fontFamily);
				styles.push({
					fontFamily: `"${fontFamily.label}"`,
					fontSize: `${fontSize}pt`,
					textAlign: `${section.textOptions.align}`,
					color: `${section.textOptions.color}`,
				})
				if (['center', 'right'].indexOf(section.textOptions.align) !== -1) {
					styles.push({width: '100%', left: '0%'})
				}
			}
			return styles
		},
		sectionImageStyle(section) {
			let styles = [], width = Math.round(100 / this.card_width_in_mm * section.imageOptions.width);
			styles.push({width: `${width}%`});
			return styles;
		},
		update_fonts_import(sections) {
			let string = '';
			sections.forEach(section => {
				let is_text = ["static-text", "input-text"].indexOf(section.section_type) !== -1,
					font = is_text ? this.font_families.find(_font => _font.key === section.textOptions.fontFamily) : false;
				if (font && is_text) {
					string += `@font-face {
					  font-family: '${font.label}';
					  font-style: normal;
					  font-weight: normal;
					  src: url(${font.fontUrl}) format('truetype');
					}\n`;
				}
			});
			string += '';

			let styleSheet = document.querySelector('#card_preview_dynamic_font_import');
			if (styleSheet) {
				styleSheet.innerHTML = string;
			} else {
				let styles = document.createElement('style');
				styles.setAttribute('id', 'card_preview_dynamic_font_import');
				styles.innerHTML = string;
				document.head.appendChild(styles);
			}
		},
	},
	mounted() {

	}
}
</script>

<style lang="scss">
.card-canvas {
	background-image: url("../../img/viewport-bg.png");
	border: 1px dotted rgba(#000, .12);
	display: flex;
	height: 100%;
	position: relative;
	flex-shrink: 0;
	overflow: hidden;

	&__background {
		position: absolute;
		width: 100%;
		height: 100%;
	}

	&__section {
		position: absolute;
		line-height: 1;
	}
}
</style>
