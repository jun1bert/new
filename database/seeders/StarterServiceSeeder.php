<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class StarterServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Classic Manicure', 'category' => 'Nail Care', 'duration' => 60, 'price' => 450, 'session_count' => 1, 'description' => 'Detailed cuticle care, shaping, and a polished finish.'],
            ['name' => 'Gel Manicure', 'category' => 'Nail Care', 'duration' => 90, 'price' => 850, 'session_count' => 1, 'description' => 'Long-lasting color with a glossy salon finish.'],
            ['name' => 'Luxury Pedicure', 'category' => 'Nail Care', 'duration' => 90, 'price' => 950, 'session_count' => 1, 'description' => 'A restorative foot ritual with exfoliation and massage.'],
            ['name' => 'Signature Facial', 'category' => 'Skin Care', 'duration' => 75, 'price' => 1500, 'session_count' => 1, 'description' => 'A tailored facial to refresh, hydrate, and restore your glow.'],
            ['name' => 'Body Scrub', 'category' => 'Body Care', 'duration' => 60, 'price' => 1200, 'session_count' => 1, 'description' => 'A gentle exfoliating treatment for smooth, renewed skin.'],
            ['name' => 'Relaxation Massage', 'category' => 'Body Care', 'duration' => 60, 'price' => 1400, 'session_count' => 1, 'description' => 'A calming full-body massage designed to ease tension.'],
        ] as $service) {
            Service::firstOrCreate(['name' => $service['name']], $service);
        }
    }
}
