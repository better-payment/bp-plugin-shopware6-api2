import template from './capture-card.html.twig';
import whiteLabels from '../../../../data/whitelabels.json';

const {Component, Mixin, ApiService} = Shopware;

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
            capture: {
                amount: null,
                comment: this.$tc('betterpayment.capture.defaults.comment'),
            },
            captures: [],
            processSuccess: false,
            buttonDisabled: false,

            apiUrl: null,
            apiAuth: null,
        };
    },

    created() {
        this.setAPIProperties();
    },

    computed: {
        isBetterPaymentTransaction() {
            return this.transaction.customFields !== null
                && this.transaction.customFields.hasOwnProperty('better_payment_transaction_id');
        },

        betterPaymentTransactionId() {
            return this.isBetterPaymentTransaction ? this.transaction.customFields.better_payment_transaction_id : null;
        },

        isCapturablePaymentMethod() {
            const capturablePaymentMethods = ['kar', 'kar_b2b'];

            return capturablePaymentMethods.includes(this.paymentMethod);
        },

        captureCardIsVisible() {
            return this.isBetterPaymentTransaction && this.isCapturablePaymentMethod;
        },

        isCapturableState() {
            const capturableStates = ['in_progress', 'paid_partially', 'paid'];

            return capturableStates.includes(this.transaction.stateMachineState.technicalName);
        },

        canCreateCapture() {
            // TODO: add permission check here with AND
            return this.isCapturableState;
        },

        paymentMethod() {
            return this.transaction.paymentMethod.customFields.shortname;
        }
    },

    watch: {
        // when order is set get its transaction captures
        // order is not directly set in created() lifecycle hook
        order() {
            if (this.captureCardIsVisible) {
                this.getCaptures();
            }
        }
    },

    methods: {
        setAPIProperties() {
            const pluginConfig = ApiService.getByName('systemConfigApiService');
            pluginConfig.getValues('BetterPayment').then(config => {
                const environment = config['BetterPayment.config.environment'];
                const whiteLabel = config['BetterPayment.config.whiteLabel'];

                const testAPIKey = config['BetterPayment.config.testAPIKey'];
                const productionAPIKey = config['BetterPayment.config.productionAPIKey'];
                const apiKey = environment === 'test' ? testAPIKey : productionAPIKey;

                const testOutgoingKey = config['BetterPayment.config.testOutgoingKey'];
                const productionOutgoingKey = config['BetterPayment.config.productionOutgoingKey'];
                const outgoingKey = environment === 'test' ? testOutgoingKey : productionOutgoingKey;

                this.apiUrl = whiteLabels[whiteLabel][environment].api_url;
                this.apiAuth = btoa(apiKey + ':' + outgoingKey);

                return Promise.resolve();
            });
        },

        getCaptures() {
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
                        this.captures = result.filter(log => log.type === 'capture')
                            .filter(log => [1,2,3].includes(log.status));
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

        createCapture() {
            this.buttonDisabled = true;
            const url = this.apiUrl + '/rest/capture';

            const headers = new Headers();
            headers.append('Authorization', 'Basic ' + this.apiAuth);
            headers.append('Content-Type', 'application/json');

            const body = JSON.stringify({
                'transaction_id': this.betterPaymentTransactionId,
                'amount': this.capture.amount,
                'comment': this.capture.comment
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
                    // detect capture api request error and show as notification
                    if (result.error_code === 0) {
                        // capture statuses can be success|started|local|error
                        // Note: All statuses except for "error" are considered to be successful.
                        if (result.status !== 'error') {
                            // update capture card table records
                            this.getCaptures();

                            // this is to show check mark on submit button
                            this.processSuccess = true;

                            this.createNotificationSuccess({
                                message: this.$tc('betterpayment.capture.messages.successfulCaptureRequest')
                            });

                            // TODO: do we need to update order transaction state
                            // this.updateTransactionState();
                        } else {
                            this.createNotificationError({
                                message: this.$tc('betterpayment.capture.messages.invalidCaptureRequest')
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

        createCaptureFinished() {
            this.capture.amount = null;
            this.capture.comment = this.$tc('betterpayment.capture.defaults.comment'),
            this.processSuccess = false;
        },

        // updateTransactionState() {
        //     const url = this.apiUrl + '/rest/transactions/' + this.betterPaymentTransactionId;
        //
        //     const headers = new Headers();
        //     headers.append('Authorization', 'Basic ' + this.apiAuth);
        //
        //     const requestOptions = {
        //         method: 'GET',
        //         headers: headers,
        //     };
        //
        //     fetch(url, requestOptions)
        //         .then(response => response.json())
        //         .then(result => {
        //             if (!result.hasOwnProperty('error_code')) {
        //                 if (result.refunded_amount > 0) {
        //                     let actionName;
        //
        //                     if (result.refunded_amount >= result.amount) {
        //                         actionName = 'refund';
        //                     } else {
        //                         actionName = 'refund_partially';
        //                     }
        //
        //                     const docIds = [];
        //                     const sendMail = true;
        //
        //                     this.orderStateMachineService.transitionOrderTransactionState(
        //                         this.transaction.id,
        //                         actionName,
        //                         {documentIds: docIds, sendMail},
        //                     ).then(() => {
        //                         this.$emit('order-state-change');
        //                     }).catch((error) => {
        //                         this.createNotificationError(error);
        //                     });
        //                 }
        //             } else {
        //                 this.createNotificationError({
        //                     message: result.error_message
        //                 });
        //             }
        //         })
        //         .catch(exception => {
        //             this.createNotificationError({
        //                 message: exception
        //             });
        //         });
        // }
    },
});