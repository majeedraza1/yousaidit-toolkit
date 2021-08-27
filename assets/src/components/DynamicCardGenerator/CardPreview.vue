<template>
	<div :class="`card-canvas card-canvas--${card_size}`" :style="canvas_styles">
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
			<template v-if="section.section_type === 'static-image'">
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
	name: "LayerCanvas",
	props: {
		card_size: {type: String},
		canvas_width: {type: [String, Number]},
		canvas_scale_ration: {type: Number},
		image: {type: Object},
		sections: {type: Array},
	},
	computed: {
		canvas_styles() {
			return {
				'width': `${this.canvas_width}px`
			}
		},
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
				top = this.mm_to_px(section.position.top / this.canvas_scale_ration),
				left = this.mm_to_px(section.position.left / this.canvas_scale_ration);
			styles.push({left: `${left}px`});
			styles.push({top: `${top}px`});
			if (section.section_type === 'static-image' || section.section_type === 'input-image') {
				if (['center', 'right'].indexOf(section.imageOptions.align) !== -1) {
					styles.push({width: '100%', left: '0'})
				}
				if ('center' === section.imageOptions.align) {
					styles.push({width: '100%', display: 'flex', justifyContent: 'center'})
				}
				if ('right' === section.imageOptions.align) {
					styles.push({width: '100%', display: 'flex', justifyContent: 'flex-end'})
				}
			}
			if (section.section_type === 'static-text' || section.section_type === 'input-text') {
				let fontSize = this.mm_to_px(this.points_to_mm(section.textOptions.size) / this.canvas_scale_ration);
				styles.push({
					fontFamily: `${section.textOptions.fontFamily}`,
					fontSize: `${fontSize}px`,
					textAlign: `${section.textOptions.align}`,
					color: `${section.textOptions.color}`,
				})
				if (['center', 'right'].indexOf(section.textOptions.align) !== -1) {
					styles.push({width: '100%', left: '0'})
				}
			}
			return styles
		},
		sectionImageStyle(section) {
			let styles = [], width = this.mm_to_px(section.imageOptions.width / this.canvas_scale_ration)
			styles.push({width: `${width}px`});
			return styles;
		},
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
