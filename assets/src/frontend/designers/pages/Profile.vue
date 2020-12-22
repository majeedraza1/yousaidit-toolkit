<template>
	<div class="yousaidit-designer-profile">
		<designer-profile-header
				:designer-name="designer.display_name"
				:designer-location="designer.location"
				:designer-bio="designer.description"
				:cover-photo-url="designer.cover_photo_url"
				:profile-photo-url="designer.avatar_url"
				@change:cover="showChangeCoverModal = true"
				@change:profile="showChangeProfileModal = true"
		/>
		<p>&nbsp;</p>
		<tabs fullwidth tab-style="toggle">
			<tab name="Personal Detail" selected>
				<profile-field title="Name" :content="`${designer.first_name} ${designer.last_name}`"
							   @save="updateName">
					<text-field type="text" label="First Name" v-model="designer.first_name"/>
					<text-field type="text" label="Last Name" v-model="designer.last_name"/>
				</profile-field>

				<profile-field title="Display Name" :content="designer.display_name" @save="updateDisplayName">
					<select-field
							label="Display Name"
							v-model="designer.display_name"
							:options="displayNameOptions"
					/>
				</profile-field>

				<profile-field title="Location" :content="designer.location" @save="updateLocation">
					<text-field type="text" label="Location" v-model="designer.location"/>
				</profile-field>

				<profile-field title="Password" content="**********" @save="updatePassword">
					<text-field type="password" label="Current Password" v-model="current_password"/>
					<text-field type="password" label="New Password" v-model="new_password"/>
					<text-field type="password" label="Confirm Password" v-model="confirm_password"/>
				</profile-field>

				<profile-field title="About Yourself" :content="designer.description" field-width="500px"
							   @save="updateDescription">
					<text-field type="textarea" label="Detail" v-model="designer.description"/>
				</profile-field>

				<profile-field title="Profile URL" :content="`${designer.profile_base_url}/${designer.user_login}`"
							   @save="updateProfileUrl" v-if="false">
					<span v-html="`${designer.profile_base_url}/${designer.user_login}`"></span>
					<text-field type="url" label="Username" v-model="designer.user_login"
								help-text="Your username will be used as your profile slug"/>
				</profile-field>
			</tab>

			<tab name="Business Detail">
				<profile-field title="Business Name" :content="designer.business_name" @save="updateBusinessName">
					<text-field type="text" label="Business Name" v-model="designer.business_name"/>
				</profile-field>

				<profile-field title="Business Address" @save="updateBusinessAddress"
							   :content="designer.formatted_address">
					<text-field type="text" label="Address Line 1" autocomplete="address-line1"
								v-model="designer.business_address.address_1" name="address_1"/>
					<text-field type="text" label="Address Line 2" autocomplete="address-line2"
								v-model="designer.business_address.address_2" name="address_2"/>
					<text-field type="text" label="City" autocomplete="address-level2"
								v-model="designer.business_address.city" name="city"/>
					<text-field type="text" label="Post Code" autocomplete="postal-code"
								v-model="designer.business_address.post_code" name="post_code"/>
					<text-field type="text" label="Country" autocomplete="country"
								v-model="designer.business_address.country" name="country"/>
				</profile-field>

				<profile-field title="VAT" :content="designer.vat_registration_number" @save="updateVatInfo">
					<text-field type="text" label="Vat Registration Number" v-model="designer.vat_registration_number"/>
					<text-field type="date" label="Vat Certificate Issue Date"
								v-model="designer.vat_certificate_issue_date"/>
				</profile-field>
			</tab>

			<tab name="Payouts">
				<profile-field title="PayPal Email Address" :content="designer.paypal_email" @save="updatePayPalEmail">
					<text-field type="email" label="Email Address" v-model="designer.paypal_email"/>
				</profile-field>
			</tab>
		</tabs>
		<p>&nbsp;</p>
		<media-modal
				:active="showChangeProfileModal"
				:images="images"
				:url="uploadUrl"
				@close="showChangeProfileModal = false"
				@select:image="handleChooseProfileImage"
				@before:send="addNonceHeader"
				@success="refreshMediaList"
		/>
		<media-modal
				:active="showChangeCoverModal"
				:images="images"
				:url="uploadUrl"
				@close="showChangeCoverModal = false"
				@select:image="handleChooseCoverImage"
				@before:send="addNonceHeader"
				@success="refreshMediaList"
		/>
	</div>
</template>

