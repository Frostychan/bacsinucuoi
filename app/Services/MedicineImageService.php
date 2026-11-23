<?php

namespace App\Services;

use App\Models\Medicine;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class MedicineImageService
{
    public function syncMissingImages(?int $limit = 100): int
    {
        if (! Schema::hasColumn('medicines', 'image_path')) {
            return 0;
        }

        $medicines = Medicine::query()
            ->whereNull('image_path')
            ->take($limit)
            ->get();

        $imported = 0;

        foreach ($medicines as $medicine) {
            if ($this->sync($medicine)) {
                $imported++;
            }
        }

        return $imported;
    }

    public function sync(Medicine $medicine): bool
    {
        if (! Schema::hasColumn('medicines', 'image_path')) {
            return false;
        }

        if ($medicine->image_path !== null) {
            return false;
        }

        $imageUrl = $this->findImageUrl($medicine->name);
        if ($imageUrl === null) {
            return false;
        }

        try {
            $response = Http::accept('image/*')
                ->connectTimeout(5)
                ->timeout(15)
                ->get($imageUrl);
        } catch (ConnectionException $exception) {
            Log::warning('Không thể tải ảnh thuốc từ RxImage.', [
                'medicine' => $medicine->name,
                'exception' => $exception::class,
            ]);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $contentType = (string) $response->header('Content-Type');
        $extension = match (true) {
            str_starts_with($contentType, 'image/jpeg') => 'jpg',
            str_starts_with($contentType, 'image/png') => 'png',
            str_starts_with($contentType, 'image/webp') => 'webp',
            default => null,
        };

        if ($extension === null || $response->body() === '') {
            return false;
        }

        $fileName = 'medicines/rximage-'.sha1(mb_strtolower(trim($medicine->name))).'.'.$extension;
        Storage::disk('public')->put($fileName, $response->body());
        $medicine->update(['image_path' => $fileName]);

        return true;
    }

    private function findImageUrl(string $medicineName): ?string
    {
        $query = trim($medicineName);
        if ($query === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->get('https://rximage.nlm.nih.gov/api/rximage/1/rxnav', [
                    'name' => $query,
                    'limit' => 1,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        $url = $this->extractImageUrl($payload);
        if ($url !== null) {
            return $url;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->get('https://rximage.nlm.nih.gov/api/rximage/1/rxbase', [
                    'name' => $query,
                    'limit' => 1,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->extractImageUrl($response->json());
    }

    private function extractImageUrl(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        $candidates = [
            $payload['imageUrl'] ?? null,
            $payload['image_url'] ?? null,
            $payload['url'] ?? null,
            $payload['nlmRxImages'][0]['imageUrl'] ?? null,
            $payload['nlmRxImages'][0]['imageUrl'] ?? null,
            $payload['images'][0]['imageUrl'] ?? null,
            $payload['images'][0]['url'] ?? null,
            $payload['reply']['imageUrl'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_URL)) {
                return $candidate;
            }
        }

        return null;
    }
}
