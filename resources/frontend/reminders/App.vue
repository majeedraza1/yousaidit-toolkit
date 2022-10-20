<template>
	<div>
		<no-reminders v-if="reminders.length < 1" @create="openAddNewModal"/>
		<template v-if="Object.keys(reminders_grouped_by_month).length">
			<div class="mb-4 flex">
				<div class="flex-grow"></div>
				<shapla-button theme="primary" outline size="small" @click="openAddNewModal">Add Reminder
				</shapla-button>
			</div>

			<div v-for="(_reminders,yearMonth) in reminders_grouped_by_month">
				<h4 class="text-xl font-medium bg-gray-200 text-primary p-2">{{ yearMonth }}</h4>
				<div>
					<div v-for="_reminder in _reminders">
						<reminder-loop-item
							:reminder="_reminder"
							:groups="reminders_groups"
							@edit="openEditModal"
							@delete="deleteReminder"
						/>
					</div>
				</div>
			</div>
		</template>
		<modal-add-or-edit-reminder
			v-if="showModal"
			:active="true"
			title="Add New Reminder"
			:reminder="reminder"
			:reminders_groups="reminders_groups"
			:countries="countries"
			:states="states"
			:remind_days="remind_days"
			@close="closeAddNewModal"
			@save="saveReminder"
			@update:reminder="(_reminder) => reminder = _reminder"
		/>
		<modal-add-or-edit-reminder
			v-if="showEditModal"
			:active="true"
			:reminder="activeReminder"
			:reminders_groups="reminders_groups"
			:countries="countries"
			:states="states"
			:remind_days="remind_days"
			@close="closeEditModal"
			@save="updateReminder"
			@update:reminder="(_reminder) => activeReminder = _reminder"
		/>
		<confirm-dialog/>
		<spinner :active="isLoading"/>
	</div>
</template>

<script>
import {
	modal, radioButton, columns, column, textField, selectField, shaplaButton, shaplaCheckbox, ConfirmDialog,
	spinner
} from "shapla-vue-components";
import axios from "axios";
import NoReminders from "./components/NoReminders";
import ModalAddOrEditReminder from "@/frontend/reminders/components/ModalAddOrEditReminder";
import ReminderLoopItem from "@/frontend/reminders/components/ReminderLoopItem";

