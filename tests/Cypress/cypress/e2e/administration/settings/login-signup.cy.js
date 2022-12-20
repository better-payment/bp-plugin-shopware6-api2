describe('Log-in & sign-up flags are set', () => {
    it('successfully set login & signup flags', () => {
        cy.loginAsAdmin().goToSettings();

        // Go to Log-in & Sign-up
        cy.get('[data-testid="sw-icon__regular-sign-in"]').click();

        cy.get('[name="core.loginRegistration.showPhoneNumberField"]').siblings().first().should('have.css', 'background', 'rgb(24, 158, 255)');
        cy.get('[name="core.loginRegistration.showAccountTypeSelection"]').siblings().first().should('have.css', 'background', 'rgb(24, 158, 255)');
    });
});