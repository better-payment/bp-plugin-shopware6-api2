describe('Paydirekt test', () => {
    it('valid payment', () => {
        cy.buyProduct();
        cy.register({
            asCommercial: false,
            withBirthday: false,
            withGender: false,
        });
        cy.selectPaymentMethod('Paydirekt');

        cy.get('#tos').check();
        cy.get('#confirmFormSubmit').click();

        cy.origin('https://sandbox.paydirekt.de', () => {
            cy.url().should('include', 'checkout');
            cy.contains('Better Payment Germany');
        })
    })
})