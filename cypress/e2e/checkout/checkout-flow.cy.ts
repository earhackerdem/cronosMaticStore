describe('Checkout Flow E2E Tests', () => {
    beforeEach(() => {
        // Start fresh for each test
        cy.exec('php artisan migrate:fresh --seed');
        cy.visit('/');
    });

    it('should complete full checkout flow for authenticated user', () => {
        // Step 1: Register/Login user
        cy.visit('/register');
        cy.get('input#name').type('Juan Pérez');
        cy.get('input#email').type('juan@example.com');
        cy.get('input#password').type('password123');
        cy.get('input#password_confirmation').type('password123');
        cy.get('button[type="submit"]').click();

        // Verify login successful
        cy.url().should('include', '/dashboard');

        // Step 2: Create shipping address
        cy.visit('/settings/addresses');
        cy.get('[data-testid="add-address-button"]').click();

        cy.get('select[name="type"]').select('shipping');
        cy.get('input[name="first_name"]').type('Juan');
        cy.get('input[name="last_name"]').type('Pérez');
        cy.get('input[name="address_line_1"]').type('Av. Insurgentes 123');
        cy.get('input[name="city"]').type('Ciudad de México');
        cy.get('input[name="state"]').type('CDMX');
        cy.get('input[name="postal_code"]').type('06100');
        cy.get('input[name="country"]').clear().type('México');
        cy.get('input[name="phone"]').type('5555551234');

        cy.get('button[type="submit"]').click();
        cy.get('[data-testid="address-card"]').should('contain', 'Juan Pérez');

        // Step 3: Add product to cart
        cy.visit('/productos');
        cy.get('[data-testid="product-card"]').first().click();
        cy.get('[data-testid="add-to-cart-button"]').click();

        // Verify product added to cart
        cy.get('[data-testid="cart-indicator"]').should('contain', '1');

        // Step 4: Go to cart and proceed to checkout
        cy.visit('/carrito');
        cy.get('[data-testid="checkout-button"]').click();

        // Verify redirect to checkout
        cy.url().should('include', '/checkout');
        cy.get('h1').should('contain', 'Checkout');

        // Step 5: Complete checkout flow

        // Checkout Step 1: Shipping Address
        cy.get('[data-testid="step-1"]').should('be.visible');
        cy.get('div').contains('Juan Pérez').click();
        cy.get('button').contains('Continuar').click();

        // Checkout Step 2: Billing Address
        cy.get('[data-testid="step-2"]').should('be.visible');
        cy.get('input#sameAddress').should('be.checked');
        cy.get('button').contains('Continuar').click();

        // Checkout Step 3: Shipping Method
        cy.get('[data-testid="step-3"]').should('be.visible');
        cy.get('div').contains('Envío Estándar').click();
        cy.get('button').contains('Continuar').click();

        // Checkout Step 4: Review and Payment
        cy.get('[data-testid="step-4"]').should('be.visible');
        cy.get('h3').contains('Resumen y pago').should('be.visible');

        // Verify address summary
        cy.get('p').contains('Juan Pérez').should('be.visible');
        cy.get('p').contains('Av. Insurgentes 123').should('be.visible');

        // Verify payment method
        cy.get('p').contains('Pago con PayPal').should('be.visible');

        // Complete order
        cy.get('button').contains('Finalizar pedido').click();

        // Step 6: Verify order confirmation
        cy.url().should('include', '/orders/confirmation/');
        cy.get('h1').should('contain', '¡Pedido confirmado!');
        cy.get('p').contains('Número de pedido:').should('be.visible');
        cy.get('p').contains('CM-').should('be.visible'); // Order number format
    });

    it('should handle empty cart at checkout', () => {
        // Login first
        cy.visit('/login');
        cy.get('input#email').type('test@example.com');
        cy.get('input#password').type('password');
        cy.get('button[type="submit"]').click();

        // Try to access checkout with empty cart
        cy.visit('/checkout');

        cy.get('h2').should('contain', 'Tu carrito está vacío');
        cy.get('p').should('contain', 'Necesitas productos en tu carrito para proceder');
        cy.get('button').contains('Ver productos').should('be.visible');
    });

    it('should navigate between checkout steps correctly', () => {
        // Setup: Add product to cart and go to checkout
        cy.visit('/login');
        cy.get('input#email').type('test@example.com');
        cy.get('input#password').type('password');
        cy.get('button[type="submit"]').click();

        // Add product and go to checkout
        cy.visit('/productos');
        cy.get('[data-testid="product-card"]').first().click();
        cy.get('[data-testid="add-to-cart-button"]').click();
        cy.visit('/carrito');
        cy.get('[data-testid="checkout-button"]').click();

        // Test step navigation

        // Should start at step 1
        cy.get('[data-testid="step-indicator-1"]').should('have.class', 'text-blue-600');

        // Cannot go to step 2 without completing step 1
        cy.get('[data-testid="step-indicator-2"]').click();
        cy.get('[data-testid="step-1"]').should('be.visible'); // Still on step 1

        // Complete step 1 by selecting address
        cy.get('[data-testid="address-option"]').first().click();
        cy.get('button').contains('Continuar').click();

        // Now on step 2
        cy.get('[data-testid="step-indicator-2"]').should('have.class', 'text-blue-600');
        cy.get('[data-testid="step-2"]').should('be.visible');

        // Test back navigation
        cy.get('button').contains('Anterior').click();
        cy.get('[data-testid="step-1"]').should('be.visible');

        // Go forward again
        cy.get('button').contains('Continuar').click();
        cy.get('button').contains('Continuar').click(); // Skip step 2

        // Now on step 3
        cy.get('[data-testid="step-3"]').should('be.visible');
    });

    it('should handle different billing address selection', () => {
        // Setup with user who has multiple addresses
        cy.visit('/login');
        cy.get('input#email').type('user.with.addresses@example.com');
        cy.get('input#password').type('password');
        cy.get('button[type="submit"]').click();

        // Add product and go to checkout
        cy.visit('/productos');
        cy.get('[data-testid="product-card"]').first().click();
        cy.get('[data-testid="add-to-cart-button"]').click();
        cy.visit('/carrito');
        cy.get('[data-testid="checkout-button"]').click();

        // Complete step 1
        cy.get('[data-testid="shipping-address"]').first().click();
        cy.get('button').contains('Continuar').click();

        // Step 2: Billing address
        cy.get('input#sameAddress').should('be.checked');

        // Uncheck same address option
        cy.get('input#sameAddress').click();
        cy.get('input#sameAddress').should('not.be.checked');

        // Should see billing address options
        cy.get('[data-testid="billing-address"]').should('be.visible');

        // Select different billing address
        cy.get('[data-testid="billing-address"]').first().click();

        // Continue button should be enabled
        cy.get('button').contains('Continuar').should('not.be.disabled');
    });

    it('should show correct order summary', () => {
        // Setup and navigate to checkout
        cy.visit('/login');
        cy.get('input#email').type('test@example.com');
        cy.get('input#password').type('password');
        cy.get('button[type="submit"]').click();

        // Add multiple products
        cy.visit('/productos');

        // Add first product
        cy.get('[data-testid="product-card"]').first().click();
        cy.get('[data-testid="add-to-cart-button"]').click();
        cy.get('[data-testid="quantity-input"]').clear().type('2');
        cy.get('[data-testid="update-quantity"]').click();

        // Go back and add another product
        cy.go('back');
        cy.get('[data-testid="product-card"]').eq(1).click();
        cy.get('[data-testid="add-to-cart-button"]').click();

        // Go to checkout
        cy.visit('/carrito');
        cy.get('[data-testid="checkout-button"]').click();

        // Verify order summary sidebar
        cy.get('[data-testid="order-summary"]').should('be.visible');
        cy.get('[data-testid="order-summary"]').should('contain', 'Resumen del pedido');

        // Check subtotal shows correct item count
        cy.get('[data-testid="order-summary"]').should('contain', 'Subtotal (3 artículos)');

        // Check shipping is free
        cy.get('[data-testid="order-summary"]').should('contain', 'Envío');
        cy.get('[data-testid="order-summary"]').should('contain', 'Gratis');

        // Check total amount is displayed
        cy.get('[data-testid="total-amount"]').should('be.visible');

        // Check security message
        cy.get('[data-testid="order-summary"]').should('contain', 'Pago seguro con cifrado SSL');
    });

    it('should handle shipping method selection', () => {
        // Setup and navigate to step 3
        cy.visit('/login');
        cy.get('input#email').type('test@example.com');
        cy.get('input#password').type('password');
        cy.get('button[type="submit"]').click();

        cy.visit('/productos');
        cy.get('[data-testid="product-card"]').first().click();
        cy.get('[data-testid="add-to-cart-button"]').click();
        cy.visit('/carrito');
        cy.get('[data-testid="checkout-button"]').click();

        // Navigate to step 3
        cy.get('[data-testid="shipping-address"]').first().click();
        cy.get('button').contains('Continuar').click();
        cy.get('button').contains('Continuar').click();

        // Should see shipping options
        cy.get('[data-testid="shipping-method-standard"]').should('be.visible');
        cy.get('[data-testid="shipping-method-express"]').should('be.visible');

        // Standard should be selected by default
        cy.get('[data-testid="shipping-method-standard"]').should('have.class', 'border-blue-500');

        // Select express shipping
        cy.get('[data-testid="shipping-method-express"]').click();
        cy.get('[data-testid="shipping-method-express"]').should('have.class', 'border-blue-500');
        cy.get('[data-testid="shipping-method-standard"]').should('not.have.class', 'border-blue-500');

        // Both should show "Gratis"
        cy.get('[data-testid="shipping-method-standard"]').should('contain', 'Gratis');
        cy.get('[data-testid="shipping-method-express"]').should('contain', 'Gratis');

        // Check delivery times
        cy.get('[data-testid="shipping-method-standard"]').should('contain', '5-7 días hábiles');
        cy.get('[data-testid="shipping-method-express"]').should('contain', '1-2 días hábiles');
    });

    it('should show loading state during order creation', () => {
        // Setup complete checkout flow
        cy.visit('/login');
        cy.get('input#email').type('test@example.com');
        cy.get('input#password').type('password');
        cy.get('button[type="submit"]').click();

        cy.visit('/productos');
        cy.get('[data-testid="product-card"]').first().click();
        cy.get('[data-testid="add-to-cart-button"]').click();
        cy.visit('/carrito');
        cy.get('[data-testid="checkout-button"]').click();

        // Complete all steps
        cy.get('[data-testid="shipping-address"]').first().click();
        cy.get('button').contains('Continuar').click();
        cy.get('button').contains('Continuar').click();
        cy.get('button').contains('Continuar').click();

        // Click finalize order and check loading state
        cy.get('button').contains('Finalizar pedido').click();

        // Should show loading state
        cy.get('button').contains('Procesando...').should('be.visible');
        cy.get('button').contains('Procesando...').should('be.disabled');

        // Should eventually redirect to confirmation
        cy.url().should('include', '/orders/confirmation/', { timeout: 10000 });
    });

    it('should handle checkout progress bar correctly', () => {
        // Setup
        cy.visit('/login');
        cy.get('input#email').type('test@example.com');
        cy.get('input#password').type('password');
        cy.get('button[type="submit"]').click();

        cy.visit('/productos');
        cy.get('[data-testid="product-card"]').first().click();
        cy.get('[data-testid="add-to-cart-button"]').click();
        cy.visit('/carrito');
        cy.get('[data-testid="checkout-button"]').click();

        // Initially no steps completed
        cy.get('[data-testid="progress-bar"]').should('have.attr', 'style').and('include', 'width: 0%');

        // Complete step 1
        cy.get('[data-testid="shipping-address"]').first().click();
        cy.get('[data-testid="progress-bar"]').should('have.attr', 'style').and('include', 'width: 25%');

        cy.get('button').contains('Continuar').click();

        // Complete step 2 (billing address auto-completed with same address)
        cy.get('[data-testid="progress-bar"]').should('have.attr', 'style').and('include', 'width: 50%');

        cy.get('button').contains('Continuar').click();

        // Complete step 3 (shipping method auto-selected)
        cy.get('[data-testid="progress-bar"]').should('have.attr', 'style').and('include', 'width: 75%');

        // Step icons should change color as completed
        cy.get('[data-testid="step-icon-1"]').should('have.class', 'text-green-600');
        cy.get('[data-testid="step-icon-2"]').should('have.class', 'text-green-600');
        cy.get('[data-testid="step-icon-3"]').should('have.class', 'text-green-600');
        cy.get('[data-testid="step-icon-4"]').should('have.class', 'text-blue-600'); // Current step
    });
});
