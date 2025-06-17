const { PluginBaseClass } = window;

function timeout(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

export default class GooglePayPlugin extends PluginBaseClass {
    async init() {
        // to wait script load
        await timeout(1000)
        if (!this.options?.initialData) {
            return;
        }
        this.orderForm = document.getElementById('confirmOrderForm');
        this.paymentsClient = new google.payments.api.PaymentsClient({
            environment: this.options.initialData.environment?.toUpperCase(),
        });
        const button = this.paymentsClient.createButton({
            buttonSizeMode: "fill",
            buttonType: "checkout",
            onClick: this.onClick.bind(this)
        });
        document.getElementById('google-pay-button').appendChild(button);
    }

    showErrorMessage() {
        const errorContainer = document.getElementById('betterpayment-google-pay-error');
        errorContainer.style.display = 'block';
        errorContainer.scrollIntoView({ block: 'start' });
    }

    async onClick() {
        try {
            const { googlePay, ...initialData } = this.options.initialData

            if (!googlePay || !initialData) {
                console.log("no initial data");
                return;
            }
            const totalPrice = this.options.amount.toFixed(2);
            const paymentDataRequest = {
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: [
                    {
                        type: "CARD",
                        parameters: {
                            allowedAuthMethods: googlePay.allowedAuthMethods,
                            allowedCardNetworks: googlePay.allowedCardNetworks,
                            billingAddressRequired: true,
                            billingAddressParameters: {
                                format: "FULL",
                                phoneNumberRequired: true,
                            },
                        },
                        tokenizationSpecification: {
                            type: "PAYMENT_GATEWAY",
                            parameters: {
                                gateway: googlePay.gateway,
                                gatewayMerchantId: googlePay.gatewayMerchantId,
                            },
                        },
                    },
                ],
                merchantInfo: {
                    merchantId: googlePay.merchantId,
                    merchantName: googlePay.merchantName,
                },
                transactionInfo: {
                    totalPriceStatus: "FINAL",
                    totalPrice,
                    currencyCode: initialData.currency,
                    checkoutOption: "COMPLETE_IMMEDIATE_PURCHASE",
                },
                emailRequired: true,
                shippingAddressRequired: true,
                shippingAddressParameters: {
                    phoneNumberRequired: true,
                },
            };

            const paymentResponse = await this.paymentsClient.loadPaymentData(
                paymentDataRequest
            );

            const billingAddressGoogle = paymentResponse.paymentMethodData?.info?.billingAddress;

            const payload = {
                googlepay_token: paymentResponse.paymentMethodData?.tokenizationData?.token,
                amount: totalPrice,
                currency: initialData.currency,
                postback_url: initialData.postbackUrl,
                shipping_costs: initialData.shippingCosts,
                vat: initialData.vat,
                order_id: initialData.orderId,
                merchant_reference: initialData.orderId + " - " + initialData.shopName,
                customer_id: initialData.customerId,
                customer_ip: initialData.customerIp,
                app_name: initialData.appName,
                app_version: initialData.appVersion,

                // billing address parameters
                address: billingAddressGoogle?.address1 ?? null,
                address2: billingAddressGoogle?.address2 ?? null,
                city: billingAddressGoogle?.locality ?? null,
                postal_code: billingAddressGoogle?.postalCode ?? null,
                state: billingAddressGoogle?.administrativeArea ?? null,
                country: billingAddressGoogle?.countryCode ?? "DE",
                first_name: billingAddressGoogle?.name ?? null,
                last_name: billingAddressGoogle?.name ?? null,
                email: paymentResponse.email ?? null,
                phone: billingAddressGoogle?.phoneNumber ?? null,

                // shipping address parameters
                shipping_address: paymentResponse.shippingAddress?.address1 ?? null,
                shipping_address2: paymentResponse.shippingAddress?.address2 ?? null,
                shipping_city: paymentResponse.shippingAddress?.locality ?? null,
                shipping_postal_code: paymentResponse.shippingAddress?.postalCode ?? null,
                shipping_state: paymentResponse.shippingAddress?.administrativeArea ?? null,
                shipping_country: paymentResponse.shippingAddress?.countryCode ?? null,
                shipping_first_name: paymentResponse.shippingAddress?.name ?? null,
                shipping_last_name: paymentResponse.shippingAddress?.name ?? null,
                shipping_phone: paymentResponse.shippingAddress?.phoneNumber ?? null,
            };

            const response = await fetch(this.options.processPaymentPath, {
                method: "POST",
                body: JSON.stringify(payload),
                headers: {
                    "Content-Type": "application/json",
                },
            });

            if (response.ok) {
                const data = await response.json();

                if (data.error_code === 0) {
                    document.getElementById('betterpayment_google_pay_transaction_id').value = data.transaction_id;
                    document.getElementById('betterpayment_google_pay_transaction_status').value = data.status;

                    this.orderForm.submit();
                }
                else {
                    this.showErrorMessage();
                }
            }
            else {

                this.showErrorMessage();
            }

        } catch (e) {
            this.showErrorMessage();
        }
    }
}
