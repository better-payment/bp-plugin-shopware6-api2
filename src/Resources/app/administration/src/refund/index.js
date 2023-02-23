import template from './refund-card.html.twig';

const { Component } = Shopware;

Component.override('sw-order-detail-base', {
    template,

    data() {
        return {
            refunds: [],
        };
    },

    methods: {
        getRefundsData() {
            const url = "https://devapi.betterpayment.de/rest/transactions/a2df9bfb-7af7-42d5-aff8-43950797ae5a/log";

            const headers = new Headers();
            headers.append("Authorization", "Basic NzBhYmQ1OTQwODQ3ODdhMzkyZTg6NGE2NmI5MWU5YjVjOTBjYTQ3YjA=");

            const requestOptions = {
                method: 'GET',
                headers: headers,
            };

            fetch(url, requestOptions)
                .then(response => response.json())
                .then(transactions => {
                    this.refunds = transactions.filter(transaction => transaction.type === 'refund');
                })
                .catch(error => console.log('error', error));
        }
    },

    created() {
        this.getRefundsData();
    }
});