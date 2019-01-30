<template>
    <div>
        <h3>Entry</h3>
        <div>
            <el-row class="payform-entry-first-row">
                <p class="payform-entry-title">Name: {{userDetails.customer_name}}</p>
                <p class="payform-entry-mail">{{userDetails.customer_email}}</p>
            </el-row>

            <el-row class="payform-entry-details">
                <el-col :span="12">
                    <p>currency: {{userDetails.currency}}</p>
                    <p>User Id: {{userDetails.user_id}}</p>
                    <p>Customer Id:{{userDetails.customer_id}}</p>
                    <p>Payment Status:{{userDetails.payment_status}}</p>
                </el-col>
                <el-col :span="12">
                    <p>Total Payment: {{userDetails.payment_total}}</p>
                    <p>status: {{userDetails.status}}</p>
                    <p>Created At: {{userDetails.created_at}}</p>
                    <p>Updated At: {{userDetails.updated_at}}</p>
                </el-col>
            </el-row>
        </div>
    </div>

</template>

<script>
    export default {
        name: "Entry",
        props: ['form_id', 'entry_id'],
        data() {
            return {
                userDetails: {},
            }
        },
        mounted() {
            const query = {
                action: 'wpf_get_submission',
                form_id: this.form_id,
                submission_id: this.entry_id
            }
            this.$get(query)
                .then(response => {
                    this.userDetails = response.data.submission
                    console.log(this.userDetails)
                })
        }
    }
</script>

<style lang="scss" scoped>
    .payform-entry{
        &-first-row {
            text-align: center;
            p {
                font-size: 20px;
                line-height: 8px;
            }
        }
        &-details {
            text-align: center;
        }

    }
</style>
