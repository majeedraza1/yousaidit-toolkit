<script setup lang="ts">
import {onMounted, reactive} from "vue";
import {ShaplaCross, ShaplaIcon, ShaplaModal} from "@shapla/vue-components";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import axios from "../utils/axios.ts";

interface OccasionItemInterface {
  slug: string;
  label: string;
  menu_order?: number;
}

interface OccasionStateInterface {
  occasions: OccasionItemInterface[];
  occasion: OccasionItemInterface;
  activeOccasion: OccasionItemInterface;
  activeOccasionIndex: number;
  showAddNewItemModal: boolean;
  showEditItemModal: boolean;
}

const state = reactive<OccasionStateInterface>({
  occasions: [],
  occasion: {label: '', slug: '', menu_order: 0},
  activeOccasion: {label: '', slug: '', menu_order: 0},
  activeOccasionIndex: -1,
  showAddNewItemModal: false,
  showEditItemModal: false,
})

const updateSettings = () => {
  return new Promise(resolve => {
    Spinner.show();
    axios
        .post('ai-content-generator/settings', {occasions: state.occasions})
        .then(response => {
          resolve(response.data.data);
          window.StackonetToolkit.occasions = response.data.data.occasions;
          state.occasions = response.data.data.occasions;
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
  state.occasions.push({label: state.occasion.label, slug: state.occasion.label, menu_order: 0});
  updateSettings().then(() => {
    state.showAddNewItemModal = false;
    state.occasion = {label: '', slug: '', menu_order: 0}
  });
}
const openEditModal = (occasion: OccasionItemInterface, index: number) => {
  state.activeOccasion = occasion;
  state.activeOccasionIndex = index;
  state.showEditItemModal = true;
}
const removeItem = (occasion: OccasionItemInterface) => {
  Dialog.confirm('Are you sure to delete this item?').then(() => {
    state.occasions.splice(state.occasions.indexOf(occasion), 1);
    updateSettings();
  })
}

const updateActiveOccasionToServer = () => {
  const {occasions, activeOccasion, activeOccasionIndex} = state;
  occasions[activeOccasionIndex] = activeOccasion;
  state.occasions = occasions;
  updateSettings().then(() => {
    state.activeOccasion = {label: '', slug: '', menu_order: 0};
    state.activeOccasionIndex = -1;
    state.showEditItemModal = false;
  });
}

onMounted(() => {
  state.occasions = window.StackonetToolkit.occasions;
})
</script>

<template>
  <div class='border-box-deep'>
    <div class='mb-4'>
      <div class='flex flex-wrap -m-1'>
        <div v-for="(occasion, index) in state.occasions" class='w-1/2 p-1' :key="occasion.slug">
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
    <button class='button' @click.prevent="state.showAddNewItemModal = true">Add New Occasion</button>
    <ShaplaModal :active="state.showAddNewItemModal" title='Add New Occasion'>
      <table class="form-table">
        <tbody>
        <tr>
          <th>
            <label>Occasion label</label>
          </th>
          <td>
            <input type='text' v-model="state.occasion.label"/>
            <p class="description">Occasion label. Must be unique for occasion.</p>
          </td>
        </tr>
        </tbody>
      </table>
      <template v-slot:foot>
        <button class='shapla-button' @click.prevent="state.showAddNewItemModal = false">Close</button>
        <button class='shapla-button is-primary' @click.prevent="addNewItem">Save</button>
      </template>
    </ShaplaModal>
    <ShaplaModal :active="state.showEditItemModal" title='Edit Occasion' @close="state.showEditItemModal = false">
      <table class="form-table">
        <tbody>
        <tr>
          <th>
            <label>Occasion label</label>
          </th>
          <td>
            <input type='text' v-model="state.activeOccasion.label"/>
            <p class="description">Occasion label. Must be unique for occasion.</p>
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
