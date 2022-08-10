<template>
	<div class="yousaidit-designer-profile-header">

		<div class="yousaidit-designer-profile-header__cover" :style="coverStyle">

			<div @click="$emit('change:cover')" class="yousaidit-designer-profile-header__cover-upload-icon">
				<icon-container size="large">
					<svg xmlns="http://www.w3.org/2000/svg" height="32" width="32" viewBox="0 0 24 24">
						<circle cx="12" cy="12" r="3.2"/>
						<path
							d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
						<path d="M0 0h24v24H0z" fill="none"/>
					</svg>
				</icon-container>
			</div>

			<div class="yousaidit-designer-profile-header__profile">
				<image-container container-height="128px" container-width="128px" is-rounded>
					<img :src="profilePhotoUrl" :alt="designerName" width="128" height="128">

					<div @click="$emit('change:profile')"
					     class="yousaidit-designer-profile-header__profile-upload-icon">
						<icon-container size="medium">
							<svg xmlns="http://www.w3.org/2000/svg" height="24" width="24" viewBox="0 0 24 24">
								<circle cx="12" cy="12" r="3.2"/>
								<path
									d="M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
								<path d="M0 0h24v24H0z" fill="none"/>
							</svg>
						</icon-container>
					</div>
				</image-container>
			</div>
		</div>
		<div class="yousaidit-designer-profile-header__info mid-section"></div>

		<div class="mid-section">
			<div class="yousaidit-designer-profile-header__name name" v-html="designerName"></div>
			<div class="yousaidit-designer-profile-header__location place" v-if="designerLocation">
				<icon-container>
					<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
						<path
							d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
						<path d="M0 0h24v24H0z" fill="none"/>
					</svg>
				</icon-container>
				<div v-html="designerLocation"></div>
			</div>
			<div class="yousaidit-designer-profile-header__bio description" v-html="designerBio"></div>
		</div>
	</div>
</template>

<script>
import {iconContainer, imageContainer} from 'shapla-vue-components';

export default {
	name: "DesignerProfileHeader",
	components: {iconContainer, imageContainer},
	props: {
		coverPhotoUrl: {type: String, default: ''},
		profilePhotoUrl: {type: String, default: ''},
		designerName: {type: String, default: ''},
		designerLocation: {type: String, default: ''},
		designerBio: {type: String, default: ''},
	},
	computed: {
		coverStyle() {
			return {
				backgroundImage: `url(${this.coverPhotoUrl})`
			};
		}
	}
}
</script>

<style lang="scss">
@import "../../../scss/designer-profile/profile-header";
</style>
