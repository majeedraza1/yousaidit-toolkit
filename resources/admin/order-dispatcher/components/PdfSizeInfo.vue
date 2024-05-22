<template>
	<div class="w-full">
		<div class="md:flex flex-wrap -m-4" v-if="items.length">
			<template v-for="(item, key) in items">
				<print-card-box
					:key="`c-${key}`"
					:item="item"
					@need-force-refresh="$emit('need-force-refresh')"
				/>
			</template>
		</div>
	</div>
</template>

<script>
import {shaplaButton} from 'shapla-vue-components'
import PrintCardBox from "./PrintCardBox";

export default {
	name: "PdfSizeInfo",
	components: {PrintCardBox, shaplaButton},
	props: {
		marketplace: {type: String},
		items: {type: Array, default: () => []},
	}
}
</script>
