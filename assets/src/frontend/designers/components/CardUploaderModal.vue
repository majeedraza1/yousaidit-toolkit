<template>
	<div class="yousaidit-designer-profile-card-modal">
		<modal :active="active" content-size="large" @close="closeModal" :title="modalTitle"
		       :close-on-background-click="false">
			<static-card-uploader
				v-if="current_step === 1"
				:image="card_image"
				:card-size="card_size"
				@upload="handleCardImageUpload"
				@failed="handleCardImageFailed"
				@click:template="handleTemplateDownload"
			/>
			<card-options
				v-if="current_step === 2"
				v-model="card"
				:card_sizes="card_sizes"
				:market_places="market_places"
				:card_categories="card_categories"
				:card_attributes="card_attributes"
				:card_tags="card_tags"
				:has_suggest_tags="has_suggest_tags"
				:errors="errors"
			/>


			<columns multiline v-if="current_step === 3">
				<column :tablet="3"><strong>Title</strong></column>
				<column :tablet="9">{{ card.title }}</column>

				<column :tablet="3"><strong>Card Sizes</strong></column>
				<column :tablet="9">
					<shapla-chip v-for="_size in card_sizes" v-if="card.sizes.indexOf(_size.value) !== -1"
					             :key="_size.value"> {{ _size.label }}
					</shapla-chip>
				</column>

				<column :tablet="3"><strong>Card Categories</strong></column>
				<column :tablet="9">
					<shapla-chip v-for="_cat in card_categories"
					             v-if="card.categories_ids.indexOf(_cat.id.toString()) !== -1" :key="_cat.id">
						{{ _cat.name }}
					</shapla-chip>
				</column>

				<column :tablet="3"><strong>Card Tags</strong></column>
				<column :tablet="9">
					<shapla-chip v-for="_tag in card_tags" v-if="card.tags_ids.indexOf(_tag.id.toString()) !== -1"
					             :key="_tag.id"> {{ _tag.name }}
					</shapla-chip>
				</column>

				<template v-for="_attr in card_attributes">
					<column :tablet="3"><strong>{{ _attr.attribute_label }}</strong></column>
					<column :tablet="9">
						<shapla-chip v-for="_tag in _attr.options" v-if="isAttributeSelected(_attr,_tag)"
						             :key="_tag.id"> {{ _tag.name }}
						</shapla-chip>
					</column>
				</template>

				<template v-if="card_image.full">
					<column :tablet="3"><strong>Card Image</strong></column>
					<column :tablet="9">
						<pdf-image-item
							:is-multiple="false"
							:images="card_image.full"
							url-key="src"
						/>
					</column>
				</template>
			</columns>

			<template v-slot:foot>
				<shapla-button v-if="current_step !== 1" @click="current_step--" theme="primary">Previous
				</shapla-button>
				<shapla-button v-if="current_step !== 3" @click="current_step++" theme="primary"
				               :disabled="!can_go_next_step">Next
				</shapla-button>
				<shapla-button v-if="current_step === 3" theme="primary" @click="handleSubmit">Submit</shapla-button>
			</template>
		</modal>
	</div>
</template>

<script>
import axios from 'axios'
import {
	shaplaButton, modal, textField, selectField, toggle, toggles, shaplaSwitch, shaplaChip, column, columns,
	imageContainer, FileUploader
} from "shapla-vue-components";
import PdfImageItem from "../../../components/PdfImageItem";
import PdfCardItem from "../../../components/PdfCardItem";
import CardOptions from "@/components/CardOptions";
import CardSizePicker from "@/components/StaticCardGenerator/CardSizePicker";
import StaticCardUploader from "@/components/StaticCardGenerator/StaticCardUploader";

