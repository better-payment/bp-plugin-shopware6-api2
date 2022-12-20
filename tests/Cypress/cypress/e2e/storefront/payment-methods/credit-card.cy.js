describe('Credit Card test', () => {
    it('valid payment', () => {
        cy.buyProduct();
        cy.register({
            asCommercial: false,
            withBirthday: false,
            withGender: false,
        });
        cy.selectPaymentMethod('Credit Card');

        cy.get('#tos').check();
        cy.get('#confirmFormSubmit').click();

        cy.origin('https://testapi.betterpayment.de', () => {
            cy.url().should('include', 'payment');
            cy.contains('Pay Now');
            cy.contains('Cancel');
        })
    })
})