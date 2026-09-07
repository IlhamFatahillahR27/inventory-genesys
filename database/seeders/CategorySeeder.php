<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->items() as $item) {
            Category::updateOrCreate([
                'code' => $item['code']
            ], [
                'code' => $item['code'],
                'name' => $item['name']
            ]);
        }
    }

    private function items(): array
    {
        return [
            [
                'code' => 'electronic',
                'name' => 'Electronic'
            ],
            [
                'code' => 'furniture',
                'name' => 'Furniture'
            ]
        ];
    }
}
