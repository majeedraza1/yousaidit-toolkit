<template>
	<div>
		<h1 class="wp-heading-inline">Reminders</h1>
		<hr class="wp-header-end">
		<div>
			<div class="mb-4">
				<pagination
					:current_page="reminders_pagination.current_page"
					:per_page="reminders_pagination.per_page"
					:total_items="reminders_pagination.total_items"
					@pagination="changeReminderPage"
				/>
			</div>
			<data-table
				:items="reminders"
				:columns="columns"
				:show-cb="false"
				:actions="actions"
				@action:click="handleAction"
			>
				<template v-slot:reminder_group_id="data">
					{{ reminders_groups_title[data.row.reminder_group_id] }}
				</template>
				<template v-slot:user="data">
					<a :href="data.row.user.edit_url">#{{ data.row.user.id }} - {{ data.row.user.display_name }}</a>
				</template>
				<template v-slot:remind_days_count="data">
					{{ remind_days_options_title[data.row.remind_days_count] }}
				</template>
			</data-table>
			<div class="mt-4">
				<pagination
					:current_page="reminders_pagination.current_page"
					:per_page="reminders_pagination.per_page"
					:total_items="reminders_pagination.total_items"
					@pagination="changeReminderPage"
				/>
			</div>
		</div>
	</div>
</template>

<script>
import {dataTable, pagination} from "shapla-vue-components";
import {mapGetters, mapState} from "vuex";

export default {
	name: "Reminders",
	components: {dataTable, pagination},
	data() {
		return {
			columns: [
				{label: 'Title', key: 'name'},
				{label: 'User', key: 'user'},
				{label: 'Occasion Date', key: 'occasion_date'},
				{label: 'Remind Date', key: 'remind_date'},
				{label: 'Group', key: 'reminder_group_id'},
				{label: 'Remind', key: 'remind_days_count', numeric: true},
			],
			actions: [
				{label: 'View Email', key: 'view-email-template'},
			],
		}
	},
	computed: {
		...mapState(['reminders', 'reminders_pagination', 'reminders_groups']),
		...mapGetters(['remind_days_options']),
		reminders_groups_title() {
			let groups = {};
			this.reminders_groups.forEach(group => {
				groups[group.id] = group.title;
			});
			return groups;
		},
		remind_days_options_title() {
			let options = {};
			this.remind_days_options.forEach(option => {
				options[option.value] = option.label;
			});
			return options;
		}
	},
	methods: {
		handleAction(action, item) {
			if (action === 'view-email-template') {
				let a = document.createElement('a');
				a.href = item.email_template_url;
				a.target = '_blank';
				a.click();
				a.remove();
			}
		},
		changeReminderPage(page) {
			this.$store.commit('SET_REMINDERS_CURRENT_PAGE', page)
			this.$store.dispatch('getReminders');
		}
	},
	mounted() {
		if (this.reminders.length < 1) {
			this.$store.dispatch('getReminders');
		}
		if (this.reminders_groups.length < 1) {
			this.$store.dispatch('getRemindersGroups');
		}
	}
}
</script>
