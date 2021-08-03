<template>
	<div class="bg-gray-100 px-2 py-2 rounded">
		<div class="mb-2">
			<h4 class="text-base">Section type</h4>
			<div class="w-full">
				<select class="mb-0" v-model="options.section_type">
					<option value="static-text">Static Text</option>
					<option value="static-image">Static Image</option>
					<option value="input-text">Text Input</option>
					<option value="input-image">Image Uploader</option>
				</select>
			</div>
		</div>
		<div class="mb-2">
			<h4 class="text-base">Position</h4>
			<div class="flex flex-wrap bg-gray-200 rounded">
				<div class="w-1/2 p-1">
					<label class="text-sm">Left (mm)</label>
					<input type="text" class="w-full p-1" v-model="options.position.left">
				</div>
				<div class="w-1/2 p-1">
					<label class="text-sm">Top (mm)</label>
					<input type="text" class="w-full p-1" v-model="options.position.top">
				</div>
			</div>
		</div>
		<div>
			<shapla-button theme="default" @click="cancel">Cancel</shapla-button>
			<shapla-button theme="primary" @click="confirm">Confirm</shapla-button>
		</div>
	</div>
</template>

<script>
import {shaplaButton} from 'shapla-vue-components'

const defaultOptions = {
	label: '',
	section_type: '',
	position: {left: '', top: ''}
};
export default {
	name: "LayerOptions",
	components: {shaplaButton},
	emits: ['submit'],
	data() {
		return {
			options: defaultOptions
		}
	},
	methods: {
		confirm() {
			this.$emit('submit', this.options);
			this.options = defaultOptions;
		},
		cancel() {
			this.$emit('cancel');
			this.options = defaultOptions;
		}
	}
}
</script>

<style scoped>

</style>
