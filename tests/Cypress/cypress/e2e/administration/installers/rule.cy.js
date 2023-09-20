describe('Rules are installed', () => {
    it('successfully installed private customer rule', () => {
        cy.loginAsAdmin().goToSettings();

        // Go to Rule Builder
        cy.get('[data-testid="sw-icon__regular-rule"]').click();

        // Search for Private Customer Role
        cy.get('.sw-search-bar__input').type('Private Customer');
        cy.contains('Private Customer').click({force: true});

        // Check rule defined correctly
        cy.get('.sw-single-select__selection-text').first().contains('Commercial customer');
        cy.get('.sw-single-select__selection-text').last().contains('No');

        // Check rule assigned to Payment Methods correctly
        cy.get('.sw-settings-rule-detail__tab-item-assignments').click();
        cy.get('.sw-settings-rule-detail-assignments__card-payment_method').scrollIntoView().contains('Invoice');
        cy.get('.sw-settings-rule-detail-assignments__card-payment_method').scrollIntoView().contains('SEPA Direct Debit');
    });

    it('successfully installed commercial customer rule', () => {
        cy.loginAsAdmin().goToSettings();

        // Go to Rule Builder settings
        cy.get('[data-testid="sw-icon__regular-rule"]').click();

        // Search for Commercial Customer Role
        cy.get('.sw-search-bar__input').type('Commercial Customer');
        cy.contains('Commercial Customer').click({force: true});

        // Check rule defined correctly
        cy.get('.sw-single-select__selection-text').first().contains('Commercial customer');
        cy.get('.sw-single-select__selection-text').last().contains('Yes');

        // Check rule assigned to Payment Methods correctly
        cy.get('.sw-settings-rule-detail__tab-item-assignments').click();
        cy.get('.sw-settings-rule-detail-assignments__card-payment_method').scrollIntoView().contains('Invoice B2B');
        cy.get('.sw-settings-rule-detail-assignments__card-payment_method').scrollIntoView().contains('SEPA Direct Debit B2B');
    });
});