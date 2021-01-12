<template>
	<div>
		<art-work-item
			v-for="_product in item.products"
			:key="_product.id"
			:product="_product"
		/>
	</div>
</template>

<script>
import ArtWorkItem from "@/admin/order-dispatcher/components/ArtWorkItem";

export default {
	name: "ArtWorkItems",
	components: {ArtWorkItem},
	props: {
		item: {type: Object},
	}
}
</script>

<style lang="scss" scoped>

</style>
