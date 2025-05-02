import template from './applepay-card.html.twig';

const {Component} = Shopware;

Component.override('sw-order-detail-details', {
    template,

    computed: {
        isBetterPaymentTransaction() {
            return this.transaction.customFields !== null
                && this.transaction.customFields.hasOwnProperty('better_payment_transaction_id');
        },

        applePayCardIsVisible() {
            return this.isBetterPaymentTransaction && this.paymentMethod === 'apple_pay';
        },

        applePayOrderId() {
            return this.order.customFields.betterpayment_apple_pay_order_id;
        },

        paymentMethod() {
            return this.transaction.paymentMethod.customFields.shortname;
        }
    },
});