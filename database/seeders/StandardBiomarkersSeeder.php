<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StandardBiomarkersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('standard_biomarkers')->insert([
            [
                'code' => 'GLU',
                'name' => 'Glucose',
                'default_unit' => 'mmol/L',
                'aliases' => json_encode(['Blood glucose', 'Serum glucose']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'HBA1C',
                'name' => 'Hemoglobin A1c',
                'default_unit' => '%',
                'aliases' => json_encode(['HbA1c', 'Glycated hemoglobin']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CHOL',
                'name' => 'Total Cholesterol',
                'default_unit' => 'mmol/L',
                'aliases' => json_encode(['Cholesterol']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'LDL',
                'name' => 'LDL Cholesterol',
                'default_unit' => 'mmol/L',
                'aliases' => json_encode(['Low-density lipoprotein']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'HDL',
                'name' => 'HDL Cholesterol',
                'default_unit' => 'mmol/L',
                'aliases' => json_encode(['High-density lipoprotein']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'TG',
                'name' => 'Triglycerides',
                'default_unit' => 'mmol/L',
                'aliases' => json_encode(['Triglyceride']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CRP',
                'name' => 'C-Reactive Protein',
                'default_unit' => 'mg/L',
                'aliases' => json_encode(['C reactive protein', 'hs-CRP']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ALT',
                'name' => 'Alanine Aminotransferase',
                'default_unit' => 'U/L',
                'aliases' => json_encode(['GPT', 'SGPT']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'AST',
                'name' => 'Aspartate Aminotransferase',
                'default_unit' => 'U/L',
                'aliases' => json_encode(['GOT', 'SGOT']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ALP',
                'name' => 'Alkaline Phosphatase',
                'default_unit' => 'U/L',
                'aliases' => json_encode(['ALP']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'BILI',
                'name' => 'Total Bilirubin',
                'default_unit' => 'µmol/L',
                'aliases' => json_encode(['Bilirubin']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'CREA',
                'name' => 'Creatinine',
                'default_unit' => 'µmol/L',
                'aliases' => json_encode(['Serum creatinine']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'UREA',
                'name' => 'Urea',
                'default_unit' => 'mmol/L',
                'aliases' => json_encode(['Blood urea nitrogen', 'BUN']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EGFR',
                'name' => 'Estimated Glomerular Filtration Rate',
                'default_unit' => 'mL/min/1.73m²',
                'aliases' => json_encode(['eGFR']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'WBC',
                'name' => 'White Blood Cell Count',
                'default_unit' => '10^9/L',
                'aliases' => json_encode(['Leukocytes']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'RBC',
                'name' => 'Red Blood Cell Count',
                'default_unit' => '10^12/L',
                'aliases' => json_encode(['Erythrocytes']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'HGB',
                'name' => 'Hemoglobin',
                'default_unit' => 'g/dL',
                'aliases' => json_encode(['Hb']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PLT',
                'name' => 'Platelet Count',
                'default_unit' => '10^9/L',
                'aliases' => json_encode(['Platelets']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'TSH',
                'name' => 'Thyroid Stimulating Hormone',
                'default_unit' => 'mIU/L',
                'aliases' => json_encode(['Thyrotropin']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FT4',
                'name' => 'Free Thyroxine',
                'default_unit' => 'pmol/L',
                'aliases' => json_encode(['Free T4']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
