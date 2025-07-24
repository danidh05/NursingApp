<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            'Beirut',
            'Mount Lebanon',
            'North Lebanon',
            'South Lebanon',
            'Bekaa',
            'Nabatieh',
            'Akkar',
            'Baalbek-Hermel'
        ];

        foreach ($areas as $areaName) {
            Area::firstOrCreate(['name' => $areaName]);
        }
    }
}
