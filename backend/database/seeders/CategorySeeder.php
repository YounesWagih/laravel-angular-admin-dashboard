<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => ['en' => 'Electronics', 'ar' => 'إلكترونيات'],
                'description' => [
                    'en' => 'Computers, accessories, and electronic devices.',
                    'ar' => 'أجهزة الكمبيوتر وملحقاتها والأجهزة الإلكترونية.',
                ],
            ],
            [
                'name' => ['en' => 'Office Supplies', 'ar' => 'مستلزمات مكتبية'],
                'description' => [
                    'en' => 'Everyday supplies and furniture for the office.',
                    'ar' => 'المستلزمات والأثاث اليومي للمكتب.',
                ],
            ],
            [
                'name' => ['en' => 'Home Appliances', 'ar' => 'أجهزة منزلية'],
                'description' => [
                    'en' => 'Practical appliances for everyday home use.',
                    'ar' => 'أجهزة عملية للاستخدام المنزلي اليومي.',
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::query()
                ->where('name->en', $categoryData['name']['en'])
                ->firstOrNew();

            $category->fill($categoryData)->save();
        }
    }
}
