<template>
	<icon-container :size="size" :hoverable="hoverable" @click="event=>$emit('click',event)">
		<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px">
			<path d="M0 0h24v24H0V0z" fill="none"/>
			<path v-if="icon === 'plus'" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
			<path v-if="icon === 'sort'"
				  d="M11 18c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zm-2-8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0-6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm6 4c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
			<path v-if="icon === 'pencil'"
				  d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z"/>
			<path v-if="icon === 'delete'"
				  d="M16 9v10H8V9h8m-1.5-6h-5l-1 1H5v2h14V4h-3.5l-1-1zM18 7H6v12c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7z"/>
		</svg>
	</icon-container>
</template>

<script>
import {iconContainer} from 'shapla-vue-components';

export default {
	name: "SvgIcon",
	props: {
		icon: {type: String, required: true},
		size: {type: String, default: 'medium', validator: value => ['small', 'medium', 'large'].indexOf(value) !== -1},
		hoverable: {type: Boolean, default: false},
	},
	components: {iconContainer}
}
</script>

<style scoped>

</style>
