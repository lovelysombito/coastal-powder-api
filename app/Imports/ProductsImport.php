<?php

namespace App\Imports;

use App\Models\Products;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Ramsey\Uuid\Uuid;

class ProductsImport implements ToModel, WithHeadingRow, WithBatchInserts, WithValidation, WithUpserts
{
    public function batchSize(): int {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function model(array $row) {

        do {
            $newProductId = Uuid::uuid4()->toString();
        } while (Products::where('product_id', $newProductId)->exists());

        $newProduct = new Products([
            'product_id' => (isset($row['product_id']) && $row['product_id']) ? $row['product_id'] : $newProductId,
            'product_name' => $row['name'],
            'sku' => $row['sku'] ?? '',
            'description' => $row['description'] ?? '',
            'material' => $row['material'] ?? null,
            'price' => $row['price'] ?? 0,
            'brand' => $row['brand'] ?? '',
        ]);

        $newProduct->save();
    }

    public function uniqueBy() {
        return 'product_id';
    }

    public function rules(): array {
        return [
            'name' => ['required', 'string'],
        ];
    }

    public function customValidationMessages() {
        return [
            '*.name' => 'Product name is required',
        ];
    }
}
