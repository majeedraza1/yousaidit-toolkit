<template>
	<div class="yousaidit-designer-cards">
		<div class="yousaidit-designer-cards__statuses" v-if="cards.length">
			<radio-button
				v-for="_status in statuses"
				:key="_status.key"
				theme="primary"
				:label="_status.label"
				:value="_status.key"
				v-model="status"
			>{{ _status.label }} ({{ _status.count }})
			</radio-button>
		</div>
		<div class="all-type-cards">
			<columns multiline v-if="filtered_cards.length">
				<column :tablet="6" :desktop="4" :widescreen="3" v-for="_card in filtered_cards" :key="_card.id">
					<card-item
						:item="_card"
						@click:settings="openCardSettingsModal"
						@click:delete="handleDeleteCard"
						@click:comments="showComments"
					>some data
					</card-item>
				</column>
			</columns>
			<p v-if="!filtered_cards.length && readFromServer" class="card-not-found">No card found.</p>
		</div>
		<div class="yousaidit-designer-cards__fab">
			<shapla-button theme="primary" size="large" fab @click="showCardModal = true">
				<icon-container>+</icon-container>
			</shapla-button>
		</div>
		<modal :active="showCardModal" @close="showCardModal = false" title="Choose Card Type">
			<div class="space-x-2 flex justify-center">
				<div @click.prevent="chooseCardType('static')"
				     class="bg-gray-100 hover:bg-gray-200 cursor-pointer p-4 w-36 h-32 flex justify-center items-center">
					Static Card
				</div>
				<div @click.prevent="chooseCardType('dynamic')"
				     class="bg-gray-100 hover:bg-gray-200 cursor-pointer p-4 w-36 h-32 flex justify-center items-center">
					Dynamic Card
				</div>
			</div>
		</modal>
		<card-uploader-modal
			v-if="total_cards < maximum_allowed_card"
			:active="modalActive"
			:card_sizes="card_sizes"
			:card_categories="card_categories"
			:card_tags="card_tags"
			:card_attributes="card_attributes"
			:market_places="market_places"
			@close="modalActive = false"
			@card:added="onCardAdded"
		/>
		<modal v-if="total_cards >= maximum_allowed_card" :active="modalActive" @close="modalActive = false" type="box">
			<div class="m-8">
				<div class="w-full text-center mb-4">
					<h2 class="text-2xl text-primary">We are sorry, you have reached your card limit.</h2>
					<p class="px-16 mb-8">Please fill in the form bellow for a member of our time to review your account
						and up your
						limits.</p>
				</div>
				<columns>
					<column :tablet="5">
						<input type="text" class="w-full" placeholder="Username or Email" readonly
						       v-model="limit_extend_request.username">
					</column>
					<column :tablet="5">
						<input type="text" class="w-full" placeholder="Up Limit To?"
						       v-model="limit_extend_request.up_limit_to">
					</column>
					<column :tablet="2">
						<shapla-button theme="primary" fullwidth @click="submitLimitExtendRequest">Send</shapla-button>
					</column>
				</columns>
			</div>
		</modal>
		<modal :active="!!Object.keys(activeCard).length" @close="activeCard = {}" type="box">
			<div class="box--settings">
				<columns :multiline="true">
					<column :tablet="12">
						<p><strong>Request for</strong></p>
						<columns>
							<column>
								<radio-button theme="secondary" fullwidth value="pause"
								              v-model="card_request.request_for">
									Pause
								</radio-button>
							</column>
							<column>
								<radio-button theme="secondary" fullwidth value="remove"
								              v-model="card_request.request_for">
									Remove
								</radio-button>
							</column>
						</columns>
					</column>
					<column :tablet="12">
						<text-field
							label="Message to admin (optional)"
							type="textarea"
							v-model="card_request.message"
						/>
					</column>
					<column :tablet="12">
						<shapla-button theme="primary" :disabled="!card_request.request_for"
						               @click="handleSubmitRequest">Submit Request
						</shapla-button>
					</column>
				</columns>
			</div>
		</modal>
		<modal :active="!!comments.length" title="Comments" @close="comments = []">
			<div class="yousaidit-designer-cards__comments"></div>
			<div class="yousaidit-designer-cards__comment" v-for="comment in comments">
				{{ comment.content }}
			</div>
		</modal>

		<card-creator
			v-if="showDynaCardModal"
			:active="showDynaCardModal"
			@close="showDynaCardModal = false"
			:card_sizes_options="card_sizes"
			:market_places="market_places"
			:card_categories="card_categories"
			:card_attributes="card_attributes"
			:card_tags="card_tags"
			@card:added="showDynaCardModal = false"
		/>
	</div>
</template>

<script>
import axios from "axios";
import {mapGetters} from 'vuex';
import {
	columns, column, dropdown, shaplaButton, iconContainer, modal, radioButton, textField
} from 'shapla-vue-components';
import CardItem from "../components/CardItem";
import CardUploaderModal from "../components/CardUploaderModal";
import CardCreator from "@/components/CardCreator";

