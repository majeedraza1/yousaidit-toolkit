<template>
	<div class="flex lg:space-x-4 my-2 hover:bg-gray-100">
		<div class="text-xl font-medium flex-shrink-0 w-16 flex items-center">
			{{ toOrdinal(reminder.occasion_date) }}
		</div>
		<div class="flex-grow flex-shrink-0">
			<div class="text-xl font-medium">{{ reminder.name }}</div>
			<div class="text-lg">{{ group_title }}</div>
		</div>
		<div class="flex justify-center items-center space-x-2 px-2">
			<icon-container size="medium" hoverable @click="triggerEditAction">
				<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
					<path d="M0 0h24v24H0V0z" fill="none"/>
					<path
						d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
				</svg>
			</icon-container>
			<icon-container size="medium" hoverable @click="triggerDeleteAction" class="text-red-600">
				<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"
				     class="fill-current">
					<path d="M0 0h24v24H0V0z" fill="none"/>
					<path
						d="M16 9v10H8V9h8m-1.5-6h-5l-1 1H5v2h14V4h-3.5l-1-1zM18 7H6v12c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7z"/>
				</svg>
			</icon-container>
		</div>
	</div>
</template>

<script>
import {iconContainer} from "shapla-vue-components";

export default {
	name: "ReminderLoopItem",
	components: {iconContainer},
	props: {
		reminder: {type: Object, default: () => ({})},
		groups: {type: Array, default: () => []},
	},
	computed: {
		reminder_group() {
			if (Object.keys(this.reminder).length && this.groups.length) {
				let group = this.groups.find(group => group.id === this.reminder.reminder_group_id);
				if (undefined !== group) {
					return group;
				}
			}
			return {title: '', cta_link: ''}
		},
		group_title() {
			return this.reminder_group.title;
		},
	},
	methods: {
		toOrdinal(dateString) {
			const date = new Date(dateString);
			const dom = date.getDate();
			if (dom === 31 || dom === 21 || dom === 1) return dom + "st";
			else if (dom === 22 || dom === 2) return dom + "nd";
			else if (dom === 23 || dom === 3) return dom + "rd";
			else return dom + "th";
		},
		triggerEditAction() {
			this.$emit('edit', this.reminder);
		},
		triggerDeleteAction() {
			this.$emit('delete', this.reminder);
		}
	}
}
</script>
