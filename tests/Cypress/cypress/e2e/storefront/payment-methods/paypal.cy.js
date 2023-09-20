describe('PayPal test', () => {
    it('valid payment', () => {
        cy.buyProduct();
        cy.register({
            asCommercial: false,
            withBirthday: false,
            withGender: false,
        });
        cy.selectPaymentMethod('PayPal');

        cy.get('#tos').check();
        cy.get('#confirmFormSubmit').click();

        cy.origin('https://www.sandbox.paypal.com', () => {
            cy.url().should('include', 'paypal');
        })
    })
})