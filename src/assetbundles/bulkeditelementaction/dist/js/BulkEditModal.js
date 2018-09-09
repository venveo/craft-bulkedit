/** global: Craft */
/** global: Garnish */

/**
 * Select Fields Modal
 */
Craft.BulkEditModal = Garnish.Modal.extend(
    {
        $spinner: null,
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
                resizable: true
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


        /**
         * Load an asset, using starting width and height, if applicable
         * @param assetId
         * @param startingWidth
         * @param startingHeight
         */
        loadFields: function(elementIds) {

            this.$spinner = $('<div class="spinner centeralign"></div>').appendTo(this.$container);
            var top = (this.$container.height() / 2 - this.$spinner.height() / 2) + 'px',
                left = (this.$container.width() / 2 - this.$spinner.width() / 2) + 'px';

            this.$spinner.css({left: left, top: top, position: 'absolute'});
            this.requestId++;

            Craft.postActionRequest('bulkedit/bulk-edit/get-fields', {elementIds: elementIds, requestId: this.requestId}, function(response, textStatus) {
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
                        this._bindEventHandlers();
                    } else {
                        alert(response.error);

                        this.hide();
                    }
                }
            }.bind(this));
        },

        _bindEventHandlers: function() {
            this.$container.find('#field-edit-cancel').on('click', this.hide.bind(this));
            this.$container.find('#fields-table .lightswitch').on('change', this._handleFieldSelect.bind(this));
        },

        _unbindEventHandlers: function() {
            this.$container.find('#field-edit-cancel').off('click', this.hide.bind(this));
            this.$container.find('#fields-table .lightswitch').off('change', this._handleFieldSelect.bind(this));
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
            this._unbindEventHandlers();

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
