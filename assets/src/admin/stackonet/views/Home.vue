<template>
	<div>
		<h1 class="wp-heading-inline">Home Page</h1>
		<hr class="wp-header-end">

		<button class="button" @click="showConfirm">Show Confirm</button>
	</div>
</template>

<script>
export default {
	name: "Home",
	methods: {
		showConfirm() {
			this.$dialog.confirm('Message Goes here').then(confirmed => {
				console.log(confirmed);
			})
		}
	}
}
</script>

<style lang="scss">

</style>
