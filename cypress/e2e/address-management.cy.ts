describe('Address Management', () => {
    beforeEach(() => {
        // Login with seeded admin user
        cy.visit('/login');
        cy.get('input[name="email"]').type('admin@cronosmatic.com');
        cy.get('input[name="password"]').type('password');
        cy.get('button[type="submit"]').click();
        cy.url().should('not.include', '/login');
    });

    it('should display the addresses page', () => {
        cy.visit('/settings/addresses');

        cy.contains('Libreta de Direcciones').should('be.visible');
        cy.contains('Gestiona tus direcciones de envío y facturación').should('be.visible');
        cy.contains('Nueva Dirección').should('be.visible');
    });

    it('should show empty state when no addresses exist', () => {
        cy.visit('/settings/addresses');

        cy.contains('No tienes direcciones guardadas').should('be.visible');
        cy.contains('Agrega una nueva dirección para comenzar').should('be.visible');
        cy.get('[data-testid="empty-state-add-button"]').should('be.visible');
    });

    it('should open address form when clicking new address button', () => {
        cy.visit('/settings/addresses');

        cy.contains('Nueva Dirección').click();

        cy.get('[role="dialog"]').should('be.visible');
        cy.contains('Nueva Dirección').should('be.visible');
        cy.contains('Agrega una nueva dirección a tu libreta').should('be.visible');
    });

    it('should create a new shipping address', () => {
        cy.visit('/settings/addresses');

        // Open form
        cy.contains('Nueva Dirección').click();

        // Fill form
        cy.get('select[name="type"]').select('shipping');
        cy.get('input[name="first_name"]').type('John');
        cy.get('input[name="last_name"]').type('Doe');
        cy.get('input[name="company"]').type('Acme Corp');
        cy.get('input[name="address_line_1"]').type('123 Main St');
        cy.get('input[name="address_line_2"]').type('Apt 4B');
        cy.get('input[name="city"]').type('New York');
        cy.get('input[name="state"]').type('NY');
        cy.get('input[name="postal_code"]').type('10001');
        cy.get('input[name="country"]').type('USA');
        cy.get('input[name="phone"]').type('+1234567890');
        cy.get('input[name="is_default"]').check();

        // Submit form
        cy.contains('Crear Dirección').click();

        // Verify address was created
        cy.contains('John Doe').should('be.visible');
        cy.contains('Acme Corp').should('be.visible');
        cy.contains('123 Main St').should('be.visible');
        cy.contains('Envío').should('be.visible');
        cy.contains('Predeterminada').should('be.visible');
    });

    it('should create a new billing address', () => {
        cy.visit('/settings/addresses');

        // Open form
        cy.contains('Nueva Dirección').click();

        // Fill form for billing address
        cy.get('select[name="type"]').select('billing');
        cy.get('input[name="first_name"]').type('Jane');
        cy.get('input[name="last_name"]').type('Smith');
        cy.get('input[name="address_line_1"]').type('456 Oak Ave');
        cy.get('input[name="city"]').type('Los Angeles');
        cy.get('input[name="state"]').type('CA');
        cy.get('input[name="postal_code"]').type('90210');
        cy.get('input[name="country"]').type('USA');

        // Submit form
        cy.contains('Crear Dirección').click();

        // Verify address was created
        cy.contains('Jane Smith').should('be.visible');
        cy.contains('456 Oak Ave').should('be.visible');
        cy.contains('Facturación').should('be.visible');
    });

    it('should edit an existing address', () => {
        // First create an address
        cy.createAddress({
            type: 'shipping',
            first_name: 'John',
            last_name: 'Doe',
            address_line_1: '123 Main St',
            city: 'New York',
            state: 'NY',
            postal_code: '10001',
            country: 'USA'
        });

        cy.visit('/settings/addresses');

        // Open dropdown menu and click edit
        cy.get('[data-testid="address-menu-button"]').first().click();
        cy.contains('Editar').click();

        // Verify form is pre-filled
        cy.get('input[name="first_name"]').should('have.value', 'John');
        cy.get('input[name="last_name"]').should('have.value', 'Doe');

        // Update the address
        cy.get('input[name="first_name"]').clear().type('Jane');
        cy.get('input[name="city"]').clear().type('Boston');

        // Submit form
        cy.contains('Actualizar Dirección').click();

        // Verify address was updated
        cy.contains('Jane Doe').should('be.visible');
        cy.contains('Boston').should('be.visible');
    });

    it('should delete an address', () => {
        // First create an address
        cy.createAddress({
            type: 'shipping',
            first_name: 'John',
            last_name: 'Doe',
            address_line_1: '123 Main St',
            city: 'New York',
            state: 'NY',
            postal_code: '10001',
            country: 'USA'
        });

        cy.visit('/settings/addresses');

        // Verify address exists
        cy.contains('John Doe').should('be.visible');

        // Open dropdown menu and click delete
        cy.get('[data-testid="address-menu-button"]').first().click();
        cy.contains('Eliminar').click();

        // Confirm deletion
        cy.get('[role="dialog"]').should('be.visible');
        cy.contains('Eliminar Dirección').should('be.visible');
        cy.contains('¿Estás seguro de que quieres eliminar esta dirección?').should('be.visible');
        cy.get('button').contains('Eliminar Dirección').click();

        // Verify address was deleted
        cy.contains('John Doe').should('not.exist');
        cy.contains('No tienes direcciones guardadas').should('be.visible');
    });

    it('should set address as default', () => {
        // Create two shipping addresses
        cy.createAddress({
            type: 'shipping',
            first_name: 'John',
            last_name: 'Doe',
            address_line_1: '123 Main St',
            city: 'New York',
            state: 'NY',
            postal_code: '10001',
            country: 'USA',
            is_default: true
        });

        cy.createAddress({
            type: 'shipping',
            first_name: 'Jane',
            last_name: 'Smith',
            address_line_1: '456 Oak Ave',
            city: 'Los Angeles',
            state: 'CA',
            postal_code: '90210',
            country: 'USA',
            is_default: false
        });

        cy.visit('/settings/addresses');

        // Verify first address is default
        cy.contains('John Doe').parent().should('contain', 'Predeterminada');

        // Set second address as default
        cy.contains('Jane Smith').parent().within(() => {
            cy.get('[data-testid="address-menu-button"]').click();
        });
        cy.contains('Marcar como predeterminada').click();

        // Verify second address is now default
        cy.contains('Jane Smith').parent().should('contain', 'Predeterminada');
        cy.contains('John Doe').parent().should('not.contain', 'Predeterminada');
    });

    it('should filter addresses by type', () => {
        // Create addresses of different types
        cy.createAddress({
            type: 'shipping',
            first_name: 'John',
            last_name: 'Doe',
            address_line_1: '123 Main St',
            city: 'New York',
            state: 'NY',
            postal_code: '10001',
            country: 'USA'
        });

        cy.createAddress({
            type: 'billing',
            first_name: 'Jane',
            last_name: 'Smith',
            address_line_1: '456 Oak Ave',
            city: 'Los Angeles',
            state: 'CA',
            postal_code: '90210',
            country: 'USA'
        });

        cy.visit('/settings/addresses');

        // Verify both addresses are visible in "All" tab
        cy.contains('Todas (2)').should('be.visible');
        cy.contains('John Doe').should('be.visible');
        cy.contains('Jane Smith').should('be.visible');

        // Filter by shipping
        cy.contains('Envío (1)').click();
        cy.contains('John Doe').should('be.visible');
        cy.contains('Jane Smith').should('not.exist');

        // Filter by billing
        cy.contains('Facturación (1)').click();
        cy.contains('Jane Smith').should('be.visible');
        cy.contains('John Doe').should('not.exist');
    });

    it('should validate required fields', () => {
        cy.visit('/settings/addresses');

        // Open form
        cy.contains('Nueva Dirección').click();

        // Try to submit empty form
        cy.contains('Crear Dirección').click();

        // Verify validation errors
        cy.contains('El nombre es obligatorio').should('be.visible');
        cy.contains('El apellido es obligatorio').should('be.visible');
        cy.contains('La dirección es obligatoria').should('be.visible');
        cy.contains('La ciudad es obligatoria').should('be.visible');
        cy.contains('El estado es obligatorio').should('be.visible');
        cy.contains('El código postal es obligatorio').should('be.visible');
        cy.contains('El país es obligatorio').should('be.visible');
    });

    it('should close form when clicking cancel', () => {
        cy.visit('/settings/addresses');

        // Open form
        cy.contains('Nueva Dirección').click();
        cy.get('[role="dialog"]').should('be.visible');

        // Click cancel
        cy.contains('Cancelar').click();

        // Verify form is closed
        cy.get('[role="dialog"]').should('not.exist');
    });
});
