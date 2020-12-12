import CrudMixin from "./CrudMixin";

const DataTableMixin = {
	mixins: [CrudMixin],
	data() {
		return {
			status: 'all',
			items: [],
			selectedItems: [],
			current_page: 1,
			per_page: 20,
			total_items: 0,
			item: {},
			activeItem: {},
			// Need to overwrite on child
			restEndpoint: '',
		}
	},
	methods: {
		onItemSelect(ids) {
			this.selectedItems = ids;
		},
		paginate(page) {
			this.current_page = page;
			this.getItems();
		},
		onActionClick(action, item) {
			if (['trash', 'restore', 'delete'].indexOf(action) !== -1) {
				this.trashRestoreDeleteAction(item.id, action);
			}
		},
		trashRestoreDeleteAction(itemId, action = 'trash') {
			if (action === 'trash') {
				this.trashItem(itemId);
			} else if (action === 'restore') {
				this.restoreItem(itemId);
			} else if (action === 'delete') {
				this.deleteItem(itemId);
			}
		},
		batchTrashRestoreDeleteAction(action = 'trash') {
			if (['trash', 'restore', 'delete'].indexOf(action) !== -1) {
				this.batchAction(action, this.selectedItems);
			}
		},
		getItemsParams() {
			return {
				page: this.current_page,
				per_page: this.per_page,
				status: this.status,
			}
		},
		getItems() {
			this.get_items(this.restEndpoint, {params: this.getItemsParams()}).then(data => {
				this.items = data.items;
				this.total_items = data.pagination.total_items;
			})
		},
		getItem(itemId) {
			this.get_items(`${this.restEndpoint}/${itemId}`).then(data => {
				this.item = data;
			})
		},
		createItem() {
			this.create_item(this.restEndpoint, this.item).then(() => {
				this.getItems();
			})
		},
		updateItem() {
			let data = this.activeItem;
			this.create_item(`${this.restEndpoint}/${data.id}`, data).then(() => {
				this.getItems();
			})
		},
		trashItem(itemId) {
			this.$dialog.confirm(`Are you sure to trash this item?`).then(confirmed => {
				if (confirmed) {
					this.create_item(`${this.restEndpoint}/${itemId}/trash`).then(() => {
						this.getItems();
					})
				}
			});
		},
		restoreItem(itemId) {
			this.$dialog.confirm(`Are you sure to restore this item?`).then(confirmed => {
				if (confirmed) {
					this.create_item(`${this.restEndpoint}/${itemId}/trash`).then(() => {
						this.getItems();
					})
				}
			});
		},
		deleteItem(itemId) {
			this.$dialog.confirm(`Are you sure to delete this item?`).then(confirmed => {
				if (confirmed) {
					this.delete_item(`${this.restEndpoint}/${itemId}`).then(() => {
						this.getItems();
					})
				}
			});
		},
		batchAction(action, data) {
			const _data = {};
			_data[action] = data;
			this.create_item(`${this.restEndpoint}/batch`, _data).then(() => {
				this.getItems();
			})
		},
	}
}

export {DataTableMixin}
export default DataTableMixin
