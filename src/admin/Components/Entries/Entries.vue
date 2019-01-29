<template>
    <div>Entries</div>
</template>

<script>
    export default {
        name: "Entries",
        props: ['form_id'],
        mounted() {
            const query = {
                action: 'wpf_get_submissions',
                form_id: this.form_id,
                page_number: 1,
                per_page: 15
            };

            this.$get(query)
                .then(response => {
                    console.log(response);
                });
        }
    }
</script>
