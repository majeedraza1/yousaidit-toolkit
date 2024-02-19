<template>
  <div>
    <h1 class="wp-heading-inline">Reminders</h1>
    <hr class="wp-header-end">
    <div>
      <div class="mb-4">
        <ShaplaTablePagination
            :current-page="store.reminders_pagination.current_page"
            :per-page="store.reminders_pagination.per_page"
            :total-items="store.reminders_pagination.total_items"
            @paginate="changeReminderPage"
        />
      </div>
      <ShaplaTable
          :items="store.reminders"
          :columns="columns"
          :show-cb="false"
          :actions="actions"
          @click:action="handleAction"
      >
        <template v-slot:reminder_group_id="data">
          {{ reminders_groups_title[data.row.reminder_group_id] }}
        </template>
        <template v-slot:user="data">
          <a :href="data.row.user.edit_url">#{{ data.row.user.id }} - {{ data.row.user.display_name }}</a>
        </template>
        <template v-slot:remind_days_count="data">
          {{ remind_days_options_title[data.row.remind_days_count] }}
        </template>
      </ShaplaTable>
      <div class="mt-4">
        <ShaplaTablePagination
            :current-page="store.reminders_pagination.current_page"
            :per-page="store.reminders_pagination.per_page"
            :total-items="store.reminders_pagination.total_items"
            @paginate="changeReminderPage"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import useAdminReminderStore from "../store.ts";
import {computed, onMounted} from "vue";
import {ShaplaTable, ShaplaTablePagination} from "@shapla/vue-components";

const columns = [
  {label: 'Title', key: 'name'},
  {label: 'User', key: 'user'},
  {label: 'Occasion Date', key: 'occasion_date'},
  {label: 'Remind Date', key: 'remind_date'},
  {label: 'Group', key: 'reminder_group_id'},
  {label: 'Remind', key: 'remind_days_count', numeric: true},
];
const actions = [
  {label: 'View Email', key: 'view-email-template'},
]

const store = useAdminReminderStore();

const handleAction = (action: string, item) => {
  if (action === 'view-email-template') {
    let a = document.createElement('a');
    a.href = item.email_template_url;
    a.target = '_blank';
    a.click();
    a.remove();
  }
}
const changeReminderPage = (page:number) => {
  store.reminders_pagination.current_page = page;
  store.getReminders()
}

const reminders_groups_title = () => {
  let groups = {};
  store.reminders_groups.forEach(group => {
    groups[group.id] = group.title;
  });
  return groups;
}
const remind_days_options_title = computed(() => {
  let options = {};
  store.remind_days_options.forEach(option => {
    options[option.value] = option.label;
  });
  return options;
})

onMounted(() => {
  if (store.reminders.length < 1) {
    store.getReminders();
  }
  if (store.reminders_groups.length < 1) {
    store.getRemindersGroups();
  }
})
</script>
