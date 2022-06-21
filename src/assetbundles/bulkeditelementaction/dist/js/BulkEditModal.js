/** global: Craft */
/** global: Garnish */
/** global: $ */
// noinspection JSVoidFunctionReturnValueUsed

/**
 * Select Fields Modal
 */
Craft.BulkEditModal = Garnish.Modal.extend({
        elementIndex: null,
        elementTypeName: null,

        viewParams: null,
        selectedElementIds: null,
        fieldConfig: {},

        $spinner: null,
        elementIds: [],
        fieldIds: [],
        type: null,
        requestId: 0,

        namespace: null,

        /**
         * Initialize the preview file modal.
         * @returns {*|void}
         */
        init: function(elementIndex, settings) {
            this.base();
            this.setSettings(settings, {
                resizable: true,
            });

            this.elementIndex = elementIndex;

            const $container = $('<div id="select-fields-modal" class="modal loading"/>')
            this.setContainer($container);
            this.show();

            this.selectedElementIds = this.elementIndex.getSelectedElementIds()

            let viewParams = this.elementIndex.getViewParams();
            delete viewParams.criteria.offset;
            delete viewParams.criteria.limit;
            delete viewParams.collapsedElementIds;
            viewParams.selectAll = false

            if(this.selectedElementIds) {
                viewParams.criteria.id = this.selectedElementIds
            }
            viewParams.siteId = viewParams.criteria.siteId
            this.viewParams = viewParams
            this.loadFieldSelector()
        },

        _initSpinner() {
            this.$container.addClass('loading');
            this.$spinner = $('<div class="spinner centeralign"></div>').appendTo(this.$container);
            var top = (this.$container.height() / 2 - this.$spinner.height() / 2) + 'px',
                left = (this.$container.width() / 2 - this.$spinner.width() / 2) + 'px';

            this.$spinner.css({left: left, top: top, position: 'absolute'});
        },



        /**
         * Loads a list of fields that can be edited given the current selection
         */
        loadFieldSelector: function() {
            this._initSpinner();
            this.$container.find('.field-edit-modal').remove();

            Craft.sendActionRequest('POST', 'venveo-bulk-edit/bulk-edit/get-fields', {
                data: this.viewParams
            })
                .then((response) => {
                    const data = response.data
                    this.$container.append(data.modalHtml);
                    this.namespace = data.namespace
                    Craft.initUiElements(this.$container);
                    this._bindEventHandlersForFieldSelect();
                })
                .finally(() => {
                    this.$container.removeClass('loading');
                    this.$spinner.remove();
                });
        },

        loadFieldEditor: function(fieldConfig) {
            this._initSpinner();
            this.$container.find('.field-edit-modal').remove();


            Craft.sendActionRequest('POST', 'venveo-bulk-edit/bulk-edit/get-edit-screen', {
                data: {
                    ...this.viewParams,
                    fieldConfig: fieldConfig,
                    namespace: this.namespace
                }
            })
                .then((response) => {
                    const data = response.data
                    this.$container.append(data.modalHtml);
                    Craft.appendHeadHtml(data.headHtml);
                    Craft.appendBodyHtml(data.footHtml);
                    Craft.initUiElements(this.$container);

                    this._unbindEventHandlersForFieldSelect();
                    this._bindEventHandlersForFieldEditor();
                })
                .finally(() => {
                    this.$container.removeClass('loading');
                    this.$spinner.remove();
                });
        },


        _bindEventHandlersForFieldEditor: function() {
            this.$container.find('#field-edit-cancel').on('click', this.hide.bind(this));
            this.$container.find('.submit').on('click', this._handleFieldEditorSubmit.bind(this));
        },

        _unbindEventHandlersForFieldEditor: function() {
            this.$container.find('#field-edit-cancel').off('click', this.hide.bind(this));
            this.$container.find('.submit').off('click', this._handleFieldEditorSubmit.bind(this));
        },

        _bindEventHandlersForFieldSelect: function() {
            this.$container.find('#field-edit-cancel').on('click', this.hide.bind(this));
            this.$container.find('#fields-table .lightswitch').on('change', this._handleFieldSelect.bind(this));
            this.$container.find('#select-fields-form').on('submit', this._handleFieldSelectSubmit.bind(this));
            this.$container.find('#bulk-edit-select-all').on('change', this._handleFieldSelectToggleAll.bind(this))
        },

        _unbindEventHandlersForFieldSelect: function() {
            this.$container.find('#field-edit-cancel').off('click', this.hide.bind(this));
            this.$container.find('#fields-table .lightswitch').off('change', this._handleFieldSelect.bind(this));
            this.$container.find('#select-fields-form').off('submit', this._handleFieldSelectSubmit.bind(this));
            this.$container.find('#bulk-edit-select-all').off('change', this._handleFieldSelectToggleAll.bind(this))
        },

        _handleFieldSelectToggleAll: function(e) {
            this.viewParams.selectAll = e.target.checked
            if(this.viewParams.selectAll) {
                this.viewParams.criteria.id = null
            } else {
                this.viewParams.criteria.id = this.selectedElementIds
            }
            this.loadFieldSelector()
        },

        _getCheckedFields: function() {
            let values = []
            this.$container.find('.lightswitch.on > input').each(function() {
                values.push($(this).val())
            });
            return values;
        },

        _handleFieldEditorSubmit: function(e) {
            e.preventDefault();
            this.$container.find('.submit').attr('disabled', 'disabled');
            this.$container.find('.submit').addClass('disabled')
            const formValues = new FormData(this.$container.find('#bulk-edit-values-modal')[0])
            Craft.sendActionRequest('POST', 'venveo-bulk-edit/bulk-edit/save-context', {
                data: {
                    ...this.viewParams,
                    fieldConfig: this.fieldConfig,
                    formValues: (new URLSearchParams(formValues)).toString(),
                    namespace: this.namespace
                }
            }).then(() => {
                    Craft.cp.trackJobProgress(false, true);
                    Craft.cp.runQueue();
            }).finally(() => {
                this.hide();
            })
        },

        _handleFieldSelectSubmit: function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const formDataObject = this._getFieldConfig(formData)
            this.fieldConfig = formDataObject
            this.$container.find('.field-edit-modal').remove();
            this.loadFieldEditor(formDataObject);
        },

        _handleFieldSelect: function(e) {
            var enabledSwitches = this.$container.find('.lightswitch.on');
            if (enabledSwitches.length) {
                this.$container.find('.submit').removeAttr('disabled');
                this.$container.find('.submit').removeClass('disabled');
            } else {
                this.$container.find('.submit').attr('disabled', 'disabled');
                this.$container.find('.submit').addClass('disabled');
            }
        },

        _getFieldConfig: function(formData) {
            var formDataObject = {};
            const fieldHandleRegex = /fields\[(\d+)\]\[(.+)\]/;
            formData.forEach((value, key) => {
                const fieldHandle = key.match(fieldHandleRegex)[1]
                const propertyName = key.match(fieldHandleRegex)[2]
                if (!formDataObject.hasOwnProperty(fieldHandle)) {
                    formDataObject[fieldHandle] = {
                        id: fieldHandle
                    }
                }
                formDataObject[fieldHandle][propertyName] = value
            });
            return formDataObject
        },


        /**
         * Disappear immediately forever.
         * @returns {boolean}
         */
        selfDestruct: function() {
            debugger;
            var instance = Craft.BulkEditModal.openInstance;

            instance.hide();
            instance.$shade.remove();
            instance.destroy();

            Craft.BulkEditModal.openInstance = null;

            return true;
        },

        /**
         * When hiding, remove all traces and focus last focused element.
         * @private
         */
        _onHide: function() {
            debugger;
            Craft.BulkEditModal.openInstance = null;
            this.$shade.remove();
            this._unbindEventHandlersForFieldSelect();
            this._unbindEventHandlersForFieldEditor();
            return this.destroy();
        },

        /**
         * Compare two arrays for equivalence
         * @param arr1
         * @param arr2
         * @returns {boolean}
         * @private
         */
        _compareArray(arr1, arr2) {
            if (arr1.length !== arr2.length) {
                return false;
            }

            for (var i = arr1.length; i--;) {
                if (arr1[i] !== arr2[i]) {
                    return false;
                }
            }
            return true;
        },
    },
    {
        defaultSettings: {}
    }
);
