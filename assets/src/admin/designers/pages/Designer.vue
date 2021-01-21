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
				<column :tablet="9">{{designer.id}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Name</column>
				<column :tablet="9">{{designer.display_name}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Email</column>
				<column :tablet="9">{{designer.email}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Business Name</column>
				<column :tablet="9">{{designer.business_name}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Commission (unpaid)</column>
				<column :tablet="9">{{designer.unpaid_commission}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Commission (paid)</column>
				<column :tablet="9">{{designer.paid_commission}}</column>
			</columns>
			<columns multiline>
				<column :tablet="3">Total Commission</column>
				<column :tablet="9">{{designer.total_commission}}</column>
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
	</div>
</template>

<script>
	import axios from "axios";
	import {columns, column} from 'shapla-columns';
	import {toggles, toggle} from 'shapla-toggles';
	import shaplaButton from 'shapla-button'
	import modal from 'shapla-modal'
	import textField from 'shapla-text-field'

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
		}
	}
</script>

<style scoped>

</style>
