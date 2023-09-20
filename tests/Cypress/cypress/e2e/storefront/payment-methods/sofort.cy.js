describe('Sofort test', () => {
    it('valid payment', () => {
        cy.buyProduct();
        cy.register({
            asCommercial: false,
            withBirthday: false,
            withGender: false,
        });
        cy.selectPaymentMethod('Sofort');

        cy.get('#tos').check();
        cy.get('#confirmFormSubmit').click();

        cy.origin('https://www.sofort.com', () => {
            cy.url().should('include', 'sofort');
        })
    })
})