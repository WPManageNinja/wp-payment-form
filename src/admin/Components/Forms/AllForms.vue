<template>
    <div v-loading="loading" class="wppayforms">
        <welcome v-if="!hasForms" @create="createFormModal = true"/>
        <!--We Have forms Now-->
        <div class="all_payforms_wrapper payform_section" v-else>
            <div class="payform_section_header">
                <h1 class="payform_section_title">
                    {{ $t('All Payment Forms') }}
                </h1>

                <div class="payform_section_actions">
                    <div class="payform_action search_action">
                        <el-input size="small" placeholder="Search" v-model="search_string" class="input-with-select">
                            <el-button slot="append" icon="el-icon-search"></el-button>
                        </el-input>
                    </div>
                    <el-button class="payform_action" @click="createFormModal = true" size="small" type="primary">
                        {{ $t( 'Add Payment Form' ) }}
                    </el-button>
                </div>
            </div>
            <div class="payform_section_body">
                <el-table
                    class="payform_tables"
                    v-loading.body="loading"
                    :data="paymentForms"
                    border>

                    <el-table-column :label="$t('ID')" width="70">
                        <template slot-scope="scope">
                            <router-link :to="{ name: 'data_items', params: { table_id: scope.row.ID } }">
                                {{ scope.row.ID }}
                            </router-link>
                        </template>
                    </el-table-column>

                    <el-table-column :label="$t('Title')">
                        <template slot-scope="scope">
                            <strong>
                                {{ scope.row.post_title }}
                            </strong>
                            <div class="row-actions">
                                <router-link :to="{ name: 'payment_options', params: { form_id: scope.row.ID } }">
                                    {{ $t('Edit') }}
                                </router-link> |
                                <a :href="scope.row.preview_url" target="_blank">{{ $t('Preview') }}</a> |
                                <a href="#" @click.prevent="duplicate(scope.row.ID)">{{ $t('Duplicate') }}</a> |
                                <a @click.prevent="confirmDeleteTable(scope.row.ID)" href="#">{{ $t('Delete') }}</a>
                            </div>
                        </template>
                    </el-table-column>

                    <el-table-column :label="$t('ShortCode')">
                        <template slot-scope="scope">
                            <el-tooltip effect="dark"
                                        content="Click to copy shortcode"
                                        title="Click to copy shortcode"
                                        placement="top">
                                <code class="copy"
                                      :data-clipboard-text='`[wp_payment_form id="${scope.row.ID}"]`'>
                                    <i class="el-icon-document"></i> [wp_payment_form id="{{ scope.row.ID }}"]
                                </code>
                            </el-tooltip>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
        </div>
        <!-- Load Modals-->
        <create-form v-if="createFormModal" :modalVisible.sync="createFormModal"/>
    </div>
</template>

<script type="text/babel">
    import Welcome from '../Common/Welcome';
    import CreateForm from './CreateForm';

    export default {
        name: 'AllForms',
        components: {CreateForm, Welcome},
        comments: {
            Welcome
        },
        data() {
            return {
                createFormModal: false,
                paymentForms: [],
                hasForms: false,
                per_page: 10,
                page_number: 1,
                search_string: '',
                total: 0,
                loading: false
            }
        },
        methods: {
            fetchForms() {
                this.loading = true;
                this.$adminGet({
                    route: 'get_forms',
                    per_page: this.per_page,
                    page_number: this.page_number,
                    search_string: this.search_string
                })
                    .then(response => {
                        console.log(response);
                        this.paymentForms = response.data.forms;
                        this.hasForms = !!response.data.total;
                        this.total = response.data.total;
                    })
                    .fail(error => {

                    })
                    .always(() => {
                        this.loading = false;
                    });
            }
        },
        mounted() {
            this.fetchForms();
        }
    }
</script>