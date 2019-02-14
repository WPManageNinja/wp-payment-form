<template>
    <div class="ninja_intro_welcome">
        <h2>Welcome to WP Payment Form</h2>
        <p>Thank you for installing WP Payment Form - Simply accept payments in minutes</p>
        <div class="ninja_actions">
            <el-button type="success" @click="create">
                Create a Payment Form
            </el-button>
        </div>
        <template v-if="docs.length">
            <hr/>
            <div class="ninja_docs">
                <h4>WP Payment Form Documentation:</h4>
                <ul>
                    <li>
                        <a target="_blank"
                           href="https://wpmanageninja.com/docs/ninja-tables/configure-tables/?ninja_intro=1">
                            Demo and Basic Settings
                        </a>
                    </li>
                    <li>
                        <a target="_blank"
                           href="https://wpmanageninja.com/docs/ninja-tables/setting-up-a-table/?ninja_intro=1">
                            Configure Stripe Payment API
                        </a>
                    </li>
                    <li>
                        <a target="_blank"
                           href="https://wpmanageninja.com/docs/ninja-tables/configure-responsive-breakdowns-for-table/?ninja_intro=1">
                            Setting Up a Form
                        </a>
                    </li>
                </ul>
            </div>
        </template>

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
