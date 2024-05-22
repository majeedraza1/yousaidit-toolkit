<template>
  <div>
    <h2>Reminders</h2>
    <NoReminders v-if="state.reminders.length < 1" @create="openAddNewModal"/>
    <template v-if="Object.keys(state.reminders_grouped_by_month).length">
      <div class="mb-4 flex">
        <div class="flex-grow"></div>
        <ShaplaButton theme="primary" outline size="small" @click="openAddNewModal">Add Reminder</ShaplaButton>
      </div>

      <div v-for="(_reminders,yearMonth) in state.reminders_grouped_by_month">
        <h4 class="text-xl font-medium bg-gray-200 text-primary p-2">{{ yearMonth }}</h4>
        <div>
          <div v-for="_reminder in _reminders">
            <ReminderLoopItem
                :reminder="_reminder"
                :groups="state.reminders_groups"
                @edit="openEditModal"
                @delete="deleteReminder"
            />
          </div>
        </div>
      </div>
    </template>
    <ModalAddOrEditReminder
        v-if="state.showModal"
        :active="true"
        title="Add New Reminder"
        :reminder="state.reminder"
        :reminders_groups="state.reminders_groups"
        :countries="state.countries"
        :states="state.states"
        :remind_days="state.remind_days"
        @close="closeAddNewModal"
        @save="saveReminder"
        @update:reminder="(_reminder) => state.reminder = _reminder"
    />
    <ModalAddOrEditReminder
        v-if="state.showEditModal"
        :active="true"
        :reminder="state.activeReminder"
        :reminders_groups="state.reminders_groups"
        :countries="state.countries"
        :states="state.states"
        :remind_days="state.remind_days"
        @close="closeEditModal"
        @save="updateReminder"
        @update:reminder="(_reminder) => state.activeReminder = _reminder"
    />
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton} from "@shapla/vue-components";
import {Dialog, Spinner} from "@shapla/vanilla-components";
import axios from "../utils/axios";
import NoReminders from "./components/NoReminders.vue";
import ModalAddOrEditReminder from "./components/ModalAddOrEditReminder.vue";
import ReminderLoopItem from "./components/ReminderLoopItem.vue";
import {onMounted, reactive, watch} from "vue";
import {ReminderInterface} from "../interfaces/reminders.ts";

const state = reactive({
  showModal: false,
  showEditModal: false,
  isLoading: false,
  reminders: [],
  reminders_grouped_by_month: {},
  reminders_groups: [],
  countries: [],
  states: [],
  reminder: {
    reminder_group_id: '',
    name: '',
    occasion_date: '',
    remind_days_count: 10,
    address_line1: '',
    address_line2: '',
    postal_code: '',
    city: '',
    is_recurring: true,
    has_custom_address: false,
    country_code: 'UK',
  },
  activeReminder: {
    id: 0,
    reminder_group_id: '',
    name: '',
    occasion_date: '',
    remind_days_count: 10,
    address_line1: '',
    address_line2: '',
    postal_code: '',
    city: '',
    is_recurring: true,
    has_custom_address: false,
    country_code: 'UK',
  },
  remind_days: [
    {value: 3, label: '3 days before'},
    {value: 5, label: '5 days before'},
    {value: 7, label: '7 days before'},
    {value: 10, label: '10 days before'},
    {value: 15, label: '15 days before'},
    {value: 30, label: '30 days before'},
  ]
})

const refreshBodyClass = (active: boolean) => {
  let body = document.querySelector('body');
  if (active) {
    body.classList.add('has-shapla-modal');
  } else {
    setTimeout(() => {
      if (document.querySelectorAll('.shapla-modal.is-active').length === 0) {
        body.classList.remove('has-shapla-modal');
      }
    }, 50);
  }
}

watch(() => state.showModal, newValue => refreshBodyClass(newValue))
watch(() => state.showEditModal, newValue => refreshBodyClass(newValue))

const groupRemindersByMonth = (reminders: ReminderInterface[]) => {
  return new Promise(resolve => {
    let remindersByMonth = {};
    reminders.forEach(reminder => {
      const date = new Date(reminder.occasion_date);
      const month = date.toLocaleString('default', {month: 'long'});
      const key = month + ' ' + date.getFullYear();
      if (!remindersByMonth[key]) {
        remindersByMonth[key] = [reminder];
      } else {
        remindersByMonth[key].push(reminder);
      }
    });
    resolve(remindersByMonth);
  })
}
const openEditModal = (reminder: ReminderInterface) => {
  state.showEditModal = true;
  state.activeReminder = reminder;
}
const closeEditModal = () => {
  state.showEditModal = false;
  state.activeReminder = {
    id: 0,
    reminder_group_id: '',
    name: '',
    occasion_date: '',
    remind_days_count: 10,
    address_line1: '',
    address_line2: '',
    postal_code: '',
    city: '',
    is_recurring: true,
    has_custom_address: false,
    country_code: 'UK',
  }
}

const openAddNewModal = () => {
  state.showModal = true;
}

const closeAddNewModal = () => {
  state.showModal = false;
  state.reminder = {
    reminder_group_id: '',
    name: '',
    occasion_date: '',
    remind_days_count: 10,
    address_line1: '',
    address_line2: '',
    postal_code: '',
    city: '',
    is_recurring: true,
    has_custom_address: false,
    country_code: 'UK',
  }
}

const setReminderGroup = (value) => {
  state.reminder.reminder_group_id = value
  let activeReminderGroup = state.reminders_groups.find(reminderGroup => reminderGroup.id === value);
  if (activeReminderGroup) {
    state.reminder.occasion_date = activeReminderGroup.occasion_date;
  }
}

const getRemindersGroups = () => {
  Spinner.show();
  axios.get('reminders').then(response => {
    const data = response.data.data;
    state.reminders = data.reminders;
    state.reminder.reminder_group_id = data.groups[0].id;
    state.reminder.occasion_date = data.groups[0].reminder;
    groupRemindersByMonth(data.reminders).then(remindersByMonth => {
      state.reminders_grouped_by_month = remindersByMonth;
    });
  }).finally(() => {
    Spinner.hide();
  });
}

const saveReminder = () => {
  Spinner.show();
  axios.post('reminders', state.reminder).then(() => {
    getRemindersGroups();
    closeAddNewModal();
  }).finally(() => {
    Spinner.hide();
  });
}

const updateReminder = () => {
  Spinner.show();
  axios.put('reminders/' + state.activeReminder.id, state.activeReminder).then(() => {
    getRemindersGroups();
    closeEditModal();
  }).finally(() => {
    Spinner.hide();
  });
}

const deleteReminder = (reminder) => {
  Dialog.confirm('Are you sure to delete the reminder?').then((confirmed) => {
    if (confirmed) {
      Spinner.show();
      axios.delete('reminders/' + reminder.id).then(() => {
        getRemindersGroups();
      }).finally(() => {
        Spinner.hide();
      });
    }
  });
}

onMounted(() => {
  const _data = window.YousaiditMyAccountReminders;
  window.console.log(_data)
  state.reminders = _data.reminders;
  state.reminders_groups = _data.groups;
  state.states = _data.states;
  const createEl = document.querySelector('.reminders__action-create-new')
  if (createEl) {
    createEl.addEventListener('click', openAddNewModal);
  }
  Object.entries(_data.countries).forEach(([code, name]) => {
    state.countries.push({label: name, value: code});
  });
  groupRemindersByMonth(state.reminders).then(remindersByMonth => {
    state.reminders_grouped_by_month = remindersByMonth;
  });
})
</script>
