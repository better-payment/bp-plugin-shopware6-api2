describe('Custom fields are installed', () => {
    it('successfully installed customer gender custom field', () => {
        cy.loginAsAdmin().goToSettings();

        // Go to Custom Fields
        cy.get('.sw-settings__tab-system').click();
        cy.get('[data-testid="sw-icon__regular-bars-square"]').click();

        // Check custom field is defined correctly
        cy.contains('Better Payment Customer').click();
        cy.contains('Gender').click();
        cy.get('#sw-field--currentCustomField-name').should('have.value', 'better_payment_customer_gender');
    });
});