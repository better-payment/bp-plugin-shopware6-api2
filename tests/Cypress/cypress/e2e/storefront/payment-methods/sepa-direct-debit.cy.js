describe('Paydirekt test', () => {
    it('valid private customer payment', () => {
        cy.buyProduct();
        cy.register({
            asCommercial: false,
            withBirthday: false,
            withGender: false,
        });

        cy.selectPaymentMethod('SEPA Direct Debit');

        // SEPA direct debit specific fields
        cy.get('#betterpayment_iban').type('IBAN 1234567');
        cy.get('#betterpayment_bic').type('BIC 1234567');
        cy.get('#agreement').check();

        cy.get('#tos').check();
        cy.get('#confirmFormSubmit').click();

    })
})