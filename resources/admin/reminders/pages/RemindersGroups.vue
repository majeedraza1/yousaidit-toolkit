<template>
	<div>
		<h1 class="wp-heading-inline">Reminders Groups</h1>
		<hr class="wp-header-end">

		<div>
			<data-table
				:items="reminders_groups"
				:columns="columns"
				:selected-items="selectedItems"
				:actions="actions"
				@item:select="selectItems"
				@action:click="handleAction"
			>
				<template v-slot:product_categories="item">
					<div class="flex flex-wrap">
						<template v-for="cat in item.row.product_categories">
							<template v-for="_cat in product_cats">
								<shapla-chip size="small" small v-if="cat == _cat.value" class="tag">
									{{ _cat.label }}
								</shapla-chip>
							</template>
						</template>
					</div>
				</template>
			</data-table>
		</div>

		<shapla-modal v-if="showAddModal" :active="showAddModal" title="Add New Group" @close="closeAddNewModal">
			<div class="mb-4">
				<text-field label="Title" help-text="Write group title. e.g. Birthday"
				            v-model="group.title"/>
			</div>
			<div class="mb-4">
				<select-field label="Primary product category" v-model="group.product_categories"
				              :options="product_cats" :multiple="true" :searchable="true"
				/>
			</div>
			<div class="mb-4">
				<text-field type="date" label="Date" v-model="group.occasion_date"/>
			</div>
			<div class="mb-4">
				<text-field label="Call to Action Link" v-model="group.cta_link"
				            help-text="If you leave this empty, this will be generated automatically from selected product category."/>
			</div>
			<div class="mb-4">
				<text-field label="Menu order" v-model="group.menu_order"/>
			</div>
			<template v-slot:foot>
				<shapla-button theme="default" @click="closeAddNewModal">Cancel</shapla-button>
				<shapla-button theme="primary" @click="createNewGroup">Add New</shapla-button>
			</template>
		</shapla-modal>
		<shapla-modal v-if="showUpdateModal" :active="showUpdateModal" title="Edit Reminder Group"
		              @close="closeUpdateModal">
			<div style="min-height: 400px">
				<div class="mb-4">
					<text-field label="Title" help-text="Write group title. e.g. Birthday"
					            v-model="activeGroup.title"/>
				</div>
				<div class="mb-4">
					<select-field label="Primary product category" v-model="activeGroup.product_categories"
					              :options="product_cats" :multiple="true" :searchable="true"
					/>
				</div>
				<div class="mb-4">
					<text-field type="date" label="Date" v-model="activeGroup.occasion_date"/>
				</div>
				<div class="mb-4">
					<text-field label="Call to Action Link" v-model="activeGroup.cta_link"
					            help-text="If you leave this empty, this will be generated automatically from selected product category."/>
				</div>
				<div class="mb-4">
					<text-field label="Menu order" v-model="activeGroup.menu_order"/>
				</div>
			</div>
			<template v-slot:foot>
				<shapla-button theme="default" @click="closeUpdateModal">Cancel</shapla-button>
				<shapla-button theme="primary" @click="updateGroup">Update</shapla-button>
			</template>
		</shapla-modal>

		<div class="fixed bottom-4 right-4">
			<shapla-button fab theme="primary" size="large" @click="showAddModal = true">+</shapla-button>
		</div>
	</div>
</template>

<script>
import {dataTable, modal, pagination, selectField, shaplaButton, shaplaChip, textField} from 'shapla-vue-components';
import {mapState} from "vuex";

export default {
	name: "RemindersGroups",
	components: {shaplaButton, shaplaModal: modal, textField, selectField, dataTable, pagination, shaplaChip},
	data() {
		return {
			showAddModal: false,
			showUpdateModal: false,
			reminders: [],
			group: {title: '', product_categories: '', cta_link: '', menu_order: '', occasion_date: ''},
			activeGroup: {id: 0, title: '', product_categories: '', cta_link: '', menu_order: '', occasion_date: ''},
			columns: [
				{label: 'Title', key: 'title'},
				{label: 'Related Product Category', key: 'product_categories'},
				{label: 'CTA Link', key: 'cta_link'},
				{label: 'Default Date', key: 'occasion_date'},
				{label: 'Order', key: 'menu_order', numeric: true},
			],
			actions: [
				{label: 'Edit', key: 'edit'},
				{label: 'View Email', key: 'view-email-template'},
				{label: 'Delete', key: 'delete'},
			],
			selectedItems: [],
		}
	},
	computed: {
		...mapState(['reminders_groups', 'product_cats'])
	},
	methods: {
		closeAddNewModal() {
			this.showAddModal = false;
		},
		closeUpdateModal() {
			this.showUpdateModal = false;
		},
		createNewGroup() {
			this.$store.dispatch('createReminderGroup', this.group).then(() => {
				this.closeAddNewModal();
				this.group = {title: '', product_categories: '', cta_link: '', menu_order: ''}
			});
		},
		updateGroup() {
			this.$store.dispatch('updateReminderGroup', this.activeGroup).then(() => {
				this.closeUpdateModal();
				this.activeGroup = {id: 0, title: '', product_categories: '', cta_link: '', menu_order: ''}
			});
		},
		selectItems(items) {
			this.selectedItems = items;
		},
		handleAction(action, item) {
			if (action === 'edit') {
				this.activeGroup = item;
				this.showUpdateModal = true;
			} else if (action === 'view-email-template') {
				let a = document.createElement('a');
				a.href = item.email_template_url;
				a.target = '_blank';
				a.click();
				a.remove();
			} else if (action === 'delete') {
				this.$dialog.confirm('Are you sure to delete this reminder group?').then(confirmed => {
					if (confirmed) {
						this.$store.dispatch('deleteReminderGroup', item.id).then(() => {
							this.selectedItems = [];
						}).catch(error => {
							console.log(error.message);
						});
					}
				})
			}
		}
	},
	mounted() {
		this.$store.dispatch('getRemindersGroups');
	}
}
</script>
