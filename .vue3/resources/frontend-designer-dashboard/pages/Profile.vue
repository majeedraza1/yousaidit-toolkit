<template>
  <div class="yousaidit-designer-profile">
    <designer-profile-header
        :designer-name="store.designer.display_name"
        :designer-location="store.designer.location"
        :designer-bio="store.designer.description"
        :cover-photo-url="store.designer.cover_photo_url"
        :profile-photo-url="store.designer.avatar_url"
        @change:cover="state.showChangeCoverModal = true"
        @change:profile="state.showChangeProfileModal = true"
    />
    <p>&nbsp;</p>
    <ShaplaTabs fullwidth tab-style="toggle">
      <ShaplaTab name="Personal Detail" selected>
        <profile-field title="Name" :content="`${store.designer.first_name} ${store.designer.last_name}`"
                       @save="updateName">
          <ShaplaInput type="text" label="First Name" v-model="store.designer.first_name"/>
          <ShaplaInput type="text" label="Last Name" v-model="store.designer.last_name"/>
        </profile-field>

        <profile-field title="Display Name" :content="store.designer.display_name" @save="updateDisplayName">
          <ShaplaSelect
              label="Display Name"
              v-model="store.designer.display_name"
              :options="store.displayNameOptions"
          />
        </profile-field>

        <profile-field title="Location" :content="store.designer.location" @save="updateLocation">
          <ShaplaInput type="text" label="Location" v-model="store.designer.location"/>
        </profile-field>

        <profile-field title="Password" content="**********" @save="updatePassword">
          <ShaplaInput type="password" label="Current Password" v-model="store.current_password"/>
          <ShaplaInput type="password" label="New Password" v-model="store.new_password"/>
          <ShaplaInput type="password" label="Confirm Password" v-model="store.confirm_password"/>
        </profile-field>

        <profile-field title="About Yourself" :content="store.designer.description" field-width="500px"
                       @save="updateDescription">
          <ShaplaInput type="textarea" label="Detail" v-model="store.designer.description"/>
        </profile-field>

        <profile-field title="Profile URL" :content="`${store.designer.profile_base_url}/${store.designer.user_login}`"
                       @save="store.updateProfileUrl">
          <span v-html="`${store.designer.profile_base_url}/${store.designer.user_login}`"></span>
          <ShaplaInput type="url" label="Username" v-model="store.designer.user_login"
                      help-text="Your username will be used as your profile page URL."/>
        </profile-field>

        <profile-field title="Instagram Profile" :content="store.designer.instagram_url" field-width="500px"
                       @save="updateInstagramUrl">
          <ShaplaInput type="url" label="URL" v-model="store.designer.instagram_url"/>
        </profile-field>
      </ShaplaTab>

      <ShaplaTab name="Business Detail">
        <profile-field title="Business Name" :content="store.designer.business_name" @save="updateBusinessName">
          <ShaplaInput type="text" label="Business Name" v-model="store.designer.business_name"/>
        </profile-field>

        <profile-field title="Business Address" @save="updateBusinessAddress"
                       :content="store.designer.formatted_address">
          <ShaplaInput type="text" label="Address Line 1" autocomplete="address-line1"
                      v-model="store.designer.business_address.address_1" name="address_1"/>
          <ShaplaInput type="text" label="Address Line 2" autocomplete="address-line2"
                      v-model="store.designer.business_address.address_2" name="address_2"/>
          <ShaplaInput type="text" label="City" autocomplete="address-level2"
                      v-model="store.designer.business_address.city" name="city"/>
          <ShaplaInput type="text" label="Post Code" autocomplete="postal-code"
                      v-model="store.designer.business_address.post_code" name="post_code"/>
          <ShaplaInput type="text" label="Country" autocomplete="country"
                      v-model="store.designer.business_address.country" name="country"/>
        </profile-field>

        <profile-field title="VAT" :content="store.designer.vat_registration_number" @save="updateVatInfo">
          <ShaplaInput type="text" label="Vat Registration Number" v-model="store.designer.vat_registration_number"/>
          <ShaplaInput type="date" label="Vat Certificate Issue Date"
                      v-model="store.designer.vat_certificate_issue_date"/>
        </profile-field>
      </ShaplaTab>

      <ShaplaTab name="Payouts">
        <profile-field title="PayPal Email Address" :content="store.designer.paypal_email" @save="updatePayPalEmail">
          <ShaplaInput type="email" label="Email Address" v-model="store.designer.paypal_email"/>
        </profile-field>
      </ShaplaTab>

      <ShaplaTab name="Card Settings">
        <div v-if="store.designer.card_logo_id">
          <div class="max-w-sm">
            <img :src="store.designer.card_logo_url" alt="">
          </div>
          <ShaplaButton theme="primary" @click="state.showCardLogoModal = true">Change image</ShaplaButton>
        </div>
        <div v-if="!store.designer.card_logo_id">
          <ShaplaButton theme="primary" @click="state.showCardLogoModal = true">Upload image</ShaplaButton>
        </div>
      </ShaplaTab>
    </ShaplaTabs>
    <p>&nbsp;</p>
    <ShaplaMediaModal
        v-if="state.showChangeProfileModal"
        :active="state.showChangeProfileModal"
        :images="store.images"
        :url="store.uploadUrl"
        primary-key="id"
        src-key="attachment_url"
        @close="state.showChangeProfileModal = false"
        @select:image="handleChooseProfileImage"
        @before:send="addNonceHeaderForProfileImage"
        @success="(file,response)=>refreshMediaList(response,'avatar')"
    />
    <ShaplaMediaModal
        v-if="state.showChangeCoverModal"
        :active="state.showChangeCoverModal"
        :images="store.images"
        :url="store.uploadUrl"
        primary-key="id"
        src-key="attachment_url"
        @close="state.showChangeCoverModal = false"
        @select:image="handleChooseCoverImage"
        @before:send="addNonceHeaderForCover"
        @success="(file,response)=>refreshMediaList(response,'cover')"
    />
    <ShaplaMediaModal
        v-if="state.showCardLogoModal"
        :active="state.showCardLogoModal"
        @close="state.showCardLogoModal = false"
        :images="store.images"
        :url="store.uploadUrl"
        primary-key="id"
        src-key="attachment_url"
        @select:image="handleCardLogoImageId"
        @before:send="addNonceHeaderForCardLogo"
        @success="(file,response)=>refreshMediaList(response,'card-logo')"
    />
  </div>
