var config = {
    map: {
        '*': {
            'ClassyLlama_AvaTax/template/checkout/summary/grand-total.html': 'Chetu_VatTax/template/checkout/summary/grand-total.html',
            'ClassyLlama_AvaTax/template/checkout/cart/totals/grand-total.html': 'Chetu_VatTax/template/checkout/cart/totals/grand-total.html',
            'Magento_Catalog/js/price-utils': 'Chetu_VatTax/js/price-utils'
        }
    },
    'config': {
        'mixins': {
            'Magento_Tax/js/view/checkout/summary/tax': {
                'Chetu_VatTax/js/view/checkout/summary/tax': true
            },
            'Magento_Tax/js/view/checkout/summary/grand-total': {
                'Chetu_VatTax/js/view/checkout/summary/grand-total': true
            }
        }
    }
};