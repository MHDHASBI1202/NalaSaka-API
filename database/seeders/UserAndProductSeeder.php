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
        // 1. Buat Akun Penjual (Seller)
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

        // 2. Buat Akun Pembeli (Customer)
        $customer = User::create([
            'name' => 'Yang Mulia Hasbi',
            'email' => 'customer@gmail.com',
            'password' => Hash::make('password123'),
            'phone_number' => '08987654321',
            'address' => 'Istana NalaSaka, Padang',
            'role' => 'customer',
        ]);

        // 3. Buat Produk Normal
        Saka::create([
            'user_id' => $seller->id,
            'name' => 'Bayam Segar',
            'category' => 'Sayur',
            'description' => 'Bayam organik langsung petik.',
            'price' => 5000,
            'stock' => 50,
            'photo_url' => 'https://api.nalasaka.com/storage/sakas/bayam.jpg',
        ]);

        // 4. Buat Produk Promo (Harga Coret)
        Saka::create([
            'user_id' => $seller->id,
            'name' => 'Apel Merah Import',
            'category' => 'Buah',
            'description' => 'Apel manis diskon user baru.',
            'price' => 45000,
            'discount_price' => 30000, // Ini yang akan jadi harga coret
            'stock' => 20,
            'photo_url' => 'https://api.nalasaka.com/storage/sakas/apel.jpg',
        ]);
        
        $this->command->info('Akun dan Produk Promo berhasil disiapkan, Yang Mulia!');
    }
}