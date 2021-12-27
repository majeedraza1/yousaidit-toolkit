<template>
	<div class="yousaiditcard_designer_card">
		<h1 class="wp-heading-inline">Card</h1>
		<hr class="wp-header-end">
		<columns>
			<column :tablet="6">
				<h4>Type: {{ card.card_type }}</h4>
				<h4>Status: {{ card.status }}</h4>
			</column>
			<column :tablet="6">
				<div class="yousaiditcard_designer_card__actions-top">
					<template v-if="'trash' !== card.status">
						<template v-if="'processing' === card.status">
							<shapla-button theme="success" size="small" @click="updateStatus('accepted')">Accept
							</shapla-button>
							<shapla-button theme="error" size="small" @click="updateStatus('rejected')">Reject
							</shapla-button>
						</template>
						<template v-if="'accepted' === card.status && !card.product_id">
							<shapla-button theme="primary" size="small" @click="showCreateProductModal = true">Create
								Product
							</shapla-button>
						</template>
						<template
							v-if="'accepted' === card.status && card.market_place.indexOf('yousaidit-trade') !== -1">
							<shapla-button theme="primary" size="small" outline @click="createProductOnTradeSite">Create
								Product on
								Trade site
							</shapla-button>
						</template>
						<template v-if="'accepted' === card.status && card.product_id">
							<shapla-button theme="success" size="small" outline @click="showUpdateSkuModal = true">
								Update SKU
							</shapla-button>
							<a class="shapla-button is-primary is-small" :href="card.product_url" target="_blank">View
								Product</a>
						</template>
						<template v-if="hasCommissionData">
							<shapla-button theme="secondary" size="small" @click="showEditCommissionModal = true">Change
								Commission
							</shapla-button>
						</template>
						<shapla-button v-if="card.card_type === 'dynamic'" theme="secondary" size="small" outline
						               @click="previewDynamicCardPDF">Preview PDF
						</shapla-button>
						<shapla-button theme="secondary" size="small" outline @click="generateImage">Generate Image
						</shapla-button>
						<shapla-button theme="error" size="small" @click="trashCard"> Trash Card</shapla-button>
					</template>
				</div>
			</column>
		</columns>
		<toggles>
			<toggle name="Card Info" selected>
				<div class="yousaiditcard_designer_card__content" v-if="Object.keys(card).length">
					<columns multiline>
						<column :tablet="3"><strong>Title</strong></column>
						<column :tablet="9">{{ card.card_title }}</column>
					</columns>
					<columns multiline>
						<column :tablet="3"><strong>SKU</strong></column>
						<column :tablet="9">
							<span v-if="card.card_sku">{{ card.card_sku }}</span>
							<span v-else>-</span>
						</column>
					</columns>
					<columns multiline>
						<column :tablet="3"><strong>Market places</strong></column>
						<column :tablet="9">
							<shapla-chip v-for="market_place in card.market_place" :key="market_place">
								{{ market_place }}
							</shapla-chip>
						</column>
					</columns>
					<columns multiline>
						<column :tablet="3"><strong>Card Sizes</strong></column>
						<column :tablet="9">
							<shapla-chip v-for="_size in card_sizes" v-if="card.card_sizes.indexOf(_size.value) !== -1"
							             :key="_size.value"> {{ _size.label }}
							</shapla-chip>
						</column>
					</columns>
					<columns multiline>
						<column :tablet="3"><strong>Card Categories</strong></column>
						<column :tablet="9">
							<shapla-chip v-for="_cat in card.categories" :key="_cat.id">{{ _cat.title }}</shapla-chip>
						</column>
					</columns>
					<columns multiline>
						<column :tablet="3"><strong>Card Tags</strong></column>
						<column :tablet="9">
							<shapla-chip v-for="_tag in card.tags" :key="_tag.id"> {{ _tag.title }}</shapla-chip>
						</column>
					</columns>
					<columns multiline>
						<column :tablet="3"><strong>Suggested Tags</strong></column>
						<column :tablet="9">
							<span v-if="card.suggest_tags">{{ card.suggest_tags }}</span>
							<span v-else>-</span>
						</column>
					</columns>
					<columns multiline v-for="_attr in card.attributes" :key="_attr.attribute_name">
						<column :tablet="3"><strong>{{ _attr.attribute_label }}</strong></column>
						<column :tablet="9">
							<shapla-chip v-for="_tag in _attr.options" :key="_tag.id"> {{ _tag.title }}</shapla-chip>
						</column>
					</columns>
					<columns multiline>
						<column :tablet="3"><strong>Card Image</strong></column>
						<column :tablet="9">
							<pdf-image-item
								:is-multiple="false"
								:images="card.image"
							/>
						</column>
					</columns>
					<columns multiline>
						<column :tablet="3"><strong>Card Gallery Images</strong></column>
						<column :tablet="9">
							<pdf-image-item
								:is-multiple="true"
								:images="card.gallery_images"
							/>
						</column>
					</columns>
					<columns multiline>
						<column :tablet="3"><strong>Card PDFs</strong></column>
						<column :tablet="9">
							<pdf-card-item
								v-for="(pdf_data,size_slug) in card.pdf_data"
								:key="size_slug"
								:header-text="getHeaderText(size_slug)"
								:items="pdf_data"
							/>
						</column>
					</columns>
				</div>
			</toggle>
			<toggle name="Designer Info" v-if="card.designer">
				<columns multiline>
					<column :tablet="3"><strong>Name</strong></column>
					<column :tablet="9">{{ card.designer.display_name }}</column>
				</columns>
				<columns multiline>
					<column :tablet="3"><strong>Email</strong></column>
					<column :tablet="9">{{ card.designer.email }}</column>
				</columns>
				<columns multiline>
					<column :tablet="3"><strong>Total Cards</strong></column>
					<column :tablet="9">{{ card.designer.total_cards }}</column>
				</columns>
			</toggle>
			<toggle name="Commission Info" v-if="'accepted' === card.status">
				<template v-if="hasCommissionData">
					<columns multiline>
						<column :tablet="3">Commission type</column>
						<column :tablet="9">
							{{ card.commission.commission_type }}
						</column>
					</columns>
					<columns multiline>
						<column :tablet="3">Commission Amount</column>
						<column :tablet="9">
							<div v-for="(amount,key) in card.commission.commission_amount">
								<strong>{{ amount }}</strong> <small>for size: {{ key }}</small>
							</div>
						</column>
					</columns>
				</template>
				<template v-else>
					No commission information yet
				</template>
			</toggle>
		</toggles>
		<modal :active="showRejectConfirmModal" @close="showRejectConfirmModal = false" :show-close-icon="false"
		       type="box">
			<div style="background: #fff;padding: 1rem;border-radius: 4px;">
				<columns multiline>
					<column :tablet="12">
						<text-field
							type="textarea"
							label="Reject Reason"
							help-text="Describe reason of rejection."
							v-model="reject_reason"
						/>
					</column>
					<column :tablet="12">
						<shapla-button theme="primary" :disabled="reject_reason.length < 10"
						               @click="handleAcceptOrReject('rejected')"> Confirm Reject
						</shapla-button>
					</column>
				</columns>
			</div>
		</modal>
		<modal :active="showAcceptConfirmModal" @close="showAcceptConfirmModal = false" :show-close-icon="false"
		       type="box">
			<div style="background: #fff;padding: 1rem;border-radius: 4px;" v-if="Object.keys(card).length">
				<columns multiline>
					<column :tablet="12" style="display: none">
						<span style="display: block;margin-bottom:8px;">Commission type</span>
						<radio-button v-model="commission_type" value="fix" theme="secondary">Fix</radio-button>
						<radio-button v-model="commission_type" value="percentage" theme="secondary">Percentage
						</radio-button>
					</column>
					<column :tablet="12" v-if="commission_type">
						<span style="display: block;margin-bottom:8px;">Commission per sale (fix)</span>
						<columns>
							<column v-for="_size in card.card_sizes" :key="_size">
								<text-field
									v-model="commission[_size]"
									:label="`${_size}`"
								/>
							</column>
						</columns>
					</column>
					<column :tablet="12">
						<text-field
							type="textarea"
							label="Note to Designer (option)"
							v-model="note_to_designer"
						/>
					</column>
					<column :tablet="12">
						<shapla-button theme="primary" :disabled="!enableAcceptButton"
						               @click="handleAcceptOrReject('accepted')">
							Confirm Accept
						</shapla-button>
					</column>
				</columns>
			</div>
		</modal>
		<modal :active="showCreateProductModal" @close="showCreateProductModal = false" :show-close-icon="false"
		       title="Create New Product">
			<columns :multiline="true" v-if="card.card_sizes">
				<template v-for="_size in card_sizes" v-if="card.card_sizes.indexOf(_size.value) !== -1">
					<column :tablet="3">{{ _size.label }}</column>
					<column :tablet="9">
						<columns>
							<column :tablet="6">
								<text-field
									label="SKU"
									v-model="product_sku[_size.value]"
								/>
							</column>
							<column :tablet="6">
								<text-field
									label="Price"
									v-model="product_price[_size.value]"
								/>
							</column>
						</columns>
					</column>
				</template>
			</columns>
			<template v-slot:foot>
				<shapla-button theme="primary" :disabled="!enableCreateProductButton" @click="createProduct">Create
					Product
				</shapla-button>
			</template>
		</modal>
		<modal :active="showUpdateSkuModal" @close="showUpdateSkuModal = false" type="box" content-size="small">
			<div style="background:#fff;padding:1rem;border-radius:4px;">
				<columns multiline>
					<column :tablet="12">
						<text-field
							label="Card SKU"
							v-model="card.card_sku"
						/>
					</column>
					<column :tablet="12">
						<shapla-button theme="primary" @click="updateSku">Update</shapla-button>
					</column>
				</columns>
			</div>
		</modal>
		<modal-card-commission
			v-if="hasCommissionData"
			:active="showEditCommissionModal"
			:card_id="card.id"
			:card_sizes="card.card_sizes"
			:marketplaces="card.market_place"
			:value="card.commission.commission_amount"
			:commissions="card.marketplace_commission"
			@close="showEditCommissionModal = false"
			@submit="handleCommissionUpdate"

		/>
	</div>
