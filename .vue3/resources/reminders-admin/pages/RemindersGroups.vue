<template>
  <div>
    <h1 class="wp-heading-inline">Reminders Groups</h1>
    <hr class="wp-header-end">

    <div>
      <ShaplaTable
          :items="store.reminders_groups"
          :columns="columns"
          :selected-items="state.selectedItems"
          :actions="actions"
          @select:item="selectItems"
          @click:action="handleAction"
      >
        <template v-slot:product_categories="item">
          <div class="flex flex-wrap">
            <template v-for="cat in item.row.product_categories">
              <template v-for="_cat in store.product_cats">
                <ShaplaChip size="small" small v-if="cat == _cat.value" class="tag">
                  {{ _cat.label }}
                </ShaplaChip>
              </template>
            </template>
          </div>
        </template>
      </ShaplaTable>
    </div>

    <ShaplaModal v-if="state.showAddModal" :active="state.showAddModal" title="Add New Group" @close="closeAddNewModal">
      <div class="mb-4">
        <ShaplaInput label="Title" help-text="Write group title. e.g. Birthday"
                     v-model="state.group.title"/>
      </div>
      <div class="mb-4">
        <ShaplaSelect label="Primary product category" v-model="state.group.product_categories"
                      :options="store.product_cats" :multiple="true" :searchable="true"
        />
      </div>
      <div class="mb-4">
        <ShaplaInput type="date" label="Date" v-model="state.group.occasion_date"/>
      </div>
      <div class="mb-4">
        <ShaplaInput label="Call to Action Link" v-model="state.group.cta_link"
                     help-text="If you leave this empty, this will be generated automatically from selected product category."/>
      </div>
      <div class="mb-4">
        <ShaplaInput label="Menu order" v-model="state.group.menu_order"/>
      </div>
      <template v-slot:foot>
        <ShaplaButton theme="default" @click="closeAddNewModal">Cancel</ShaplaButton>
        <ShaplaButton theme="primary" @click="createNewGroup">Add New</ShaplaButton>
      </template>
    </ShaplaModal>
    <shapla-modal v-if="state.showUpdateModal" :active="state.showUpdateModal" title="Edit Reminder Group"
                  @close="closeUpdateModal">
      <div style="min-height: 400px">
        <div class="mb-4">
          <ShaplaInput label="Title" help-text="Write group title. e.g. Birthday"
                       v-model="state.activeGroup.title"/>
        </div>
        <div class="mb-4">
          <ShaplaSelect label="Primary product category" v-model="state.activeGroup.product_categories"
                        :options="store.product_cats" :multiple="true" :searchable="true"
          />
        </div>
        <div class="mb-4">
          <ShaplaInput type="date" label="Date" v-model="state.activeGroup.occasion_date"/>
        </div>
        <div class="mb-4">
          <ShaplaInput label="Call to Action Link" v-model="state.activeGroup.cta_link"
                       help-text="If you leave this empty, this will be generated automatically from selected product category."/>
        </div>
        <div class="mb-4">
          <ShaplaInput label="Menu order" v-model="state.activeGroup.menu_order"/>
        </div>
      </div>
      <template v-slot:foot>
        <ShaplaButton theme="default" @click="closeUpdateModal">Cancel</ShaplaButton>
        <ShaplaButton theme="primary" @click="updateGroup">Update</ShaplaButton>
      </template>
    </shapla-modal>

    <div class="fixed bottom-4 right-4">
      <ShaplaButton fab theme="primary" size="large" @click="state.showAddModal = true">+</ShaplaButton>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {ShaplaButton, ShaplaChip, ShaplaInput, ShaplaModal, ShaplaSelect, ShaplaTable} from '@shapla/vue-components';
import {onMounted, reactive} from "vue";
import useAdminReminderStore from "../store.ts";
import {ReminderGroupInterface} from "../../interfaces/reminders.ts";

const state = reactive<{
  showAddModal: boolean,
  showUpdateModal: boolean,
  selectedItems: number[],
  group: ReminderGroupInterface,
  activeGroup: ReminderGroupInterface,
}>({
  showAddModal: false,
  showUpdateModal: false,
  group: {id: 0, title: '', product_categories: [], cta_link: '', menu_order: 0, occasion_date: ''},
  activeGroup: {id: 0, title: '', product_categories: [], cta_link: '', menu_order: 0, occasion_date: ''},
  selectedItems: [],
});
const columns = [
  {label: 'Title', key: 'title'},
  {label: 'Related Product Category', key: 'product_categories'},
  {label: 'CTA Link', key: 'cta_link'},
  {label: 'Default Date', key: 'occasion_date'},
  {label: 'Order', key: 'menu_order', numeric: true},
];
const actions = [
  {label: 'Edit', key: 'edit'},
  {label: 'View Email', key: 'view-email-template'},
  {label: 'Delete', key: 'delete'},
];
const store = useAdminReminderStore();

const closeAddNewModal = () => {
  state.showAddModal = false;
}
const closeUpdateModal = () => {
  state.showUpdateModal = false;
}

const createNewGroup = () => {
  store.createReminderGroup(state.group).then(() => {
    closeAddNewModal();
    state.group = {id: 0, title: '', product_categories: [], cta_link: '', menu_order: 0, occasion_date: ''}
  });
}

const updateGroup = () => {
  store.updateReminderGroup(state.activeGroup).then(() => {
    closeUpdateModal();
    state.activeGroup = {id: 0, title: '', product_categories: [], cta_link: '', menu_order: 0, occasion_date: ''}
  })
}

const selectItems = (items: number[]) => {
  state.selectedItems = items;
}
const handleAction = (action: string, item: ReminderGroupInterface) => {
  if (action === 'edit') {
    state.activeGroup = item;
    state.showUpdateModal = true;
  } else if (action === 'view-email-template') {
    let a = document.createElement('a');
    a.href = item.email_template_url;
    a.target = '_blank';
    a.click();
    a.remove();
  } else if (action === 'delete') {
    store.deleteReminderGroup(item.id).then(() => {
      state.selectedItems = [];
    }).catch(error => {
      console.log(error.message);
    });
  }
}

onMounted(() => {
  if (store.reminders_groups.length < 1) {
    store.getRemindersGroups();
  }
})
</script>
