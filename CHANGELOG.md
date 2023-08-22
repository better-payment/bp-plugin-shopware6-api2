# 2.0.0

- Added support for Shopware 6.5.*
- Removed support for Shopware 6.4.*
- Refund/Capture cards on administration moved to under Payment card in Details tab of Order page
- Tested with 6.5.0.0 & 6.5.3.0


# 1.3.0

- Bug fix for invoice payments
- Feature to notify the existing customers when date of birth or gender fields are made required by the shop owner, while their account is missing this information. In such cases, customer will be redirected to the profile page to update their information and then proceed to checkout.
- Tested with 6.4.0.0 & 6.4.20.0

# 1.2.0

Enhancement to remove `/` character at the end of APP_URL environment variable when generating webhook URLs.
This change helps to avoid errors for generating return and webhook URLs when users leave `/` character at the end of their APP_URL environment variable.

# 1.1.0

- Customer number is passed as Better Payment API request parameter
- Added Capture card to administration under Refunds card
- Automatic Capture during Document Creation for Invoice B2C and B2B depending on plugin configuration flag
- Tested with 6.4.0.0 & 6.4.20.0

# 1.0.0 - Initial release of the Better Payment API2 plugin for Shopware 6

### Supported Shopware versions

- > 6.4 < 6.5

### Included Payment Methods

- Credit Card
- SEPA Direct Debit B2C and B2B
- Invoice B2C and B2B
- PayPal
- Sofort
- Paydirekt

### Included actions

- Payments
- Refunds

### Included services

- Multi whitelabel support
- Risk checks, including collection and configuration of Date of Birth and Gender fields

### Tested with
- 6.4.0.0
- 6.4.20.0
