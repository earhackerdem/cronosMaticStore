import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { AddressCard } from '@/components/address-card';
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

const mockDefaultAddress: Address = {
    ...mockAddress,
    id: 2,
    is_default: true,
};

describe('AddressCard', () => {
    const mockOnEdit = vi.fn();
    const mockOnDelete = vi.fn();
    const mockOnSetDefault = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should render address information correctly', () => {
        render(
            <AddressCard
                address={mockAddress}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('Acme Corp')).toBeInTheDocument();
        expect(screen.getByText('123 Main St')).toBeInTheDocument();
        expect(screen.getByText('Apt 4B')).toBeInTheDocument();
        expect(screen.getByText('New York, NY 10001')).toBeInTheDocument();
        expect(screen.getByText('USA')).toBeInTheDocument();
        expect(screen.getByText('Tel: +1234567890')).toBeInTheDocument();
    });

    it('should display shipping type badge', () => {
        render(
            <AddressCard
                address={mockAddress}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        expect(screen.getByText('Envío')).toBeInTheDocument();
    });

    it('should display billing type badge', () => {
        const billingAddress = { ...mockAddress, type: 'billing' as const };
        render(
            <AddressCard
                address={billingAddress}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        expect(screen.getByText('Facturación')).toBeInTheDocument();
    });

    it('should show default badge for default address', () => {
        render(
            <AddressCard
                address={mockDefaultAddress}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        expect(screen.getByText('Predeterminada')).toBeInTheDocument();
    });

    it('should call onEdit when edit button is clicked', async () => {
        const user = userEvent.setup();

        render(
            <AddressCard
                address={mockAddress}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        // Open dropdown menu
        const menuButton = screen.getByRole('button', { name: /abrir menú/i });
        await user.click(menuButton);

        // Find and click edit option
        const editButton = await screen.findByText('Editar');
        await user.click(editButton);

        expect(mockOnEdit).toHaveBeenCalledWith(mockAddress);
    });

    it('should call onDelete when delete button is clicked', async () => {
        const user = userEvent.setup();

        render(
            <AddressCard
                address={mockAddress}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        // Open dropdown menu
        const menuButton = screen.getByRole('button', { name: /abrir menú/i });
        await user.click(menuButton);

        // Find and click delete option
        const deleteButton = await screen.findByText('Eliminar');
        await user.click(deleteButton);

        expect(mockOnDelete).toHaveBeenCalledWith(mockAddress);
    });

    it('should call onSetDefault when set default button is clicked for non-default address', async () => {
        const user = userEvent.setup();

        render(
            <AddressCard
                address={mockAddress}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        // Open dropdown menu
        const menuButton = screen.getByRole('button', { name: /abrir menú/i });
        await user.click(menuButton);

        // Find and click set default option
        const setDefaultButton = await screen.findByText('Marcar como predeterminada');
        await user.click(setDefaultButton);

        expect(mockOnSetDefault).toHaveBeenCalledWith(mockAddress);
    });

    it('should show disabled set default option for default address', async () => {
        const user = userEvent.setup();

        render(
            <AddressCard
                address={mockDefaultAddress}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        // Open dropdown menu
        const menuButton = screen.getByRole('button', { name: /abrir menú/i });
        await user.click(menuButton);

        // Check that disabled option appears
        const disabledOption = await screen.findByText('Ya es predeterminada');
        expect(disabledOption).toBeInTheDocument();
    });

    it('should be disabled when loading', () => {
        render(
            <AddressCard
                address={mockAddress}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
                isLoading={true}
            />
        );

        const menuButton = screen.getByRole('button', { name: /abrir menú/i });
        expect(menuButton).toBeDisabled();
    });

    it('should not render company if not provided', () => {
        const addressWithoutCompany = { ...mockAddress, company: null };
        render(
            <AddressCard
                address={addressWithoutCompany}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        expect(screen.queryByText('Acme Corp')).not.toBeInTheDocument();
    });

    it('should not render phone if not provided', () => {
        const addressWithoutPhone = { ...mockAddress, phone: null };
        render(
            <AddressCard
                address={addressWithoutPhone}
                onEdit={mockOnEdit}
                onDelete={mockOnDelete}
                onSetDefault={mockOnSetDefault}
            />
        );

        expect(screen.queryByText(/Tel:/)).not.toBeInTheDocument();
    });
});
