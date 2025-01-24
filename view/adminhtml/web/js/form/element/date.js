define([
    'Magento_Ui/js/form/element/date',
    'moment',
    'jquery',
    'mage/utils/misc'
], function (Component, moment, $, utils) {
    'use strict';

    return Component.extend({
        /**
         * Replace value if date is before the earliest
         */
        onShiftedValueChange: function (value) {
            const earliestDate = this.options.minDate;
            const dateFormat = utils.convertToMomentFormat(this.dateFormat);
            let newValue = value;

            if (value) {
                let valueMoment = moment(value, dateFormat, true);

                if (!valueMoment.isValid()) {
                    let element = this.getDatePickerElement();
                    if (element) {
                        /** Get date from datepicker **/
                        let datepickerDate = element.datepicker("getDate");
                        valueMoment = moment(datepickerDate);
                    }
                }

                if (valueMoment.isValid()) {
                    let earliestMoment = moment(earliestDate, dateFormat, true);
                    if (valueMoment.isBefore(earliestMoment)) {
                        newValue = earliestMoment.format(dateFormat).toString();
                        if (this.getDatePickerElement()) {
                            this.getDatePickerElement().datepicker("setDate", newValue);
                        }
                    }
                }
            }

            this._super(newValue);
        },

        /**
         * Get date picker element
         */
        getDatePickerElement: function () {
            let element = $('#' + this.uid);
            if (element.length && element.data('datepicker')) {
                return element;
            }

            return false;
        }
    });
});