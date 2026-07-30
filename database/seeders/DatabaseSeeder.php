<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@shopsphere.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '1234567890',
            'address' => '123 Admin Street',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $customer = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'phone' => '0987654321',
            'address' => '456 Customer Lane',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip_code' => '90001',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Gadgets and electronic devices'],
            ['name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Fashion and apparel'],
            ['name' => 'Books', 'slug' => 'books', 'description' => 'Books and literature'],
            ['name' => 'Home & Garden', 'slug' => 'home-garden', 'description' => 'Home and garden essentials'],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'Sports and outdoor equipment'],
        ];

        $createdCategories = collect($categories)->map(fn ($cat) => Category::create($cat));

        $products = [
            [
                'category_id' => $createdCategories[0]->id,
                'name' => 'Laptop Pro 15"',
                'slug' => 'laptop-pro-15',
                'price' => 1199.99,
                'sale_price' => 1099.99,
                'quantity' => 50,
                'description' => 'High-performance laptop with 15-inch display',
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'category_id' => $createdCategories[0]->id,
                'name' => 'Wireless Headphones',
                'slug' => 'wireless-headphones',
                'price' => 199.99,
                'quantity' => 100,
                'description' => 'Premium noise-canceling wireless headphones',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'category_id' => $createdCategories[0]->id,
                'name' => 'Smartphone X',
                'slug' => 'smartphone-x',
                'price' => 899.99,
                'sale_price' => 799.99,
                'quantity' => 75,
                'description' => 'Latest smartphone with advanced camera',
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'category_id' => $createdCategories[1]->id,
                'name' => 'Classic Denim Jacket',
                'slug' => 'classic-denim-jacket',
                'price' => 89.99,
                'quantity' => 200,
                'description' => 'Timeless denim jacket for all occasions',
                'image' => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'category_id' => $createdCategories[1]->id,
                'name' => 'Running Shoes Pro',
                'slug' => 'running-shoes-pro',
                'price' => 129.99,
                'sale_price' => 99.99,
                'quantity' => 150,
                'description' => 'Professional running shoes with superior comfort',
                'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'category_id' => $createdCategories[2]->id,
                'name' => 'The Art of Programming',
                'slug' => 'the-art-of-programming',
                'price' => 49.99,
                'quantity' => 300,
                'description' => 'A comprehensive guide to modern programming',
                'image' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'category_id' => $createdCategories[2]->id,
                'name' => 'Clean Code',
                'slug' => 'clean-code',
                'price' => 39.99,
                'quantity' => 250,
                'description' => 'A handbook of agile software craftsmanship',
                'image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'category_id' => $createdCategories[3]->id,
                'name' => 'Smart Garden Kit',
                'slug' => 'smart-garden-kit',
                'price' => 159.99,
                'quantity' => 60,
                'description' => 'Automated indoor garden system',
                'image' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'category_id' => $createdCategories[4]->id,
                'name' => 'Yoga Mat Premium',
                'slug' => 'yoga-mat-premium',
                'price' => 59.99,
                'quantity' => 180,
                'description' => 'Non-slip premium yoga mat',
                'image' => 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'category_id' => $createdCategories[0]->id,
                'name' => '4K Monitor 27"',
                'slug' => '4k-monitor-27',
                'price' => 449.99,
                'sale_price' => 399.99,
                'quantity' => 40,
                'description' => 'Ultra HD 4K monitor with HDR support',
                'image' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=600&h=400&fit=crop',
                'is_active' => true,
                'is_featured' => true,
            ],
        ];

        $createdProducts = collect($products)->map(fn ($prod) => Product::create($prod));

        Coupon::create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 50,
            'max_uses' => 100,
            'used_count' => 0,
            'starts_at' => now(),
            'expires_at' => now()->addMonths(3),
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'FLAT50',
            'type' => 'fixed',
            'value' => 50,
            'min_order_amount' => 200,
            'max_uses' => 50,
            'used_count' => 0,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => Order::generateOrderNumber(),
            'status' => 'delivered',
            'subtotal' => 1099.99,
            'discount' => 0,
            'shipping_cost' => 0,
            'tax' => 88.00,
            'total' => 1187.99,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'shipping_name' => $customer->name,
            'shipping_email' => $customer->email,
            'shipping_phone' => $customer->phone,
            'shipping_address' => $customer->address,
            'shipping_city' => $customer->city,
            'shipping_state' => $customer->state,
            'shipping_zip_code' => $customer->zip_code,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $createdProducts[0]->id,
            'quantity' => 1,
            'price' => 1099.99,
            'total' => 1099.99,
        ]);
    }
}
