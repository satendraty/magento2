/**
 * Copyright © Chetu, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define(
    ["Magento_Checkout/js/model/totals"], function (totals) {
        "use strict";
        return function (Component) {
            return Component.extend(
                {
                    initialize: function () {
                        this._super();
                    },
                    /**
                     * @return {*}
                     */
                    getValue: function () {
                        var amount;
                        if (!this.isCalculated()) {
                            return this.notCalculatedMessage;
                        }
                        amount = totals.getSegment("tax").value;
                        return this.getFormattedPrice(amount);
                    },
                }
            );
        };
    }
);
