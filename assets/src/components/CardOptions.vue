<template>
	<columns multiline v-if="Object.keys(card).length">
		<column :tablet="12">
			<div class="w-full text-center">
				<h2 class="text-xl">Card Details</h2>
			</div>
		</column>
		<column :tablet="12">
			<div class="flex items-center">
				<div class="mr-4">
					<shapla-switch v-model="card.rude_card" true-value="yes" false-value="no"/>
				</div>
				<span class="flex-grow">
					<span class="bg-primary text-on-primary text-sm py-4 px-2 inline-block">Is your design classed as a rude card?</span>
					<span class="bg-gray-100 text-sm py-4 px-2 inline-block">We class rude cards what contain words, phrases or themes of an adult content: e.i. swearing or innuendos.</span>
				</span>
			</div>
		</column>
		<column :tablet="6">
			<text-field
				type="textarea" v-model="card.title" label="Title"
				:has-error="!!errors.title" :validation-text="errors.title?errors.title[0]:''" :rows="1"
				help-text="Write card title. Card title will be used as product title."
			/>
		</column>
		<column :tablet="6" style="display: none">
			<select-field
				v-model="card.sizes" :options="card_sizes" label="Size" multiple
				:has-error="!!errors.sizes" :validation-text="errors.sizes?errors.sizes[0]:''"
				help-text="Choose card size(s). You need to upload file for each selected size on next step."
			/>
		</column>
		<column :tablet="6">
			<select-field
				v-model="card.categories_ids" :options="card_categories" label="Category"
				label-key="name" value-key="id" multiple
				:searchable="card_categories.length > 5"
				singular-selected-text="category selected"
				plural-selected-text="categories selected"
				:has-error="!!errors.categories_ids"
				:validation-text="errors.categories_ids?errors.categories_ids[0]:''"
				help-text="Choose card category. Try to choose only one category but not more than three categories."
			/>
		</column>
		<column :tablet="6">
			<select-field
				v-model="card.tags_ids" :options="card_tags" label="Tags"
				label-key="name" value-key="id" multiple searchable
				singular-selected-text="tag selected"
				plural-selected-text="tags selected"
				:has-error="!!errors.tags_ids"
				:validation-text="errors.tags_ids?errors.tags_ids[0]:''"
				help-text="Choose card tags. Choose as many tags as you need. Make sure tags are relevant to your card."
			/>
		</column>
		<column :tablet="6">
			<div class="additional_tags">
				<shapla-switch v-model="has_suggest_tags" true-value="yes" false-value="no"
							   label="Suggest a new tag."/>
				<text-field
					v-if="has_suggest_tags === 'yes'" label="Tags" type="textarea" :rows="1"
					help-text="Write your suggested tags, separate by comma if you have multiple suggestion"
					v-model="card.suggest_tags"
				/>
			</div>
		</column>
		<column :tablet="6" v-for="attribute in card_attributes" :key="attribute.attribute_name"
				v-if="card_attributes.length">
			<select-field
				v-model="card.attributes[attribute.attribute_name]"
				:options="attribute.options" :label="attribute.attribute_label"
				label-key="name" value-key="id" multiple :searchable="attribute.options.length > 5"
				:has-error="!!(errors.attributes && errors.attributes[attribute.attribute_name])"
				:validation-text="errors.attributes?errors.attributes[0]:''"
			/>
		</column>
		<column :tablet="12">
			<div class="market-places">
				<h3 class="font-normal text-lg text-primary uppercase">Where to list your card</h3>
				<h4 class="font-normal text-sm">You can choose other market places for us to list your card on</h4>
				<columns multiline>
					<column :tablet="2" v-for="marketPlace in market_places" :key="marketPlace.key">
						<div>
							<div>
								<shapla-switch
									:value="marketPlace.key"
									v-model="card.market_places"
									:readonly="marketPlace.key === 'yousaidit'"
								/>
							</div>
							<img :src="marketPlace.logo" :alt="marketPlace.key">
						</div>
					</column>
				</columns>
			</div>
		</column>
	</columns>
</template>

<script>
import {columns, column, shaplaSwitch, textField, selectField} from "shapla-vue-components";

export default {
	name: "CardOptions",
	components: {columns, column, shaplaSwitch, textField, selectField},
	props: {
		value: {
			type: Object, default: () => {
				return {}
			}
		},
		errors: {
			type: Object, default: () => {
				return {}
			}
		},
		card_sizes: {type: Array, default: () => []},
		market_places: {type: Array, default: () => []},
		card_categories: {type: Array, default: () => []},
		card_attributes: {type: Array, default: () => []},
		card_tags: {type: Array, default: () => []},
		has_suggest_tags: {type: String, default: 'no'},
	},
	data() {
		return {
			card: {}
		}
	},
	watch: {
		value: {
			deep: true,
			handler(newValue) {
				this.card = newValue;
			}
		},
		card: {
			deep: true,
			handler(newValue) {
				this.$emit('input', newValue);
			}
		}
	},
	mounted() {
		this.card = this.value;
	}
}
</script>

<style scoped>

</style>
