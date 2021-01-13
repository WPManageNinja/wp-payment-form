<template>
    <el-row v-if="roles.length">
        <div class="wpf_ninja_header">
            <h2>Permission</h2>
        </div>
        <div v-loading="loading" class="wpf_ninja_content">
            <div class="wpf_ninja_block">
                <p>By default, only Administrator have access to manage the forms. By selecting additional roles bellow, you can give access to manage your forms to other user roles.</p>
            </div>
            <hr />
            <template>
                <div class="wpf-form-group">
                    <el-checkbox :indeterminate="isIndeterminate" v-model="checkAll" @change="handleCheckAllChange">
                        Check all
                    </el-checkbox>
                </div>

                <div class="wpf-form-group">
                    <el-checkbox-group v-model="capability" @change="handleCheckedCapabilitiesChange">
                        <el-checkbox v-for="role in roles" :label="role.key" :key="role.key">
                            {{ role.name }}
                        </el-checkbox>
                    </el-checkbox-group>
                </div>
            </template>
        </div>
    </el-row>
</template>

<script>
    export default {
        name: "Privacy",
        data() {
            return {
                loading: false,
                roles: [],
                checkAll: false,
                capability: ["administrator"],
                isIndeterminate: false
            };
        },
        methods: {
            get() {
                this.loading = true;
                this.$get({
                    action: 'wpf_global_settings_handler',
                    route: 'get_access_roles'
                })
                    .then(response => {
                        let capability = response.data.capability;
                        if(!capability || typeof capability != 'object') {
                            capability = ["administrator"];
                        }
                        this.capability = capability;
                        this.roles = response.data.roles;
                        this.handleCheckedCapabilitiesChange(this.capability);
                    })
                    .fail(e => {})
                    .always(() => {
                        this.loading = false;
                    });
            },
            store() {
                if(!this.roles.length) {
                    return;
                }
                this.loading = true;
                this.$post({
                    action: "wpf_global_settings_handler",
                    route: "set_access_roles",
                    capability: this.capability
                })
                    .then(res => {
                        this.loading = false;
                    })
                    .fail(e => {
                        this.loading = false;
                        this.$notify.error( {
                            message: e.responseJSON.data.message,
                            offset: 30
                        });
                    }).always(()=>{
                        this.loading = false;
                    });

            },
            handleCheckAllChange(val) {
                this.capability = val ? this.roles.map(item => item.key) : [];
                this.isIndeterminate = false;
            },
            handleCheckedCapabilitiesChange(value) {
                let checkedCount = value.length;
                this.checkAll = checkedCount === this.roles.length;
                this.isIndeterminate = checkedCount > 0 && checkedCount < this.roles.length;
                this.store();
            }
        },
        mounted() {
            this.get();
        }
    };
</script>

<style scoped>
    .wpf-form-group {
        margin-bottom: 20px;
    }
</style>


