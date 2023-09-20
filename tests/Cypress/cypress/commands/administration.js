Cypress.Commands.add('loginAsAdmin', () => {
    cy.visit('admin#/login');
    cy.get('#sw-field--username').type('admin');
    cy.get('#sw-field--password').type('shopware');
    cy.get('.sw-button').click();

    cy.url().should('include', 'dashboard');
});

Cypress.Commands.add('goToSettings', () => {
    cy.get('[data-testid="sw-icon__regular-cog"]').click({force: true});
    cy.contains('Settings');
});
