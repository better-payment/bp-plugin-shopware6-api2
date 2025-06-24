import template from './googlepay-card.html.twig';

const {Component} = Shopware;

Component.override('sw-order-detail-details', {
    template,

    computed: {
        isBetterPaymentTransaction() {
            return this.transaction.customFields !== null
                && this.transaction.customFields.hasOwnProperty('better_payment_transaction_id');
        },

        googlePayCardIsVisible() {
            return this.isBetterPaymentTransaction && this.paymentMethod === 'google_pay';
        },

        googlePayOrderId() {
            return this.order.customFields.betterpayment_google_pay_order_id;
        },

        paymentMethod() {
            return this.transaction.paymentMethod.customFields.shortname;
        }
    },
});