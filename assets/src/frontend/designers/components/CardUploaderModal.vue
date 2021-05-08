<template>
	<div class="yousaidit-designer-profile-card-modal">
		<modal :active="active" content-size="large" @close="closeModal" :title="modalTitle"
			   :close-on-background-click="false">
			<columns multiline v-if="current_step === 1">
				<column :tablet="12">
					<div class="w-full text-center">
						<h2 class="text-xl">Choose Card size</h2>
						<p>Please check the correct size of your design</p>
					</div>
				</column>
				<column :tablet="6" class="md:flex items-center justify-end">
					<div @click="card_size = 'square'"
						 class="border border-solid border-gray-200 w-36 h-36 flex items-center justify-center bg-gray-100 cursor-pointer"
						 :class="{'border-primary':card_size === 'square'}"
					>
						<div class="text-lg">Square</div>
					</div>
				</column>
				<column :tablet="6" class="md:flex items-center justify-start">
					<div @click="card_size = 'a'"
						 class="border border-solid border-gray-200 w-36 h-44 flex flex-col items-center justify-center bg-gray-100 cursor-pointer"
						 :class="{'border-primary':card_size === 'a'}"
					>
						<div class="text-lg">A Size</div>
						<div class="text-sm">(A6 & A5)</div>
					</div>
				</column>
			</columns>
			<columns v-if="current_step === 2" multiline>
				<column :tablet="12">
					<div class="w-full text-center">
						<h2 class="text-xl">Card Details</h2>
					</div>
				</column>
				<column :tablet="12">
					<div class="flex items-center">
						<div class="mr-4">
							<shapla-switch v-model="card.rude_card" true-value="yes" false-value="no"/>
						</div>
						<span class="flex-grow">
							<span class="bg-primary text-on-primary text-sm py-4 px-2 inline-block">Is your design classed as a rude card?</span>
							<span class="bg-gray-100 text-sm py-4 px-2 inline-block">We class rude cards what contain words, phrases or themes of an adult content: e.i. swearing or innuendos.</span>
						</span>
					</div>
				</column>
				<column :tablet="6">
					<text-field
						type="textarea" v-model="card.title" label="Title"
						:has-error="!!errors.title" :validation-text="errors.title?errors.title[0]:''" :rows="1"
						help-text="Write card title. Card title will be used as product title."
					/>
				</column>
				<column :tablet="6" style="display: none">
					<select-field
						v-model="card.sizes" :options="card_sizes" label="Size" multiple
						:has-error="!!errors.sizes" :validation-text="errors.sizes?errors.sizes[0]:''"
						help-text="Choose card size(s). You need to upload file for each selected size on next step."
					/>
				</column>
				<column :tablet="6">
					<select-field
						v-model="card.categories_ids" :options="card_categories" label="Category"
						label-key="name" value-key="id" multiple
						:searchable="card_categories.length > 5"
						singular-selected-text="category selected"
						plural-selected-text="categories selected"
						:has-error="!!errors.categories_ids"
						:validation-text="errors.categories_ids?errors.categories_ids[0]:''"
						help-text="Choose card category. Try to choose only one category but not more than three categories."
					/>
				</column>
				<column :tablet="6">
					<select-field
						v-model="card.tags_ids" :options="card_tags" label="Tags"
						label-key="name" value-key="id" multiple searchable
						singular-selected-text="tag selected"
						plural-selected-text="tags selected"
						:has-error="!!errors.tags_ids"
						:validation-text="errors.tags_ids?errors.tags_ids[0]:''"
						help-text="Choose card tags. Choose as many tags as you need. Make sure tags are relevant to your card."
					/>
				</column>
				<column :tablet="6">
					<div class="additional_tags">
						<shapla-switch v-model="has_suggest_tags" true-value="yes" false-value="no"
									   label="Suggest a new tag."/>
						<text-field
							v-if="has_suggest_tags === 'yes'" label="Tags" type="textarea" :rows="1"
							help-text="Write your suggested tags, separate by comma if you have multiple suggestion"
							v-model="card.suggest_tags"
						/>
					</div>
				</column>
				<column :tablet="6" v-for="attribute in card_attributes" :key="attribute.attribute_name"
						v-if="card_attributes.length">
					<select-field
						v-model="card.attributes[attribute.attribute_name]"
						:options="attribute.options" :label="attribute.attribute_label"
						label-key="name" value-key="id" multiple :searchable="attribute.options.length > 5"
						:has-error="!!(errors.attributes && errors.attributes[attribute.attribute_name])"
						:validation-text="errors.attributes?errors.attributes[0]:''"
					/>
				</column>
				<div class="market-places">
					<column :tablet="12">
						<h3 class="section-title">Where to list your card</h3>
						<h4 class="section-subtitle">You can choose other market places for us to list your card on</h4>
						<columns multiline>
							<column :tablet="2" v-for="marketPlace in market_places" :key="marketPlace.key">
								<div>
									<div>
										<shapla-switch
											:value="marketPlace.key"
											v-model="card.market_places"
											@change="handleMarketPlaceChange"
											:readonly="marketPlace.key === 'yousaidit'"
										/>
									</div>
									<img :src="marketPlace.logo" :alt="marketPlace.key">
								</div>
							</column>
						</columns>
					</column>
				</div>
			</columns>

			<columns multiline v-show="current_step === 3">
				<column :tablet="12">
					<toggles>
						<toggle :name="`Upload files for size: ${getHeaderText(size)}`" :key="`upload-${size}`"
								v-for="(size, index) in card.sizes" :selected="index === 0">
							<file-uploader
								:url="attachment_upload_url"
								@before:send="addAdditionalData"
								:params="{type:'card_pdf',card_size:size}"
								@success="handlePdfUpload"
								:input-id="`yousaiditcard-pdf-input-${size}`"
							/>
						</toggle>
					</toggles>
				</column>
			</columns>

			<columns multiline v-if="current_step === 4">
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

				<template v-if="card_images.length">
					<column :tablet="3"><strong>Card Gallery Images</strong></column>
					<column :tablet="9">
						<pdf-image-item
							:is-multiple="true"
							:images="get_formatted_card_images"
						/>
					</column>
				</template>

				<column :tablet="12">
					<columns>
						<column :tablet="3"><strong>Card PDFs</strong></column>
						<column :tablet="9">
							<pdf-card-item
								v-for="(pdf_data,size_slug) in pdf_files"
								:key="size_slug"
								:header-text="getHeaderText(size_slug)"
								:items="pdf_data"
								url-key="attachment_url"
							/>
						</column>
					</columns>
				</column>

			</columns>

			<columns multiline v-show="current_step === 10">
				<column :tablet="12">
					<toggles>
						<toggle name="Card image" selected>
							<file-uploader
								:url="attachment_upload_url"
								@before:send="addAdditionalData"
								:params="{type:'card_image'}"
								@success="handleCardImageUpload"
								input-id="yousaiditcard-image-upload-input"
							/>
						</toggle>
						<toggle name="Card gallery images (optional)">
							<file-uploader
								:url="attachment_upload_url"
								@before:send="addAdditionalData"
								:params="{type:'card_gallery_images'}"
								@success="handleCardGalleryImagesUpload"
								input-id="yousaiditcard-gallery-image-upload-input"
							/>
						</toggle>
					</toggles>
				</column>
			</columns>

			<template v-slot:foot>
				<shapla-button v-if="current_step !== 1" @click="current_step--" theme="primary">Previous
				</shapla-button>
				<shapla-button v-if="current_step !== 4" @click="current_step++" theme="primary"
							   :disabled="!can_go_next_step">Next
				</shapla-button>
				<shapla-button v-if="current_step === 4" theme="primary" @click="handleSubmit">Submit</shapla-button>
			</template>
		</modal>
	</div>
