<template>
    <div v-if="errors.has(field)" class="el-form-item__error">
        <p>{{ errors.first(field) }}</p>
    </div>
</template>

<script>
    export default {
        name: 'errorView',
        props: ['field', 'errors']
    }
</script>

<style lang="scss">
    .is-error .el-input__inner {
        border-color: #ff4949;
    }

    .el-form-item__error {
        /*position: unset;*/
    }

    .el-form-item__error {
        position: relative !important;
        p {
            margin-bottom: 0px;
            margin-top: 0px;
        }
    }
</style>