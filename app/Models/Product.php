<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'sku',
        'price',
        'stock_quantity',
        'brand',
        'movement_type',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'stock_quantity' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    /**
     * Default product images to use when no image is set
     */
    private static array $defaultImages = [
        'https://img.chrono24.com/images/uhren/39539607-m9too3m1kpkrqpnnxklsvnxp-Zoom.jpg',
        'https://img.chrono24.com/images/uhren/40851974-em5oh9xyb3j849bffkxv8rls-Zoom.jpg',
        'https://img.chrono24.com/images/uhren/26900830-3i5ennqwbi0zcqufcqyxjs5v-Zoom.jpg',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the full URL for the product image
     */
    public function getImageUrlAttribute(): string
    {
        if (!$this->image_path) {
            // Si no hay imagen, retornar una imagen por defecto aleatoria
            return $this->getRandomDefaultImage();
        }

        // Si la ruta ya es una URL completa, devolverla tal como está
        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        // Generar URL completa para rutas locales
        return url($this->image_path);
    }

    /**
     * Get a random default image for products without an image
     */
    private function getRandomDefaultImage(): string
    {
        // Usar el ID del producto como seed para que siempre devuelva la misma imagen
        // para el mismo producto (consistencia en múltiples cargas)
        $seed = $this->id ?? 0;
        $index = $seed % count(self::$defaultImages);

        return self::$defaultImages[$index];
    }

    /**
     * Get all default images (useful for testing or admin purposes)
     */
    public static function getDefaultImages(): array
    {
        return self::$defaultImages;
    }
}
