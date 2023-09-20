Cypress.Commands.add('register', (data) => {
    cy.url().should('include', 'checkout/register')

    if (data.asCommercial) {
        cy.get('#accountType').select('Commercial');
    }

    cy.get('#personalSalutation').select('Mr.');
    cy.get('#personalFirstName').type('Cypress');
    cy.get('#personalLastName').type('Test');
    if (data.withBirthday) {
        cy.get('#personalBirthday').select('1');
        cy.get(':nth-child(5) > :nth-child(2) > .custom-select').select('1');
        cy.get(':nth-child(3) > .custom-select').select('2000');
    }

    if (data.withGender) {
        cy.get('#better_payment_customer_gender').select('m');
    }

    // cy.get('.register-personal > .custom-control > .custom-control-label').click();
    // cy.get('#personalGuest').check();
    cy.get('#personalMail').type('cypress@test.com');
    cy.get('#billingAddressAddressStreet').type('Main street');
    cy.get('#billingAddressAddressZipcode').type('1234567');
    cy.get('#billingAddressAddressCity').type('White city');
    cy.get('#billingAddressAddressCountry').select('Germany');
    cy.get('#billingAddressAddressCountryState').select('Hamburg');
    cy.get('#billingAddressAddressPhoneNumber').type('1234567');
    cy.get('.register-submit > .btn').click();
});

Cypress.Commands.add('buyProduct', () => {
    // TODO: improve url and path access, consider below lines
    // cy.visit('/');
    // cy.get('.main-navigation-link-text > span').first().click();

    cy.visit('Clothing/');
    cy.contains('Add to shopping cart').first().click();
    cy.contains('Go to checkout').click();
})

Cypress.Commands.add('selectPaymentMethod', (name) => {
    cy.url().should('include', 'checkout/confirm');

    // show more button in case payment method name is not shown
    if (cy.get('.confirm-checkout-collapse-trigger-label')) {
        cy.get('.confirm-checkout-collapse-trigger-label').click();
    }

    cy.contains(name).click();
})
