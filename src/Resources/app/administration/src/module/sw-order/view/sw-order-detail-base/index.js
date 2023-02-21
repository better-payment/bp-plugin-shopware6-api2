import template from './sw-order-detail-base.html.twig';

const { Component } = Shopware;

Component.override('sw-order-detail-base', {
    template,

    data() {
        return {
            refunds: []
        };
    },

    created() {
        this.refunds = [
            {amount: '10.78 $', comment:'Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet', date: '2023-01-12 10:45'},
            {amount: '12.25 $', comment:'Lorem ipsum dolor sit amet', date: '2023-01-12 10:45'},
            {amount: '5.30 $', comment:'Lorem ipsum dolor sit amet Lorem ipsum', date: '2023-01-12 10:45'}
        ];
    }

});