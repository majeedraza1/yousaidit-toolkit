<script setup lang="ts">
import {onMounted, reactive} from "vue";
import {ShaplaButton, ShaplaCross, ShaplaIcon, ShaplaModal} from "@shapla/vue-components";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import axios from "../utils/axios.ts";

interface CommonPublicHolidayInterface {
  label: string,
  date_string: string
}

interface CommonPublicHolidaysStateInterface {
  holidays: CommonPublicHolidayInterface[];
  holiday: CommonPublicHolidayInterface;
  activeItem: CommonPublicHolidayInterface;
  stringToDate: string;
  showAddNewItemModal: boolean;
  showEditItemModal: boolean;
  validatingDate: boolean;
  activeItemIndex: number;
}

const state = reactive<CommonPublicHolidaysStateInterface>({
  holidays: [],
  holiday: {label: '', date_string: ''},
  activeItem: {label: '', date_string: ''},
  activeItemIndex: -1,
  showAddNewItemModal: false,
  showEditItemModal: false,
  validatingDate: false,
  stringToDate: '',
});

const openEditModal = (item: CommonPublicHolidayInterface, index: number) => {
  state.showEditItemModal = true;
  state.activeItemIndex = index
  state.activeItem = item;
}
const removeItem = (holiday: CommonPublicHolidayInterface) => {
  Dialog.confirm('Are you sure to delete this item?').then(confirmed => {
    if (confirmed) {
      const {holidays} = state;
      holidays.splice(holidays.indexOf(holiday), 1);
      state.holidays = holidays;
      updateSettings();
    }
  })
}


const addNewItem = () => {
  state.holidays.push(state.holiday);
  updateSettings().then(() => {
    state.showAddNewItemModal = false;
    state.holiday = {label: '', date_string: ''};
  });
}

const updateSettings = () => {
  return new Promise(resolve => {
    Spinner.show();
    axios
        .post('dispatch-timer/settings', {common_holidays: state.holidays})
        .then(response => {
          resolve(response.data.data);
          window.StackonetToolkit.common_holidays = response.data.data.common_holidays;
          state.holidays = response.data.data.common_holidays;
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

const validateDateString = (string: string = null) => {
  state.validatingDate = true;
  if (!string) {
    string = state.holiday.date_string;
  }
  axios
      .post('dispatch-timer/string-to-date', {string: string})
      .then(response => {
        state.stringToDate = response.data.data.date as string
      })
      .finally(() => {
        state.validatingDate = false;
      })
}

const updateActiveHolidayToServer = () => {
  const {holidays, activeItemIndex, activeItem} = state;
  holidays[activeItemIndex] = activeItem;
  state.holidays = holidays
  updateSettings().then(() => {
    state.showEditItemModal = false;
    state.activeItem = {label: '', date_string: ''};
    state.activeItemIndex = -1;
  });
}

onMounted(() => {
  state.holidays = window.StackonetToolkit.common_holidays;
})
</script>

<template>
  <div class='border-box-deep flex flex-wrap'>
    <div v-for="(topic, index) in state.holidays" class='w-1/2 p-1' :key='index'>
      <div class='p-2 bg-white relative'>
        <div>
          {{ topic.label }}
          <div class='text-xs italic text-gray-400'>{{ topic.date_string }}</div>
        </div>
        <div class='absolute top-1 right-1 space-x-2'>
          <ShaplaIcon hoverable @click.prevent="() => openEditModal(topic, index)">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class='w-4 h-4 fill-current'>
              <path d="M0 0h24v24H0V0z" fill="none"/>
              <path
                  d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
            </svg>
          </ShaplaIcon>
          <ShaplaCross @click.prevent="() => removeItem(topic)"></ShaplaCross>
        </div>
      </div>
    </div>
  </div>
  <div class="mt-2">
    <button class='button' @click.prevent="state.showAddNewItemModal = true">Add Common Holiday</button>
  </div>

  <ShaplaModal :active="state.showAddNewItemModal" title='Add New Holiday' @close='state.showAddNewItemModal = false'>
    <table class="form-table">
      <tbody>
      <tr>
        <th>
          <label>Label</label>
        </th>
        <td>
          <input type='text' class='regular-text' v-model="state.holiday.label"/>
        </td>
      </tr>
      <tr>
        <th>
          <label>Date string</label>
        </th>
        <td>
          <div class='flex items-center space-x-2'>
            <input type='text' v-model="state.holiday.date_string" class='regular-text'/>
            <ShaplaButton theme="primary" outline size="small" :loading="state.validatingDate"
                          @click.prevent='() => validateDateString(state.holiday.date_string)'>Validate
            </ShaplaButton>
          </div>
          <p class='description' v-if="state.stringToDate">{{ state.stringToDate }}</p>
          <p class="description">English textual datetime description. Examples:</p>
          <p class="description">
            January 1st<br/>
            December 25th<br/>
            last Monday of May<br/>
            last Monday of August<br/>
            first Monday of May
          </p>
          <p class="description">
            <span>To learn more, visit </span>
            <a href="https://www.php.net/manual/en/function.strtotime.php" target='_blank'>
              https://www.php.net/manual/en/function.strtotime.php
            </a>
          </p>
        </td>
      </tr>
      </tbody>
    </table>
    <template v-slot:foot>
      <button class='shapla-button' @click.prevent="state.showAddNewItemModal = false">Close</button>
      <button class='shapla-button is-primary' @click.prevent="addNewItem">Save</button>
    </template>
  </ShaplaModal>

  <ShaplaModal :active="state.showEditItemModal" title='Edit Holiday' @close="state.showEditItemModal = false">
    <table class="form-table">
      <tbody>
      <tr>
        <th>
          <label>Label</label>
        </th>
        <td>
          <input type='text' class='regular-text' v-model="state.activeItem.label"/>
        </td>
      </tr>
      <tr>
        <th>
          <label>Date string</label>
        </th>
        <td>
          <div class='flex items-center space-x-2'>
            <input type='text' v-model="state.activeItem.date_string" class='regular-text'/>
            <ShaplaButton theme="primary" outline size="small" :loading="state.validatingDate"
                          @click.prevent="()=>validateDateString(state.activeItem.date_string)">Validate
            </ShaplaButton>
          </div>
          <p v-if="state.stringToDate" class='description'>{{ state.stringToDate }}</p>
          <p class="description">English textual datetime description. Examples:</p>
          <p class="description">
            January 1st<br/>
            December 25th<br/>
            last Monday of May<br/>
            last Monday of August<br/>
            first Monday of May
          </p>
          <p class="description">
            <span>To learn more, visit </span>
            <a href="https://www.php.net/manual/en/function.strtotime.php" target='_blank'>
              https://www.php.net/manual/en/function.strtotime.php
            </a>
          </p>
        </td>
      </tr>
      </tbody>
    </table>
    <template v-slot:foot>
      <button class='shapla-button' @click.prevent="state.showEditItemModal = false">Close
      </button>
      <button class='shapla-button is-primary' @click.prevent="updateActiveHolidayToServer">Save</button>
    </template>
  </ShaplaModal>
</template>
