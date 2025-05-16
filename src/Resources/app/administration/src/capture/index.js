import template from './capture-card.html.twig';

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
            capture: {
                amount: null,
                invoice_id: null,
                comment: this.$tc('betterpayment.capture.defaults.comment'),
                execution_date: null,
            },
            captures: [],
            processSuccess: false,
            buttonDisabled: false,

            apiUrl: null,
            apiAuth: null,

            config: {
                apiUrl: null,
                apiKey: null,
                outgoingKey: null,
                sepaDirectDebitEnableManualCapture: null,
                sepaDirectDebitB2BEnableManualCapture: null,
            }
        };
    },

    created() {
        this.loadPluginConfig().then(() => {
            this.setAPIProperties();

            if (this.captureCardIsVisible) {
                this.getCaptures();
            }
        });

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
            const capturablePaymentMethods = ['kar', 'kar_b2b', 'dd', 'dd_b2b'];

            return capturablePaymentMethods.includes(this.paymentMethod);
        },

        relatedPluginConfigEnabled() {
            if (this.paymentMethod === 'dd') {
                return this.config.sepaDirectDebitEnableManualCapture;
            }

            if (this.paymentMethod === 'dd_b2b') {
                return this.config.sepaDirectDebitB2BEnableManualCapture;
            }

            return true;
        },

        captureCardIsVisible() {
            return this.isBetterPaymentTransaction && this.isCapturablePaymentMethod && this.relatedPluginConfigEnabled;
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

    methods: {
        loadPluginConfig() {
            const pluginConfig = ApiService.getByName('systemConfigApiService');
            return pluginConfig.getValues('BetterPayment').then(config => {
                const environment = config['BetterPayment.config.environment'];

                const testAPIUrl = config['BetterPayment.config.testAPIUrl'];
                const productionAPIUrl = config['BetterPayment.config.productionAPIUrl'];
                this.config.apiUrl = environment === 'test' ? testAPIUrl : productionAPIUrl;

                const testAPIKey = config['BetterPayment.config.testAPIKey'];
                const productionAPIKey = config['BetterPayment.config.productionAPIKey'];
                this.config.apiKey = environment === 'test' ? testAPIKey : productionAPIKey;

                const testOutgoingKey = config['BetterPayment.config.testOutgoingKey'];
                const productionOutgoingKey = config['BetterPayment.config.productionOutgoingKey'];
                this.config.outgoingKey = environment === 'test' ? testOutgoingKey : productionOutgoingKey;

                this.config.sepaDirectDebitEnableManualCapture = config['BetterPayment.config.sepaDirectDebitEnableManualCapture'];
                this.config.sepaDirectDebitB2BEnableManualCapture = config['BetterPayment.config.sepaDirectDebitB2BEnableManualCapture'];
            });
        },

        setAPIProperties() {
            this.apiUrl = this.config.apiUrl;
            this.apiAuth = btoa(this.config.apiKey + ':' + this.config.outgoingKey);
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
                'invoice_id': this.capture.invoice_id,
                'comment': this.capture.comment,
                'execution_date': this.capture.execution_date
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
            this.capture.invoice_id = null;
            this.capture.comment = this.$tc('betterpayment.capture.defaults.comment');
            this.capture.execution_date = null;

            this.processSuccess = false;
        },
    },
});