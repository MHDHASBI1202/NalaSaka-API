<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Saka;
use Illuminate\Support\Facades\Hash;

class UserAndProductSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::create([
            'name' => 'Hasbi Si Petani',
            'email' => 'seller@gmail.com',
            'password' => Hash::make('password123'),
            'phone_number' => '08123456789',
            'address' => 'Pasar Baru, Padang',
            'role' => 'seller',
            'store_name' => 'Toko Tani Berkah',
            'verification_status' => 'verified',
        ]);

        $customer = User::create([
            'name' => 'Yang Mulia Hasbi',
            'email' => 'customer@gmail.com',
            'password' => Hash::make('password123'),
            'phone_number' => '08987654321',
            'address' => 'Istana NalaSaka, Padang',
            'role' => 'customer',
        ]);

        Saka::create([
            'user_id' => $seller->id,
            'name' => 'Bayam Segar',
            'category' => 'Sayur',
            'description' => 'Bayam organik langsung petik.',
            'price' => 5000,
            'stock' => 50,
            'photo_url' => 'Sbc3a4643d5d1476ea30ab9f328da620c0.jpg',
        ]);

        Saka::create([
            'user_id' => $seller->id,
            'name' => 'Apel Merah Import',
            'category' => 'Buah',
            'description' => 'Apel manis diskon user baru.',
            'price' => 45000,
            'discount_price' => 30000,
            'stock' => 20,
            'photo_url' => '22268c378ba3f8812bb9280e27ff17e0.jpg_720x720q80.jpg',
        ]);
        
        $this->command->info('Akun dan Produk Promo berhasil disiapkan, Yang Mulia!');
    }
}