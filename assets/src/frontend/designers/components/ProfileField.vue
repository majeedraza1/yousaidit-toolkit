<template>
	<div class="yousaidit-designer-profile__field">
		<div class="yousaidit-designer-profile__label" v-html="title"></div>
		<div class="yousaidit-designer-profile__value">
			<div v-if="!isEditMode">
				<template v-if="content">
					<slot name="content">{{ content }}</slot>
				</template>
				<template v-else>-</template>
			</div>
			<template v-if="isEditMode">
				<div class="yousaidit-designer-profile__input-fields" :style="`width:${fieldWidth}`">
					<slot></slot>
					<div class="yousaidit-designer-profile__actions">
						<shapla-button theme="primary" @click="saveData">Save</shapla-button>
						<shapla-button theme="primary" outline @click="isEditMode = !isEditMode">Cancel</shapla-button>
					</div>
				</div>
			</template>
		</div>
		<div class="yousaidit-designer-profile__action" v-if="!isEditMode">
			<a href="#" @click.prevent="isEditMode = !isEditMode">Edit</a>
		</div>
	</div>
</template>

<script>
import {shaplaButton} from 'shapla-vue-components';

export default {
	name: "ProfileField",
	components: {shaplaButton},
	props: {
		title: {type: String},
		content: {type: String},
		fieldWidth: {type: String, default: '300px'},
	},
	data() {
		return {
			isEditMode: false,
		}
	},
	methods: {
		saveData() {
			this.$emit('save', this.title);
			this.isEditMode = false;
		}
	}
}
</script>

<style lang="scss">
.yousaidit-designer-profile {
	// Field
	&__field {
		display: flex;

		&:not(:last-child) {
			border-bottom: 1px solid rgba(#000, 0.04);
			margin-bottom: 15px;
			padding-bottom: 15px;
		}
	}

	&__label {
		flex-shrink: 0;
		font-weight: 500;
		max-width: 200px;
		width: 30%;
	}

	&__value {
		flex-grow: 1;
	}

	&__action {
		width: 50px;
		text-align: right;
	}

	&__input-fields {
		> * {
			margin-bottom: 15px;
		}
	}

	&__actions {
		display: flex;

		> * {
			flex-grow: 1;

			&:not(:last-child) {
				margin-right: 10px;
			}
		}
	}

	@media screen and (max-width: 782px) {
		&__field {
			flex-wrap: wrap;
		}
		&__label {
			max-width: 100%;
			width: auto;
			flex-grow: 1;
		}
		&__value {
			order: 1;
			width: 100%;
		}
	}
}
</style>
