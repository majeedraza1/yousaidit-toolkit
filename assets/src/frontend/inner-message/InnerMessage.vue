<template>
	<div class="yousaidit-inner-message">
		<modal :active="showModal" type="box" content-size="full" @close="closeModal" :show-close-icon="false"
			   :close-on-background-click="false">
			<compose :active="showModal" :card-size="card_size" @close="closeModal" @submit="submit"/>
		</modal>
		<modal v-if="showViewModal" :active="true" type="box" content-size="medium" @close="showViewModal = false">
			<div v-if="innerMessage.content" :style="innerMessageStyle">
				{{ innerMessage.content }}
			</div>
		</modal>
		<confirm-dialog/>
		<notification :options="notification"/>
		<spinner :active="loading"/>
	</div>
</template>

<script>
import {mapState} from 'vuex';
import axios from "axios";
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
			card_size: '',
			showViewModal: false,
			innerMessage: {},
		}
	},
	computed: {
		...mapState(['loading', 'notification']),
		innerMessageStyle() {
			let styles = [];
			if (!Object.keys(this.innerMessage).length) {
				return styles;
			}
			// {  "font": "\\'Indie Flower\\', cursive", "size": 18, "align": "center", "color": "#1D1D1B" }
			styles.push({"font-family": this.innerMessage.font});
			styles.push({"text-align": this.innerMessage.align});
			styles.push({"color": this.innerMessage.color});
			styles.push({"font-size": this.innerMessage.size + 'px'});
			return styles;
		}
	},
	mounted() {
		let customMessage = document.querySelector('#custom_message');
		if (customMessage) {
			customMessage.addEventListener('change', event => {
				this.showModal = event.target.checked;
			});
			customMessage.addEventListener('blur', event => {
				this.showModal = event.target.checked;
			});
		} else {
			console.log('Inner message is not detected.')
		}
		let btnIM = document.querySelector('.button--add-inner-message');
		if (btnIM) {
			btnIM.addEventListener('click', event => {
				event.preventDefault();
				this.showModal = true;
				let variations_form = document.querySelector('form.variations_form');
				if (variations_form) {
					let form = new FormData(variations_form);
					for (const [key, value] of form.entries()) {
						if (key === "attribute_pa_size") {
							this.card_size = value;
						}
					}
				}
			});
		}
		document.addEventListener('click', event => {
			let dataset = event.target.dataset;
			if (dataset['cartItemKey']) {
				let data = {action: 'get_cart_item_info', item_key: dataset['cartItemKey'], mode: dataset['mode']}
				axios.get(StackonetToolkit.ajaxUrl, {params: data}).then(response => {
					console.log(response.data);
					if (data.mode === 'view') {
						this.showViewModal = true;
						this.innerMessage = response.data._inner_message;
					}
				})
			}
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

			let variations_form = document.querySelector('form.variations_form');
			if (variations_form) {
				let form = new FormData(variations_form), data = {};
				for (const [key, value] of form.entries()) {
					if (key === "attribute_pa_size") {
						this.card_size = value;
					}
					data[`${key}`] = value;
				}
				variations_form.submit();
			}
		}
	}
}
</script>

<style lang="scss">
.yousaidit-inner-message {
	.shapla-modal-content.is-full {
		max-height: 100vh;
	}

	&__actions {
		margin-top: 1rem;
		text-align: right;

		> *:not(:last-child) {
			margin-right: 8px;
		}
	}
}
</style>
