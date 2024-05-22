<script setup lang="ts">
import {onMounted, reactive} from "vue";
import {ShaplaCross, ShaplaIcon, ShaplaModal} from "@shapla/vue-components";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import axios from "../../utils/axios.ts";

interface ModeItemInterface {
  slug: string;
  label: string;
  menu_order?: number;
}

interface ModeStateInterface {
  moods: ModeItemInterface[];
  mood: ModeItemInterface;
  showAddNewItemModal: boolean;
  activeMode: ModeItemInterface;
  activeModeIndex: number;
  showEditItemModal: boolean;
}

const state = reactive<ModeStateInterface>({
  moods: [],
  mood: {label: '', slug: '', menu_order: 0},
  activeMode: {label: '', slug: '', menu_order: 0},
  activeModeIndex: -1,
  showAddNewItemModal: false,
  showEditItemModal: false,
})

const updateSettings = () => {
  return new Promise(resolve => {
    Spinner.show();
    axios
        .post('admin/stability-ai-logs/settings', {moods: state.moods})
        .then(response => {
          resolve(response.data.data);
          window.StackonetToolkit.stability_ai.moods = response.data.data.moods;
          state.moods = response.data.data.moods;
          Notify.success('Settings have been updated.', 'Success!');
        })
        .catch(error => {
          const responseData = error.response.data;
          if (responseData.message) {
            Notify.error(responseData.message, 'Error!');
          }
        })
        .finally(() => {
          Spinner.hide();
        })
  })
}

const addNewItem = () => {
  state.moods.push({label: state.mood.label, slug: state.mood.label, menu_order: 0});
  updateSettings().then(() => {
    state.showAddNewItemModal = false;
    state.mood = {label: '', slug: '', menu_order: 0}
  });
}
const openEditModal = (occasion: ModeItemInterface, index: number) => {
  state.activeMode = occasion;
  state.activeModeIndex = index;
  state.showEditItemModal = true;
}
const removeItem = (occasion: ModeItemInterface) => {
  Dialog.confirm('Are you sure to delete this item?').then(() => {
    state.moods.splice(state.moods.indexOf(occasion), 1);
    updateSettings();
  })
}

const updateActiveOccasionToServer = () => {
  const {moods, activeMode, activeModeIndex} = state;
  moods[activeModeIndex] = activeMode;
  state.moods = moods;
  updateSettings().then(() => {
    state.activeMode = {label: '', slug: '', menu_order: 0};
    state.activeModeIndex = -1;
    state.showEditItemModal = false;
  });
}

onMounted(() => {
  state.moods = window.StackonetToolkit.stability_ai.moods;
})
</script>

<template>
  <div class='border-box-deep'>
    <div class='mb-4'>
      <div class='flex flex-wrap -m-1'>
        <div v-for="(occasion, index) in state.moods" class='w-1/2 p-1' :key="occasion.slug">
          <div class='p-2 bg-white relative'>
            <div>
              {{ occasion.label }}
              <div class='text-xs italic text-gray-400'>{{ occasion.slug }}</div>
            </div>
            <div class='absolute top-1 right-1 space-x-2'>
              <ShaplaIcon hoverable @click="()=> openEditModal(occasion, index)">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class='w-4 h-4 fill-current'>
                  <path d="M0 0h24v24H0V0z" fill="none"/>
                  <path
                      d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
                </svg>
              </ShaplaIcon>
              <ShaplaCross @click="() => removeItem(occasion)"></ShaplaCross>
            </div>
          </div>
        </div>
      </div>
    </div>
    <button class='button' @click.prevent="state.showAddNewItemModal = true">Add New Mode</button>
    <ShaplaModal :active="state.showAddNewItemModal" title='Add New Mode'
                 @close="state.showAddNewItemModal = false">
      <table class="form-table">
        <tbody>
        <tr>
          <th>
            <label>Mode label</label>
          </th>
          <td>
            <input type='text' v-model="state.mood.label"/>
            <p class="description">Mode label. Must be unique for modes.</p>
          </td>
        </tr>
        </tbody>
      </table>
      <template v-slot:foot>
        <button class='shapla-button' @click.prevent="state.showAddNewItemModal = false">Close</button>
        <button class='shapla-button is-primary' @click.prevent="addNewItem">Save</button>
      </template>
    </ShaplaModal>
    <ShaplaModal :active="state.showEditItemModal" title='Edit Mode' @close="state.showEditItemModal = false">
      <table class="form-table">
        <tbody>
        <tr>
          <th>
            <label>Mode label</label>
          </th>
          <td>
            <input type='text' v-model="state.activeMode.label"/>
            <p class="description">Mode label. Must be unique for modes.</p>
          </td>
        </tr>
        </tbody>
      </table>
      <template v-slot:foot>
        <button class='shapla-button' @click.prevent="state.showEditItemModal = false">Close</button>
        <button class='shapla-button is-primary' @click.prevent='updateActiveOccasionToServer'>Save</button>
      </template>
    </ShaplaModal>
  </div>
</template>
