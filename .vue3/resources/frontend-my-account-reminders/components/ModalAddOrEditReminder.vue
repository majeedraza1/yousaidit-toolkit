<template>
  <ShaplaModal :active="active" @close="closeEditModal" :title="title">
    <div>
      <div class="my-4">
        <ShaplaSelectSimple
            label="Occasion"
            :options="reminders_groups"
            v-model="state.activeReminder.reminder_group_id"
            label-key="title"
            value-key="id"
        />
      </div>
      <div class="my-4">
        <ShaplaInput label="Title" help-text="Write reminder title. e.g. Mom's Birthday"
                     v-model="state.activeReminder.name"/>
      </div>
      <div class="my-4">
        <ShaplaInput type="date" label="Date" v-model="state.activeReminder.occasion_date"/>
        <div class="-mt-2">
          <ShaplaCheckbox v-model="state.activeReminder.is_recurring">Remind me every year</ShaplaCheckbox>
        </div>
      </div>
      <div class="my-4">
        <ShaplaSelectSimple
            label="Remind me"
            :options="remind_days"
            v-model="state.activeReminder.remind_days_count"
            label-key="label"
            value-key="value"
        />
      </div>
    </div>
    <div class="mb-4">
      <ShaplaCheckbox v-model="state.activeReminder.has_custom_address">Add a shipping address</ShaplaCheckbox>
    </div>
    <div v-show="state.activeReminder.has_custom_address">
      <h4>Address</h4>
      <div class="-m-2 flex flex-wrap">
        <div class="w-1/2 p-2">
          <ShaplaInput label="First Name" autocomplete="given-name" v-model="state.activeReminder.first_name"/>
        </div>
        <div class="w-1/2 p-2">
          <ShaplaInput label="Last Name" autocomplete="family-name" v-model="state.activeReminder.last_name"/>
        </div>
      </div>
      <div class="mb-4">
        <ShaplaSelect label="Country" autocomplete="country" :options="countries" searchable
                      :clearable="false" v-model="state.activeReminder.country_code"/>
      </div>
      <div class="mb-4">
        <ShaplaInput label="Address Line 1" autocomplete="address-line1"
                     v-model="state.activeReminder.address_line1"/>
      </div>
      <div class="mb-4">
        <ShaplaInput label="Address Line 2" autocomplete="address-line2"
                     v-model="state.activeReminder.address_line2"/>
      </div>
      <div class="mb-4">
        <ShaplaInput label="City" autocomplete="address-level2" v-model="state.activeReminder.city"/>
      </div>
      <div class="mb-4">
        <ShaplaSelect label="State" autocomplete="address-level1" :options="active_states" searchable
                      :clearable="false" v-model="state.activeReminder.state"/>
      </div>
      <div class="mb-4">
        <ShaplaInput label="Postal Code" autocomplete="postal-code" v-model="state.activeReminder.postal_code"/>
      </div>
    </div>
    <template v-slot:foot>
      <ShaplaButton theme="default" @click="closeEditModal">Cancel</ShaplaButton>
      <ShaplaButton theme="primary" @click="updateReminder">Update</ShaplaButton>
    </template>
  </ShaplaModal>
</template>

<script lang="ts" setup>
import {ShaplaButton, ShaplaCheckbox, ShaplaInput, ShaplaModal, ShaplaSelect} from "@shapla/vue-components";
import {computed, onMounted, reactive, watch} from "vue";
import {ReminderInterface} from "../../interfaces/reminders.ts";
import ShaplaSelectSimple from "./ShaplaSelectSimple.vue";

const defaultReminder = (): ReminderInterface => {
  return {
    id: 0,
    user_id: 0,
    reminder_group_id: 0,
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
    remind_date: '',
    is_recurring: true,
    is_in_queue: false,
    has_custom_address: false,
  }
}

const props = defineProps({
  title: {type: String, default: "Add Reminder"},
  active: {type: Boolean, default: false},
  reminder: {type: Object, default: () => ({})},
  reminders_groups: {type: Array, default: () => []},
  remind_days: {type: Array, default: () => []},
  countries: {type: Array, default: () => []},
  states: {type: Object, default: () => ({})},
})

const emit = defineEmits<{
  "update:reminder": [value: ReminderInterface];
  close: [];
  save: [value: ReminderInterface];
}>();

const state = reactive({
  activeReminder: defaultReminder()
})

const active_states = computed(() => {
  let states = props.states[state.activeReminder.country_code] || {},
      states_array = [];
  Object.entries(states).forEach(([code, name]) => {
    states_array.push({label: name, value: code});
  })
  return states_array;
})

const closeEditModal = () => {
  emit('close');
  state.activeReminder = defaultReminder();
}
const updateReminder = () => {
  emit('save', state.activeReminder);
  state.activeReminder = defaultReminder();
}

watch(() => state.activeReminder, newValue => emit('update:reminder', newValue), {deep: true})

onMounted(() => {
  state.activeReminder = Object.assign({}, defaultReminder(), props.reminder);
})
</script>
