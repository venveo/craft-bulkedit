/** global: Craft */
/** global: Garnish */
/** global: $ */

/**
 * Select Fields Modal
 */
Craft.BulkEditModal = Garnish.Modal.extend(
    {
        $spinner: null,
        siteId: null,
        elementIds: [],
        fieldIds: [],
        type: null,
        loaded: null,
        requestId: 0,

        /**
         * Initialize the preview file modal.
         * @returns {*|void}
         */
        init: function(elementIds, settings) {
            settings = $.extend(this.defaultSettings, settings);

            settings.onHide = this._onHide.bind(this);

            if (Craft.BulkEditModal.openInstance) {
                if (!this._compareArray(elementIds, this.elementIds)) {
                    var instance = Craft.BulkEditModal.openInstance;
                    instance.loadFields(elementIds);
                }
                return this.destroy();
            }

            Craft.BulkEditModal.openInstance = this;
            this.elementIds = elementIds;

            this.$container = $('<div id="select-fields-modal" class="modal loading"/>').appendTo(Garnish.$bod);

            this.base(this.$container, $.extend({
                resizable: false
            }, settings));

            // Cut the flicker, just show the nice person the preview.
            if (this.$container) {
                this.$container.velocity('stop');
                this.$container.show().css('opacity', 1);

                this.$shade.velocity('stop');
                this.$shade.show().css('opacity', 1);
            }

            this.loadFields(elementIds)
        },

        loadFieldEditor: function(data) {
            // Make sure we tack on our request ID to the form submission...
            data = data + '&requestId=' + this.requestId;
            Craft.postActionRequest('venveo-bulk-edit/bulk-edit/get-edit-screen', data, function(response, textStatus) {
                if (textStatus === 'success') {
                    if (response.success) {
                        if (response.requestId != this.requestId) {
                            return;
                        }
                        this.$container.removeClass('loading');
                        this.$spinner.remove();
                        this.loaded = true;
                        this.$container.append(response.modalHtml);
                        Craft.initUiElements(this.$container);
                        Craft.appendHeadHtml(response.headHtml);
                        Craft.appendFootHtml(response.footHtml);

                        this._unbindEventHandlersForFieldSelect();
                        this._bindEventHandlersForFieldEditor();
                    } else {
                        alert(response.error);
                        this.hide();
                    }
                }
            }.bind(this));
        },

        _initSpinner() {
            this.$container.addClass('loading');
            this.$spinner = $('<div class="spinner centeralign"></div>').appendTo(this.$container);
            var top = (this.$container.height() / 2 - this.$spinner.height() / 2) + 'px',
                left = (this.$container.width() / 2 - this.$spinner.width() / 2) + 'px';

            this.$spinner.css({left: left, top: top, position: 'absolute'});
        },


        loadFields: function(elementIds) {
            this._initSpinner();
            this.requestId++;

            var viewParams = Craft.elementIndex.getViewParams();
            Craft.postActionRequest('venveo-bulk-edit/bulk-edit/get-fields',
                {
                    elementIds: elementIds,
                    requestId: this.requestId,
                    viewParams: viewParams
                }, function(response, textStatus) {
                if (textStatus === 'success') {
                    if (response.success) {
                        if (response.requestId != this.requestId) {
                            return;
                        }

                        this.$container.removeClass('loading');
                        this.$spinner.remove();

                        this.loaded = true;
                        this.$container.append(response.modalHtml);
                        this.elementIds = response.elementIds;
                        this.siteId = response.siteId;
                        Craft.initUiElements(this.$container);
                        this._bindEventHandlersForFieldSelect();
                    } else {
                        alert(response.error);
                        this.hide();
                    }
                }
            }.bind(this));
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
            this.$container.find('#select-all-elements').on('click', this._handleSelectAllElementsClicked.bind(this));
            this.$container.find('#fields-table .lightswitch').on('change', this._handleFieldSelect.bind(this));
            this.$container.find('.submit').on('click', this._handleFieldSelectSubmit.bind(this));
        },

        _unbindEventHandlersForFieldSelect: function() {
            this.$container.find('#field-edit-cancel').off('click', this.hide.bind(this));
            this.$container.find('#fields-table .lightswitch').off('change', this._handleFieldSelect.bind(this));
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
            const formValues = this.$container.find('#bulk-edit-values-modal').serializeArray();
            Craft.postActionRequest('venveo-bulk-edit/bulk-edit/save-context', formValues, function(response) {
                this.hide();
                Craft.cp.trackJobProgress(false, true);
                Craft.cp.runQueue();
            }.bind(this));
        },

        _handleSelectAllElementsClicked: function(e) {
            e.preventDefault();
            this._initSpinner();
        },

        _handleFieldSelectSubmit: function(e) {
            e.preventDefault();
            const data = this.$container.find('form').serialize();
            this.$container.find('.field-edit-modal').remove();
            this._initSpinner();
            this.loadFieldEditor(data);
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


        /**
         * Disappear immediately forever.
         * @returns {boolean}
         */
        selfDestruct: function() {
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
