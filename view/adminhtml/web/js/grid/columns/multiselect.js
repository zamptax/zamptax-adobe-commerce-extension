/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/grid/columns/multiselect',
    'underscore'
], function (Component, _) {
    'use strict';

    return Component.extend({
        /**
         * Is invoked when rows has changed. Recalculates selected items
         */
        onRowsChange: function () {
            var self = this;

            if (!_.isEmpty(this.getIds())) {
                /**
                 * wait for the imports to be initialized
                 */
                setTimeout(() => {
                    self.selectAll();
                }, 100);

            } else {
                this._super();
            }
        }
    });
});