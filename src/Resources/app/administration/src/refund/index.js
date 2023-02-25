import template from './refund-card.html.twig';

const {Component, Mixin} = Shopware;

Component.override('sw-order-detail-base', {
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
            },
            refunds: [],
            processSuccess: false,
            buttonDisabled: false,
        };
    },

    computed: {
        apiUrl() {
            // TODO: get api base url based on config
            return 'https://devapi.betterpayment.de';
        },

        apiAuth() {
            // TODO: get keys from config and generate authorisation
            return 'NzBhYmQ1OTQwODQ3ODdhMzkyZTg6NGE2NmI5MWU5YjVjOTBjYTQ3YjA=';
        },

        isBetterPaymentTransaction() {
            return this.transaction.customFields !== null
                && this.transaction.customFields.hasOwnProperty('better_payment_transaction_id');
        },

        betterPaymentTransactionId() {
            return this.isBetterPaymentTransaction ? this.transaction.customFields.better_payment_transaction_id : null;
        },

        // TODO: resolve following error
        // Cross-Origin Request Blocked: The Same Origin Policy disallows reading the remote resource at https://devapi.betterpayment.de/rest/transactions/039c4158-6ba5-407d-8bce-96b254e8e140.
        // (Reason: CORS header ‘Access-Control-Allow-Origin’ missing). Status code: 200.
        betterPaymentTransaction() {
            console.log('hmmm');
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
                        return result;
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

        cardIsVisible() {
            const visibleStates = ['paid', 'paid_partially', 'refunded', 'refunded_partially'];

            return this.isBetterPaymentTransaction
                && visibleStates.includes(this.transaction.stateMachineState.technicalName);
        },

        isRefundable() {
            return this.transaction.stateMachineState.technicalName !== 'refunded';
        },

        canCreateRefund() {
            // TODO: add permission check here with AND
            return this.isRefundable;
        },
    },

    watch: {
        // when order is set get its transaction refunds
        // order is not directly set in created() lifecycle hook
        order() {
            if (this.cardIsVisible) {
                this.getRefunds();
            }
        }
    },

    methods: {
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

        storeRefund() {
            this.buttonDisabled = true;
            const url = this.apiUrl + '/rest/refund';

            const headers = new Headers();
            headers.append('Authorization', 'Basic ' + this.apiAuth);
            headers.append('Content-Type', 'application/json');

            const body = JSON.stringify({
                'transaction_id': this.betterPaymentTransactionId,
                'amount': this.refund.amount,
                'comment': this.refund.comment,
                'execution_date': this.refund.execution_date
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

        storeRefundFinished() {
            this.refund.amount = null;
            this.refund.comment = null;
            this.refund.execution_date = null;
            this.processSuccess = false;
        },

        updateTransactionState() {
            const actionName = this.betterPaymentTransaction.amount === this.betterPaymentTransaction.refunded_amount
                ? 'refund' : 'refund_partially';
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
    },
});