</template>

<script lang="ts" setup>
import {
  ShaplaButton,
  ShaplaInput,
  ShaplaMediaModal,
  ShaplaSelect,
  ShaplaTab,
  ShaplaTabs
} from '@shapla/vue-components'
import ProfileField from "../components/ProfileField.vue";
import DesignerProfileHeader from "../components/DesignerProfileHeader.vue";
import useDesignerProfileStore from "../stores/store-profile.ts";
import {onMounted, reactive} from "vue";

const store = useDesignerProfileStore();
const state = reactive({
  showChangeCoverModal: false,
  showChangeProfileModal: false,
  showCardLogoModal: false,
})

onMounted(()=>{
  store.getUserData();
  store.getUserUploadedImages();
})


const refreshMediaList = (response, type = 'avatar') => {
  let image = response.data.attachment;
  if ('avatar' === type) {
    store.update({avatar_id: image.id});
    state.showChangeProfileModal = false;
  }
  if ('cover' === type) {
    store.update({cover_photo_id: image.id});
    state.showChangeCoverModal = false;
  }
  if ('card-logo' === type) {
    store.update({card_logo_id: image.id});
    state.showCardLogoModal = false;
  }
}
const handleChooseProfileImage = (image) => {
  store.update({avatar_id: image.id});
}
const handleCardLogoImageId = (image) => {
  store.update({card_logo_id: image.id});
}
const handleChooseCoverImage =(image) => {
  store.update({cover_photo_id: image.id});
}
const addNonceHeaderForCardLogo =(xhr, formData) => {
  xhr.setRequestHeader('X-WP-Nonce', window.DesignerProfile.restNonce);
  formData.append('type', 'card-logo');
}
const addNonceHeaderForCover =(xhr, formData) => {
  xhr.setRequestHeader('X-WP-Nonce', window.DesignerProfile.restNonce);
  formData.append('type', 'cover');
}
const addNonceHeaderForProfileImage = (xhr, formData) => {
  xhr.setRequestHeader('X-WP-Nonce', window.DesignerProfile.restNonce);
  formData.append('type', 'avatar');
}
const updateName = () => {
  store.update({first_name: store.designer.first_name, last_name: store.designer.last_name});
}
const updateDisplayName  = () => {
  store.update({display_name: store.designer.display_name});
}
const updateLocation = () => {
  store.update({location: store.designer.location});
}
const updatePassword = () => {
  store.update({
    current_password: store.current_password,
    new_password: store.new_password,
    confirm_password: store.confirm_password,
  });
}
const updateDescription = () => {
  store.update({description: store.designer.description});
}
const updatePayPalEmail = () => {
  store.update({paypal_email: store.designer.paypal_email});
}
const updateInstagramUrl = () => {
  store.update({instagram_url: store.designer.instagram_url});
}
const updateBusinessName = () => {
  store.update({business_name: store.designer.business_name});
}
const updateBusinessAddress = () => {
  store.update({business_address: store.designer.business_address});
}
const updateVatInfo = () => {
  store.update({
    vat_registration_number: store.designer.vat_registration_number,
    vat_certificate_issue_date: store.designer.vat_certificate_issue_date,
  });
}
</script>
