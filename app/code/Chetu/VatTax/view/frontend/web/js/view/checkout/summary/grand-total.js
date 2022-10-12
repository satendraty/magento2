/**
 * Copyright © Chetu, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define(
    [
    "Magento_Checkout/js/model/totals",
    "Magento_Catalog/js/price-utils",
    "Magento_Checkout/js/model/quote",
    ], function (totals, priceUtils, quote) {
        "use strict";
        return function (Component) {
            return Component.extend(
                {
                    initialize: function () {
                        this._super();
                    },

                    /**
                     * Get Selected VatStore List of Array, getSelectedStoreCodes
                     *
                     * @return int
                     */

                    getSelectedStoreCodes: function () {
                        return window.checkoutConfig.scope_var;
                    },

                    /**
                    * Get Current Store Code getCurrentStoreCode
                     *
                     * @return int
                     */
                    getCurrentStoreCode(){
                        return window.checkoutConfig.storeCode;
                    },

                    /**
                    * Match Selected Store code with current Store code checkCurrentVatStore
                     *
                     * @return int
                     */
                    checkCurrentVatStore() {
                   
                        var storesCodes = this.getSelectedStoreCodes();
                        if(storesCodes!=undefined) {
                            var currentStoreCode = this.getCurrentStoreCode();
                            storesCodes = storesCodes.split(",");
                            return storesCodes.indexOf(currentStoreCode) > -1;
                        } else{
                            return 0;
                        }
                    },
                }
            );
        };
    }
);
