<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()
            ->get()
            ->keyBy(fn (Category $category): string => $category->getTranslation('name', 'en'));

        $products = [
            [
                'category' => 'Electronics',
                'name' => ['en' => 'Laptop', 'ar' => 'حاسوب محمول'],
                'description' => [
                    'en' => 'A reliable laptop for everyday office work.',
                    'ar' => 'حاسوب محمول موثوق لأعمال المكتب اليومية.',
                ],
                'price' => 42500,
                'stock' => 18,
                'status' => Status::Active,
            ],
            [
                'category' => 'Electronics',
                'name' => ['en' => 'Wireless Mouse', 'ar' => 'فأرة لاسلكية'],
                'description' => [
                    'en' => 'Compact wireless mouse with an ergonomic shape.',
                    'ar' => 'فأرة لاسلكية صغيرة بتصميم مريح.',
                ],
                'price' => 850,
                'stock' => 75,
                'status' => Status::Active,
            ],
            [
                'category' => 'Office Supplies',
                'name' => ['en' => 'Notebook', 'ar' => 'دفتر ملاحظات'],
                'description' => [
                    'en' => 'A ruled notebook for notes and daily planning.',
                    'ar' => 'دفتر مسطر للملاحظات والتخطيط اليومي.',
                ],
                'price' => 120,
                'stock' => 140,
                'status' => Status::Active,
            ],
            [
                'category' => 'Office Supplies',
                'name' => ['en' => 'Office Chair', 'ar' => 'كرسي مكتب'],
                'description' => [
                    'en' => 'An adjustable chair for comfortable desk work.',
                    'ar' => 'كرسي قابل للتعديل للعمل المكتبي المريح.',
                ],
                'price' => 6800,
                'stock' => 9,
                'status' => Status::Inactive,
            ],
            [
                'category' => 'Home Appliances',
                'name' => ['en' => 'Blender', 'ar' => 'خلاط كهربائي'],
                'description' => [
                    'en' => 'A compact blender with multiple speed settings.',
                    'ar' => 'خلاط صغير مزود بإعدادات سرعة متعددة.',
                ],
                'price' => 2300,
                'stock' => 24,
                'status' => Status::Active,
            ],
            [
                'category' => 'Home Appliances',
                'name' => ['en' => 'Coffee Maker', 'ar' => 'ماكينة قهوة'],
                'description' => [
                    'en' => 'A simple coffee maker for home or office use.',
                    'ar' => 'ماكينة قهوة بسيطة للاستخدام المنزلي أو المكتبي.',
                ],
                'price' => 3100,
                'stock' => 16,
                'status' => Status::Active,
            ],
        ];

        foreach ($products as $productData) {
            $categoryName = $productData['category'];
            unset($productData['category']);

            $product = Product::query()
                ->where('name->en', $productData['name']['en'])
                ->firstOrNew();

            $product->fill([
                ...$productData,
                'category_id' => $categories[$categoryName]->id,
            ])->save();
        }
    }
}
