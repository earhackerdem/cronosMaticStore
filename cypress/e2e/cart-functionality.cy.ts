describe('Funcionalidad del Carrito', () => {
    beforeEach(() => {
        // Interceptar las llamadas a la API del carrito
        cy.intercept('GET', '/api/v1/cart', { fixture: 'empty-cart.json' }).as('getEmptyCart');
        cy.intercept('POST', '/api/v1/cart', { fixture: 'cart-with-item.json' }).as('addToCart');
        cy.intercept('PUT', '/api/v1/cart/items/*', { fixture: 'cart-updated.json' }).as('updateCartItem');
        cy.intercept('DELETE', '/api/v1/cart/items/*', { fixture: 'cart-item-removed.json' }).as('removeCartItem');
        cy.intercept('DELETE', '/api/v1/cart/clear', { fixture: 'empty-cart.json' }).as('clearCart');
    });

    describe('Indicador del carrito en el header', () => {
        it('muestra el icono del carrito sin badge cuando está vacío', () => {
            cy.visit('/productos');
            cy.wait('@getEmptyCart');

            cy.get('[data-testid="cart-indicator"]').should('be.visible');
            cy.get('[data-testid="cart-badge"]').should('not.exist');
        });

        it('muestra el badge con la cantidad correcta cuando hay items', () => {
            cy.intercept('GET', '/api/v1/cart', { fixture: 'cart-with-items.json' }).as('getCartWithItems');

            cy.visit('/productos');
            cy.wait('@getCartWithItems');

            cy.get('[data-testid="cart-indicator"]').should('be.visible');
            cy.get('[data-testid="cart-badge"]').should('contain', '3');
        });

        it('navega a la página del carrito al hacer clic', () => {
            cy.visit('/productos');
            cy.wait('@getEmptyCart');

            cy.get('[data-testid="cart-indicator"]').click();
            cy.url().should('include', '/carrito');
        });
    });

    describe('Botón "Añadir al carrito" en página de producto', () => {
        beforeEach(() => {
            cy.visit('/productos/reloj-automatico-test');
        });

        it('muestra el botón habilitado cuando el producto tiene stock', () => {
            cy.get('[data-testid="add-to-cart-button"]').should('be.visible');
            cy.get('[data-testid="add-to-cart-button"]').should('not.be.disabled');
            cy.get('[data-testid="add-to-cart-button"]').should('contain', 'Añadir al carrito');
        });

        it('añade el producto al carrito exitosamente', () => {
            cy.get('[data-testid="add-to-cart-button"]').click();
            cy.wait('@addToCart');

            // Verificar que el botón muestra el estado de éxito
            cy.get('[data-testid="add-to-cart-button"]').should('contain', '¡Añadido!');

            // Verificar que el indicador del carrito se actualiza
            cy.get('[data-testid="cart-badge"]').should('contain', '1');
        });

        it('muestra estado de carga durante la operación', () => {
            // Simular una respuesta lenta
            cy.intercept('POST', '/api/v1/cart', {
                fixture: 'cart-with-item.json',
                delay: 1000
            }).as('slowAddToCart');

            cy.get('[data-testid="add-to-cart-button"]').click();

            // Verificar estado de carga
            cy.get('[data-testid="add-to-cart-button"]').should('contain', 'Añadiendo...');
            cy.get('[data-testid="add-to-cart-button"]').should('be.disabled');

            cy.wait('@slowAddToCart');
            cy.get('[data-testid="add-to-cart-button"]').should('contain', '¡Añadido!');
        });

        it('maneja errores de la API correctamente', () => {
            cy.intercept('POST', '/api/v1/cart', {
                statusCode: 400,
                body: { success: false, message: 'Producto sin stock' }
            }).as('addToCartError');

            cy.get('[data-testid="add-to-cart-button"]').click();
            cy.wait('@addToCartError');

            // El botón debería volver al estado normal
            cy.get('[data-testid="add-to-cart-button"]').should('contain', 'Añadir al carrito');
        });
    });

    describe('Página del carrito', () => {
        it('muestra mensaje de carrito vacío cuando no hay items', () => {
            cy.visit('/carrito');
            cy.wait('@getEmptyCart');

            cy.get('[data-testid="empty-cart-message"]').should('be.visible');
            cy.get('[data-testid="empty-cart-message"]').should('contain', 'Tu carrito está vacío');
            cy.get('[data-testid="continue-shopping-button"]').should('be.visible');
        });

        it('muestra los items del carrito correctamente', () => {
            cy.intercept('GET', '/api/v1/cart', { fixture: 'cart-with-items.json' }).as('getCartWithItems');

            cy.visit('/carrito');
            cy.wait('@getCartWithItems');

            // Verificar que se muestran los items
            cy.get('[data-testid="cart-item"]').should('have.length', 2);
            cy.get('[data-testid="cart-item"]').first().should('contain', 'Reloj Automático Test');

            // Verificar resumen del pedido
            cy.get('[data-testid="order-summary"]').should('be.visible');
            cy.get('[data-testid="total-amount"]').should('contain', '$4,500.00');
            cy.get('[data-testid="checkout-button"]').should('be.visible');
        });

        it('permite actualizar la cantidad de un item', () => {
            cy.intercept('GET', '/api/v1/cart', { fixture: 'cart-with-items.json' }).as('getCartWithItems');

            cy.visit('/carrito');
            cy.wait('@getCartWithItems');

            // Incrementar cantidad
            cy.get('[data-testid="cart-item"]').first().within(() => {
                cy.get('[data-testid="increment-quantity"]').click();
            });

            cy.wait('@updateCartItem');

            // Verificar que la cantidad se actualiza
            cy.get('[data-testid="cart-item"]').first().within(() => {
                cy.get('[data-testid="item-quantity"]').should('contain', '3');
            });
        });

        it('permite eliminar un item del carrito', () => {
            cy.intercept('GET', '/api/v1/cart', { fixture: 'cart-with-items.json' }).as('getCartWithItems');

            cy.visit('/carrito');
            cy.wait('@getCartWithItems');

            // Eliminar primer item
            cy.get('[data-testid="cart-item"]').first().within(() => {
                cy.get('[data-testid="remove-item"]').click();
            });

            cy.wait('@removeCartItem');

            // Verificar que el item se elimina
            cy.get('[data-testid="cart-item"]').should('have.length', 1);
        });

        it('permite vaciar el carrito completo', () => {
            cy.intercept('GET', '/api/v1/cart', { fixture: 'cart-with-items.json' }).as('getCartWithItems');

            cy.visit('/carrito');
            cy.wait('@getCartWithItems');

            // Vaciar carrito
            cy.get('[data-testid="clear-cart-button"]').click();

            // Confirmar en el modal
            cy.on('window:confirm', () => true);

            cy.wait('@clearCart');

            // Verificar que se muestra el mensaje de carrito vacío
            cy.get('[data-testid="empty-cart-message"]').should('be.visible');
        });

        it('previene decrementar cantidad por debajo de 1', () => {
            cy.intercept('GET', '/api/v1/cart', { fixture: 'cart-with-single-item.json' }).as('getCartWithSingleItem');

            cy.visit('/carrito');
            cy.wait('@getCartWithSingleItem');

            // El botón de decrementar debería estar deshabilitado cuando la cantidad es 1
            cy.get('[data-testid="cart-item"]').first().within(() => {
                cy.get('[data-testid="decrement-quantity"]').should('be.disabled');
            });
        });

        it('previene incrementar cantidad por encima del stock disponible', () => {
            cy.intercept('GET', '/api/v1/cart', { fixture: 'cart-with-max-stock-item.json' }).as('getCartWithMaxStock');

            cy.visit('/carrito');
            cy.wait('@getCartWithMaxStock');

            // El botón de incrementar debería estar deshabilitado cuando se alcanza el stock máximo
            cy.get('[data-testid="cart-item"]').first().within(() => {
                cy.get('[data-testid="increment-quantity"]').should('be.disabled');
                cy.get('[data-testid="stock-warning"]').should('contain', 'Stock máximo alcanzado');
            });
        });
    });

    describe('Flujo completo de compra', () => {
        it('permite añadir múltiples productos y proceder al checkout', () => {
            // Añadir primer producto
            cy.visit('/productos/reloj-automatico-test');
            cy.get('[data-testid="add-to-cart-button"]').click();
            cy.wait('@addToCart');

            // Verificar que el indicador se actualiza
            cy.get('[data-testid="cart-badge"]').should('contain', '1');

            // Añadir segundo producto
            cy.visit('/productos/reloj-cuarzo-test');
            cy.intercept('POST', '/api/v1/cart', { fixture: 'cart-with-two-items.json' }).as('addSecondItem');
            cy.get('[data-testid="add-to-cart-button"]').click();
            cy.wait('@addSecondItem');

            // Verificar que el indicador se actualiza
            cy.get('[data-testid="cart-badge"]').should('contain', '2');

            // Ir al carrito
            cy.get('[data-testid="cart-indicator"]').click();

            // Verificar que ambos productos están en el carrito
            cy.get('[data-testid="cart-item"]').should('have.length', 2);

            // Proceder al checkout
            cy.get('[data-testid="checkout-button"]').should('be.visible');
            cy.get('[data-testid="checkout-button"]').should('contain', 'Proceder al pago');
        });
    });
});
