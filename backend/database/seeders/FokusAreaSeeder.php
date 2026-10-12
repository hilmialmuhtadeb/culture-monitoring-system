<?php

namespace Database\Seeders;

use App\Models\FokusArea;
use Illuminate\Database\Seeder;

class FokusAreaSeeder extends Seeder
{
    public function run(): void
    {
        $fokusAreas = [
            'Safety & Wellbeing First',
            'Quality Excellence',
            'Risk Aware & Compliant',
            'ESG & Sustainability',
            'Customer Centricity',
            'Innovation & Digital Mindset',
        ];

        foreach ($fokusAreas as $nama) {
            FokusArea::firstOrCreate([
                'nama' => $nama,
            ]);
        }
    }
}