export default {
	name: "Cards",
	components: {
		CardCreator, dropdown,
		CardUploaderModal, CardItem, columns, column, shaplaButton, iconContainer, modal, radioButton, textField
	},
	data() {
		return {
			readFromServer: false,
			showCardModal: false,
			showDynaCardModal: false,
			modalActive: false,
			cards: [],
			maximum_allowed_card: 0,
			total_cards: 0,
			pagination: {
				total_items: 0,
				total_pages: 0,
			},
			pagination_lock: false,
			end: false,
			per_page: 12,
			current_page: 1,
			activeCard: {},
			card_request: {
				request_for: '',
				message: '',
			},
			limit_extend_request: {
				username: '',
				up_limit_to: '',
			},
			statuses: [],
			status: 'all',
			comments: [],
		}
	},
	mounted() {
		this.getCards();
		this.paginateOnScroll();

		this.limit_extend_request.username = this.user.display_name;
	},
	computed: {
		...mapGetters(['user', 'card_categories', 'card_tags', 'card_attributes', 'card_sizes', 'market_places']),
		filtered_cards() {
			if (this.status === 'all') {
				return this.cards;
			}
			return this.cards.filter(card => card.status === this.status);
		},
	},
	methods: {
		onCardAdded(card) {
			this.cards.unshift(card);
			// window.location.reload();
		},
		chooseCardType(type) {
			if ('static' === type) {
				this.modalActive = true
			} else {
				this.showDynaCardModal = true;
			}
			this.showCardModal = false;
		},
		submitLimitExtendRequest() {
			if (!this.limit_extend_request.up_limit_to) {
				return this.$store.commit('SET_NOTIFICATION', {
					type: 'error',
					message: 'Please fill in "up limit to" field'
				})
			}
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.post(DesignerProfile.restRoot + '/designers/extend-card-limit', this.limit_extend_request).then(() => {
				this.modalActive = false;
				this.$store.commit('SET_LOADING_STATUS', false);
				this.$store.commit('SET_NOTIFICATION', {
					type: 'success',
					message: 'Your message has been sent.'
				})
			}).catch(errors => {
				console.log(errors);
				this.$store.commit('SET_LOADING_STATUS', false);
			})
		},
		handleDeleteCard(card) {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.delete(window.DesignerProfile.restRoot + '/designers/' + this.user.id + '/cards/' + card.id, {
				params: {action: 'delete'}
			}).then(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.cards = [];
				this.getCards();
			}).catch(errors => {
				console.log(errors);
				this.$store.commit('SET_LOADING_STATUS', false);
			});
		},
		showComments(card) {
			this.$store.commit('SET_LOADING_STATUS', true);
			let url = window.DesignerProfile.restRoot + '/designers/' + this.user.id + '/cards/' + card.id + '/comments';
			axios.get(url).then(response => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.comments = response.data.data.comments;
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			});
		},
		openCardSettingsModal(card) {
			this.activeCard = card;
		},
		handleSubmitRequest() {
			this.$store.commit('SET_LOADING_STATUS', true);
			let url = window.DesignerProfile.restRoot + '/designers/' + this.user.id + '/cards/' + this.activeCard.id + '/requests';
			axios.put(url, {
				request_for: this.card_request.request_for,
				message: this.card_request.message,
			}).then(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.$store.commit('SET_NOTIFICATION', {
					type: 'success',
					title: 'Request Submitted!',
					message: 'You request has been sent to admin.'
				});
				this.activeCard = {};
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			});
		},
		paginateOnScroll() {
			let dashboardContent = document.querySelector('.shapla-dashboard-content');
			dashboardContent.addEventListener('scroll', () => {
				let offsetHeight = document.body.scrollTop || document.documentElement.scrollTop || dashboardContent.scrollTop,
					cardContainer = document.querySelector('.all-type-cards'),
					mailListHeight = cardContainer.offsetHeight + cardContainer.offsetTop;

				if (!this.pagination_lock && !this.end && (window.innerHeight + offsetHeight) >= mailListHeight &&
					this.cards.length < this.pagination.total_items && this.current_page < this.pagination.total_pages
				) {
					this.current_page += 1;
					this.getCards();
				}
			});
		},
		getCards() {
			this.pagination_lock = true;
			this.getItems().then(data => {
				this.cards = this.cards.concat(data.items);
				this.statuses = data.statuses;
				this.pagination = data.pagination;
				this.maximum_allowed_card = data.maximum_allowed_card;
				this.total_cards = data.total_cards;
				this.end = (this.cards.length === data.pagination.totalCount);
				this.pagination_lock = false;
				this.readFromServer = true;
			});
		},
		getItems() {
			return new Promise(resolve => {
				axios.get(window.DesignerProfile.restRoot + '/designers/' + this.user.id + '/cards', {
					params: {
						per_page: this.per_page,
						page: this.current_page,
					}
				}).then(response => {
					resolve(response.data.data);
				}).catch(errors => {
					console.log(errors);
				});
			});
		},
	}
}
</script>

<style lang="scss">
@import "~shapla-color-system/src/variables";

.yousaidit-designer-cards {
	&__fab {
		position: fixed;
		bottom: 30px;
		right: 30px;
	}

	.card-not-found {
		font-size: 3rem;
		text-align: center;
	}

	.box--settings {
		background: white;
		padding: 1rem;
		border-radius: 4px;
	}

	&__statuses {
		display: flex;
		flex-wrap: wrap;
		justify-content: center;
		align-items: center;
		margin-bottom: 1.5rem;

		> * {
			margin-right: .5rem;
		}
	}

	&__comment {
		background: $primary-alpha;
		border-radius: 4px;
		padding: 1rem;
	}
}
</style>
