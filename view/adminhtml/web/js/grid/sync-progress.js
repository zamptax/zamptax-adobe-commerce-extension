/**
 * Copyright © Above The Fray Design, Inc. All rights reserved.
 * See ATF_COPYING.txt for license details.
 */

define([
    'uiComponent',
    'jquery',
    'underscore',
    'uiRegistry',
    'domReady!',
    "ATF_Zamp/js/lib/loading-bar"
], function (Component, $, _, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            statusUrl: '',
            parentSelector: '',
            loadBarSelector: '',
            progress: '',
            initialProgress: '',
            syncCompleteMessage: '',
            listens: {
                progress: 'progressChanged'
            }
        },
        loadBar: null,

        initialize: function () {
            this._super();

            this.loadBar = new ldBar(this.loadBarSelector);
            this.loadBar.set(this.initialProgress);
            this.progress(this.initialProgress);

            this.checkProgress();

            return this;
        },

        initObservable: function () {
            this._super().observe([
                'progress'
            ]);

            return this;
        },

        checkProgress: function () {
            this.getStatus().done(function (data) {
                if (data.status === 'success') {
                    this.progress(data.progress);

                    if (parseInt(this.progress()) < 100) {
                        setTimeout(this.checkProgress.bind(this), 5000);
                    }
                }
            }.bind(this));
        },

        getStatus: function () {
            var result = $.Deferred();

            $.get(this.statusUrl, function (data) {
                result.resolve(data);
            });

            return result;
        },

        progressChanged: function () {
            this.loadBar.set(this.progress());

            var component = registry.get('index = zamp_historical_transaction_queue_listing');
            if (component) {
                component.source.reload({'refresh': true});
            }

            if (parseInt(this.progress()) === 100) {
                let self = this;
                setTimeout(function () {
                    $(self.loadBarSelector).remove();
                    let message = $('<span></span>', {
                        text: self.syncCompleteMessage,
                    });
                    $(self.parentSelector).html(message);
                }, 5000);
            }
        }
    })
});