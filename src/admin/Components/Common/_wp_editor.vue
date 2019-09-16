<template>
    <div class="wp_vue_editor_wrapper">
        <popover
            v-if="editorShortcodes.length"
            class="popover-wrapper"
            :class="{'popover-wrapper-plaintext': !hasWpEditor}"
            :data="editorShortcodes"
            @command="handleCommand"></popover>
        <textarea v-if="hasWpEditor && !no_tiny_mce" class="wp_vue_editor" :id="editor_id">{{value}}</textarea>

        <el-input :rows="4" class="wp_vue_editor wp_vue_editor_plain" v-else type="textarea" v-model="plain_content" />
    </div>
</template>

<script type="text/babel">
    import popover from './input-popover-dropdown.vue'

    export default {
        name: 'wp_editor',
        components: {
            popover
        },
        props: {
            editor_id: {
                type: String,
                default() {
                    return 'wp_editor_' + Date.now() + parseInt(Math.random() * 1000);
                }
            },
            value: {
                type: String,
                default() {
                    return '';
                }
            },
            editorShortcodes: {
                type: Array,
                default() {
                    return []
                }
            },
            height: Number
        },
        data() {
            return {
                hasWpEditor: !!window.wp.editor,
                plain_content: this.value,
                cursorPos: this.value.length,
                no_tiny_mce: false
            }
        },
        watch: {
            plain_content() {
                if(this.no_tiny_mce) {
                    this.$emit('input', this.plain_content);
                }
            }
        },
        methods: {
            initEditor() {
                if (!window.tinymce) {
                    this.no_tiny_mce = true;
                    return;
                }
                this.no_tiny_mce = false;
                wp.editor.remove(this.editor_id);
                const that = this;
                wp.editor.initialize(this.editor_id, {
                    mediaButtons: false,
                    tinymce: {
                        height: that.height,
                        toolbar1: 'bold,italic,bullist,numlist,link,blockquote,alignleft,aligncenter,alignright,strikethrough,forecolor,codeformat,undo,redo',
                        setup(ed) {
                            ed.on('init', (ed) => {
                                    tinyMCE.get(that.editor_id).setContent(that.value);
                                    tinyMCE.execCommand('mceRepaint');
                                }
                            );
                            ed.on('change', function (ed, l) {
                                that.changeContentEvent();
                            });
                        }
                    },
                    quicktags: true
                });

                jQuery('#' + this.editor_id).on('change', function (e) {
                    that.changeContentEvent();
                });
            },
            changeContentEvent() {
                let content = wp.editor.getContent(this.editor_id);
                this.$emit('input', content);
            },
            handleCommand(command) {
                if (this.hasWpEditor) {
                    tinymce.activeEditor.insertContent(command);
                } else {
                    var part1 = this.plain_content.slice(0, this.cursorPos);
                    var part2 = this.plain_content.slice(this.cursorPos, this.plain_content.length);
                    this.plain_content = part1 + command + part2;
                    this.cursorPos += command.length;
                }
            },
            updateCursorPos() {
                var cursorPos = jQuery('.wp_vue_editor_plain').prop('selectionStart');
                this.$set(this, 'cursorPos', cursorPos);
            },
            reloadEditor() {
                wp.editor.remove(this.editor_id);
                jQuery('#' + this.editor_id).val('');
                this.initEditor();
            }
        },
        mounted() {
            if (this.hasWpEditor) {
                this.initEditor();
            }
        }
    }
</script>
<style lang="scss">
    .wp_vue_editor {
        width: 100%;
        min-height: 100px;
    }

    .wp_vue_editor.wp_vue_editor_plain.el-textarea {
        margin-top: 30px;
    }

    .wp_vue_editor_wrapper {
        position: relative;

        .popover-wrapper {
            z-index: 2;
            position: absolute;
            top: 0;
            right: 0;

            &-plaintext {
                left: auto;
                right: 0;
                top: -32px;
            }
        }
        .wp-editor-tabs {
            float: left;
        }
    }
</style>