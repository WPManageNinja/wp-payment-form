<template>
    <div class="ninja_intro_welcome">
        <h2>Welcome to WPPayForm</h2>
        <p>Thank you for installing WPPayForm - WordPress Payments Made Simple</p>
        <div class="ninja_actions">
            <el-button type="success" @click="create">
                Create a Form
            </el-button>
        </div>
        <hr/>
        <div class="ninja_docs">
            <h4>WPPayForm Documentation:</h4>
            <ul>
                <li>
                    <a target="_blank"
                       href="https://wppayform.wpmanageninja.com/?payform_intro=1">
                        View Available Demos
                    </a>
                </li>
                <li>
                    <a target="_blank"
                       href="https://wpmanageninja.com/docs/wppayform/getting-started-with-wppayform/configure-payment-methods-and-currency/?payform_intro=1">
                        Configure Stripe Payment API
                    </a>
                </li>
                <li>
                    <a target="_blank"
                       href="https://wpmanageninja.com/docs/wppayform/form-configuration-wppayform/create-your-first-payment-form-under-a-minute-and-accept-payments/">
                        Setting up a Form in a minute and accept payments
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        name: "Welcome",
        data() {
            return {
                docs: []
            }
        },
        methods: {
            create() {
                this.$emit('create', true);
            }
        }
    }
</script>

<style lang="scss">
    .ninja_intro_welcome {
        max-width: 600px;
        margin: 45px auto 0px;
        padding: 30px 20px;
        background: white;
        text-align: center;

        h2 {
            font-size: 30px;
        }

        .ninja_actions {
            margin-bottom: 30px;
        }

        .ninja_docs {
            text-align: left;
        }
    }
</style>
