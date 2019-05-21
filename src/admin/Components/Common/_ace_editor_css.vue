<template>
    <div v-loading="loading" element-loading-text="Loading Editor...">
        <div class="ace_container">
            <div class="ninja_custom_css_editor" id="ninja_custom_css">{{ value }}</div>
        </div>
        <div  class="editor_errors" :class="'ninja_'+mode+'_errors'">
            <span v-show="editorError" style="text-align: right; display: inline-block; color: #ff7171; float: right">{{ editorError }}</span>
        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'ninja_ace_editor',
        props: ['value', 'mode', 'editor_id'],
        data() {
            return {
                ace_path: window.wpPayFormsAdmin.ace_path_url,
                editorError: '',
                loading: true
            }
        },
        methods: {
            loadDependencies() {
                if(typeof ace == 'undefined') {
                    jQuery.get(this.ace_path + '/ace.min.js', () => {
                        this.initAce();
                    });
                } else {
                    this.initAce();
                }
            },
            initAce() {
                ace.config.set("workerPath", this.ace_path);
                ace.config.set("modePath", this.ace_path);
                ace.config.set("themePath", this.ace_path);
                let editor = ace.edit('ninja_custom_css');
                editor.setTheme("ace/theme/monokai");
                editor.session.setMode("ace/mode/"+this.mode);
                editor.getSession().on("changeAnnotation", () => {
                    var annot = editor.getSession().getAnnotations();
                    this.editorError = '';
                    for (var key in annot) {
                       if(annot[key].type == 'error') {
                           this.editorError = annot[key].text;
                       }
                    }
                });
                editor.getSession().on("change", () => {
                    this.$emit('input', editor.getSession().getValue());
                });
                this.loading = false;
            }
        },
        mounted() {
            this.loadDependencies();
        }
    }
</script>

<style>
    .ninja_custom_css_editor {
       min-height: 350px;
        height: auto;
    }
    .ninja_css_errors .ace_gutter-cell.ace_warning {
        display: none;
    }
</style>
