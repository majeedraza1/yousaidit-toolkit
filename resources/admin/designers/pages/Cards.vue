<template>
  <div>
    <h1 class="wp-heading-inline">Cards</h1>
    <hr class="wp-header-end">
    <columns multiline>
      <column :tablet="4"></column>
      <column :tablet="4"></column>
      <column :tablet="4">
        <search-form placeholder="Search title, sku" @input="handleSearchInput" @search="handleSearch"/>
      </column>
    </columns>
    <columns multiline>
      <column :tablet="8">
        <div class="relative flex space-x-2 items-center">
          <select-field
              label="Card Status"
              :options="statuses"
              label-key="label_with_count"
              value-key="key"
              v-model="status"
              :clearable="false"
          />
          <select-field
              label="Card Type"
              :options="[{label:'All',value:'all'},{label:'Static',value:'static'},{label:'Dynamic',value:'dynamic'}]"
              v-model="card_type"
              :clearable="false"
          />
          <select-field
              label="Card Designer"
              :options="designers"
              label-key="display_name"
              value-key="id"
              v-model="designer"
          />
        </div>
      </column>
      <column :tablet="4">
        <pagination :current_page="current_page" :per_page="per_page" :total_items="total_items"
                    @pagination="paginate"/>
      </column>
      <column :tablet="12">
        <data-table
            :items="items"
            :columns="columns"
            :show-cb="false"
            :actions="actions"
            @action:click="handleActionClick"
        >
          <template v-slot:card_sizes="data">
            <template v-for="(_size,index) in card_sizes"
                      v-if="data.row.card_sizes.indexOf(_size.value) !== -1">
              <template v-if="index === 0">{{ _size.label }}</template>
              <template v-else>, {{ _size.label }}</template>
            </template>
          </template>
          <template v-slot:status="data">
						<span v-for="_status in statuses" v-if="_status.key === data.row.status">
							{{ _status.label }}
						</span>
          </template>
          <template v-slot:designer="data">
            <a href="" @click.prevent="goToDesignerProfile(data.row.designer)">
              {{ data.row.designer.display_name }}
            </a>
          </template>
        </data-table>
      </column>
      <column :tablet="12">
        <pagination :current_page="current_page" :per_page="per_page" :total_items="total_items"
                    @pagination="paginate"/>
      </column>
    </columns>
  </div>
</template>

<script>
import {column, columns, dataTable, pagination, searchForm, selectField, statusList} from 'shapla-vue-components';
import axios from "@/utils/axios";
import {Spinner} from "@shapla/vanilla-components";

export default {
  name: "Cards",
  components: {dataTable, pagination, columns, column, searchForm, statusList, selectField},
  data() {
    return {
      items: [],
      columns: [
        {key: 'card_title', label: 'Title'},
        {key: 'card_type', label: 'Type'},
        {key: 'designer', label: 'Designer'},
        {key: 'card_sizes', label: 'Sizes'},
        {key: 'status', label: 'Status'},
        {key: 'card_sku', label: 'SKU'},
        {key: 'total_sale', label: 'Total Sales', numeric: true},
      ],
      current_page: 1,
      per_page: 20,
      total_items: 0,
      search: '',
      status: 'all',
      card_type: 'all',
      statuses: [],
      designers: [],
      designer: '',
      actions: [
        {key: 'view', label: 'View'}
      ],
    }
  },
  mounted() {
    Spinner.hide();
    this.getItems();
    this.getDesigners();
  },
  watch: {
    designer() {
      this.getItems();
    },
    status() {
      this.getItems();
    },
    card_type() {
      this.getItems();
    }
  },
  computed: {
    card_sizes() {
      return DesignerProfile.card_sizes.map(size => {
        return {
          value: size.slug,
          label: size.name
        }
      });
    },
  },
  methods: {
    handleStatusChange(status) {
      this.status = status.key;
    },
    handleSearchInput(search) {
      if (search.length < 1) {
        this.handleSearch('');
      }
    },
    handleSearch(search) {
      this.search = search;
      this.getItems();
    },
    paginate(page) {
      this.current_page = page;
      this.getItems();
    },
    getItemsFilteredByDesigner() {
    },
    getItems() {
      Spinner.show();
      axios.get(Stackonet.root + '/designers-cards', {
        params: {
          page: this.current_page,
          per_page: this.per_page,
          search: this.search,
          status: this.status,
          card_type: this.card_type,
          designer_id: this.designer,
        }
      }).then(response => {
        let data = response.data.data;
        this.items = data.items;
        this.statuses = data.statuses;
        this.total_items = data.pagination.total_items;
        Spinner.hide();
      }).catch(errors => {
        console.log(errors);
        Spinner.hide();
      })
    },
    handleActionClick(action, item) {
      if ('view' === action) {
        this.$router.push({name: 'Card', params: {id: item.id}});
      }
    },
    goToDesignerProfile(designer) {
      this.$router.push({name: 'Designer', params: {id: designer.id}});
    },
    getDesigners() {
      Spinner.show();
      axios.get(Stackonet.root + '/designers', {params: {page: 1, per_page: 100,}}).then(response => {
        let data = response.data.data;
        this.designers = data.items;
        Spinner.hide();
      }).catch(errors => {
        console.log(errors);
        Spinner.hide();
      });
    }
  }
}
</script>
