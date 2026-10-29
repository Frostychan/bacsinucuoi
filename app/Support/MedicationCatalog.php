<?php

namespace App\Support;

class MedicationCatalog
{
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
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
