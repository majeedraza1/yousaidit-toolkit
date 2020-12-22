<template>
	<div class="yousaidit-inner-message">
		<modal :active="showModal" type="box" content-size="large" @close="closeModal" :show-close-icon="false"
			   :close-on-background-click="false">
			<compose @close="closeModal" @submit="submit"/>
		</modal>
		<confirm-dialog/>
		<notification :options="notification"/>
		<spinner :active="loading"/>
	</div>
</template>

<script>
import {mapState} from 'vuex';
import spinner from 'shapla-spinner';
import notification from 'shapla-notifications';
import {ConfirmDialog} from 'shapla-confirm-dialog'
import modal from 'shapla-modal';
import shaplaButton from 'shapla-button';
import Compose from "./Compose";

export default {
	name: "InnerMessage",
	components: {Compose, spinner, notification, ConfirmDialog, modal, shaplaButton},
	data() {
		return {
			showModal: false,
		}
	},
	computed: {
		...mapState(['loading', 'notification']),
	},
	mounted() {
		let customMessage = document.querySelector('#custom_message');
		customMessage.addEventListener('change', event => {
			this.showModal = event.target.checked;
		});
	},
	methods: {
		closeModal() {
			this.showModal = false;
			document.querySelector('#custom_message').checked = false;
		},
		submit(data) {
			let message = '';
			if (!data.message.length) {
				message = "Add some message";
			}
			if (data.message.length && data.message.length < 10) {
				message = "Message too short.";
			}

			if (message.length) {
				return this.$store.commit("SET_NOTIFICATION", {type: 'error', title: 'Error!', message: message});
			}
			this.showModal = false;
			let fieldsContainer = document.querySelector('#_inner_message_fields');
			fieldsContainer.querySelector('#_inner_message_content').value = data.message;
			fieldsContainer.querySelector('#_inner_message_font').value = data.font_family;
			fieldsContainer.querySelector('#_inner_message_size').value = data.font_size;
			fieldsContainer.querySelector('#_inner_message_align').value = data.alignment;
			fieldsContainer.querySelector('#_inner_message_color').value = data.color;
		}
	}
}
</script>

<style lang="scss">
.yousaidit-inner-message {
	&__actions {
		margin-top: 1rem;
		text-align: right;

		> *:not(:last-child) {
			margin-right: 8px;
		}
	}
}
</style>
