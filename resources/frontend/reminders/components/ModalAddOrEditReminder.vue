<template>
	<modal :active="active" @close="closeEditModal" :title="title">
		<div>
			<div class="my-4">
				<select-field label="Occasion" :options="reminders_groups" :clearable="false"
				              v-model="activeReminder.reminder_group_id" label-key="title" value-key="id"/>
			</div>
			<div class="my-4">
				<text-field label="Title" help-text="Write reminder title. e.g. Mom's Birthday"
				            v-model="activeReminder.name"/>
			</div>
			<div class="my-4">
				<text-field type="date" label="Date" v-model="activeReminder.occasion_date"/>
				<div class="-mt-2">
					<shapla-checkbox v-model="activeReminder.is_recurring">Remind me every year</shapla-checkbox>
				</div>
			</div>
			<div class="my-4">
				<select-field label="Remind me" :options="remind_days" v-model="activeReminder.remind_days_count"
				              :clearable="false"/>
			</div>
		</div>
		<div class="mb-4">
			<shapla-checkbox v-model="activeReminder.has_custom_address">Add a shipping address</shapla-checkbox>
		</div>
		<div v-show="activeReminder.has_custom_address">
			<h4>Address</h4>
			<div class="-m-2 flex flex-wrap">
				<div class="w-1/2 p-2">
					<text-field label="First Name" autocomplete="given-name" v-model="activeReminder.first_name"/>
				</div>
				<div class="w-1/2 p-2">
					<text-field label="Last Name" autocomplete="family-name" v-model="activeReminder.last_name"/>
				</div>
			</div>
			<div class="mb-4">
				<select-field label="Country" autocomplete="country" :options="countries" searchable
				              :clearable="false" v-model="activeReminder.country_code"/>
			</div>
			<div class="mb-4">
				<text-field label="Address Line 1" autocomplete="address-line1"
				            v-model="activeReminder.address_line1"/>
			</div>
			<div class="mb-4">
				<text-field label="Address Line 2" autocomplete="address-line2"
				            v-model="activeReminder.address_line2"/>
			</div>
			<div class="mb-4">
				<text-field label="City" autocomplete="address-level2" v-model="activeReminder.city"/>
			</div>
			<div class="mb-4">
				<select-field label="State" autocomplete="address-level1" :options="active_states" searchable
				              :clearable="false" v-model="activeReminder.state"/>
			</div>
			<div class="mb-4">
				<text-field label="Postal Code" autocomplete="postal-code" v-model="activeReminder.postal_code"/>
			</div>
		</div>
		<template v-slot:foot>
			<shapla-button theme="default" @click="closeEditModal">Cancel</shapla-button>
			<shapla-button theme="primary" @click="updateReminder">Update</shapla-button>
		</template>
	</modal>
</template>

<script>
import {modal, shaplaCheckbox, shaplaButton, textField, selectField, radioButton} from "shapla-vue-components";

const defaultReminder = () => {
	return {
		reminder_group_id: '',
		name: '',
		occasion_date: '',
		remind_days_count: 10,
		first_name: '',
		last_name: '',
		address_line1: '',
		address_line2: '',
		postal_code: '',
		city: '',
		state: '',
		country_code: 'UK',
		is_recurring: true,
		has_custom_address: false,
	}
}

export default {
	name: "ModalAddOrEditReminder",
	components: {modal, shaplaButton, shaplaCheckbox, textField, selectField, radioButton},
	props: {
		title: {type: String, default: "Add Reminder"},
		active: {type: Boolean, default: false},
		reminder: {type: Object, default: () => defaultReminder()},
		reminders_groups: {type: Array, default: () => []},
		remind_days: {type: Array, default: () => []},
		countries: {type: Array, default: () => []},
		states: {type: Object, default: () => ({})},
	},
	data() {
		return {
			activeReminder: defaultReminder()
		}
	},
	computed: {
		active_states() {
			let states = this.states[this.activeReminder.country_code] || {},
				states_array = [];
			Object.entries(states).forEach(([code, name]) => {
				states_array.push({label: name, value: code});
			})
			return states_array;
		}
	},
	watch: {
		activeReminder: {
			handler: function (val) {
				this.$emit('update:reminder', val)
			},
			deep: true
		},
	},
	methods: {
		closeEditModal() {
			this.$emit('close');
			this.activeReminder = defaultReminder();
		},
		updateReminder() {
			this.$emit('save', this.activeReminder);
			this.activeReminder = defaultReminder();
		}
	},
	mounted() {
		this.activeReminder = Object.assign({}, defaultReminder(), this.reminder);
	}
}
</script>
