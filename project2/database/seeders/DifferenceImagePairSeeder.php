<?php

namespace Database\Seeders;

use App\Models\DifferenceImagePair;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DifferenceImagePairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DifferenceImagePair::create([
            'image_1' => 'differences/img1_1.jpg',
            'image_2' => 'differences/img1_2.jpg',
        ]);

        DifferenceImagePair::create([
            'image_1' => 'differences/img2_1.jpg',
            'image_2' => 'differences/img2_2.jpg',
        ]);
    }
}
