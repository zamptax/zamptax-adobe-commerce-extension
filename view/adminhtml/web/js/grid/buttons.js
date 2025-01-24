/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

define([
    'uiComponent',
    'jquery',
    'underscore',
    'domReady!'
], function (Component, $, _) {
    'use strict';

    return Component.extend({
        defaults: {
            listingNamespace: null,
            syncButtonSelector: ".transaction-sync",
            searchButtonSelector: ".transaction-search",
            filterProvider: 'componentType = filters, ns = ${ $.listingNamespace }',
            massActionProvider: 'componentType = massaction, ns = ${ $.listingNamespace }',
            modules: {
                filterComponent: '${ $.filterProvider }',
                massActionComponent: '${ $.massActionProvider }'
            }
        },

        initialize: function () {
            this._super();

            this.bindSearch();
            this.bindSync();

            return this;
        },

        bindSearch: function () {
            let self = this;
            $(this.searchButtonSelector).click(function (e) {
                e.preventDefault();
                self.filterComponent().apply();
            });
        },

        bindSync: function () {
            let self = this;

            $(this.syncButtonSelector).click(function (e) {
                e.preventDefault();
                self.massActionComponent().applyAction('sync');
            });
        }
    });
});