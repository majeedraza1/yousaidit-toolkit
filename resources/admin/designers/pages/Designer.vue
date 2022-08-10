<template>
	<div>
		<h1 class="wp-heading-inline">Designer</h1>
		<hr class="wp-header-end">
		<div style="margin-bottom: 1rem;display: flex; justify-content: flex-end;">
			<shapla-button theme="primary" size="small" @click="showEditModal = true">Edit</shapla-button>
		</div>
		<div style="background: #fff;padding: 16px;">
			<columns multiline>
				<column :tablet="3">ID</column>
				<column :tablet="9">{{ designer.id }}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Name</column>
				<column :tablet="9">{{ designer.display_name }}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Email</column>
				<column :tablet="9">{{ designer.email }}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Business Name</column>
				<column :tablet="9">{{ designer.business_name }}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Commission (unpaid)</column>
				<column :tablet="9">{{ designer.unpaid_commission }}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Commission (paid)</column>
				<column :tablet="9">{{ designer.paid_commission }}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Total Commission</column>
				<column :tablet="9">{{ designer.total_commission }}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Card Limit</column>
				<column :tablet="9">
					<div>
						<div>{{ designer.maximum_allowed_card }}</div>
						<div><a href="" @click.prevent="showCardLimitModal = true">Increase</a></div>
					</div>
				</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Is dynamic card allowed?</column>
				<column :tablet="9">
					<div>
						<div>
							{{ designer.can_add_dynamic_card ? 'Yes' : 'No' }}
							<a href="" @click.prevent="toggleDynamicCard">{{
									designer.can_add_dynamic_card ? 'Disallow' : 'Allow'
								}}</a>
						</div>
						<div><strong>Note:</strong> Dynamic card always allowed for admin user.</div>
					</div>
				</column>
			</columns>
		</div>
		<h2 class="title">Card Info</h2>
		<toggles v-if="cards.length">
			<toggle :name="`Card #${_card.id}: ${_card.card_title}`" :subtext="`Status: ${_card.status}`"
			        v-for="_card in cards" :key="_card.id"></toggle>
		</toggles>
		<p v-else>No card yet.</p>
		<modal :active="showEditModal" @close="showEditModal = false" content-size="small" title="Edit Profile">
			<columns multiline>
				<column>
					<text-field
						label="Business Name"
						v-model="designer.business_name"
					/>
				</column>
			</columns>
			<template v-slot:foot>
				<shapla-button theme="primary" @click="updateProfile">Update</shapla-button>
			</template>
		</modal>
		<modal :active="showCardLimitModal" @close="showCardLimitModal = false" title="Extend Card Limit">
			<columns multiline>
				<column :tablet="3">Current Limit</column>
				<column :tablet="9">{{ designer.maximum_allowed_card }}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">New Limit</column>
				<column :tablet="9">
					<input type="text" v-model="maximum_allowed_card">
				</column>
			</columns>
			<template v-slot:foot>
				<shapla-button theme="primary" @click="updateCardLimit">Update</shapla-button>
			</template>
		</modal>
	</div>
</template>

<script>
import axios from "axios";
import {columns, column, toggles, toggle, shaplaButton, modal, textField} from 'shapla-vue-components';

export default {
	name: "Designer",
	components: {columns, column, toggles, toggle, shaplaButton, modal, textField},
	data() {
		return {
			id: 0,
			designer: {},
			pagination: {},
			cards: [],
			showEditModal: false,
			showCardLimitModal: false,
			maximum_allowed_card: '',
		}
	},
	mounted() {
		this.$store.commit('SET_LOADING_STATUS', false);
		this.id = parseInt(this.$route.params.id);
		this.getItem();
		this.getCards();
	},
	methods: {
		getItem() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/designers/' + this.id).then(response => {
				let data = response.data.data;
				this.designer = data.designer;
				this.designer.total_commission = data.total_commission;
				this.designer.unpaid_commission = data.unpaid_commission;
				this.designer.paid_commission = data.paid_commission;
				this.designer.maximum_allowed_card = data.maximum_allowed_card;
				this.designer.can_add_dynamic_card = data.can_add_dynamic_card;
				this.$store.commit('SET_LOADING_STATUS', false);
			}).catch(errors => {
				console.log(errors);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		getCards() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/designers/' + this.id + '/cards').then(response => {
				let data = response.data.data;
				this.cards = data.items;
				this.pagination = data.pagination;
				this.$store.commit('SET_LOADING_STATUS', false);
			}).catch(errors => {
				console.log(errors);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		updateProfile() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.put(Stackonet.root + '/designers/' + this.id, {
				business_name: this.designer.business_name,
			}).then(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.showEditModal = false;
				this.getItem();
			}).catch(errors => {
				console.log(errors);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		updateCardLimit() {
			if (!this.maximum_allowed_card) {
				return this.$store.commit('SET_NOTIFICATION', {
					type: 'error',
					title: 'Error!',
					message: 'Add new limit first.'
				})
			}
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.post(Stackonet.root + '/admin/designers/', {
				designer_id: this.designer.id,
				card_limit: this.maximum_allowed_card,
			}).then(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.showCardLimitModal = false;
				this.getItem();
			}).catch(errors => {
				console.log(errors);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		toggleDynamicCard() {
			this.$dialog.confirm('Are you sure?').then(confirmed => {
				if (confirmed) {
					this.$store.commit('SET_LOADING_STATUS', true);
					axios.post(Stackonet.root + '/admin/designers/', {
						designer_id: this.designer.id,
						can_add_dynamic_card: !this.designer.can_add_dynamic_card ? 'yes' : 'no',
					}).then(() => {
						this.$store.commit('SET_LOADING_STATUS', false);
						this.getItem();
					}).catch(errors => {
						console.log(errors);
						this.$store.commit('SET_LOADING_STATUS', false);
					})
				}
			})
		}
	}
}
</script>

<style scoped>

</style>
