<template>
	<div>
		<v-emoji-picker @select="selectEmoji"/>
	</div>
</template>

<script>
import {VEmojiPicker} from 'v-emoji-picker';

export default {
	name: "EmojiPicker",
	components: {VEmojiPicker},
	methods: {
		selectEmoji(emoji) {
			this.$emit('select', emoji.data);
		}
	}
}
</script>

<style lang="scss">
@import "~shapla-color-system/src/variables";
.container-emoji::-webkit-scrollbar-thumb {
	background: $primary;
}
</style>
