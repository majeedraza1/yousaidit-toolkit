<template>
	<div class="mark-as-shipped-info">
		<ShaplaColumns multiline>
			<ShaplaColumn :tablet="12">
				<ShaplaSelect
					label="Select shipping provider"
					help-text="Choose shipping provider (required)"
					label-key="name"
					value-key="code"
					:options="carriers"
					v-model="shipping.carrierCode"
				/>
			</ShaplaColumn>
			<ShaplaColumn :tablet="12">
				<ShaplaInput
					type="date"
					label="Shipping Date"
					v-model="shipping.shipDate"
					help-text="Shipping Date (optional). If empty, today's date will be use."
				/>
			</ShaplaColumn>
			<ShaplaColumn :tablet="12">
				<ShaplaInput
					label="Shipping tracking number"
					v-model="shipping.trackingNumber"
					help-text="Shipping tracking number (optional)."
				/>
			</ShaplaColumn>
			<ShaplaColumn :tablet="12">
				<ShaplaCheckbox v-model="shipping.notifyCustomer">Notify Customer</ShaplaCheckbox>
			</ShaplaColumn>
			<ShaplaColumn :tablet="12">
				<ShaplaCheckbox v-model="shipping.notifySalesChannel">Notify Sales Channel</ShaplaCheckbox>
			</ShaplaColumn>
			<ShaplaColumn :tablet="12">
				<ShaplaButton theme="primary" @click.prevent="shipped" fullwidth>Mark as Shipped</ShaplaButton>
			</ShaplaColumn>
		</ShaplaColumns>
	</div>
</template>

<script lang="ts" setup>
import {ShaplaSelect, ShaplaColumns, ShaplaColumn, ShaplaInput, ShaplaCheckbox, ShaplaButton} from '@shapla/vue-components';
import {onMounted, reactive} from "vue";
const emit = defineEmits<{
  shipped:[value:any]
}>()
const props = defineProps( {
  orderId: {type: [Number, String]}
})
const shipping =reactive( {
  carrierCode: 'royal_mail',
  shipDate: '',
  trackingNumber: '',
  notifyCustomer: false,
  notifySalesChannel: true,
})
const carriers = [
  {code: 'royal_mail', name: 'Royal Mail'}
]

const shipped = () => {
  let data = shipping;
  data['orderId'] = props.orderId;
  emit('shipped', data);
}
const defaultDate = ()=> {
  let today = new Date(),
      month = today.getMonth() + 1,
      date = today.getDate();

  const _month = month < 10 ? `0${month}` : `${month}`;
  const _date = date < 10 ? `0${date}` : `${date}`;

  return `${today.getFullYear()}-${_month}-${_date}`;
}

onMounted(()=>{
  shipping.shipDate = defaultDate();
})
</script>
