{{--
  Organisation schema (JSON-LD) for NRAPA. Renders once per page; safe to
  include on every public page including welcome.blade.php, layouts/info,
  privacy, terms etc.
--}}
@php
    $orgPayload = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'National Rifle and Pistol Association of South Africa',
        'alternateName' => 'NRAPA',
        'url' => config('app.url'),
        'logo' => asset('logo-nrapa-blue-text.png'),
        'image' => asset('nrapa-icon.png'),
        'email' => 'info@nrapa.co.za',
        'telephone' => '+27 87 151 0987',
        'parentOrganization' => [
            '@type' => 'Organization',
            'name' => 'Ranyati Firearm Motivations (Pty) Ltd',
            'url' => 'https://ranyati.co.za',
        ],
        'sameAs' => [
            'https://ranyati.co.za',
            'https://motivations.ranyati.co.za',
            'https://storage.ranyati.co.za',
            'https://arms.ranyati.co.za',
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'ZA',
        ],
        'areaServed' => 'ZA',
        'identifier' => [
            ['@type' => 'PropertyValue', 'propertyID' => 'SAPS FAR', 'value' => '1300122'],
            ['@type' => 'PropertyValue', 'propertyID' => 'SAPS FAR', 'value' => '1300127'],
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($orgPayload, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
