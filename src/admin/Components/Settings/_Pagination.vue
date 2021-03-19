<template>
    <el-pagination class="fluentcrm-pagination"
                   :background="false"
                   layout="total, sizes, prev, pager, next"
                   @current-change="changePage"
                   @size-change="changeSize"
                   :hide-on-single-page="true"
                   :current-page.sync="pagination.current_page"
                   :page-sizes="page_sizes"
                   :page-size="pagination.per_page"
                   :total="pagination.total"
    />
</template>

<script type="text/babel">
export default {
    name: 'Pagination',
    props: {
        pagination: {
            required: true,
            type: Object
        }
    },
    computed: {
        page_sizes() {
            const sizes = [];
            if (this.pagination.per_page < 10) {
                sizes.push(this.pagination.per_page);
            }

            const defaults = [
                10,
                20,
                50,
                80,
                100,
                120,
                150
            ];

            return [...sizes, ...defaults];
        }
    },
    methods: {
        changePage(page) {
            this.pagination.current_page = page;

            this.$emit('fetch');
        },
        changeSize(size) {
            this.pagination.per_page = size;

            this.$emit('fetch');
        }
    }
}
</script>