export default {
	name: "CardUploaderModal",
	components: {
		StaticCardUploader, CardSizePicker, CardOptions,
		PdfImageItem, textField, selectField, shaplaButton, modal, columns, column, FileUploader,
		toggles, toggle, imageContainer, shaplaSwitch, PdfCardItem, shaplaChip
	},
	props: {
		active: {type: Boolean, default: false},
		card_sizes: {type: Array, default: () => []},
		card_categories: {type: Array, default: () => []},
		card_tags: {type: Array, default: () => []},
		card_attributes: {type: Array, default: () => []},
		market_places: {type: Array, default: () => []},
	},
	data() {
		return {
			card: {
				title: '',
				sizes: [],
				categories_ids: [],
				tags_ids: [],
				attributes: {},
				image_id: 0,
				gallery_images_ids: [],
				market_places: ['yousaidit'],
				pdf_ids: {},
				rude_card: 'no',
				suggest_tags: '',
			},
			card_size: 'square',
			pdf_files: {},
			card_images: [],
			card_image: {},
			errors: {},
			current_step: 1,
			has_suggest_tags: 'no',
		}
	},
	watch: {
		card_size(newValue) {
			this.chooseCardSize(newValue);
		}
	},
	computed: {
		can_go_next_step() {
			if (this.current_step === 1) {
				return !!Object.keys(this.card_image).length;
			}
			if (this.current_step === 2) {
				return !!(this.card.title.length > 1 && this.card.sizes.length && this.card.categories_ids.length);
			}
			return false;
		},
		designer_id() {
			return DesignerProfile.user.id;
		},
		attachment_upload_url() {
			return DesignerProfile.restRoot + '/designers/' + this.designer_id + '/attachment';
		},
		modalTitle() {
			if (2 === this.current_step) {
				return 'Add card detail'
			}
			if (3 === this.current_step) {
				return 'Preview'
			}
			return 'Upload card image';
		},
		get_formatted_card_images() {
			if (this.card_images.length < 1) {
				return [];
			}
			return this.card_images.map(img => {
				return {
					url: img.full.src,
					width: img.full.width,
					height: img.full.height
				}
			});
		}
	},
	methods: {
		chooseCardSize(cardSize) {
			this.card.sizes = [];
			if ('square' === cardSize) {
				this.card.sizes.push('square');
			} else {
				this.card_sizes.forEach(card => {
					if (-1 === ['a4', 'square'].indexOf(card.value)) {
						this.card.sizes.push(card.value);
					}
				})
			}
		},
		isAttributeSelected(attribute, term) {
			if (this.card.attributes[attribute.attribute_name]) {
				return this.card.attributes[attribute.attribute_name].indexOf(term.id.toString()) !== -1;
			}
			return false;
		},
		closeModal() {
			this.$emit('close');
		},
		handleSubmit() {
			this.errors = {};
			this.$store.commit('SET_LOADING_STATUS', true);
			axios.post(DesignerProfile.restRoot + '/designers/' + this.designer_id + '/cards', this.card).then(response => {
				this.closeModal();
				this.$store.commit('SET_LOADING_STATUS', false);
				this.$store.commit('SET_NOTIFICATION', {
					title: 'Success',
					message: 'Card has been submitted successfully.',
					type: 'success'
				});
				this.card = {
					title: '', sizes: [], categories_ids: [], tags_ids: [], attributes: {}, image_id: 0,
					gallery_images_ids: [], pdf_ids: {}, rude_card: 'no',
				};
				this.current_step = 1;
				this.$emit('card:added', response.data.data);
			}).catch(error => {
				this.$store.commit('SET_LOADING_STATUS', false);
				if (error.response.data.errors) {
					this.errors = error.response.data.errors;
				}
			})
		},
		handleCardImageUpload(fileObject, serverResponse) {
			this.card.image_id = serverResponse.data.attachment.id;
			this.card_image = serverResponse.data.attachment;
		},
		handleCardImageFailed(fileObject, serverResponse) {
			if (serverResponse.message) {
				this.$store.commit('SET_NOTIFICATION', {
					type: 'error',
					title: 'Error',
					message: serverResponse.message
				})
			}
		},
		handleCardGalleryImagesUpload(fileObject, serverResponse) {
			this.card.gallery_images_ids.push(serverResponse.data.attachment.id);
			this.card_images.push(serverResponse.data.attachment);
		},
		handlePdfUpload(fileObject, serverResponse) {
			let key = serverResponse.data.query.card_size, pdf_ids = {}, pdf_files = {};

			if (this.card.pdf_ids[key]) {
				this.card.pdf_ids[key].push(serverResponse.data.attachment.id);
			} else {
				pdf_ids[key] = [serverResponse.data.attachment.id];
				this.card.pdf_ids = Object.assign({}, this.card.pdf_ids, pdf_ids);
			}

			if (this.pdf_files[key]) {
				this.pdf_files[key].push(serverResponse.data.attachment);
			} else {
				pdf_files[key] = [serverResponse.data.attachment];
				this.pdf_files = Object.assign({}, this.pdf_files, pdf_files);
			}
		},
		addAdditionalData(xhr) {
			xhr.setRequestHeader('X-WP-Nonce', window.DesignerProfile.restNonce);
		},
		getHeaderText(size_slug) {
			let item = this.card_sizes.find(size => size.value === size_slug);
			return item.label;
		},
		handleTemplateDownload(templateName) {
			const a = document.createElement('a')
			a.target = 'blank'
			a.href = window.DesignerProfile.templates[templateName];
			a.click();
		}
	},
	mounted() {
		this.chooseCardSize('square');
	}
}
</script>

<style lang="scss">
.yousaidit-designer-profile-card-modal {
	.shapla-chip:not(:last-child) {
		margin-right: 5px;
	}

	h3.section-title {
		color: var(--shapla-primary);
		font-size: 1.25rem;
		font-weight: 400;
		text-transform: uppercase;
	}

	h4.section-subtitle {
		font-size: .75rem;
		font-weight: 400;
	}
}
</style>
