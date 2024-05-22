<template>
	<modal :active="active" @close="$emit('close')" title="Dynamic Card Preview" content-size="full">
		<card-web-viewer
			v-if="active && Object.keys(options).length"
			:args="options"
			:upload-url="uploadUrl"
			:images="images"
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
		options: {type: Object},
		uploadUrl: {type: String, default: ''},
		images: {type: Array, default: () => []}
	},
	methods: {
		handleEditSection(section) {

		}
	}
}
</script>

<style lang="scss">

</style>
