import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { DeleteAddressDialog } from '@/components/delete-address-dialog';
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

describe('DeleteAddressDialog', () => {
    const mockOnClose = vi.fn();
    const mockOnConfirm = vi.fn();

    beforeEach(() => {
        vi.clearAllMocks();
        mockOnConfirm.mockResolvedValue(undefined);
    });

    it('should render dialog when open and address provided', () => {
        render(
            <DeleteAddressDialog
                address={mockAddress}
                isOpen={true}
                onClose={mockOnClose}
                onConfirm={mockOnConfirm}
            />
        );

        expect(screen.getByRole('heading', { name: /eliminar dirección/i })).toBeInTheDocument();
        expect(screen.getByText('Esta acción no se puede deshacer.')).toBeInTheDocument();
        expect(screen.getByText('¿Estás seguro de que quieres eliminar esta dirección?')).toBeInTheDocument();
    });

    it('should display address information', () => {
        render(
            <DeleteAddressDialog
                address={mockAddress}
                isOpen={true}
                onClose={mockOnClose}
                onConfirm={mockOnConfirm}
            />
        );

        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('123 Main St, Apt 4B')).toBeInTheDocument();
        expect(screen.getByText('New York, NY 10001')).toBeInTheDocument();
        expect(screen.getByText('USA')).toBeInTheDocument();
    });

    it('should handle address without address_line_2', () => {
        const addressWithoutLine2 = { ...mockAddress, address_line_2: null };

        render(
            <DeleteAddressDialog
                address={addressWithoutLine2}
                isOpen={true}
                onClose={mockOnClose}
                onConfirm={mockOnConfirm}
            />
        );

        expect(screen.getByText('123 Main St')).toBeInTheDocument();
        expect(screen.queryByText('123 Main St, Apt 4B')).not.toBeInTheDocument();
    });

    it('should call onConfirm when delete button is clicked', async () => {
        const user = userEvent.setup();

        render(
            <DeleteAddressDialog
                address={mockAddress}
                isOpen={true}
                onClose={mockOnClose}
                onConfirm={mockOnConfirm}
            />
        );

        const deleteButtons = screen.getAllByText('Eliminar Dirección');
        const deleteButton = deleteButtons.find(element => element.tagName === 'BUTTON');

        await user.click(deleteButton!);

        expect(mockOnConfirm).toHaveBeenCalledWith(mockAddress);
    });

    it('should call onClose when cancel button is clicked', async () => {
        const user = userEvent.setup();

        render(
            <DeleteAddressDialog
                address={mockAddress}
                isOpen={true}
                onClose={mockOnClose}
                onConfirm={mockOnConfirm}
            />
        );

        const cancelButton = screen.getByRole('button', { name: /cancelar/i });
        await user.click(cancelButton);

        expect(mockOnClose).toHaveBeenCalled();
    });

    it('should disable buttons when loading', () => {
        render(
            <DeleteAddressDialog
                address={mockAddress}
                isOpen={true}
                onClose={mockOnClose}
                onConfirm={mockOnConfirm}
                isLoading={true}
            />
        );

        const cancelButton = screen.getByRole('button', { name: /cancelar/i });
        const deleteButtons = screen.getAllByText('Eliminar Dirección');
        const deleteButton = deleteButtons.find(element => element.tagName === 'BUTTON');

        expect(cancelButton).toBeDisabled();
        expect(deleteButton).toBeDisabled();
    });

    it('should show loading spinner when loading', () => {
        render(
            <DeleteAddressDialog
                address={mockAddress}
                isOpen={true}
                onClose={mockOnClose}
                onConfirm={mockOnConfirm}
                isLoading={true}
            />
        );

        const loadingSpinner = document.querySelector('.animate-spin');
        expect(loadingSpinner).toBeInTheDocument();
    });

    it('should not render when address is null', () => {
        render(
            <DeleteAddressDialog
                address={null}
                isOpen={true}
                onClose={mockOnClose}
                onConfirm={mockOnConfirm}
            />
        );

        expect(screen.queryByRole('heading', { name: /eliminar dirección/i })).not.toBeInTheDocument();
    });

    it('should not render when closed', () => {
        render(
            <DeleteAddressDialog
                address={mockAddress}
                isOpen={false}
                onClose={mockOnClose}
                onConfirm={mockOnConfirm}
            />
        );

        expect(screen.queryByRole('heading', { name: /eliminar dirección/i })).not.toBeInTheDocument();
    });
});