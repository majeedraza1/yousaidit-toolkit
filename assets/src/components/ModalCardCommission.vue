<template>
	<modal :active="active" @close="$emit('close')" title="Commission per sale (fix)">

		<div>
			<div class="flex space-x-4 w-full my-4 items-center">
				<div class="col-marketplace">Default Commission</div>
				<div v-for="size in card_sizes">
					<text-field :label="`${size}`" v-model="commission[size]"/>
				</div>
			</div>
			<toggle name="Commission for marketplace" subtext="Leave empty to use default commission."
							v-if="Object.keys(marketplace_commission).length">
				<div class="flex space-x-4 w-full my-4 items-center" v-for="marketplace in marketplaces" :key="marketplace">
					<div class="col-marketplace">{{ marketplace }}</div>
					<div v-for="size in card_sizes" :key="size">
						<text-field
								:label="`${size}`"
								:value="marketplace_commission[marketplace][size]"
								@input="updateMarketPlaceCommission($event,marketplace,size)"
						/>
					</div>
				</div>
			</toggle>
		</div>

		<div class="mt-4">
			<text-field
					type="textarea"
					label="Note to Designer (option)"
					v-model="note_to_designer"
			/>
		</div>

		<template v-slot:foot>
			<shapla-button theme="primary" @click="updateCommission">Update</shapla-button>
		</template>
	</modal>
</template>

<script>
import {modal, shaplaButton, textField, toggle} from 'shapla-vue-components';

export default {
	name: "ModalCardCommission",
	components: {modal, textField, toggle, shaplaButton},
	props: {
		active: {type: Boolean, default: false},
		card_id: {type: [String, Number], default: 0},
		card_sizes: {type: Array, default: () => []},
		marketplaces: {type: Array, default: () => []},
		value: {
			type: Object, default: () => {
			}
		},
		commissions: {}
	},
	data() {
		return {
			commission: {},
			marketplace_commission: {},
			note_to_designer: '',
		}
	},
	watch: {
		commission: {
			deep: true, handler(newValue) {
				this.$emit('input', newValue);
			}
		},
		value: {
			deep: true, handler(newValue) {
				this.commission = newValue;
			}
		}
	},
	mounted() {
		this.commission = this.value;
		this.marketplace_commission = Object.assign(
				this.getDefaultMarketPlaceCommission(this.card_sizes, this.marketplaces),
				this.commissions
		);
	},
	methods: {
		getDefaultMarketPlaceCommission(cards, marketplaces) {
			let sizeObject = {}, marketplaceObject = {};
			cards.forEach(el => sizeObject[el] = '')
			marketplaces.forEach(el => marketplaceObject[el] = sizeObject);
			return JSON.parse(JSON.stringify(marketplaceObject));
		},
		updateCommission() {
			this.$emit('submit', this.commission, this.marketplace_commission);
		},
		updateMarketPlaceCommission(event, marketplace, size) {
			this.marketplace_commission[marketplace][size] = event;
		}
	}
}
</script>

<style lang="scss" scoped>
.col-marketplace {
	min-width: 120px;
}
</style>
