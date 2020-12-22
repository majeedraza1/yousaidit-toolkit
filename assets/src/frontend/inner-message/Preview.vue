<template>
	<div class="yousaidit-inner-message-preview">
		<div class="yousaidit-inner-message-preview__placeholder">
			<slot></slot>
		</div>
	</div>
</template>

<script>
export default {
	name: "Preview",
	props: {
		hasBorder: {type: Boolean, default: true}
	},
	computed: {},
	mounted() {
		let width = this.$el.offsetWidth;
		console.log(width);
		this.$el.style.height = `${width}px`;
	}
}
</script>

<style lang="scss">
.yousaidit-inner-message-preview {
	border: 1px solid rgba(#000, 0.12);
	padding: 30px 30px;
	min-height: 300px;
	display: flex;
	align-items: center;
	justify-content: center;

	&__placeholder {
		border: 1px dashed rgba(#000, 0.12);
		padding: 1rem;
		width: 100%;
		text-align: center;
	}
}
</style>
