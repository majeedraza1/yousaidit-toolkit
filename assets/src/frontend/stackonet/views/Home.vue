<template>
	<div>
		Frontend sample
	</div>
</template>

<script>
	export default {
		name: "Home",
		data() {
			return {}
		},
		mounted() {
			this.$store.commit('SET_LOADING_STATUS', false);
		},
		methods: {}
	}
</script>

<style lang="scss">

</style>
