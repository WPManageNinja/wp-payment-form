<template>
    <div class="add_on_modules">
        <div class="modules_header">
            <div class="module_title">
                WP Pay Form Module Manager
            </div>
            <div class="module_search">
            </div>
        </div>
        <div class="modules_body">
            <div v-for="(addon, addonKey) in addOns" :key="addonKey" :class="'addon_enabled_'+addon.enabled" class="add_on_card">
                <div class="addon_header">{{addon.title}}</div>
                <div class="addon_body">
                    <img v-if="addon.logo" :src="addon.logo" />
                    {{addon.description}}
                </div>
                <div class="addon_footer">
                    <!-- <template v-if="addon.purchase_url">
                        <a class="pro_update_btn" rel="noopener" :href="addon.purchase_url">Upgrade To Pro</a>
                    </template> -->
                    <template>
                        <el-switch active-color="#13ce66" @change="saveStatus(addonKey)" active-value="yes" inactive-value="no" v-model="addon.enabled" />
                        <span>Currently</span> <span v-if="addon.enabled == 'yes'">Enabled</span><span v-else>Disabled</span>
                    </template>
                    <a style="float: right;text-decoration: none;" v-if="addon.config_url && addon.enabled == 'yes'" :href="addon.config_url"><span class="dashicons dashicons-admin-generic"></span></a>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'pay_form_addon_modules',
        data() {
            return {
                addOns: window.payform_addon_modules.addons,
                // has_pro: window.fluent_addon_modules.has_pro,
            }
        },
        methods: {
            saveStatus(addonKey) {
                               

                let addonModules = {};
                jQuery.each(this.addOns, (key, addon) => {
                    addonModules[key] = addon.enabled;
                });
                window.WPPayForms.$post({ 
                    route:  'payform_update_modules', 
                    action: 'wppayform_forms_admin_ajax',
                    addons:  addonModules
                 }).then((response) => {
                     console.log(response);
                    this.$message({
                        message: response.data.message,
                        type: 'success',
                        offset: 32
                    });
                })
                .fail(error => console.error(error))
                .always(() => {});
            }
        },
        created() {
            console.log(window.payform_addon_modules.addons)
        }
    }
</script>

<style lang="scss">
    .add_on_modules {
        .modules_header {
            background: white;
            margin: 20px 0px;
            padding: 20px;
            width: 100%;
            display: block;
        }
        .module_title {
            display: inline-block;
            font-size: 18px;
            font-weight: 500;
        }

        .modules_body {
            width: 100%;
            clear: both;
            overflow: hidden;
            .add_on_card {
                min-width: 300px;
                background: white;
                margin: 1% 1% 1% 1%;
                float: left;
                width: 31%;
                border-radius: 5px;
                box-shadow: -1px 0px 4px 2px #c7c7c7;
                &.addon_enabled_no {
                    img {
                        -webkit-filter: grayscale(100%);
                        filter: grayscale(100%);
                    }
                }
            }
            .addon_header {
                padding: 10px;
                text-align: center;
                border-bottom: 1px solid #f1f1f1;
                font-weight: bold;
            }
            .addon_body {
                padding: 15px;
                height: 130px;
                overflow: hidden;
                img {
                    max-width: 50%;
                    max-height: 30px;
                    text-align: center;
                    display: block;
                    margin: 0 auto;
                    margin-bottom: 20px;
                }
            }
            .addon_footer {
                border-top: 1px solid #e2dfdf;
                padding: 10px 15px;
            }
            a.pro_update_btn {
                display: inline-block;
                margin: 0 auto;
                text-align: center;
                width: auto;
                padding: 3px 10px;
                border: 1px solid #356ae6;
                font-weight: bold;
                text-decoration: none;
                color: #366be6;
                border-radius: 4px;
                &:hover {
                    color: white;
                    background:  #366be6;
                }
            }
        }
    }
</style>