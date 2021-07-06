<template>
	<div class="bg-gray-100 px-2 py-2 rounded">
		<div class="mb-2">
			<h4 class="text-base">Section type</h4>
			<div class="w-full">
				<select class="mb-0">
					<option value="">Text</option>
					<option value="">Image</option>
				</select>
			</div>
		</div>
		<div class="">
			<h4 class="text-base">Position</h4>
			<div class="flex flex-wrap bg-gray-200 rounded">
				<div class="w-1/2 p-1">
					<label class="text-sm">Top (%)</label>
					<input type="text" class="w-full p-1">
				</div>
				<div class="w-1/2 p-1">
					<label class="text-sm">Right (%)</label>
					<input type="text" class="w-full p-1">
				</div>
				<div class="w-1/2 p-1">
					<label class="text-sm">Bottom (%)</label>
					<input type="text" class="w-full p-1">
				</div>
				<div class="w-1/2 p-1">
					<label class="text-sm">Left (%)</label>
					<input type="text" class="w-full p-1">
				</div>
			</div>
		</div>
	</div>
</template>

<script>
const defaultOptions = {
	section_type: '',
	position: {top: '', right: '', bottom: '', left: ''}
};
export default {
	name: "LayerOptions",
	data() {
		return {
			options: defaultOptions
		}
	}
}
</script>

<style scoped>

</style>
