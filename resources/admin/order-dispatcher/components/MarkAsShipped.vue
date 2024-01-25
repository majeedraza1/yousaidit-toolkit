<template>
	<div class="mark-as-shipped-info">
		<columns multiline>
			<column :tablet="12">
				<select-field
					label="Select shipping provider"
					help-text="Choose shipping provider (required)"
					label-key="name"
					value-key="code"
					:options="carriers"
					v-model="shipping.carrierCode"
				/>
			</column>
			<column :tablet="12">
				<text-field
					type="date"
					label="Shipping Date"
					v-model="shipping.shipDate"
					help-text="Shipping Date (optional). If empty, today's date will be use."
				/>
			</column>
			<column :tablet="12">
				<text-field
					label="Shipping tracking number"
					v-model="shipping.trackingNumber"
					help-text="Shipping tracking number (optional)."
				/>
			</column>
			<column :tablet="12">
				<shapla-checkbox v-model="shipping.notifyCustomer">Notify Customer</shapla-checkbox>
			</column>
			<column :tablet="12">
				<shapla-checkbox v-model="shipping.notifySalesChannel">Notify Sales Channel</shapla-checkbox>
			</column>
			<column :tablet="12">
				<shapla-button theme="primary" @click.prevent="shipped" fullwidth>Mark as Shipped</shapla-button>
			</column>
		</columns>
	</div>
</template>

<script>
import {selectField, columns, column, textField, shaplaCheckbox, shaplaButton} from 'shapla-vue-components';

export default {
	name: "MarkAsShipped",
	components: {selectField, columns, column, textField, shaplaCheckbox, shaplaButton},
	props: {
		orderId: {type: [Number, String]}
	},
	data() {
		return {
			shipping: {
				carrierCode: 'royal_mail',
				shipDate: '',
				trackingNumber: '',
				notifyCustomer: false,
				notifySalesChannel: true,
			},
			carriers: [
				{code: 'royal_mail', name: 'Royal Mail'}
			]
		}
	},
	methods: {
		shipped() {
			let data = this.shipping;
			data['orderId'] = this.orderId;
			this.$emit('shipped', data);
		},
		defaultDate() {
			let today = new Date(), month = today.getMonth() + 1, date = today.getDate();

			month = month < 10 ? `0${month}` : `${month}`;
			date = date < 10 ? `0${date}` : `${date}`;

			return `${today.getFullYear()}-${month}-${date}`;
		}
	},
	mounted() {
		this.shipping.shipDate = this.defaultDate();
	}
}
</script>
