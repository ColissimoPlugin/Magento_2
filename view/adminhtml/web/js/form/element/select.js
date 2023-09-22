define([
    'jquery',
    'underscore',
    'uiRegistry'
], function ($, _, uiRegistry) {
    'use strict';
    return function (Select) {
        return Select.extend({
            /**
             * On value change handler.
             *
             * @param {String} value
             */
            onUpdate: function (value) {
                if (this.index !== 'method') return this._super();
                var area = uiRegistry.get('index = area');
                if (undefined !== area && undefined !== window.allOptions) {
                    area.setOptions(window.allOptions[value]);
                }
                return this._super();
            }
        });
    };
});
