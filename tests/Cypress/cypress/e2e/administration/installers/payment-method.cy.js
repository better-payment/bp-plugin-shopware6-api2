describe('Payment Methods are installed', () => {
    it('successfully installed Payment Methods', () => {
        cy.loginAsAdmin().goToSettings();

        // Go to Payment Methods
        cy.get('[data-testid="sw-icon__regular-credit-card"]').click();

        const paymentMethods = [
            'Invoice',
            'Invoice B2B',
            'SEPA Direct Debit',
            'SEPA Direct Debit B2B',
            'Credit Card',
            'PayPal',
            'Paydirekt',
            'Sofort'
        ];

        paymentMethods.forEach(paymentMethod => {
            cy.contains(paymentMethod + ' | Better Payment');
            // TODO: find a way to check if they are active (active toggle turned on)
        });
    });
});