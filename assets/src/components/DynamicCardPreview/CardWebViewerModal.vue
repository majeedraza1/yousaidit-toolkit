<template>
	<modal :active="active" @close="$emit('close')" title="Dynamic Card Preview" content-size="full">
		<card-web-viewer :options="options"/>
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
	}
}
</script>

<style lang="scss">

</style>
