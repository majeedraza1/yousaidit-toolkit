<template>
	<form action="#" @submit.prevent="submit" autocomplete="off">
		<div class="field--input-container">
			<label class="screen-reader-text" for="input--search">Scan code or enter ShipStation ID</label>
			<input id="input--search" class="input--search" type="text" v-model="search"
			       placeholder="Scan code or enter ShipStation ID">
			<shapla-button theme="primary">Search</shapla-button>
			<shapla-button theme="default" class="button--clear" :class="{'is-active':search.length}"
			               @click.prevent="clear">Clear
			</shapla-button>
		</div>
	</form>
</template>

<script>
import {shaplaButton} from 'shapla-vue-components';

export default {
	name: "BarcodeSearchForm",
	components: {shaplaButton},
	props: {
		value: {type: String, default: ''},
	},
	data() {
		return {
			search: '',
		}
	},
	methods: {
		submit() {
			this.triggerInputEvent();
			this.$emit('submit', this.search);
		},
		clear() {
			this.search = '';
			this.triggerInputEvent();
			this.focusInput();
		},
		triggerInputEvent() {
			this.$emit('input', this.search);
		},
		focusInput() {
			this.$el.querySelector('.input--search').focus();
		}
	},
	mounted() {
		this.search = this.value;
		setTimeout(() => this.focusInput(), 10);
	}
}
</script>
