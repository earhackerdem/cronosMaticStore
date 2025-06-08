import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { AddressForm } from '@/components/address-form';
import { Address } from '@/types';

const mockAddress: Address = {
    id: 1,
    type: 'shipping',
    first_name: 'John',
    last_name: 'Doe',
    full_name: 'John Doe',
    company: 'Acme Corp',
    address_line_1: '123 Main St',
    address_line_2: 'Apt 4B',
    city: 'New York',
    state: 'NY',
    postal_code: '10001',
    country: 'USA',
    phone: '+1234567890',
    is_default: false,
    full_address: '123 Main St, Apt 4B, New York, NY 10001, USA',
    created_at: '2023-01-01T00:00:00.000000Z',
    updated_at: '2023-01-01T00:00:00.000000Z',
};

describe('AddressForm', () => {
    const mockOnClose = vi.fn();
    const mockOnSave = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
        mockOnSave.mockResolvedValue(undefined);
    });

    it('should render create form when no address provided', () => {
        render(
            <AddressForm
                isOpen={true}
                onClose={mockOnClose}
                onSave={mockOnSave}
            />
        );

        expect(screen.getByText('Nueva Dirección')).toBeInTheDocument();
        expect(screen.getByText('Agrega una nueva dirección a tu libreta.')).toBeInTheDocument();
    });

    it('should render edit form when address is provided', () => {
        render(
            <AddressForm
                address={mockAddress}
                isOpen={true}
                onClose={mockOnClose}
                onSave={mockOnSave}
            />
        );

        expect(screen.getByText('Editar Dirección')).toBeInTheDocument();
        expect(screen.getByText('Modifica los datos de tu dirección.')).toBeInTheDocument();
    });

    it('should populate form fields when editing address', () => {
        render(
            <AddressForm
                address={mockAddress}
                isOpen={true}
                onClose={mockOnClose}
                onSave={mockOnSave}
            />
        );

        expect(screen.getByDisplayValue('John')).toBeInTheDocument();
        expect(screen.getByDisplayValue('Doe')).toBeInTheDocument();
        expect(screen.getByDisplayValue('Acme Corp')).toBeInTheDocument();
        expect(screen.getByDisplayValue('123 Main St')).toBeInTheDocument();
        expect(screen.getByDisplayValue('Apt 4B')).toBeInTheDocument();
        expect(screen.getByDisplayValue('New York')).toBeInTheDocument();
        expect(screen.getByDisplayValue('NY')).toBeInTheDocument();
        expect(screen.getByDisplayValue('10001')).toBeInTheDocument();
        expect(screen.getByDisplayValue('USA')).toBeInTheDocument();
        expect(screen.getByDisplayValue('+1234567890')).toBeInTheDocument();
    });

    it('should show required field validation errors', async () => {
        const user = userEvent.setup();

        render(
            <AddressForm
                isOpen={true}
                onClose={mockOnClose}
                onSave={mockOnSave}
            />
        );

        const submitButton = screen.getByRole('button', { name: /crear dirección/i });
        await user.click(submitButton);

        await waitFor(() => {
            expect(screen.getByText('El nombre es obligatorio')).toBeInTheDocument();
            expect(screen.getByText('El apellido es obligatorio')).toBeInTheDocument();
            expect(screen.getByText('La dirección es obligatoria')).toBeInTheDocument();
            expect(screen.getByText('La ciudad es obligatoria')).toBeInTheDocument();
            expect(screen.getByText('El estado es obligatorio')).toBeInTheDocument();
            expect(screen.getByText('El código postal es obligatorio')).toBeInTheDocument();
        });
    });

    it('should call onSave with form data when submitted', async () => {
        const user = userEvent.setup();

        render(
            <AddressForm
                isOpen={true}
                onClose={mockOnClose}
                onSave={mockOnSave}
            />
        );

        // Fill required fields using correct placeholders and labels
        await user.type(screen.getByLabelText(/nombre/i), 'Jane');
        await user.type(screen.getByLabelText(/apellido/i), 'Smith');
        await user.type(screen.getByPlaceholderText(/calle y número/i), '456 Oak Ave');
        await user.type(screen.getByLabelText(/ciudad/i), 'Boston');
        await user.type(screen.getByLabelText(/estado/i), 'MA');
        await user.type(screen.getByPlaceholderText(/12345/i), '02101');

        const submitButton = screen.getByRole('button', { name: /crear dirección/i });
        await user.click(submitButton);

        await waitFor(() => {
            expect(mockOnSave).toHaveBeenCalledWith(
                expect.objectContaining({
                    first_name: 'Jane',
                    last_name: 'Smith',
                    address_line_1: '456 Oak Ave',
                    city: 'Boston',
                    state: 'MA',
                    postal_code: '02101',
                    country: 'México',
                    type: 'shipping'
                })
            );
        });
    });

    it('should call onClose when cancel button is clicked', async () => {
        const user = userEvent.setup();

        render(
            <AddressForm
                isOpen={true}
                onClose={mockOnClose}
                onSave={mockOnSave}
            />
        );

        const cancelButton = screen.getByRole('button', { name: /cancelar/i });
        await user.click(cancelButton);

        expect(mockOnClose).toHaveBeenCalled();
    });

    it('should disable form when loading', () => {
        render(
            <AddressForm
                isOpen={true}
                onClose={mockOnClose}
                onSave={mockOnSave}
                isLoading={true}
            />
        );

        const submitButton = screen.getByRole('button', { name: /crear dirección/i });
        expect(submitButton).toBeDisabled();
    });

    it('should not render when not open', () => {
        render(
            <AddressForm
                isOpen={false}
                onClose={mockOnClose}
                onSave={mockOnSave}
            />
        );

        expect(screen.queryByText('Nueva Dirección')).not.toBeInTheDocument();
    });

    it('should show update button text when editing', () => {
        render(
            <AddressForm
                address={mockAddress}
                isOpen={true}
                onClose={mockOnClose}
                onSave={mockOnSave}
            />
        );

        expect(screen.getByRole('button', { name: /actualizar dirección/i })).toBeInTheDocument();
    });
});