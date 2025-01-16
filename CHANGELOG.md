This changelog follows the specifications of https://keepachangelog.com. Please, follow the specs when adding a new entry

## [3.1.2] - 2025-01-16

### Fixed

- Issue related to automatic capture of invoice transaction for which doesn't have transaction ID present


## [3.1.1] - 2024-10-30

### Fixed

- Issue related to automatic capture of invoice transactions

## [3.1.0] - 2024-08-23

### Added

- Aiia payment method
- iDEAL payment method


## [3.0.2] - 2024-07-09

### Added

- Add safe status update for async payment methods with redirect url

## [3.0.1] - 2024-07-09

### Added

- Implement a safe flow when custom fields' gender key is missing in customer record

## [3.0.0] - 2024-06-03

### Added

- Support for Shopware 6.6.* versions

## [2.2.0] - 2024-04-24

### Added

- Request To Pay Payment Method

## [2.1.1] - 2024-02-07

### Added

- API URL as a text configuration field

### Removed

- Selecting payment gateway as dropdown select field. You should now enter API URL manually instead.

### Notes

If updating from 2.1.0, navigate to extension settings and save the configuration, making sure the API URL is the same as in your payment gateway dashboard.

## [2.1.0] - 2023-09-20

### Added

- Giropay Payment Method

## [1.4.0] - 2023-09-20

### Added

- Giropay Payment Method

## [2.0.0] - 2023-08-22

### Added

- Support for Shopware 6.5.*

### Changed

- Refund and Capture cards on administration moved under Payment card in Details tab of the Order page

### Removed

- Removed support for Shopware 6.4.\*. Bug fixes for our plugin's 1.3.\* release will still be available for 6 months.

## [1.3.0] - 2023-08-09

### Added

- Feature to notify the existing customers when date of birth or gender fields are made required by the shop owner, while their account is missing this information. In such cases, customer will be redirected to the profile page to update their information and then proceed to checkout. This feature is not compatible with 3-step-checkout plugin in Shopware store, due to that plugin replacing the standard checkout page with a custom one.

### Fixed

- Fixed an issue in invoice payments where customer had no VAT IDs

## [1.2.0] - 2023-07-31

### Fixed

- Removal of trailing `/` character in APP_URL environment variable, in order to general webhook and return URLs correctly, when shop owner included a trailing `/` character in their domain in APP_URL.

## [1.1.0] - 2023-06-19

### Added

- Passing customer_id in payment requests to Better Payment API
- Captures
- Configuration option to automatically capture a transaction when an invoice document is created

## [1.0.0] - 2023-04-17

### Added

- Support Shopware 6.4.*
- Credit Card Payments
- SEPA Direct Debit B2C and B2B payments
- Invoice B2C and B2B Payments
- PayPal Payments
- Sofort Payments
- Paydirekt Payments
- Refunds for all payment methods
- Multi whitelabel(partner company) support
- Risk checks, including collection and configuration of DOB and Gender fields
