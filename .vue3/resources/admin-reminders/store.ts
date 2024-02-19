import axios from "../utils/axios";
import {defineStore} from 'pinia'
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import {reactive, toRefs} from "vue";
import {
  PaginationDataInterface,
  ReminderExtendedInterface,
  ReminderGroupInterface,
  ReminderQueueInterface,
  ReminderUserInterface,
  SelectOptionInterface
} from "../interfaces/reminders.ts";

const useAdminReminderStore = defineStore('reminders-admin', () => {
  const state = reactive<{
    reminders: ReminderExtendedInterface[],
    reminders_pagination: PaginationDataInterface,
    reminders_groups: ReminderGroupInterface[],
    reminders_queues: ReminderQueueInterface[],
    reminders_queues_pagination: PaginationDataInterface,
    users: ReminderUserInterface[],
    product_cats: SelectOptionInterface[],
  }>({
    product_cats: [],
    users: [],
    reminders: [],
    reminders_pagination: {
      current_page: 1,
      per_page: 50,
      total_items: 0,
    },
    reminders_groups: [],
    reminders_queues: [],
    reminders_queues_pagination: {
      current_page: 1,
      per_page: 50,
      total_items: 0,
    },
  });

  const remind_days_options = [
    {value: 3, label: '3 days before'},
    {value: 5, label: '5 days before'},
    {value: 7, label: '7 days before'},
    {value: 10, label: '10 days before'},
    {value: 15, label: '15 days before'},
    {value: 30, label: '30 days before'},
  ]

  const getReminders = () => {
    Spinner.show();
    axios.get('admin/reminders', {
      params: {
        page: state.reminders_pagination.current_page,
        per_page: state.reminders_pagination.per_page
      }
    }).then(response => {
      const data = response.data.data;
      state.reminders = data.reminders;
      state.reminders_pagination = data.pagination;
      state.users = data.users;
    }).finally(() => {
      Spinner.hide();
    })
  }

  const getRemindersQueues = (status = 'all') => {
    return new Promise((resolve) => {
      Spinner.show();
      axios
        .get('admin/reminders-queue', {
          params: {
            page: state.reminders_queues_pagination.current_page,
            per_page: state.reminders_queues_pagination.per_page,
            status
          }
        })
        .then(response => {
          const data = response.data.data;
          state.reminders_queues = data.items;
          state.reminders_queues_pagination = data.pagination;
          resolve(data);
        })
        .finally(() => {
          Spinner.hide();
        })
    });
  }

  const getRemindersGroups = () => {
    Spinner.show();
    axios.get('admin/reminders/groups').then(response => {
      const data = response.data.data;
      state.reminders_groups = data.items;
      state.product_cats = data.product_cats;
    }).finally(() => {
      Spinner.hide();
    })
  }

  const createReminderGroup = (data: ReminderGroupInterface) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.post('admin/reminders/groups', data).then((response) => {
        getRemindersGroups();
        resolve(response.data.data);
      }).finally(() => {
        Spinner.hide();
      })
    })
  }

  const updateReminderGroup = (data: ReminderGroupInterface) => {
    return new Promise(resolve => {
      Spinner.show();
      axios.put('admin/reminders/groups/' + data.id, data).then((response) => {
        resolve(response.data.data);
        getRemindersGroups();
      }).finally(() => {
        Spinner.hide();
      })
    })
  }

  const deleteReminderGroup = (id: number) => {
    return new Promise(resolve => {
      Dialog.confirm('Are you sure to delete this reminder group?').then(() => {
        Spinner.show();
        axios.delete('admin/reminders/groups/' + id).then(() => {
          getRemindersGroups();
          resolve(true);
          Notify.success('Reminder group has been deleted successfully.', 'Success!');
        }).finally(() => {
          Spinner.hide();
        })
      })
    })
  }

  return {
    ...toRefs(state),
    remind_days_options,
    getReminders,
    getRemindersQueues,
    getRemindersGroups,
    createReminderGroup,
    updateReminderGroup,
    deleteReminderGroup
  }
})

export default useAdminReminderStore;
