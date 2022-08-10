<template>
	<div class="designer-contact-us">
		<columns multiline>
			<column :tablet="12">
				<text-field
					label="Subject"
					v-model="subject"
					:has-error="!!errors.subject.length"
					:validation-text="errors.subject"
				/>
			</column>
			<column :tablet="12">
				<text-field
					type="textarea"
					label="Message"
					v-model="message"
					:has-error="!!errors.message.length"
					:validation-text="errors.message"
				/>
			</column>
			<column :tablet="12">
				<shapla-button theme="primary" shadow @click="sendMessage">Send</shapla-button>
			</column>
		</columns>
	</div>
</template>

<script>
import {column, columns, textField, shaplaButton} from 'shapla-vue-components';
import axios from 'axios';

export default {
	name: "ContactUs",
	components: {columns, column, textField, shaplaButton},
	data() {
		return {
			subject: '',
			message: '',
			errors: {
				subject: '',
				message: '',
			}
		}
	},
	methods: {
		sendMessage() {
			this.errors = {subject: '', message: '',};
			this.$store.commit('SET_LOADING_STATUS', false);
			axios.post(DesignerProfile.restRoot + '/designer-contact', {
				subject: this.subject,
				message: this.message,
			}).then(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.$store.commit('SET_NOTIFICATION', {
					type: 'success',
					title: 'Success!',
					message: 'Message has been send successfully.'
				});
				this.subject = '';
				this.message = '';
			}).catch(error => {
				if (error.response.data.message) {
					this.$store.commit('SET_NOTIFICATION', {
						type: 'error',
						title: 'Error!',
						message: error.response.data.message
					});
				}
				if (error.response.data.errors) {
					this.errors = error.response.data.errors;
				}
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		}
	}
}
</script>

<style lang="scss">
.designer-contact-us {
	max-width: 600px;
	margin-left: auto;
	margin-right: auto;
}
</style>
