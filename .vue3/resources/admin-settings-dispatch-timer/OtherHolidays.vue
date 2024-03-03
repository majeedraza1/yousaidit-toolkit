<script setup lang="ts">
import {onMounted, reactive} from "vue";
import {ShaplaCross, ShaplaModal} from "@shapla/vue-components";
import {Dialog, Notify, Spinner} from "@shapla/vanilla-components";
import axios from "../utils/axios.ts";

interface SpecialHolidayInterface {
  label: string,
  date: string
}

interface OtherHolidaysStateInterface {
  special_holidays: Record<string, SpecialHolidayInterface[]>;
  special_holiday: SpecialHolidayInterface;
  showModal: boolean;
}

const state = reactive<OtherHolidaysStateInterface>({
  special_holidays: {},
  special_holiday: {label: '', date: ''},
  showModal: false,
})

const updateSettings = () => {
  return new Promise(resolve => {
    Spinner.show();
    axios
        .post('dispatch-timer/settings', {special_holidays: state.special_holidays})
        .then(response => {
          resolve(response.data.data);
          window.StackonetToolkit.special_holidays = response.data.data.special_holidays;
          state.special_holidays = response.data.data.special_holidays
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

const removeItem = (year: string | number, index: number) => {
  Dialog.confirm('Are you sure to delete this item?').then(confirmed => {
    if (confirmed) {
      const {special_holidays} = state;
      special_holidays[year].splice(index, 1);
      state.special_holidays = special_holidays
      updateSettings();
    }
  })
}

const addNewItem = () => {
  const {special_holidays, special_holiday} = state;
  const year = special_holiday.date.substring(0, 4);
  if (!special_holidays[year]) {
    special_holidays[year] = [];
  }
  special_holidays[year].push({
    label: special_holiday.label,
    date: special_holiday.date
  });
  state.special_holidays = special_holidays
  updateSettings().then(() => {
    state.showModal = false;
  });
}

onMounted(() => {
  state.special_holidays = window.StackonetToolkit.special_holidays
})
</script>

<template>
  <div class='border-box-deep flex flex-col'>
    <div v-for="(holidays, year) in state.special_holidays">
      <div class='mb-1 font-bold text-lg'>{{ year }}</div>
      <div class='border-box-deep flex flex-wrap'>
        <div v-for="(item, index) in holidays" class='w-1/2 p-1' :key="`${year}-${index}`">
          <div class='p-2 bg-white relative'>
            <div>
              {{ item.label }}
              <div class='text-xs italic text-gray-400'>{{ item.date }}</div>
            </div>
            <div class='absolute top-1 right-1 space-x-2'>
              <ShaplaCross @click.prevent="() => removeItem(year,index)"></ShaplaCross>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="mt-2">
    <button class='button' @click.prevent="state.showModal = true">Add Holiday</button>
  </div>

  <ShaplaModal :active="state.showModal" @close="state.showModal = false" title='Add New Holiday'>
    <table class="form-table">
      <tbody>
      <tr>
        <th>
          <label>Label</label>
        </th>
        <td>
          <input type='text' class='regular-text' v-model="state.special_holiday.label"/>
        </td>
      </tr>
      <tr>
        <th>
          <label>Date</label>
        </th>
        <td>
          <div class='flex items-center space-x-2'>
            <input type='date' v-model="state.special_holiday.date" class='regular-text'/>
          </div>
        </td>
      </tr>
      </tbody>
    </table>
    <template v-slot:foot>
      <button class='shapla-button' @click.prevent="state.showModal = false">Close</button>
      <button class='shapla-button is-primary' @click.prevent="addNewItem">Save</button>
    </template>
  </ShaplaModal>
</template>
