<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['slug' => 'sql', 'name' => 'SQL'],
            ['slug' => 'uml', 'name' => 'UML'],
            ['slug' => 'er-modell', 'name' => 'ER-Modell'],
            ['slug' => 'programmierung', 'name' => 'Programmierung'],
            ['slug' => 'netzwerktechnik', 'name' => 'Netzwerktechnik'],
            ['slug' => 'calculation', 'name' => 'Calculation'],
            ['slug' => 'wiso', 'name' => 'WISO'],
            ['slug' => 'sonstiges', 'name' => 'Sonstiges'],
            ['slug' => 'it-sicherheit', 'name' => 'IT-Sicherheit'],
        ];

        foreach ($categories as $sortOrder => $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort_order' => ($sortOrder + 1) * 10,
                    'is_active' => true,
                ],
            );
        }
    }
}
