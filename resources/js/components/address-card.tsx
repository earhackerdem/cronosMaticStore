import { Address } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';
import { MoreHorizontal, Edit, Trash2, Star, StarOff } from 'lucide-react';
import { cn } from '@/lib/utils';

interface AddressCardProps {
    address: Address;
    onEdit: (address: Address) => void;
    onDelete: (address: Address) => void;
    onSetDefault: (address: Address) => void;
    isLoading?: boolean;
}

export function AddressCard({
    address,
    onEdit,
    onDelete,
    onSetDefault,
    isLoading = false
}: AddressCardProps) {
    const typeLabel = address.type === 'shipping' ? 'Envío' : 'Facturación';
    const typeColor = address.type === 'shipping' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';

    return (
        <Card className={cn(
            "relative transition-all duration-200 hover:shadow-md",
            address.is_default && "ring-2 ring-primary/20",
            isLoading && "opacity-50 pointer-events-none"
        )} data-testid="address-card">
            <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <Badge variant="secondary" className={typeColor}>
                            {typeLabel}
                        </Badge>
                        {address.is_default && (
                            <Badge variant="default" className="bg-primary/10 text-primary">
                                <Star className="w-3 h-3 mr-1" />
                                Predeterminada
                            </Badge>
                        )}
                    </div>

                    <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-8 w-8 p-0"
                            disabled={isLoading}
                            data-testid="address-menu-button"
                        >
                            <MoreHorizontal className="h-4 w-4" />
                            <span className="sr-only">Abrir menú</span>
                        </Button>
                    </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => onEdit(address)}>
                                <Edit className="mr-2 h-4 w-4" />
                                Editar
                            </DropdownMenuItem>

                            {!address.is_default && (
                                <DropdownMenuItem onClick={() => onSetDefault(address)}>
                                    <Star className="mr-2 h-4 w-4" />
                                    Marcar como predeterminada
                                </DropdownMenuItem>
                            )}

                            {address.is_default && (
                                <DropdownMenuItem disabled>
                                    <StarOff className="mr-2 h-4 w-4" />
                                    Ya es predeterminada
                                </DropdownMenuItem>
                            )}

                            <DropdownMenuSeparator />

                            <DropdownMenuItem
                                onClick={() => onDelete(address)}
                                className="text-destructive focus:text-destructive"
                            >
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </CardHeader>

            <CardContent className="pt-0">
                <div className="space-y-2">
                    <div className="font-medium text-foreground">
                        {address.full_name}
                    </div>

                    {address.company && (
                        <div className="text-sm text-muted-foreground">
                            {address.company}
                        </div>
                    )}

                    <div className="text-sm text-muted-foreground space-y-1">
                        <div>{address.address_line_1}</div>
                        {address.address_line_2 && <div>{address.address_line_2}</div>}
                        <div>
                            {address.city}, {address.state} {address.postal_code}
                        </div>
                        <div>{address.country}</div>
                    </div>

                    {address.phone && (
                        <div className="text-sm text-muted-foreground">
                            Tel: {address.phone}
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