export default {
	name: "App",
	components: {
		ReminderLoopItem, NoReminders, modal, radioButton, columns, column, textField, selectField, shaplaButton,
		shaplaCheckbox, ConfirmDialog, spinner, ModalAddOrEditReminder,
	},
	data() {
		return {
			showModal: false,
			showEditModal: false,
			isLoading: false,
			reminders: [],
			reminders_grouped_by_month: {},
			reminders_groups: [],
			countries: [],
			states: [],
			reminder: {
				reminder_group_id: '',
				name: '',
				occasion_date: '',
				remind_days_count: 10,
				address_line1: '',
				address_line2: '',
				postal_code: '',
				city: '',
				is_recurring: true,
				has_custom_address: false,
				country_code: 'UK',
			},
			activeReminder: {
				id: 0,
				reminder_group_id: '',
				name: '',
				occasion_date: '',
				remind_days_count: 10,
				address_line1: '',
				address_line2: '',
				postal_code: '',
				city: '',
				is_recurring: true,
				has_custom_address: false,
				country_code: 'UK',
			},
			remind_days: [
				{value: 3, label: '3 days before'},
				{value: 5, label: '5 days before'},
				{value: 7, label: '7 days before'},
				{value: 10, label: '10 days before'},
				{value: 15, label: '15 days before'},
				{value: 30, label: '30 days before'},
			]
		};
	},
	watch: {
		showModal(newValue) {
			this.refreshBodyClass(newValue);
		},
		showEditModal(newValue) {
			this.refreshBodyClass(newValue);
		}
	},
	methods: {
		refreshBodyClass(active) {
			let body = document.querySelector('body');
			if (active) {
				body.classList.add('has-shapla-modal');
			} else {
				setTimeout(() => {
					if (document.querySelectorAll('.shapla-modal.is-active').length === 0) {
						body.classList.remove('has-shapla-modal');
					}
				}, 50);
			}
		},
		groupRemindersByMonth(reminders) {
			return new Promise(resolve => {
				let remindersByMonth = {};
				reminders.forEach(reminder => {
					const date = new Date(reminder.occasion_date);
					const month = date.toLocaleString('default', {month: 'long'});
					const key = month + ' ' + date.getFullYear();
					if (!remindersByMonth[key]) {
						remindersByMonth[key] = [reminder];
					} else {
						remindersByMonth[key].push(reminder);
					}
				});
				resolve(remindersByMonth);
			})
		},
		openEditModal(reminder) {
			this.showEditModal = true;
			this.activeReminder = reminder;
		},
		closeEditModal() {
			this.showEditModal = false;
			this.activeReminder = {
				id: 0,
				reminder_group_id: '',
				name: '',
				occasion_date: '',
				remind_days_count: 10,
				address_line1: '',
				address_line2: '',
				postal_code: '',
				city: '',
				is_recurring: true,
				has_custom_address: false,
				country_code: 'UK',
			}
		},
		openAddNewModal() {
			this.showModal = true;
		},
		closeAddNewModal() {
			this.showModal = false;
			this.reminder = {
				reminder_group_id: '',
				name: '',
				occasion_date: '',
				remind_days_count: 10,
				address_line1: '',
				address_line2: '',
				postal_code: '',
				city: '',
				is_recurring: true,
				has_custom_address: false,
				country_code: 'UK',
			}
		},
		setReminderGroup(value) {
			this.reminder.reminder_group_id = value
			let activeReminderGroup = this.reminders_groups.find(reminderGroup => reminderGroup.id === value);
			if (activeReminderGroup) {
				this.reminder.occasion_date = activeReminderGroup.occasion_date;
			}
		},
		getRemindersGroups() {
			this.isLoading = true;
			axios.get(window.StackonetToolkit.restRoot + '/reminders').then(response => {
				const data = response.data.data;
				this.reminders = data.reminders;
				this.reminder.reminder_group_id = data.groups[0].id;
				this.reminder.occasion_date = data.groups[0].reminder;
				this.groupRemindersByMonth(data.reminders).then(remindersByMonth => {
					this.reminders_grouped_by_month = remindersByMonth;
				});
			}).finally(() => {
				this.isLoading = false;
			});
		},
		saveReminder() {
			this.isLoading = true;
			axios.post(window.StackonetToolkit.restRoot + '/reminders', this.reminder).then(() => {
				this.getRemindersGroups();
				this.closeAddNewModal();
			}).finally(() => {
				this.isLoading = false;
			});
		},
		updateReminder() {
			this.isLoading = true;
			axios.put(window.StackonetToolkit.restRoot + '/reminders/' + this.activeReminder.id, this.activeReminder).then(() => {
				this.getRemindersGroups();
				this.closeEditModal();
			}).finally(() => {
				this.isLoading = false;
			});
		},
		deleteReminder(reminder) {
			this.$dialog.confirm('Are you sure to delete the reminder?').then((confirmed) => {
				if (confirmed) {
					this.isLoading = true;
					axios.delete(window.StackonetToolkit.restRoot + '/reminders/' + reminder.id).then(() => {
						this.getRemindersGroups();
					}).finally(() => {
						this.isLoading = false;
					});
				}
			});
		},
	},
	mounted() {
		const _data = window.YousaiditMyAccountReminders;
		this.reminders = _data.reminders;
		this.reminders_groups = _data.groups;
		this.states = _data.states;
		const createEl = document.querySelector('.reminders__action-create-new')
		if (createEl) {
			createEl.addEventListener('click', this.openAddNewModal);
		}
		Object.entries(_data.countries).forEach(([code, name]) => {
			this.countries.push({label: name, value: code});
		});
		this.groupRemindersByMonth(this.reminders).then(remindersByMonth => {
			this.reminders_grouped_by_month = remindersByMonth;
		});
	}
}
</script>
