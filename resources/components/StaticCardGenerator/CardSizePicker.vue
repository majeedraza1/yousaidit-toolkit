<template>
	<columns multiline>
		<column :tablet="12">
			<div class="w-full text-center">
				<h2 class="text-xl">Choose Card size</h2>
				<p>Please check the correct size of your design</p>
			</div>
		</column>
		<column :tablet="6" class="md:flex items-center justify-end">
			<div @click="card_size = 'square'"
			     class="border border-solid border-gray-200 w-36 h-36 flex items-center justify-center bg-gray-100 cursor-pointer"
			     :class="{'border-primary':card_size === 'square'}"
			>
				<div class="text-lg">Square</div>
			</div>
		</column>
		<column :tablet="6" class="md:flex items-center justify-start" v-if="false">
			<div @click="card_size = 'a'"
			     class="border border-solid border-gray-200 w-36 h-44 flex flex-col items-center justify-center bg-gray-100 cursor-pointer"
			     :class="{'border-primary':card_size === 'a'}"
			>
				<div class="text-lg">A Size</div>
				<div class="text-sm">(A6 & A5)</div>
			</div>
		</column>
	</columns>
</template>

<script>
import {columns, column} from 'shapla-vue-components';

export default {
	name: "CardSizePicker",
	components: {columns, column},
	props: {
		card_size: {type: String, default: 'square'}
	},
	methods: {
		chooseCardSize(size) {
			this.$emit('change:size', size);
		}
	}
}
</script>
