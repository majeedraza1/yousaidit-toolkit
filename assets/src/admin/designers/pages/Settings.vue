<template>
	<div class="yousaidit-admin-designers--settings">
		<h1 class="wp-heading-inline">Settings</h1>
		<hr class="wp-header-end">

		<tabs>
			<tab v-for="(panel,index) in panels" :key="panel.id" :name="panel.title" :selected="index === 0">
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
										<select-field
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
					<shapla-button theme="primary" size="medium" :fab="true" @click="saveOptions">
						<icon-container>
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
								<path d="M0 0h24v24H0z" fill="none"/>
								<path
										d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
							</svg>
						</icon-container>
					</shapla-button>
				</div>
			</tab>
		</tabs>
	</div>
</template>

<script>
import axios from 'axios';
import {tab, tabs} from 'shapla-tabs';
import shaplaButton from "shapla-button";
import iconContainer from "shapla-icon-container";
import selectField from 'shapla-select-field';

export default {
	name: "Settings",
	components: {shaplaButton, tabs, tab, iconContainer, selectField},
	data() {
		return {
			panels: [],
			sections: [],
			fields: [],
			options: {}
		}
	},
	mounted() {
		this.$store.commit('SET_LOADING_STATUS', false);
		this.getSettingsFields();
	},
	methods: {
		getSettingsFields() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/designers-settings').then(response => {
				let data = response.data.data;
				this.panels = data.panels;
				this.sections = data.sections;
				this.fields = data.fields;
				this.options = data.options;
				this.$store.commit('SET_LOADING_STATUS', false);
			}).catch(errors => {
				console.log(errors);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		saveOptions() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.post(Stackonet.root + '/designers-settings', {options: this.options}).then(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.$store.commit('SET_NOTIFICATION', {
					title: 'Success!',
					message: 'Options has been updated.',
					type: 'success'
				})
			}).catch(error => {
				console.error(error);
				this.$store.commit('SET_LOADING_STATUS', false);
				this.$store.commit('SET_NOTIFICATION', {
					title: 'Error!',
					message: 'Something went wrong.',
					type: 'error'
				})
			})
		}
	}
}
</script>

<style lang="scss">
.yousaidit-admin-designers--settings {
	.button-save-settings-container {
		position: fixed;
		bottom: 30px;
		right: 30px;

		svg {
			fill: currentColor;
		}
	}

	.shapla-text-field__input {
		width: 100% !important;
	}
}
</style>
