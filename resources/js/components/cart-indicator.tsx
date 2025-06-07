import { ShoppingCart } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useCart } from '@/contexts/CartContext';

export function CartIndicator() {
    const { cart, isLoading } = useCart();

    const itemCount = cart?.total_items || 0;
    const hasItems = itemCount > 0;

        return (
        <Link href="/carrito" className="relative" data-testid="cart-indicator">
            <Button variant="ghost" size="icon" className="group h-9 w-9 cursor-pointer">
                <ShoppingCart className="!size-5 opacity-80 group-hover:opacity-100" />
                {hasItems && (
                    <Badge
                        variant="destructive"
                        className="absolute -right-2 -top-2 h-5 w-5 p-0 text-xs font-bold flex items-center justify-center"
                        data-testid="cart-badge"
                    >
                        {itemCount > 99 ? '99+' : itemCount}
                    </Badge>
                )}
                {isLoading && (
                    <div className="absolute -right-1 -top-1 h-3 w-3 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
                )}
            </Button>
        </Link>
    );
}
