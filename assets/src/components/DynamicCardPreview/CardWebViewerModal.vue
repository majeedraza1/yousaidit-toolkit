<template>
	<modal :active="active" @close="$emit('close')" title="Dynamic Card Preview" content-size="full">
		<card-web-viewer
			v-if="active"
			:options="options"
			@edit:section="handleEditSection"
		/>
	</modal>
</template>

<script>
import {modal} from 'shapla-vue-components';
import CardWebViewer from "./CardWebViewer";

export default {
	name: "CardWebViewerModal",
	components: {CardWebViewer, modal},
	props: {
		active: {type: Boolean, default: false},
		options: {type: Object}
	},
	methods: {
		handleEditSection(section) {

		}
	}
}
</script>

<style lang="scss">

</style>
