define([
    'Magento_Ui/js/grid/filters/range',
    'ko',
    'uiRegistry'
], function (Component, ko, registry) {
    'use strict';

    var minDate;
    registry.get('zamp_historical_transaction_sync_listing.zamp_historical_transaction_sync_listing_data_source',
        function (dataSource) {
            minDate = dataSource.earliestDateToSync;
        });

    return Component.extend({
        defaults: {
            templates: {
                date: {
                    component: 'ATF_Zamp/js/form/element/date',
                    dateFormat: 'MM/dd/YYYY',
                    shiftedValue: 'filter',
                    options: {
                        minDate: minDate
                    }
                }
            }
        }
    });
});