<script>
	import axios from "axios";
	import {tab, tabs} from 'shapla-tabs';
	import textField from 'shapla-text-field'
	import selectField from 'shapla-select-field'
	import ProfileField from "../components/ProfileField";
	import DesignerProfileHeader from "../components/DesignerProfileHeader";
	import MediaModal from "../../../shapla/shapla-media-uploader/src/MediaModal";

	export default {
		name: "Profile",
		components: {MediaModal, DesignerProfileHeader, ProfileField, tabs, tab, textField, selectField},
		data() {
			return {
				showChangeCoverModal: false,
				showChangeProfileModal: false,
				images: [],
				current_password: '',
				new_password: '',
				confirm_password: '',
				user_login: '',
				designer: {
					display_name: '',
					first_name: '',
					last_name: '',
					paypal_email: '',
					description: '',
					user_url: '',
					location: '',
					business_name: '',
					formatted_address: '',
					user_login: '',
					profile_base_url: '',
					business_address: {
						address_1: '',
						address_2: '',
						city: '',
						post_code: '',
						country: '',
						state: '',
					},
					vat_registration_number: '',
					vat_certificate_issue_date: '',
					avatar_url: '',
					cover_photo_url: '',
				},
				display_name: '',
			}
		},
		computed: {
			user() {
				return DesignerProfile.user
			},
			uploadUrl() {
				return window.DesignerProfile.restRoot + '/designers/' + this.user.id + '/attachment';
			},
			displayNameOptions() {
				let options = [];
				if (!this.designer) {
					return options;
				}

				if (this.designer.first_name) {
					options.push(this.designer.first_name);
				}

				if (this.designer.last_name) {
					options.push(this.designer.last_name);
				}

				if (this.designer.first_name && this.designer.last_name) {
					options.push(`${this.designer.first_name} ${this.designer.last_name}`);
				}

				if (this.designer.business_name) {
					options.push(this.designer.business_name);
				}

				if (this.designer.display_name && options.indexOf(this.designer.display_name) === -1) {
					options.push(this.designer.display_name);
				}

				if (this.display_name && options.indexOf(this.display_name) === -1) {
					options.push(this.display_name);
				}

				return options;
			}
		},
		mounted() {
			this.getUserData();
			this.getUserUploadedImages();
		},
		methods: {
			refreshMediaList() {
				this.getUserUploadedImages();
			},
			getUserUploadedImages() {
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.get(window.DesignerProfile.restRoot + '/designers/' + this.user.id + '/attachment', {
					params: {
						mime_types: ['image/jpeg', 'image/png']
					}
				}).then(response => {
					this.$store.commit('SET_LOADING_STATUS', false);
					this.images = response.data.data;
				}).catch(errors => {
					this.$store.commit('SET_LOADING_STATUS', false);
					console.log(errors);
				});
			},
			handleChooseProfileImage(image) {
				this.update({avatar_id: image.id});
			},
			handleChooseCoverImage(image) {
				this.update({cover_photo_id: image.id});
			},
			addNonceHeader(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', window.DesignerProfile.restNonce);
			},
			getUserData() {
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.get(window.DesignerProfile.restRoot + '/designers/' + this.user.id).then(response => {
					this.$store.commit('SET_LOADING_STATUS', false);
					let data = response.data.data;
					this.designer = data.designer;
					this.display_name = data.designer.display_name;
					this.user_login = data.designer.user_login;
				}).catch(errors => {
					this.$store.commit('SET_LOADING_STATUS', false);
					console.log(errors);
				});
			},
			update(data) {
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.put(window.DesignerProfile.restRoot + '/designers/' + this.user.id, data).then(response => {
					this.$store.commit('SET_LOADING_STATUS', false);
					this.$store.commit('SET_NOTIFICATION', {type: 'success', message: 'Profile updated.'});
					let data = response.data.data;
					this.designer = data.designer;
				}).catch(errors => {
					this.$store.commit('SET_LOADING_STATUS', false);
					let error = errors.response.data;
					if (error.message) {
						this.$store.commit('SET_NOTIFICATION', {type: 'error', message: error.message});
					}
				});
			},
			updateName() {
				this.update({first_name: this.designer.first_name, last_name: this.designer.last_name});
			},
			updateDisplayName() {
				this.update({display_name: this.designer.display_name});
			},
			updateLocation() {
				this.update({location: this.designer.location});
			},
			updatePassword() {
				this.update({
					current_password: this.current_password,
					new_password: this.new_password,
					confirm_password: this.confirm_password,
				});
			},
			updateDescription() {
				this.update({description: this.designer.description});
			},
			updatePayPalEmail() {
				this.update({paypal_email: this.designer.paypal_email});
			},
			updateProfileUrl() {
				let currentLogin = this.user_login;
				this.$store.commit('SET_LOADING_STATUS', true);
				axios.put(window.DesignerProfile.restRoot + '/designers/' + this.user.id, {user_login: this.designer.user_login}).then(response => {
					this.$store.commit('SET_LOADING_STATUS', false);
					this.$store.commit('SET_NOTIFICATION', {type: 'success', message: 'Profile updated.'});
					let data = response.data.data;
					this.designer = data.designer;
					window.location.reload();
				}).catch(errors => {
					this.$store.commit('SET_LOADING_STATUS', false);
					let error = errors.response.data;
					this.designer.user_login = currentLogin;
					if (error.message) {
						this.$store.commit('SET_NOTIFICATION', {type: 'error', message: error.message});
					}
				});
			},
			updateBusinessName() {
				this.update({business_name: this.designer.business_name});
			},
			updateBusinessAddress() {
				this.update({business_address: this.designer.business_address});
			},
			updateVatInfo() {
				this.update({
					vat_registration_number: this.designer.vat_registration_number,
					vat_certificate_issue_date: this.designer.vat_certificate_issue_date,
				});
			},
		}
	}
</script>

<style lang="scss">

</style>
