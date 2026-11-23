<?php

namespace App\Support;

class MedicationCatalog
{
    public static function imagePath(string $medicineName): string
    {
        $slug = mb_strtolower(trim($medicineName));
        $slug = str_replace(
            [' ', '(', ')', '/'],
            ['-', '', '', '-'],
            $slug,
        );
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?: 'placeholder';
        $imagePath = public_path('images/medicines/'.$slug.'.svg');

        return file_exists($imagePath)
            ? 'images/medicines/'.$slug.'.svg'
            : 'images/medicines/placeholder.svg';
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        $fallback = [
            'Paracetamol',
            'Ibuprofen',
            'Amoxicillin',
            'Azithromycin',
            'Cefuroxime',
            'Metronidazole',
            'Omeprazole',
            'Esomeprazole',
            'Loratadine',
            'Cetirizine',
            'Amlodipine',
            'Losartan',
            'Metformin',
            'Atorvastatin',
            'Aspirin',
            'Vitamin C',
            'Calcium carbonate',
            'Oresol (ORS)',
            'Salbutamol',
            'Guaifenesin',
        ];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('medicines')) {
                $databaseNames = \App\Models\Medicine::query()
                    ->orderBy('name')
                    ->pluck('name')
                    ->filter()
                    ->unique(fn (string $name): string => mb_strtolower($name))
                    ->values()
                    ->all();

                if ($databaseNames !== []) {
                    return $databaseNames;
                }
            }
        } catch (\Throwable) {
            // Keep the built-in catalog available if the database is temporarily unavailable.
        }

        return $fallback;
    }

    /**
     * @return array<int, string>
     */
    public static function prescribedNames(?string $prescription): array
    {
        if ($prescription === null || trim($prescription) === '') {
            return [];
        }

        $catalog = array_fill_keys(array_map(mb_strtolower(...), self::all()), true);
        $prescribedNames = [];

        foreach (preg_split('/\R/u', $prescription) ?: [] as $line) {
            $name = trim($line);
            $normalizedName = mb_strtolower($name);

            if ($name !== '' && isset($catalog[$normalizedName]) && ! in_array($name, $prescribedNames, true)) {
                $prescribedNames[] = $name;
            }
        }

        return $prescribedNames;
    }

    public static function details(?string $prescription): string
    {
        if ($prescription === null || trim($prescription) === '') {
            return '';
        }

        $catalog = array_fill_keys(array_map(mb_strtolower(...), self::all()), true);
        $details = array_filter(
            preg_split('/\R/u', $prescription) ?: [],
            fn (string $line): bool => ! isset($catalog[mb_strtolower(trim($line))]),
        );

        return trim(implode("\n", $details));
    }
}
