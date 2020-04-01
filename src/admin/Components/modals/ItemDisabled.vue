<template>
    <div :class="{'ff_backdrop': visibility}">
        <el-dialog
            title="Field disabled"
            :visible.sync="isVisible"
            :before-close="close"
            width="50%">
            <template v-if="contentComponent">
                <component :is="contentComponent"></component>
            </template>

            <template v-else>
                <div style="text-align: center;">
                    <p style="margin-bottom: 30px; font-size: 18px;">This field is only available on pro add-on</p>

                    <a  target="_blank"
                        class="el-button el-button--danger"
                        :href="campaignUrl" >
                        Buy Pro Now
                    </a>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script>
    import recaptcha from './Recaptcha.vue';

    export default {
        name: 'ItemDisabled',
        props: ['visibility', 'modal', 'value'],
        components: { recaptcha },
        data() {
            return {
                contentComponent: '',
                campaignUrl: 'https://wpmanageninja.com/downloads/fluentform-pro-add-on/?utm_source=fluentform&utm_medium=wp&utm_campaign=wp_plugin&utm_term=upgrade&utm_content=pop'
            }
        },
        watch: {
            modal() {
                if (this.modal && this.modal.contentComponent) {
                    this.contentComponent = this.modal.contentComponent
                }
            }
        },
        computed: {
            isVisible() {
                return !!this.visibility || !!this.value;
            }
        },
        methods: {
            close() {
                this.$emit('update:visibility', false);
                this.$emit('input', false);
                setTimeout(() => {
                    this.contentComponent = '';
                }, 350);
            }
        }
    }
</script>