</template>

<script>
import axios from "axios";
import {
	column, columns, imageContainer, iconContainer, shaplaButton, shaplaChip, textField, toggles, toggle, modal,
	radioButton
} from 'shapla-vue-components';
import PdfCardItem from "../../../components/PdfCardItem";
import PdfImageItem from "../../../components/PdfImageItem";
import ModalCardCommission from "../../../components/ModalCardCommission";

export default {
	name: "Card",
	components: {
		ModalCardCommission,
		PdfCardItem, PdfImageItem, columns, column, imageContainer, iconContainer, shaplaButton, shaplaChip,
		toggles, toggle, modal, textField, radioButton
	},
	data() {
		return {
			id: 0,
			card: {},
			commission: {},
			commission_type: 'fix',
			reject_reason: '',
			note_to_designer: '',
			product_sku: {},
			product_price: {},
			showRejectConfirmModal: false,
			showAcceptConfirmModal: false,
			showCreateProductModal: false,
			showEditCommissionModal: false,
			showUpdateSkuModal: false,
		}
	},
	mounted() {
		this.$store.commit('SET_LOADING_STATUS', false);
		this.id = parseInt(this.$route.params.id);
		this.getItem();
	},
	computed: {
		hasCommissionData() {
			return !!(this.card.commission && Object.keys(this.card.commission).length);
		},
		enableAcceptButton() {
			let _value = [];
			for (let [key, value] of Object.entries(this.commission)) {
				if (value.length) {
					_value.push(value);
				}
			}
			return _value.length === this.card.card_sizes.length;
		},
		enableCreateProductButton() {
			let _value = [], _pValue = [];
			if (!(this.card.card_sizes)) {
				return false;
			}
			for (let [key, value] of Object.entries(this.product_sku)) {
				if (value.length) {
					_value.push(value);
				}
			}
			for (let [key, value] of Object.entries(this.product_price)) {
				if (value.length) {
					_pValue.push(value);
				}
			}
			return _pValue.length === this.card.card_sizes.length && _value.length === this.card.card_sizes.length;
		},
		card_categories() {
			return DesignerProfile.categories
		},
		card_tags() {
			return DesignerProfile.tags
		},
		card_attributes() {
			return DesignerProfile.attributes
		},
		card_sizes() {
			return DesignerProfile.card_sizes.map(size => {
				return {
					value: size.slug,
					label: size.name
				}
			});
		},
	},
	methods: {
		createProductOnTradeSite() {
			this.$dialog.confirm('Are you sure?').then(confirmed => {
				if (confirmed) {
					this.$store.commit('SET_LOADING_STATUS', true);
					axios.post(`${Stackonet.root}/trade-site/${this.id}/create-product`).then(() => {
						this.$store.commit('SET_LOADING_STATUS', false);
						this.$store.commit('SET_NOTIFICATION', {
							type: 'success',
							title: 'Success!',
							message: 'Request has been sent successfully.'
						});
						this.getItem();
					}).catch(errors => {
						this.$store.commit('SET_LOADING_STATUS', false);
						if (typeof errors.response.data.message === "string") {
							this.$store.commit('SET_NOTIFICATION', {
								type: 'error',
								title: 'Error!',
								message: errors.response.data.message
							});
						}
					});
				}
			})
		},
		updateSku() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.put(Stackonet.root + '/designers-cards/' + this.id, {
				card_sku: this.card.card_sku
			}).then(() => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.showUpdateSkuModal = false;
				this.getItem();
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			});
		},
		getItem() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.get(Stackonet.root + '/designers-cards/' + this.id).then(response => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.card = response.data.data;
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			})
		},
		updateStatus(status) {
			if ('accepted' === status) {
				this.showAcceptConfirmModal = true;
			}
			if ('rejected' === status) {
				this.showRejectConfirmModal = true;
			}
		},
		handleAcceptOrReject(status) {
			let data = {status: status};
			if ('accepted' === status) {
				data.commission_type = this.commission_type;
				data.commission = this.commission;
				data.note_to_designer = this.note_to_designer;
			}
			if ('rejected' === status) {
				data.reject_reason = this.reject_reason;
			}

			this.$store.commit('SET_LOADING_STATUS', true);
			axios.put(Stackonet.root + '/designers-cards/' + this.id, data).then(response => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.card = response.data.data;
				this.showAcceptConfirmModal = false;
				this.showRejectConfirmModal = false;
				this.getItem();
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			})
		},
		createProduct() {
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.post(Stackonet.root + '/designers-cards/' + this.id + '/product', {
				product_sku: this.product_sku,
				product_price: this.product_price,
			}).then(response => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.card = response.data.data;
				this.showCreateProductModal = false;
				this.getItem();
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			})
		},
		getHeaderText(size_slug) {
			let item = this.card_sizes.find(size => size.value === size_slug);
			return item.label;
		},
		changeCommission() {
			this.$store.commit('SET_LOADING_STATUS', true);
			let commission = this.card.commission;
			axios.put(Stackonet.root + '/designers-cards/' + this.id, {
				commission_type: commission.commission_type ? commission.commission_type : 'fix',
				commission: commission.commission_amount ? commission.commission_amount : {},
				note_to_designer: this.note_to_designer,
				status: 'change_commission',
			}).then(response => {
				this.$store.commit('SET_LOADING_STATUS', false);
				this.$store.commit('SET_NOTIFICATION', {
					type: 'error',
					message: 'Commission has been changed.'
				});
				this.showEditCommissionModal = false;
				this.getItem();
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			})
		},
		trashCard() {
			this.$dialog.confirm('Are you sure to trash this card?').then(confirmed => {
				if (confirmed) {
					this.$store.commit('SET_LOADING_STATUS', true);
					axios.delete(Stackonet.root + '/designers-cards/' + this.id).then(() => {
						this.getItem();
						this.$store.commit('SET_NOTIFICATION', {
							type: 'success',
							message: 'Card has been trashed.'
						});
						this.$store.commit('SET_LOADING_STATUS', false);
					}).catch(errors => {
						this.$store.commit('SET_LOADING_STATUS', false);
						console.log(errors);
					})
				}
			})
		},
		handleCommissionUpdate(commission, marketplace_commission) {
			console.log(commission, marketplace_commission);
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.post(Stackonet.root + '/designers-cards/' + this.id + '/commission', {
				commission: commission,
				marketplace_commission: marketplace_commission
			}).then(() => {
				this.getItem();
				this.$store.commit('SET_NOTIFICATION', {
					type: 'success',
					message: 'Commission has been update successfully.'
				});
				this.$store.commit('SET_LOADING_STATUS', false);
				this.showEditCommissionModal = false;
			}).catch(errors => {
				this.$store.commit('SET_LOADING_STATUS', false);
				console.log(errors);
			})
		},
		previewDynamicCardPDF() {
			let url = new URL(window.StackonetToolkit.ajaxUrl);
			url.searchParams.append('action', 'yousaidit_preview_card');
			url.searchParams.append('card_id', this.id);
			url.searchParams.append('_token', Math.random().toString());

			const a = document.createElement('a');
			a.href = url.toString();
			a.target = '_blank'
			a.click();
			a.remove();
		},
		generateImage() {
			let url = new URL(window.StackonetToolkit.ajaxUrl);
			url.searchParams.append('action', 'yousaidit_save_dynamic_card');
			url.searchParams.append('card_id', this.id);

			const a = document.createElement('a');
			a.href = url.toString();
			a.target = '_blank'
			a.click();
			a.remove();
		}
	}
}
</script>

<style lang="scss">
@import "~shapla-color-system/src/variables";

.yousaiditcard_designer_card {
	font-size: 16px;

	&__content {
		background-color: #fff;
		border: rgba(#000, 0.12);
		border-radius: 4px;
		padding: 10px;
	}

	.shapla-chip:not(:last-child) {
		margin-right: 5px;
	}

	&__actions-top {
		display: flex;
		justify-content: flex-end;

		> * {
			margin-left: 5px;
		}
	}
}
</style>
