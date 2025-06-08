import { Address } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription
} from '@/components/ui/dialog';
import { AlertTriangle, Loader2 } from 'lucide-react';

interface DeleteAddressDialogProps {
    address: Address | null;
    isOpen: boolean;
    onClose: () => void;
    onConfirm: (address: Address) => Promise<void>;
    isLoading?: boolean;
}

export function DeleteAddressDialog({
    address,
    isOpen,
    onClose,
    onConfirm,
    isLoading = false
}: DeleteAddressDialogProps) {
    if (!address) return null;

    const handleConfirm = async () => {
        await onConfirm(address);
        onClose();
    };

    const handleClose = () => {
        if (!isLoading) {
            onClose();
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-destructive/10">
                            <AlertTriangle className="h-5 w-5 text-destructive" />
                        </div>
                        <div>
                            <DialogTitle>Eliminar Dirección</DialogTitle>
                            <DialogDescription>
                                Esta acción no se puede deshacer.
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div className="py-4">
                    <p className="text-sm text-muted-foreground mb-4">
                        ¿Estás seguro de que quieres eliminar esta dirección?
                    </p>

                    <div className="rounded-lg border p-3 bg-muted/50">
                        <div className="font-medium text-sm">{address.full_name}</div>
                        <div className="text-sm text-muted-foreground mt-1">
                            {address.address_line_1}
                            {address.address_line_2 && `, ${address.address_line_2}`}
                        </div>
                        <div className="text-sm text-muted-foreground">
                            {address.city}, {address.state} {address.postal_code}
                        </div>
                        <div className="text-sm text-muted-foreground">
                            {address.country}
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleClose}
                        disabled={isLoading}
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={handleConfirm}
                        disabled={isLoading}
                    >
                        {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Eliminar Dirección
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
