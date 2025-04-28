const { PluginBaseClass } = window;

const APPLE_PAY_JS_API_VERSION = 14;
const REQUIRED_CONTACT_FIELDS = [
    "postalAddress",
    "email",
    "phone",
];

export default class ApplePayPlugin extends PluginBaseClass {
    init() {
        this.el.addEventListener('click', this.onClick.bind(this));
        this.orderForm = document.getElementById('confirmOrderForm');
    }

    showErrorMessage() {
        const errorContainer = document.getElementById('betterpayment-apple-pay-error');
        errorContainer.style.display = 'block';
        errorContainer.scrollIntoView({block: 'start'});
    }

    onClick() {
        // default checkout form validation
        if (!this.orderForm.reportValidity()) {
            return;
        }

        const initialData = this.options.initialData;
        const amount = this.options.amount;

        try {
            const requestBody = {
                countryCode: initialData.countryCode,
                currencyCode: initialData.currency,
                merchantCapabilities: initialData.applePay.merchantCapabilities,
                supportedNetworks: initialData.applePay.supportedNetworks,
                total: {
                    label: initialData.shopName,
                    amount: amount,
                },
                requiredShippingContactFields: REQUIRED_CONTACT_FIELDS,
                requiredBillingContactFields: REQUIRED_CONTACT_FIELDS,
            };

            const session = new ApplePaySession(
                APPLE_PAY_JS_API_VERSION,
                requestBody
            );

            session.onvalidatemerchant = () => {
                fetch(this.options.validateMerchantPath, {
                    method: 'POST',
                })
                    .then(res => res.json())
                    .then(data => {
                        const merchantSession = JSON.parse(atob(data.applepay_payment_session_token));
                        session.completeMerchantValidation(merchantSession);
                    })
                    .catch(err => {
                        this.showErrorMessage();
                    });
            };

            session.onpaymentmethodselected = () => {
                const update = {
                    newTotal: {
                        label: initialData.shopName,
                        amount: amount,
                    },
                };

                session.completePaymentMethodSelection(update);
            };

            session.onpaymentauthorized = async (event) => {
                const billingAddress = {
                    first_name: event.payment?.billingContact?.givenName ?? null,
                    last_name: event.payment?.billingContact?.familyName ?? null,
                    address_1: event.payment?.billingContact?.addressLines?.[0] ?? null,
                    address_2: event.payment?.billingContact?.addressLines?.[1] ?? null,
                    city: event.payment?.billingContact?.locality ?? null,
                    state: event.payment?.billingContact?.administrativeArea ?? null,
                    country: event.payment?.billingContact?.countryCode ?? "DE",
                    postcode: event.payment?.billingContact?.postalCode ?? null,
                    email: event.payment?.shippingContact?.emailAddress ?? null,
                    phone: event.payment?.shippingContact?.phoneNumber ?? null
                };

                const shippingAddress = {
                    first_name: event.payment?.shippingContact?.givenName ?? null,
                    last_name: event.payment?.shippingContact?.familyName ?? null,
                    address_1: event.payment?.shippingContact?.addressLines?.[0] ?? null,
                    address_2: event.payment?.shippingContact?.addressLines?.[1] ?? null,
                    city: event.payment?.shippingContact?.locality ?? null,
                    state: event.payment?.shippingContact?.administrativeArea ?? null,
                    country: event.payment?.shippingContact?.countryCode ?? "DE",
                    postcode: event.payment?.shippingContact?.postalCode ?? null,
                    email: event.payment?.shippingContact?.emailAddress ?? null,
                    phone: event.payment?.shippingContact?.phoneNumber ?? null,
                }

                const payload = {
                    // common parameters
                    applepay_token: btoa(JSON.stringify(event.payment?.token)),
                    amount: amount,
                    currency: initialData.currency,
                    postback_url: initialData.postback_url,
                    shipping_costs: initialData.shippingCosts,
                    vat: initialData.vat,
                    order_id: initialData.orderId,
                    merchant_reference: initialData.orderId + ' - ' + initialData.shopName,
                    customer_id: initialData.customerId,
                    customer_ip: initialData.customerIp,
                    app_name: initialData.appName,
                    app_version: initialData.appVersion,

                    // billing address parameters
                    address: billingAddress.address_1,
                    address2: billingAddress.address_2,
                    city: billingAddress.city,
                    postal_code: billingAddress.postcode,
                    state: billingAddress.state,
                    country: billingAddress.country,
                    first_name: billingAddress.first_name,
                    last_name: billingAddress.last_name,
                    email: billingAddress.email,
                    phone: billingAddress.phone,

                    // shipping address parameters
                    shipping_address: shippingAddress.address_1,
                    shipping_address2: shippingAddress.address_2,
                    shipping_city: shippingAddress.city,
                    shipping_postal_code: shippingAddress.postcode,
                    shipping_state: shippingAddress.state,
                    shipping_country: shippingAddress.country,
                    shipping_first_name: shippingAddress.first_name,
                    shipping_last_name: shippingAddress.last_name,
                };

                const response = await fetch(this.options.processPaymentPath, {
                    method: 'POST',
                    body: JSON.stringify(payload),
                    headers: {
                        'Content-Type': 'application/json',
                    },
                });

                if (response.ok) {
                    const data = await response.json();

                    if (data.error_code === 0) {
                        session.completePayment({
                            status: ApplePaySession.STATUS_SUCCESS
                        });

                        document.getElementById('betterpayment_transaction_id').value = data.transaction_id;
                        document.getElementById('betterpayment_transaction_status').value = data.status;

                        this.orderForm.submit();
                    }
                    else {
                        session.completePayment({
                            status: ApplePaySession.STATUS_FAILURE
                        });

                        this.showErrorMessage();
                    }
                }
                else {
                    session.completePayment({
                        status: ApplePaySession.STATUS_FAILURE
                    });

                    this.showErrorMessage();
                }
            };

            session.oncancel = (event) => {
                this.showErrorMessage();
            };

            session.begin();
        } catch (e) {
            this.showErrorMessage();
        }
    }
}