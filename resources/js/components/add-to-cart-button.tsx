import { useState } from 'react';
import { ShoppingCart, Check, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useCart } from '@/contexts/CartContext';
import { Product } from '@/types';

interface AddToCartButtonProps {
    product: Product;
    quantity?: number;
    className?: string;
    size?: "default" | "sm" | "lg" | "icon";
    variant?: "default" | "destructive" | "outline" | "secondary" | "ghost" | "link";
}

export function AddToCartButton({
    product,
    quantity = 1,
    className,
    size = "lg",
    variant = "default"
}: AddToCartButtonProps) {
    const { addToCart, isLoading } = useCart();
    const [isAdding, setIsAdding] = useState(false);
    const [justAdded, setJustAdded] = useState(false);

    const isOutOfStock = product.stock_quantity === 0;
    const isDisabled = isOutOfStock || isLoading || isAdding;

    const handleAddToCart = async () => {
        if (isDisabled) return;

        setIsAdding(true);
                try {
            await addToCart(product.id, quantity);
            setJustAdded(true);
            console.log(`${product.name} añadido al carrito`);

            // Reset "just added" state after 2 seconds
            setTimeout(() => {
                setJustAdded(false);
            }, 2000);
        } catch (error) {
            console.error('Error al añadir el producto:', error);
        } finally {
            setIsAdding(false);
        }
    };

    const getButtonContent = () => {
        if (isAdding) {
            return (
                <>
                    <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                    Añadiendo...
                </>
            );
        }

        if (justAdded) {
            return (
                <>
                    <Check className="w-5 h-5 mr-2" />
                    ¡Añadido!
                </>
            );
        }

        if (isOutOfStock) {
            return (
                <>
                    <AlertCircle className="w-5 h-5 mr-2" />
                    Producto agotado
                </>
            );
        }

        return (
            <>
                <ShoppingCart className="w-5 h-5 mr-2" />
                Añadir al carrito
            </>
        );
    };

    return (
        <Button
            size={size}
            variant={justAdded ? 'outline' : variant}
            className={className}
            disabled={isDisabled}
            onClick={handleAddToCart}
            data-testid="add-to-cart-button"
        >
            {getButtonContent()}
        </Button>
    );
}
