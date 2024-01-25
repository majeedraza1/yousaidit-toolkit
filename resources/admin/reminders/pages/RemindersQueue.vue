<template>
	<div>
		<h1 class="wp-heading-inline">Reminders Queue</h1>
		<hr class="wp-header-end">
		<div>
			<div class="mb-4">
				<status-list
					:statuses="statuses"
					@change="changeStatus"
				/>
				<pagination
					:current_page="reminders_queues_pagination.current_page"
					:per_page="reminders_queues_pagination.per_page"
					:total_items="reminders_queues_pagination.total_items"
					@pagination="changeReminderQueuePage"
				/>
			</div>
			<data-table
				:items="reminders_queues"
				:columns="columns"
				:show-cb="false"
				:actions="actions"
			>

			</data-table>
			<div class="mt-4">
				<pagination
					:current_page="reminders_queues_pagination.current_page"
					:per_page="reminders_queues_pagination.per_page"
					:total_items="reminders_queues_pagination.total_items"
					@pagination="changeReminderQueuePage"
				/>
			</div>
		</div>
	</div>
</template>

<script>
import {mapState} from "vuex";
import {dataTable, pagination, statusList} from "shapla-vue-components";

export default {
	name: "RemindersQueue",
	components: {dataTable, pagination, statusList},
	data() {
		return {
			columns: [
				{label: 'Title', key: 'title'},
				{label: 'User ID', key: 'user_id'},
				{label: 'Occasion Date', key: 'occasion_date'},
				{label: 'Remind Date', key: 'remind_date'},
				{label: 'Status', key: 'status'},
			],
			statuses: [
				{key: 'pending', label: 'Pending', count: 0, active: true},
				{key: 'sent', label: 'Sent', count: 0, active: false},
				{key: 'failed', label: 'Failed', count: 0, active: false},
				{key: 'all', label: 'All', count: 0, active: false},
			],
			status: 'pending',
			actions: [],
		}
	},
	computed: {
		...mapState(['reminders_queues', 'reminders_queues_pagination'])
	},
	methods: {
		changeReminderQueuePage(page) {
			this.$store.commit('SET_REMINDERS_QUEUE_CURRENT_PAGE', page)
			this.$store.dispatch('getRemindersQueues', this.status);
		},
		changeStatus(status) {
			this.$store.dispatch('getRemindersQueues', status.key).then(() => {
				this.status = status.key;
				// loop through statuses and set active status
				this.statuses.forEach((item) => {
					item.active = item.key === status.key;
				});
			});
		},
	},
	mounted() {
		if (this.reminders_queues.length === 0) {
			this.$store.dispatch('getRemindersQueues', this.status).then(data => {
				this.statuses.forEach((status, index) => {
					this.statuses[index].count = data.status_counts[status.key]
				});
			});
		}
	}
}
</script>
