<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create Admin User
        User::create([
            'name' => 'Admin',
            'email' => 'admin@restaurant.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '0612345678',
        ]);

        // Create Test Customer
        User::create([
            'name' => 'Test Klant',
            'email' => 'klant@test.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'phone' => '0687654321',
            'address' => 'Teststraat 123, 1234 AB Amsterdam',
        ]);

        // Create Categories
        $categories = [
            ['name' => 'Voorgerechten', 'description' => 'Heerlijke starters', 'sort_order' => 1],
            ['name' => 'Hoofdgerechten', 'description' => 'Onze specialiteiten', 'sort_order' => 2],
            ['name' => 'Pizza', 'description' => 'Verse pizza uit de oven', 'sort_order' => 3],
            ['name' => 'Pasta', 'description' => 'Authentieke Italiaanse pasta', 'sort_order' => 4],
            ['name' => 'Salades', 'description' => 'Verse en gezonde salades', 'sort_order' => 5],
            ['name' => 'Desserts', 'description' => 'Zoete afsluiting', 'sort_order' => 6],
            ['name' => 'Dranken', 'description' => 'Frisdranken en meer', 'sort_order' => 7],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'name' => $category['name'],
                'description' => $category['description'],
                'sort_order' => $category['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Products
        $products = [
            // Voorgerechten (category_id: 1)
            ['name' => 'Bruschetta', 'price' => 6.50, 'category_id' => 1, 'description' => 'Geroosterd brood met tomaat en basilicum'],
            ['name' => 'Carpaccio', 'price' => 12.50, 'category_id' => 1, 'description' => 'Dungesneden rundvlees met rucola en parmezaan'],
            ['name' => 'Soep van de Dag', 'price' => 5.50, 'category_id' => 1, 'description' => 'Vraag naar onze dagverse soep'],
            
            // Hoofdgerechten (category_id: 2)
            ['name' => 'Biefstuk', 'price' => 22.50, 'category_id' => 2, 'description' => 'Malse biefstuk met friet en salade'],
            ['name' => 'Zalm Filet', 'price' => 19.50, 'category_id' => 2, 'description' => 'Gebakken zalm met groenten'],
            ['name' => 'Kip Parmezaan', 'price' => 17.50, 'category_id' => 2, 'description' => 'Gepaneerde kip met tomatensaus en kaas'],
            
            // Pizza (category_id: 3)
            ['name' => 'Margherita', 'price' => 9.50, 'category_id' => 3, 'description' => 'Tomaat, mozzarella, basilicum'],
            ['name' => 'Pepperoni', 'price' => 12.50, 'category_id' => 3, 'description' => 'Tomaat, mozzarella, pepperoni'],
            ['name' => 'Quattro Formaggi', 'price' => 13.50, 'category_id' => 3, 'description' => 'Vier soorten kaas'],
            ['name' => 'Hawaii', 'price' => 11.50, 'category_id' => 3, 'description' => 'Tomaat, mozzarella, ham, ananas'],
            
            // Pasta (category_id: 4)
            ['name' => 'Spaghetti Carbonara', 'price' => 12.50, 'category_id' => 4, 'description' => 'Romige saus met spek en ei'],
            ['name' => 'Penne Arrabiata', 'price' => 10.50, 'category_id' => 4, 'description' => 'Pittige tomatensaus'],
            ['name' => 'Lasagne', 'price' => 13.50, 'category_id' => 4, 'description' => 'Huisgemaakte lasagne bolognese'],
            
            // Salades (category_id: 5)
            ['name' => 'Caesar Salade', 'price' => 9.50, 'category_id' => 5, 'description' => 'Romaine sla, croutons, parmezaan, Caesar dressing'],
            ['name' => 'Griekse Salade', 'price' => 8.50, 'category_id' => 5, 'description' => 'Tomaat, komkommer, feta, olijven'],
            
            // Desserts (category_id: 6)
            ['name' => 'Tiramisu', 'price' => 6.50, 'category_id' => 6, 'description' => 'Huisgemaakte Italiaanse klassieker'],
            ['name' => 'Panna Cotta', 'price' => 5.50, 'category_id' => 6, 'description' => 'Romige pudding met bessensaus'],
            ['name' => 'IJs (3 bollen)', 'price' => 4.50, 'category_id' => 6, 'description' => 'Keuze uit verschillende smaken'],
            
            // Dranken (category_id: 7)
            ['name' => 'Cola', 'price' => 2.50, 'category_id' => 7, 'description' => 'Coca Cola regular'],
            ['name' => 'Fanta', 'price' => 2.50, 'category_id' => 7, 'description' => 'Fanta Orange'],
            ['name' => 'Sprite', 'price' => 2.50, 'category_id' => 7, 'description' => 'Sprite regular'],
            ['name' => 'Water', 'price' => 2.00, 'category_id' => 7, 'description' => 'Spa blauw of rood'],
            ['name' => 'Bier', 'price' => 3.50, 'category_id' => 7, 'description' => 'Heineken van de tap'],
            ['name' => 'Wijn', 'price' => 4.50, 'category_id' => 7, 'description' => 'Rood, wit of rosé'],
        ];

        foreach ($products as $product) {
            DB::table('products')->insert([
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'category_id' => $product['category_id'],
                'is_available' => true,
                'is_popular' => rand(0, 1),
                'preparation_time' => rand(10, 30),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Settings
        $settings = [
            ['key' => 'restaurant_name', 'value' => 'Bella Italia', 'type' => 'text', 'group' => 'general'],
            ['key' => 'restaurant_address', 'value' => 'Damrak 50, 1012 LL Amsterdam', 'type' => 'text', 'group' => 'general'],
            ['key' => 'restaurant_phone', 'value' => '020-1234567', 'type' => 'text', 'group' => 'general'],
            ['key' => 'restaurant_email', 'value' => 'info@bellaitalia.nl', 'type' => 'text', 'group' => 'general'],
            ['key' => 'opening_time', 'value' => '11:00', 'type' => 'text', 'group' => 'hours'],
            ['key' => 'closing_time', 'value' => '22:00', 'type' => 'text', 'group' => 'hours'],
            ['key' => 'tax_percentage', 'value' => '9', 'type' => 'number', 'group' => 'finance'],
            ['key' => 'currency', 'value' => 'EUR', 'type' => 'text', 'group' => 'finance'],
            ['key' => 'minimum_order', 'value' => '10', 'type' => 'number', 'group' => 'orders'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'type' => $setting['type'],
                'group' => $setting['group'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Time Slots
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $timeSlots = [
            ['start' => '11:00', 'end' => '12:00'],
            ['start' => '12:00', 'end' => '13:00'],
            ['start' => '13:00', 'end' => '14:00'],
            ['start' => '17:00', 'end' => '18:00'],
            ['start' => '18:00', 'end' => '19:00'],
            ['start' => '19:00', 'end' => '20:00'],
            ['start' => '20:00', 'end' => '21:00'],
            ['start' => '21:00', 'end' => '22:00'],
        ];

        foreach ($days as $day) {
            foreach ($timeSlots as $slot) {
                DB::table('time_slots')->insert([
                    'day' => $day,
                    'start_time' => $slot['start'],
                    'end_time' => $slot['end'],
                    'max_orders' => 10,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        echo "Seeding completed successfully!\n";
        echo "Admin login: admin@restaurant.com / password\n";
        echo "Customer login: klant@test.com / password\n";
    }
}