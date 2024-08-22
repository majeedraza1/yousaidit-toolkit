<template>
	<div class="no-reminders text-center border border-solid p-8">
		<h5 class="reminders__heading">No reminders set!</h5>
		<p class="reminders__description">Set your first reminder and never miss a birthday or other occasion
			again.</p>
		<div class="reminders__actions mt-4">
			<button class="shapla-button is-primary reminders__action-create-new" @click="createReminder">Create a
				reminder
			</button>
		</div>
	</div>
</template>

<script>
export default {
	name: "NoReminders",
	methods: {
		createReminder() {
			this.$emit('create');
		}
	}
}
</script>
