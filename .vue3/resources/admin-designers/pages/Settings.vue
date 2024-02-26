<template>
  <div class="yousaidit-admin-designers--settings">
    <h1 class="wp-heading-inline">Settings</h1>
    <hr class="wp-header-end">

    <ShaplaTabs>
      <ShaplaTab v-for="(panel,index) in panels" :key="panel.id" :name="panel.title" :selected="index === 0">
        <template v-for="section in sections" v-if="panel.id === section.panel">
          <h2 class="title" v-if="section.title">{{ section.title }}</h2>
          <p class="description" v-if="section.description" v-html="section.description"></p>

          <table class="form-table">
            <template v-for="field in fields" v-if="field.section === section.id">
              <tr>
                <th scope="row">
                  <label :for="field.id" v-text="field.title"></label>
                </th>
                <td>
                  <template v-if="field.type === 'textarea'">
										<textarea class="regular-text" :id="field.id" :rows="field.rows"
                              v-model="options[field.id]"></textarea>
                  </template>
                  <template v-else-if="field.type === 'select'">
                    <ShaplaSelect
                        :label="field.title"
                        v-model="options[field.id]"
                        :multiple="field.multiple"
                        :options="field.options"
                        :searchable="true"
                    />
                  </template>
                  <template v-else-if="field.type === 'media-uploader'">
                    <input type="text" class="regular-text" :id="field.id"
                           v-model="options[field.id]">
                  </template>
                  <template v-else>
                    <input :type="field.type" class="regular-text" :id="field.id"
                           v-model="options[field.id]">
                  </template>
                  <p class="description" v-if="field.description" v-html="field.description"></p>
                </td>
              </tr>
            </template>
          </table>

        </template>
        <div class="button-save-settings-container">
          <ShaplaButton theme="primary" size="medium" :fab="true" @click="saveOptions">
            <ShaplaIcon>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                <path d="M0 0h24v24H0z" fill="none"/>
                <path
                    d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
              </svg>
            </ShaplaIcon>
          </ShaplaButton>
        </div>
      </ShaplaTab>
    </ShaplaTabs>
  </div>
</template>

<script lang="ts" setup>
import axios from "../../utils/axios";
import {ShaplaButton, ShaplaIcon, ShaplaSelect, ShaplaTab, ShaplaTabs} from '@shapla/vue-components';
import {Notify, Spinner} from "@shapla/vanilla-components";
import {onMounted, reactive} from "vue";

const state = reactive({
  panels: [],
  sections: [],
  fields: [],
  options: {}
})


const getSettingsFields = () => {
  Spinner.show();
  axios.get( 'designers-settings').then(response => {
    let data = response.data.data;
    state.panels = data.panels;
    state.sections = data.sections;
    state.fields = data.fields;
    state.options = data.options;
    Spinner.hide();
  }).catch(errors => {
    if (typeof errors.response.data.message === "string") {
      Notify.error(errors.response.data.message, 'Error!');
    }
    Spinner.hide();
  })
}
const saveOptions = () => {
  Spinner.show();
  axios.post( 'designers-settings', {options: state.options}).then(() => {
    Spinner.hide();
    Notify.success('Options has been updated.')
  }).catch(errors => {
    Spinner.hide();
    if (typeof errors.response.data.message === "string") {
      Notify.error(errors.response.data.message, 'Error!');
    }
  })
}

onMounted(() => {
  getSettingsFields()
})
</script>
