import template from './refund-card.html.twig';

const {Component, Mixin, ApiService} = Shopware;

Component.override('sw-order-detail-details', {
    template,

    inject: [
        'orderStateMachineService',
        // 'acl',
    ],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            refund: {
                amount: null,
                comment: null,
                execution_date: null,
                refund_id: null,
            },
            refunds: [],
            processSuccess: false,
            buttonDisabled: false,

            apiUrl: null,
            apiAuth: null,
        };
    },

    created() {
        this.setAPIProperties();

        if (this.refundCardIsVisible) {
            setTimeout(() => this.getRefunds(), 1000);
        }
    },

    computed: {
        isBetterPaymentTransaction() {
            return this.transaction.customFields !== null
                && this.transaction.customFields.hasOwnProperty('better_payment_transaction_id');
        },

        betterPaymentTransactionId() {
            return this.isBetterPaymentTransaction ? this.transaction.customFields.better_payment_transaction_id : null;
        },

        refundCardIsVisible() {
            return this.isBetterPaymentTransaction;
        },

        isRefundable() {
            const refundableStates = ['paid', 'paid_partially', 'refunded_partially'];

            return refundableStates.includes(this.transaction.stateMachineState.technicalName);
        },

        isFullyRefunded() {
            return this.transaction.stateMachineState.technicalName === 'refunded';
        },

        canCreateRefund() {
            // TODO: add permission check here with AND
            return this.isRefundable;
        },

        paymentMethod() {
            return this.transaction.paymentMethod.customFields.shortname;
        }
    },

    methods: {
        setAPIProperties() {
            const pluginConfig = ApiService.getByName('systemConfigApiService');
            pluginConfig.getValues('BetterPayment').then(config => {
                const environment = config['BetterPayment.config.environment'];

                const testAPIUrl = config['BetterPayment.config.testAPIUrl'];
                const productionAPIUrl = config['BetterPayment.config.productionAPIUrl'];
                const apiUrl = environment === 'test' ? testAPIUrl : productionAPIUrl;

                const testAPIKey = config['BetterPayment.config.testAPIKey'];
                const productionAPIKey = config['BetterPayment.config.productionAPIKey'];
                const apiKey = environment === 'test' ? testAPIKey : productionAPIKey;

                const testOutgoingKey = config['BetterPayment.config.testOutgoingKey'];
                const productionOutgoingKey = config['BetterPayment.config.productionOutgoingKey'];
                const outgoingKey = environment === 'test' ? testOutgoingKey : productionOutgoingKey;

                this.apiUrl = apiUrl;
                this.apiAuth = btoa(apiKey + ':' + outgoingKey);
            });
        },

        getRefunds() {
            const url = this.apiUrl + '/rest/transactions/' + this.betterPaymentTransactionId + '/log';

            const headers = new Headers();
            headers.append('Authorization', 'Basic ' + this.apiAuth);

            const requestOptions = {
                method: 'GET',
                headers: headers,
            };

            fetch(url, requestOptions)
                .then(response => response.json())
                .then(result => {
                    if (!result.hasOwnProperty('error_code')) {
                        this.refunds = result.filter(log => log.type === 'refund')
                            .filter(log => log.status === 7); // status 7 means successful
                    } else {
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

        createRefund() {
            this.buttonDisabled = true;
            const url = this.apiUrl + '/rest/refund';

            const headers = new Headers();
            headers.append('Authorization', 'Basic ' + this.apiAuth);
            headers.append('Content-Type', 'application/json');

            const body = JSON.stringify({
                'transaction_id': this.betterPaymentTransactionId,
                'amount': this.refund.amount,
                'comment': this.refund.comment,
                'execution_date': this.refund.execution_date,
                'refund_id': this.refund.refund_id,
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
                    // detect refund api request error and show as notification
                    if (result.error_code === 0) {
                        // refund statuses can be success|started|local|error
                        // Note: All statuses except for "error" are considered to be successful.
                        if (result.status !== 'error') {
                            // update refund card table records
                            this.getRefunds();

                            // this is to show check mark on submit button
                            this.processSuccess = true;

                            this.createNotificationSuccess({
                                message: this.$tc('betterpayment.refund.messages.successfulRefundRequest')
                            });

                            // update order transaction state
                            this.updateTransactionState();
                        } else {
                            this.createNotificationError({
                                message: this.$tc('betterpayment.refund.messages.invalidRefundRequest')
                            });
                        }
                    } else {
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

        createRefundFinished() {
            this.refund.amount = null;
            this.refund.comment = null;
            this.refund.execution_date = null;
            this.refund.refund_id = null;
            this.processSuccess = false;
        },

        updateTransactionState() {
            const url = this.apiUrl + '/rest/transactions/' + this.betterPaymentTransactionId;

            const headers = new Headers();
            headers.append('Authorization', 'Basic ' + this.apiAuth);

            const requestOptions = {
                method: 'GET',
                headers: headers,
            };

            fetch(url, requestOptions)
                .then(response => response.json())
                .then(result => {
                    if (!result.hasOwnProperty('error_code')) {
                        if (result.refunded_amount > 0) {
                            let actionName;
                            
                            if (result.refunded_amount >= result.amount) {
                                actionName = 'refund';
                            } else {
                                actionName = 'refund_partially';
                            }

                            const docIds = [];
                            const sendMail = true;

                            this.orderStateMachineService.transitionOrderTransactionState(
                                this.transaction.id,
                                actionName,
                                {documentIds: docIds, sendMail},
                            ).then(() => {
                                this.$emit('order-state-change');
                            }).catch((error) => {
                                this.createNotificationError(error);
                            });
                        }
                    } else {
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
        }
    },
});