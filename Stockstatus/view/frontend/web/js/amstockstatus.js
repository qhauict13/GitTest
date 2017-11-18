/*jshint browser:true jquery:true*/

define(['jquery'], function ($) {
    'use strict';

   var amstockstatusRenderer = {
        configurableStatus: null,
        spanElement: null,
        options: {},

        init: function (options) {
            this.options = options;
            this.spanElement = $('.stock').first();
            this.dropdowns   = $('select.super-attribute-select, select.swatch-select');

            this._initialization();
        },

        /**
         * remove stock alert block
         */
        _hideStockAlert: function () {
            $('.amstockstatus-stockalert').remove();
        },

        _reloadDefaultContent: function (key) {
            if (this.spanElement.length) {
                this.spanElement.html(this.configurableStatus);
            }
            $('.box-tocart').show();
        },

        showStockAlert: function (code) {
            jQuery('<div/>', {
                class: 'amstockstatus-stockalert',
                title: 'Become a Googler',
                rel: 'external',
                html: code
            }).appendTo('.product-add-form');

            $('#form-validate-stock').mage('validation');
        },

        /*
         * configure statuses at product page
         */
        onConfigure: function (key) {
            this.dropdowns   = $('select.super-attribute-select, select.swatch-select');
            this._hideStockAlert();
            if (null == this.configurableStatus && this.spanElement.length) {
                this.configurableStatus = this.spanElement.html();
            }
            //get current selected key
            var selectedKey = "";
            this.settingsForKey = $('select.super-attribute-select, div.swatch-option.selected');
            if (this.settingsForKey.length) {
                for (var i = 0; i < this.settingsForKey.length; i++) {
                    if (parseInt(this.settingsForKey[i].value) > 0) {
                        selectedKey += this.settingsForKey[i].value + ',';
                    }
                    if (parseInt($(this.settingsForKey[i]).attr('option-id')) > 0) {
                        selectedKey += $(this.settingsForKey[i]).attr('option-id') + ',';
                    }
                }
            }
            var trimSelectedKey = selectedKey.substr(0, selectedKey.length - 1);
            var countKeys = selectedKey.split(",").length - 1;

            /*reload main status*/
            if ('undefined' != typeof(this.options[trimSelectedKey])) {
                this._reloadContent(trimSelectedKey);
            }
            else {
                this._reloadDefaultContent(trimSelectedKey);
            }

            /*add statuses to dropdown*/
            var settings = this.dropdowns;
            for (var i = 0; i < settings.length; i++) {
                for (var x = 0; x < settings[i].options.length; x++) {
                    if (!settings[i].options[x].value) continue;

                    if (countKeys == i + 1) {
                        var keyCheckParts = trimSelectedKey.split(',');
                        keyCheckParts[keyCheckParts.length - 1] = settings[i].options[x].value;
                        var keyCheck = keyCheckParts.join(',');

                    }
                    else {
                        if (countKeys < i + 1) {
                            var keyCheck = selectedKey + settings[i].options[x].value;
                        }
                    }

                    if ('undefined' != typeof(this.options[keyCheck]) && this.options[keyCheck]) {
                        var status = this.options[keyCheck]['custom_status'];
                        if (status) {
                            status = status.replace(/<(?:.|\n)*?>/gm, ''); // replace html tags
                            if (settings[i].options[x].text.indexOf(status) === -1) {
                                settings[i].options[x].text = settings[i].options[x].text + ' (' + status + ')';
                            }
                        }
                    }
                }
            }

        },
        /*
         * reload default stock status after select option
         */
        _reloadContent: function (key) {
            if ('undefined' != typeof(this.options.changeConfigurableStatus) && this.options.changeConfigurableStatus && this.spanElement.length) {
                if (this.options[key] && this.options[key]['custom_status']) {
                    if (this.options[key]['custom_status_icon_only'] == 1) {
                        this.spanElement.html(this.options[key]['custom_status_icon']);
                    } else {
                        this.spanElement.html( this.options[key]['custom_status_icon'] + this.options[key]['custom_status']);
                    }
                } else {
                    this.spanElement.html(this.configurableStatus);
                }
            }

            if ('undefined' != typeof(this.options[key]) && this.options[key] && 0 == this.options[key]['is_in_stock']) {
                $('.box-tocart').each(function (index,elem) {
                    $(elem).hide();
                });
                if (this.options[key]['stockalert']) {
                    this.showStockAlert(this.options[key]['stockalert']);
                }
            } else {
                $('.box-tocart').each(function (index,elem) {
                    $(elem).show();
                });
            }
        },

        _initialization: function () {
	        var me = this;
            $(document).ready($.proxy(function() {
		        setTimeout(function() { me.onConfigure(); }, 300);
            },this));

            $('body').on( {
                    'click': function(){setTimeout(function() { me.onConfigure(); }, 300);},
                },
                'div.swatch-option, select.super-attribute-select, select.swatch-select'
            );

            $('body').on( {
                    'change': function(){setTimeout(function() { me.onConfigure(); }, 300);},
                },
                'select.super-attribute-select, select.swatch-select'
            );
        }
    }

    return amstockstatusRenderer;
});
