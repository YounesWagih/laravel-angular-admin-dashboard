<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use RuntimeException;

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
                'status' => Status::Active,
                'images' => [
                    'laptop/1.jpeg',
                    'laptop/2.jpeg',
                    'laptop/3.jpeg',
                    'laptop/4.jpg',
                ],
            ],
            [
                'category' => 'Electronics',
                'name' => ['en' => 'Wireless Mouse', 'ar' => 'فأرة لاسلكية'],
                'description' => [
                    'en' => 'Compact wireless mouse with an ergonomic shape.',
                    'ar' => 'فأرة لاسلكية صغيرة بتصميم مريح.',
                ],
                'price' => 850,
                'status' => Status::Active,
                'images' => [
                    'wireless-mouse/1.jpg',
                    'wireless-mouse/2.jpeg',
                    'wireless-mouse/3.jpg',
                    'wireless-mouse/4.jpg',
                ],
            ],
            [
                'category' => 'Office Supplies',
                'name' => ['en' => 'Notebook', 'ar' => 'دفتر ملاحظات'],
                'description' => [
                    'en' => 'A ruled notebook for notes and daily planning.',
                    'ar' => 'دفتر مسطر للملاحظات والتخطيط اليومي.',
                ],
                'price' => 120,
                'status' => Status::Active,
                'images' => [
                    'notebook/1.jpeg',
                    'notebook/2.jpg',
                    'notebook/3.jpeg',
                    'notebook/4.jpeg',
                ],
            ],
            [
                'category' => 'Office Supplies',
                'name' => ['en' => 'Office Chair', 'ar' => 'كرسي مكتب'],
                'description' => [
                    'en' => 'An adjustable chair for comfortable desk work.',
                    'ar' => 'كرسي قابل للتعديل للعمل المكتبي المريح.',
                ],
                'price' => 6800,
                'status' => Status::Inactive,
                'images' => [
                    'office-chair/1.jpeg',
                    'office-chair/2.jpeg',
                ],
            ],
            [
                'category' => 'Home Appliances',
                'name' => ['en' => 'Blender', 'ar' => 'خلاط كهربائي'],
                'description' => [
                    'en' => 'A compact blender with multiple speed settings.',
                    'ar' => 'خلاط صغير مزود بإعدادات سرعة متعددة.',
                ],
                'price' => 2300,
                'status' => Status::Active,
                'images' => [
                    'blender/1.jpeg',
                    'blender/2.jpeg',
                ],
            ],
            [
                'category' => 'Home Appliances',
                'name' => ['en' => 'Coffee Maker', 'ar' => 'ماكينة قهوة'],
                'description' => [
                    'en' => 'A simple coffee maker for home or office use.',
                    'ar' => 'ماكينة قهوة بسيطة للاستخدام المنزلي أو المكتبي.',
                ],
                'price' => 3100,
                'status' => Status::Active,
                'images' => [
                    'coffee-maker/1.jpeg',
                    'coffee-maker/2.jpeg',
                ],
            ],
            [
                'category' => 'Home Appliances',
                'name' => ['en' => 'Frying Pan', 'ar' => 'مقلاة'],
                'description' => [
                    'en' => 'A durable frying pan for everyday cooking.',
                    'ar' => 'مقلاة متينة للطهي اليومي.',
                ],
                'price' => 950,
                'status' => Status::Active,
                'images' => ['frying-pan/pan.jpg'],
            ],
            [
                'category' => 'Electronics',
                'name' => ['en' => 'Desktop Computer', 'ar' => 'حاسوب مكتبي'],
                'description' => [
                    'en' => 'A desktop computer suitable for work and study.',
                    'ar' => 'حاسوب مكتبي مناسب للعمل والدراسة.',
                ],
                'price' => 36500,
                'status' => Status::Active,
                'images' => ['desktop-computer/pc.jpeg'],
            ],
            [
                'category' => 'Electronics',
                'name' => ['en' => 'Mobile Phone', 'ar' => 'هاتف محمول'],
                'description' => [
                    'en' => 'A modern mobile phone for communication and daily use.',
                    'ar' => 'هاتف محمول حديث للتواصل والاستخدام اليومي.',
                ],
                'price' => 18500,
                'status' => Status::Active,
                'images' => ['mobile-phone/mobile.jpg'],
            ],
            [
                'category' => 'Office Supplies',
                'name' => ['en' => 'Laptop Bag', 'ar' => 'حقيبة حاسوب محمول'],
                'description' => [
                    'en' => 'A practical padded bag for carrying a laptop and accessories.',
                    'ar' => 'حقيبة عملية مبطنة لحمل الحاسوب المحمول وملحقاته.',
                ],
                'price' => 1250,
                'status' => Status::Active,
                'images' => ['bag/bag.jpeg'],
            ],
        ];

        foreach ($products as $productData) {
            $categoryName = $productData['category'];
            $imagePaths = $productData['images'];
            unset($productData['category'], $productData['images']);

            $product = Product::query()
                ->where('name->en', $productData['name']['en'])
                ->firstOrNew();

            $product->fill([
                ...$productData,
                'category_id' => $categories[$categoryName]->id,
            ])->save();

            $this->syncImages($product, $imagePaths);
        }
    }

    private function syncImages(Product $product, array $relativeImagePaths): void
    {
        if (count($relativeImagePaths) > Product::MAX_IMAGES) {
            throw new RuntimeException("Product {$product->id} has too many seed images.");
        }

        $imagePaths = array_map(
            static fn (string $relativeImagePath): string => database_path(
                "seeders/images/products/{$relativeImagePath}",
            ),
            $relativeImagePaths,
        );

        foreach ($imagePaths as $imagePath) {
            if (! is_file($imagePath)) {
                throw new RuntimeException("Missing product seed image: {$imagePath}");
            }
        }

        $product->clearMediaCollection(Product::IMAGE_COLLECTION);

        foreach ($imagePaths as $imagePath) {
            $product
                ->addMedia($imagePath)
                ->preservingOriginal()
                ->toMediaCollection(Product::IMAGE_COLLECTION);
        }
    }
}
