<template>
	<div>
		<h1 class="wp-heading-inline">Packing Slip</h1>
		<hr class="wp-header-end mb-4">
		<div>
			<orders/>
		</div>
	</div>
</template>

<script>
import Orders from "../components/Orders";

export default {
	name: "PagePackingSlip",
	components: {Orders}
}
</script>
