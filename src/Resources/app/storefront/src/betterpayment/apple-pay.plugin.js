// import DomAccess from 'src/helper/dom-access.helper';
// import HttpClient from 'src/service/http-client.service';

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
    }

    onClick() {
        if (!ApplePaySession) {
            return;
        }

        try {
            const requestBody = {
                countryCode: this.options.countryCode,
                currencyCode: this.options.currencyCode,
                merchantCapabilities: this.options.merchantCapabilities,
                supportedNetworks: this.options.supportedNetworks,
                total: this.options.total,
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
                        console.error("Error fetching merchant session", err);
                    });
            };

            // session.onpaymentmethodselected = () => {
            //     const update = {
            //         newTotal: {
            //             label: orderDescription,
            //             amount: totalAmount,
            //         },
            //     };
            //
            //     session.completePaymentMethodSelection(update);
            // };

            // session.onpaymentauthorized = async (event) => {
            //     billingAddress.first_name = event.payment?.billingContact?.givenName ?? null;
            //     billingAddress.last_name = event.payment?.billingContact?.familyName ?? null;
            //     billingAddress.address_1 = event.payment?.billingContact?.addressLines?.[0] ?? null;
            //     billingAddress.address_2 = event.payment?.billingContact?.addressLines?.[1] ?? null;
            //     billingAddress.city = event.payment?.billingContact?.locality ?? null;
            //     billingAddress.state = event.payment?.billingContact?.administrativeArea ?? null;
            //     billingAddress.country = event.payment?.billingContact?.countryCode ?? "DE";
            //     billingAddress.postcode = event.payment?.billingContact?.postalCode ?? null;
            //     billingAddress.email = event.payment?.shippingContact?.emailAddress ?? null;
            //     billingAddress.phone = event.payment?.shippingContact?.phoneNumber ?? null;
            //
            //     shippingAddress.first_name = event.payment?.shippingContact?.givenName ?? null;
            //     shippingAddress.last_name = event.payment?.shippingContact?.familyName ?? null;
            //     shippingAddress.address_1 = event.payment?.shippingContact?.addressLines?.[0] ?? null;
            //     shippingAddress.address_2 = event.payment?.shippingContact?.addressLines?.[1] ?? null;
            //     shippingAddress.city = event.payment?.shippingContact?.locality ?? null;
            //     shippingAddress.state = event.payment?.shippingContact?.administrativeArea ?? null;
            //     shippingAddress.country = event.payment?.shippingContact?.countryCode ?? "DE";
            //     shippingAddress.postcode = event.payment?.shippingContact?.postalCode ?? null;
            //     shippingAddress.email = event.payment?.shippingContact?.emailAddress ?? null;
            //     shippingAddress.phone = event.payment?.shippingContact?.phoneNumber ?? null;
            //
            //     const payload = {
            //         // common parameters
            //         applepay_token: btoa(JSON.stringify(event.payment?.token)),
            //         amount: totalAmount,
            //         currency: currency,
            //         postback_url: initialData.postback_url,
            //         shipping_costs: totalShipping,
            //         vat: totalTax,
            //         order_id: initialData.order_id,
            //         merchant_reference: initialData.order_id + ' - ' + initialData.shop_name,
            //         customer_id: initialData.customer_id,
            //         customer_ip: initialData.customer_ip,
            //         app_name: initialData.app_name,
            //         app_version: initialData.app_version,
            //
            //         // billing address parameters
            //         address: billingAddress.address_1,
            //         address2: billingAddress.address_2,
            //         city: billingAddress.city,
            //         postal_code: billingAddress.postcode,
            //         state: billingAddress.state,
            //         country: billingAddress.country,
            //         first_name: billingAddress.first_name,
            //         last_name: billingAddress.last_name,
            //         email: billingAddress.email,
            //         phone: billingAddress.phone,
            //
            //         // shipping address parameters
            //         shipping_address: shippingAddress.address_1,
            //         shipping_address2: shippingAddress.address_2,
            //         shipping_city: shippingAddress.city,
            //         shipping_postal_code: shippingAddress.postcode,
            //         shipping_state: shippingAddress.state,
            //         shipping_country: shippingAddress.country,
            //         shipping_first_name: shippingAddress.first_name,
            //         shipping_last_name: shippingAddress.last_name,
            //     };
            //
            //     const response = await fetch('/wp-json/betterpayment/payment', {
            //         method: 'POST',
            //         body: JSON.stringify(payload),
            //         headers: {
            //             'Content-Type': 'application/json',
            //         },
            //     });
            //
            //     if (response.ok) {
            //         const data = JSON.parse(await response.json());
            //
            //         if (data.error_code === 0) {
            //             session.completePayment({
            //                 status: ApplePaySession.STATUS_SUCCESS
            //             });
            //
            //             transaction_id = data.transaction_id;
            //             transaction_status = data.status;
            //
            //             // WC Function
            //             // Submits the checkout and begins processing
            //             onSubmit();
            //         }
            //         else {
            //             console.error('Payment Gateway request failed:', response.status, response.statusText);
            //             console.error('Error details:', data);
            //
            //             session.completePayment({
            //                 status: ApplePaySession.STATUS_FAILURE
            //             });
            //
            //             onClose();
            //         }
            //     }
            //     else {
            //         const errorData = await response.json();
            //         console.error('Payment Gateway request failed:', response.status, response.statusText);
            //         console.error('Error details:', errorData);
            //
            //         session.completePayment({
            //             status: ApplePaySession.STATUS_FAILURE
            //         });
            //
            //         onClose();
            //     }
            // };

            session.oncancel = (event) => {
                // onClose();
            };

            session.begin();
        } catch (e) {
            // onClose();
            console.error(e);
        }
    }
}