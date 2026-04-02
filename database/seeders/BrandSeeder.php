<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            // Core PC Components
            'AMD', 'Intel', 'Nvidia', 'ASUS', 'Gigabyte', 'MSI', 
            'ASRock', 'EVGA', 'Sapphire', 'Zotac', 'PowerColor', 'Colorful',

            // Memory & Storage
            'Samsung', 'Western Digital', 'Seagate', 'Kingston', 'Crucial', 
            'Corsair', 'G.Skill', 'ADATA', 'XPG', 'Sabrent', 'Transcend', 
            'PNY', 'SanDisk',

            // Peripherals
            'Logitech', 'Razer', 'SteelSeries', 'HyperX', 'Keychron', 
            'Ducky', 'Glorious', 'BenQ', 'ZOWIE', 'Audio-Technica', 'Sennheiser',

            // Cases, Cooling & Power Supplies
            'Noctua', 'Cooler Master', 'NZXT', 'Fractal Design', 'be quiet!', 
            'Lian Li', 'DeepCool', 'Arctic', 'SeaSonic', 'Thermaltake',

            // Monitors & Display
            'Dell', 'Alienware', 'LG', 'AOC', 'ViewSonic', 'Acer', 'Predator', 'Philips',

            // Networking & Mobile
            'TP-Link', 'Ubiquiti', 'Netgear', 'Cisco', 'Apple', 'Google', 'Xiaomi'
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->updateOrInsert(
                ['name' => $brand],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}