import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Address } from '@/types';
import { CreateAddressData, UpdateAddressData } from '@/lib/address-api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription
} from '@/components/ui/dialog';
import { Loader2 } from 'lucide-react';

const addressSchema = z.object({
    type: z.enum(['shipping', 'billing']),
    first_name: z.string().min(1, 'El nombre es obligatorio').max(255),
    last_name: z.string().min(1, 'El apellido es obligatorio').max(255),
    company: z.string().max(255).optional(),
    address_line_1: z.string().min(1, 'La dirección es obligatoria').max(255),
    address_line_2: z.string().max(255).optional(),
    city: z.string().min(1, 'La ciudad es obligatoria').max(255),
    state: z.string().min(1, 'El estado es obligatorio').max(255),
    postal_code: z.string().min(1, 'El código postal es obligatorio').max(20),
    country: z.string().min(1, 'El país es obligatorio').max(255),
    phone: z.string().max(20).optional(),
    is_default: z.boolean().optional(),
});

type AddressFormData = z.infer<typeof addressSchema>;

interface AddressFormProps {
    address?: Address | null;
    isOpen: boolean;
    onClose: () => void;
    onSave: (data: CreateAddressData | UpdateAddressData) => Promise<void>;
    isLoading?: boolean;
}

export function AddressForm({
    address,
    isOpen,
    onClose,
    onSave,
    isLoading = false
}: AddressFormProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const isEditing = Boolean(address);

    const {
        register,
        handleSubmit,
        reset,
        setValue,
        watch,
        formState: { errors }
    } = useForm<AddressFormData>({
        resolver: zodResolver(addressSchema),
        defaultValues: {
            type: 'shipping',
            first_name: '',
            last_name: '',
            company: '',
            address_line_1: '',
            address_line_2: '',
            city: '',
            state: '',
            postal_code: '',
            country: 'México',
            phone: '',
            is_default: false,
        }
    });

    const watchedType = watch('type');
    const watchedIsDefault = watch('is_default');

    useEffect(() => {
        if (address && isOpen) {
            reset({
                type: address.type,
                first_name: address.first_name,
                last_name: address.last_name,
                company: address.company || '',
                address_line_1: address.address_line_1,
                address_line_2: address.address_line_2 || '',
                city: address.city,
                state: address.state,
                postal_code: address.postal_code,
                country: address.country,
                phone: address.phone || '',
                is_default: address.is_default,
            });
        } else if (!address && isOpen) {
            reset({
                type: 'shipping',
                first_name: '',
                last_name: '',
                company: '',
                address_line_1: '',
                address_line_2: '',
                city: '',
                state: '',
                postal_code: '',
                country: 'México',
                phone: '',
                is_default: false,
            });
        }
    }, [address, isOpen, reset]);

    const onSubmit = async (data: AddressFormData) => {
        setIsSubmitting(true);
        try {
            // Remove empty strings and convert to undefined for optional fields
            const processedData = {
                ...data,
                company: data.company?.trim() || undefined,
                address_line_2: data.address_line_2?.trim() || undefined,
                phone: data.phone?.trim() || undefined,
            };

            await onSave(processedData);
            onClose();
        } catch (error) {
            console.error('Error saving address:', error);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleClose = () => {
        if (!isSubmitting) {
            onClose();
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[425px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Editar Dirección' : 'Nueva Dirección'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Modifica los datos de tu dirección.'
                            : 'Agrega una nueva dirección a tu libreta.'
                        }
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    {/* Type */}
                    <div className="space-y-2">
                        <Label htmlFor="type">Tipo de dirección</Label>
                        <Select
                            value={watchedType}
                            onValueChange={(value: 'shipping' | 'billing') => setValue('type', value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona el tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="shipping">Envío</SelectItem>
                                <SelectItem value="billing">Facturación</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.type && (
                            <p className="text-sm text-destructive">{errors.type.message}</p>
                        )}
                    </div>

                    {/* Names */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="first_name">Nombre</Label>
                            <Input
                                id="first_name"
                                {...register('first_name')}
                                placeholder="Nombre"
                            />
                            {errors.first_name && (
                                <p className="text-sm text-destructive">{errors.first_name.message}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="last_name">Apellido</Label>
                            <Input
                                id="last_name"
                                {...register('last_name')}
                                placeholder="Apellido"
                            />
                            {errors.last_name && (
                                <p className="text-sm text-destructive">{errors.last_name.message}</p>
                            )}
                        </div>
                    </div>

                    {/* Company */}
                    <div className="space-y-2">
                        <Label htmlFor="company">Empresa (opcional)</Label>
                        <Input
                            id="company"
                            {...register('company')}
                            placeholder="Nombre de la empresa"
                        />
                        {errors.company && (
                            <p className="text-sm text-destructive">{errors.company.message}</p>
                        )}
                    </div>

                    {/* Address Lines */}
                    <div className="space-y-2">
                        <Label htmlFor="address_line_1">Dirección</Label>
                        <Input
                            id="address_line_1"
                            {...register('address_line_1')}
                            placeholder="Calle y número"
                        />
                        {errors.address_line_1 && (
                            <p className="text-sm text-destructive">{errors.address_line_1.message}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="address_line_2">Dirección 2 (opcional)</Label>
                        <Input
                            id="address_line_2"
                            {...register('address_line_2')}
                            placeholder="Colonia, departamento, etc."
                        />
                        {errors.address_line_2 && (
                            <p className="text-sm text-destructive">{errors.address_line_2.message}</p>
                        )}
                    </div>

                    {/* City, State, Postal Code */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="city">Ciudad</Label>
                            <Input
                                id="city"
                                {...register('city')}
                                placeholder="Ciudad"
                            />
                            {errors.city && (
                                <p className="text-sm text-destructive">{errors.city.message}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="state">Estado</Label>
                            <Input
                                id="state"
                                {...register('state')}
                                placeholder="Estado"
                            />
                            {errors.state && (
                                <p className="text-sm text-destructive">{errors.state.message}</p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="postal_code">Código Postal</Label>
                            <Input
                                id="postal_code"
                                {...register('postal_code')}
                                placeholder="12345"
                            />
                            {errors.postal_code && (
                                <p className="text-sm text-destructive">{errors.postal_code.message}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="country">País</Label>
                            <Input
                                id="country"
                                {...register('country')}
                                placeholder="País"
                            />
                            {errors.country && (
                                <p className="text-sm text-destructive">{errors.country.message}</p>
                            )}
                        </div>
                    </div>

                    {/* Phone */}
                    <div className="space-y-2">
                        <Label htmlFor="phone">Teléfono (opcional)</Label>
                        <Input
                            id="phone"
                            {...register('phone')}
                            placeholder="+52 555 123 4567"
                        />
                        {errors.phone && (
                            <p className="text-sm text-destructive">{errors.phone.message}</p>
                        )}
                    </div>

                    {/* Default checkbox */}
                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="is_default"
                            checked={watchedIsDefault}
                            onCheckedChange={(checked) => setValue('is_default', Boolean(checked))}
                        />
                        <Label htmlFor="is_default" className="text-sm font-normal">
                            Marcar como dirección predeterminada para {watchedType === 'shipping' ? 'envío' : 'facturación'}
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                            disabled={isSubmitting}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={isSubmitting || isLoading}
                        >
                            {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            {isEditing ? 'Actualizar' : 'Crear'} Dirección
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
