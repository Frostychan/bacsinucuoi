<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class MedicalServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Product::query()->get(['id', 'name']) as $product) {
            $cleanName = preg_replace('/\s+\d{2,}$/u', '', (string) $product->name);

            if ($cleanName !== null && $cleanName !== '' && $cleanName !== $product->name) {
                $product->update(['name' => $cleanName]);
            }
        }

        $categories = [
            ['name' => 'Khám nội tổng quát', 'price' => 180000, 'description' => 'Khám, đánh giá tình trạng sức khỏe tổng quát và tư vấn cận lâm sàng.'],
            ['name' => 'Khám tim mạch', 'price' => 300000, 'description' => 'Khám tim mạch, tư vấn nguy cơ và định hướng xét nghiệm chuyên sâu.'],
            ['name' => 'Khám nhi', 'price' => 220000, 'description' => 'Khám sức khỏe, tư vấn dinh dưỡng và theo dõi phát triển của trẻ.'],
            ['name' => 'Khám da liễu', 'price' => 250000, 'description' => 'Tư vấn, khám và theo dõi các bệnh lý da, tóc và móng.'],
            ['name' => 'Khám tai mũi họng', 'price' => 240000, 'description' => 'Khám tai, mũi, họng và tư vấn xử trí các triệu chứng liên quan.'],
            ['name' => 'Khám tiêu hóa', 'price' => 280000, 'description' => 'Khám và tư vấn các vấn đề dạ dày, ruột, gan và mật.'],
            ['name' => 'Khám tiết niệu', 'price' => 280000, 'description' => 'Khám, tư vấn và định hướng điều trị bệnh lý tiết niệu.'],
            ['name' => 'Khám phụ khoa', 'price' => 300000, 'description' => 'Tư vấn sức khỏe sinh sản, phụ khoa và kế hoạch khám phù hợp.'],
            ['name' => 'Khám sản khoa', 'price' => 320000, 'description' => 'Tư vấn thai kỳ, theo dõi sức khỏe mẹ và thai nhi.'],
            ['name' => 'Khám cơ xương khớp', 'price' => 260000, 'description' => 'Khám và tư vấn các vấn đề cơ, xương, khớp và vận động.'],
            ['name' => 'Khám thần kinh', 'price' => 320000, 'description' => 'Khám, đánh giá hội chứng thần kinh và tư vấn cận lâm sàng.'],
            ['name' => 'Khám mắt', 'price' => 230000, 'description' => 'Đo thị lực, khám mắt và tư vấn các vấn đề về thị giác.'],
            ['name' => 'Khám nội tiết', 'price' => 300000, 'description' => 'Tư vấn rối loạn nội tiết, tuyến giáp và chuyển hóa.'],
            ['name' => 'Khám hô hấp', 'price' => 260000, 'description' => 'Khám và tư vấn các vấn đề hô hấp, hen và lao phổi.'],
            ['name' => 'Khám dinh dưỡng', 'price' => 220000, 'description' => 'Đánh giá dinh dưỡng, cân nặng và xây dựng chế độ ăn phù hợp.'],
        ];

        $variants = [
            'Tư vấn ban đầu',
            'Tái khám',
            'Khám sức khỏe định kỳ',
            'Đánh giá sức khỏe chuyên sâu',
            'Tư vấn trực tiếp',
            'Khám theo triệu chứng',
            'Phân tích kết quả',
            'Khám tiền phẫu',
            'Theo dõi sau điều trị',
            'Tư vấn chăm sóc dài hạn',
            'Gói khám cơ bản',
            'Gói khám nâng cao',
            'Gói theo dõi 1 tháng',
            'Gói theo dõi 3 tháng',
            'Tư vấn từ xa',
            'Khám sau xét nghiệm',
            'Đánh giá rủi ro',
            'Lập kế hoạch chăm sóc',
            'Tư vấn cho gia đình',
            'Tư vấn phòng ngừa',
        ];

        foreach ($categories as $categoryIndex => $category) {
            foreach ($variants as $variantIndex => $variant) {
                $sequence = ($categoryIndex * count($variants)) + $variantIndex + 1;
                $name = sprintf('%s - %s', $category['name'], $variant);
                $price = $category['price'] + (($variantIndex % 5) * 40000);

                Product::firstOrCreate(
                    ['name' => $name],
                    [
                        'description' => $category['description'].' '.$variant.'.',
                        'price' => $price,
                        'stock' => 10 + (($sequence * 7) % 41),
                    ],
                );
            }
        }
    }
}
