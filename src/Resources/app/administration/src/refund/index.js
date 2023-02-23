import template from './refund-card.html.twig';

const { Component, Mixin } = Shopware;

Component.override('sw-order-detail-base', {
    template,

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            refund: {
                amount: null,
                comment: null,
                execution_date: null,
            },
            refunds: [],
            processSuccess: false,
            buttonDisabled: false,
        };
    },

    created() {
        this.getRefunds();
    },

    methods: {
        getRefunds() {
            // TODO: get api base url based on config
            // TODO: get transaction_id from DB
            const url = "https://devapi.betterpayment.de" + "/rest/transactions/b1c2c87c-5c4f-4f2a-ba44-6667e00fa6bd/log";

            const headers = new Headers();
            // TODO get keys from config and generate authorisation
            headers.append("Authorization", "Basic NzBhYmQ1OTQwODQ3ODdhMzkyZTg6NGE2NmI5MWU5YjVjOTBjYTQ3YjA=");

            const requestOptions = {
                method: 'GET',
                headers: headers,
            };

            // TODO: consider status_code=error cases
            // TODO: ? maybe show some errors to admin
            fetch(url, requestOptions)
                .then(response => response.json())
                .then(transactions => {
                    this.refunds = transactions.filter(transaction => transaction.type === 'refund');
                })
                .catch(exception => {
                    this.createNotificationError({
                        title: 'Error fetching refunds',
                        message: exception
                    });
                });
        },

        storeRefund() {
            this.buttonDisabled = true;
            // TODO: get api base url based on config
            // TODO: get transaction_id from DB
            const url = "https://devapi.betterpayment.de" + "/rest/refund";

            const headers = new Headers();
            // TODO get keys from config and generate authorisation
            headers.append("Authorization", "Basic NzBhYmQ1OTQwODQ3ODdhMzkyZTg6NGE2NmI5MWU5YjVjOTBjYTQ3YjA=");
            headers.append("Content-Type", "application/json");

            const body = JSON.stringify({
                "transaction_id": "b1c2c87c-5c4f-4f2a-ba44-6667e00fa6bd",
                "amount": this.refund.amount,
                "comment": this.refund.comment,
                "execution_date": this.refund.execution_date
            });

            const requestOptions = {
                method: 'POST',
                headers: headers,
                body: body
            };

            fetch(url, requestOptions)
                .then(response => response.json())
                .then(result => {
                    this.buttonDisabled = false;
                    if (result.error_code === 0) {
                        this.getRefunds();
                        this.processSuccess = true;
                        this.createNotificationSuccess({
                            message: 'Refund saved successfully...'
                        });
                    }
                    else {
                        this.createNotificationError({
                            message: result.error_message
                        });
                    }
                })
                .catch(exception => {
                    this.createNotificationError({
                        message: exception
                    });
                });
        },

        storeRefundFinished() {
            this.refund.amount = null;
            this.refund.comment = null;
            this.refund.execution_date = null;
            this.processSuccess = false;
        }


    },
});