</template>

<script>
import axios from 'axios'
import textField from "shapla-text-field";
import selectField from 'shapla-select-field';
import shaplaButton from 'shapla-button';
import modal from 'shapla-modal'
import {column, columns} from 'shapla-columns';
import FileUploader from 'shapla-file-uploader';
import {toggle, toggles} from 'shapla-toggles';
import DesignerEventBus from "./DesignerEventBus";
import imageContainer from 'shapla-image-container';
import shaplaSwitch from 'shapla-switch';
import shaplaChip from 'shapla-chip';
import PdfImageItem from "../../../components/PdfImageItem";
import PdfCardItem from "../../../components/PdfCardItem";

export default {
	name: "CardUploaderModal",
	components: {
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
			card_size: '',
			pdf_files: {},
			card_images: [],
			card_image: {},
			errors: {},
			current_step: 1,
			has_suggest_tags: 'no',
		}
	},
	computed: {
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
		can_go_next_step() {
			if (this.current_step === 1) {
				return !!this.card_size.length;
			}
			if (this.current_step === 2) {
				if (this.card_attributes.length && !Object.keys(this.card.attributes).length) {
					return false;
				}
				return !!(this.card.title.length > 1 && this.card.sizes.length && this.card.categories_ids.length);
			}
			if (this.current_step === 3) {
				return this.num_of_pdf_files === this.card.sizes.length;
			}
			return false;
		},
		num_of_pdf_files() {
			let count = 0;
			for (let [key, value] of Object.entries(this.pdf_files)) {
				count += Array.isArray(value) ? 1 : 0;
			}
			return count;
		},
		designer_id() {
			return DesignerProfile.user.id;
		},
		attachment_upload_url() {
			return DesignerProfile.restRoot + '/designers/' + this.designer_id + '/attachment';
		},
		modalTitle() {
			if (2 === this.current_step) {
				return 'Add card preview images'
			}
			if (3 === this.current_step) {
				return 'Add card PDF files'
			}
			if (4 === this.current_step) {
				return 'Preview'
			}
			return 'Add card detail';
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
		handleMarketPlaceChange(value) {
			console.log(value);
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
			DesignerEventBus.$emit('loading', true);
			axios.post(DesignerProfile.restRoot + '/designers/' + this.designer_id + '/cards', this.card).then(response => {
				this.closeModal();
				DesignerEventBus.$emit('loading', false);
				DesignerEventBus.$emit('notify', {
					title: 'Success',
					message: 'Card has been submitted successfully.',
					type: 'success'
				});
				this.card = {
					title: '', sizes: [], categories_ids: [], tags_ids: [], attributes: {}, image_id: 0,
					gallery_images_ids: [], pdf_ids: {}, rude_card: 'no',
				};
				this.current_step = 1;
				DesignerEventBus.$emit('card:added', response.data.data);
			}).catch(error => {
				DesignerEventBus.$emit('loading', false);
				if (error.response.data.errors) {
					this.errors = error.response.data.errors;
				}
			})
		},
		handleCardImageUpload(fileObject, serverResponse) {
			this.card.image_id = serverResponse.data.attachment.id;
			this.card_image = serverResponse.data.attachment;
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
		}
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
