<template>
  <div>
    <h1 class="wp-heading-inline">Reminders Queue</h1>
    <hr class="wp-header-end">
    <div>
      <div class="mb-4">
        <ShaplaTableStatusList
            :statuses="state.statuses"
            @change="changeStatus"
        />
        <ShaplaTablePagination
            :current-page="store.reminders_queues_pagination.current_page"
            :per-page="store.reminders_queues_pagination.per_page"
            :total-items="store.reminders_queues_pagination.total_items"
            @paginate="changeReminderQueuePage"
        />
      </div>
      <ShaplaTable
          :items="store.reminders_queues"
          :columns="columns"
          :show-cb="false"
          :actions="state.actions"
      >

      </ShaplaTable>
      <div class="mt-4">
        <ShaplaTablePagination
            :current-page="store.reminders_queues_pagination.current_page"
            :per-page="store.reminders_queues_pagination.per_page"
            :total-items="store.reminders_queues_pagination.total_items"
            @paginate="changeReminderQueuePage"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {onMounted, reactive} from "vue";
import useAdminReminderStore from "../store.ts";
import {ShaplaTable, ShaplaTablePagination, ShaplaTableStatusList} from "@shapla/vue-components";

const store = useAdminReminderStore();
const columns = [
  {label: 'Title', key: 'title'},
  {label: 'User ID', key: 'user_id'},
  {label: 'Occasion Date', key: 'occasion_date'},
  {label: 'Remind Date', key: 'remind_date'},
  {label: 'Status', key: 'status'},
];

const state = reactive({
  status: 'pending',
  actions: [],
  statuses: [
    {key: 'pending', label: 'Pending', count: 0, active: true},
    {key: 'sent', label: 'Sent', count: 0, active: false},
    {key: 'failed', label: 'Failed', count: 0, active: false},
    {key: 'all', label: 'All', count: 0, active: false},
  ],
})


const changeReminderQueuePage = (page: number) => {
  store.reminders_queues_pagination.current_page = page;
  store.getRemindersQueues(state.status);
}
const changeStatus = (status) => {
  store.getRemindersQueues(status.key).then(() => {
    state.status = status.key;
    // loop through statuses and set active status
    state.statuses.forEach((item) => {
      item.active = item.key === status.key;
    });
  });
}

onMounted(() => {
  if (store.reminders_queues.length === 0) {
    store.getRemindersQueues(state.status).then(data => {
      const status_counts = data.status_counts as Record<string, number>
      state.statuses.forEach((status, index) => {
        state.statuses[index].count = status_counts[status.key]
      });
    });
  }
})

</script>
