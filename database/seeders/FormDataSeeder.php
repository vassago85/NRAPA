<?php

namespace Database\Seeders;

use App\Models\CertificateType;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Province;
use Illuminate\Database\Seeder;

class FormDataSeeder extends Seeder
{
    /**
     * Seed all form dropdown data.
     */
    public function run(): void
    {
        $this->seedCountries();
        $this->seedProvinces();
        $this->seedDocumentTypes();
        $this->seedCertificateTypes();
        $this->seedFirearmTypes();
        // Calibre seeding removed - using FirearmCalibre system instead
        // Import calibre data via: php artisan nrapa:import-firearm-reference
    }

    /**
     * Seed countries.
     */
    protected function seedCountries(): void
    {
        $countries = [
            ['code' => 'ZA', 'name' => 'South Africa', 'sort_order' => 0],
            ['code' => 'BW', 'name' => 'Botswana', 'sort_order' => 1],
            ['code' => 'LS', 'name' => 'Lesotho', 'sort_order' => 2],
            ['code' => 'MZ', 'name' => 'Mozambique', 'sort_order' => 3],
            ['code' => 'NA', 'name' => 'Namibia', 'sort_order' => 4],
            ['code' => 'SZ', 'name' => 'Eswatini', 'sort_order' => 5],
            ['code' => 'ZW', 'name' => 'Zimbabwe', 'sort_order' => 6],
            ['code' => 'ZM', 'name' => 'Zambia', 'sort_order' => 7],
            ['code' => 'TZ', 'name' => 'Tanzania', 'sort_order' => 8],
            ['code' => 'KE', 'name' => 'Kenya', 'sort_order' => 9],
            ['code' => 'UG', 'name' => 'Uganda', 'sort_order' => 10],
            ['code' => 'XX', 'name' => 'Other', 'sort_order' => 999],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                [
                    'name' => $country['name'],
                    'is_active' => true,
                    'sort_order' => $country['sort_order'],
                ]
            );
        }
    }

    /**
     * Seed South African provinces.
     */
    protected function seedProvinces(): void
    {
        $provinces = [
            ['code' => 'EC', 'name' => 'Eastern Cape'],
            ['code' => 'FS', 'name' => 'Free State'],
            ['code' => 'GP', 'name' => 'Gauteng'],
            ['code' => 'KZN', 'name' => 'KwaZulu-Natal'],
            ['code' => 'LP', 'name' => 'Limpopo'],
            ['code' => 'MP', 'name' => 'Mpumalanga'],
            ['code' => 'NC', 'name' => 'Northern Cape'],
            ['code' => 'NW', 'name' => 'North West'],
            ['code' => 'WC', 'name' => 'Western Cape'],
        ];

        foreach ($provinces as $province) {
            Province::updateOrCreate(
                ['code' => $province['code']],
                [
                    'name' => $province['name'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed document types.
     */
    protected function seedDocumentTypes(): void
    {
        $documentTypes = [
            [
                'slug' => 'id-document',
                'name' => 'ID Document',
                'description' => 'South African ID Document or Passport',
                'expiry_months' => null,
                'archive_months' => 24,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'proof-of-address',
                'name' => 'Proof of Address',
                'description' => 'Utility bill, bank statement, or official letter showing residential address',
                'expiry_months' => 3,
                'archive_months' => 12,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'dedicated-status-proof',
                'name' => 'Dedicated Status Proof',
                'description' => 'Document proving dedicated hunter or sport shooter status',
                'expiry_months' => null,
                'archive_months' => 24,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'firearm-license',
                'name' => 'Firearm License',
                'description' => 'Valid firearm license document',
                'expiry_months' => null,
                'archive_months' => 24,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'slug' => 'competency-certificate',
                'name' => 'Competency Certificate',
                'description' => 'Firearm competency certificate',
                'expiry_months' => null,
                'archive_months' => 24,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'slug' => 'medical-certificate',
                'name' => 'Medical Certificate',
                'description' => 'Medical certificate for firearm license application',
                'expiry_months' => 6,
                'archive_months' => 12,
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'slug' => 'membership-card',
                'name' => 'Membership Card',
                'description' => 'NRAPA membership card or certificate',
                'expiry_months' => null,
                'archive_months' => 12,
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'slug' => 'other',
                'name' => 'Other Document',
                'description' => 'Other supporting document',
                'expiry_months' => null,
                'archive_months' => 12,
                'is_active' => true,
                'sort_order' => 99,
            ],
        ];

        foreach ($documentTypes as $type) {
            DocumentType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }

    /**
     * Seed certificate types.
     */
    protected function seedCertificateTypes(): void
    {
        $certificateTypes = [
            [
                'slug' => 'membership-certificate',
                'name' => 'Membership Certificate',
                'description' => 'Certificate confirming active NRAPA membership',
                'template' => 'certificates.membership',
                'validity_months' => 12,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'dedicated-status-certificate',
                'name' => 'Dedicated Status Certificate',
                'description' => 'Certificate confirming dedicated hunter/sport shooter status',
                'template' => 'certificates.dedicated',
                'validity_months' => 12,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'endorsement-letter',
                'name' => 'Endorsement Letter',
                'description' => 'Letter of endorsement for firearm applications',
                'template' => 'certificates.endorsement',
                'validity_months' => 6,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'confirmation-letter',
                'name' => 'Confirmation Letter',
                'description' => 'Letter confirming membership status',
                'template' => 'certificates.confirmation',
                'validity_months' => 3,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($certificateTypes as $type) {
            CertificateType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }

    /**
     * Seed firearm types.
     */
    protected function seedFirearmTypes(): void
    {
        // Call the existing FirearmTypeSeeder
        $this->call(FirearmTypeSeeder::class);
    }
}
