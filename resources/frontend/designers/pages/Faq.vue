<template>
	<div>
		<toggles :boxed-mode="true">
			<toggle v-for="item in items" :key="item.id" :name="item.title">
				<div v-html="item.content"></div>
			</toggle>
		</toggles>
	</div>
</template>

<script>
import axios from "@/utils/axios";
import {toggle, toggles} from 'shapla-vue-components';

export default {
	name: "Faq",
	components: {toggles, toggle},
	data() {
		return {
			items: [],
		}
	},
	mounted() {
		this.getItems();
	},
	methods: {
		getItems() {
			axios.get('designer-faqs').then(response => {
				this.items = response.data.data;
			}).catch(errors => {
				console.log(errors);
			});
		}
	}
}
</script>
