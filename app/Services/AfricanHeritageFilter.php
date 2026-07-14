<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AfricanHeritageFilter
{
    /**
     * High-confidence African terms (countries, cultures, dynasties, kingdoms)
     */
    protected array $highConfidenceTerms = [
        // Countries
        'Nigeria', 'Ghana', 'Benin', 'Togo', 'Mali', 'Burkina Faso',
        'Senegal', 'Cameroon', 'Ethiopia', 'Kenya', 'Uganda', 'Tanzania',
        'Zimbabwe', 'South Africa', 'Namibia', 'Botswana', 'Mozambique',
        'Angola', 'Sudan', 'Egypt', 'Morocco', 'Algeria', 'Tunisia',
        'Libya', 'Congo', 'Ivory Coast', 'Côte d\'Ivoire', 'Guinea',
        'Sierra Leone', 'Liberia', 'Niger', 'Chad', 'Central African Republic',
        'Gabon', 'Equatorial Guinea', 'Rwanda', 'Burundi', 'Malawi',
        'Zambia', 'Lesotho', 'Eswatini', 'Madagascar', 'South Sudan',
        'Eritrea', 'Djibouti', 'Somalia', 'Western Sahara',

        // Empires & Kingdoms
        'Kingdom of Benin', 'Benin Kingdom', 'Oyo', 'Ife', 'Ile-Ife',
        'Asante', 'Ashanti', 'Dahomey', 'Mali Empire', 'Songhai Empire',
        'Songhai', 'Kanem-Bornu', 'Kanem', 'Bornu', 'Kush', 'Nubia',
        'Kerma', 'Meroe', 'Axum', 'Aksum', 'Great Zimbabwe', 'Mutapa',
        'Mapungubwe', 'Kongo Kingdom', 'Luba Kingdom', 'Lunda Kingdom',
        'Buganda', 'Ghana Empire',

        // Ethnic Groups
        'Yoruba', 'Igbo', 'Hausa', 'Fulani', 'Fulbe', 'Fula',
        'Akan', 'Ashanti', 'Ewe', 'Ga', 'Fon', 'Bambara', 'Mandinka',
        'Mande', 'Wolof', 'Serer', 'Dogon', 'Tuareg', 'Berber',
        'Amazigh', 'Kanuri', 'Tiv', 'Nupe', 'Edo', 'Urhobo', 'Ijaw',
        'Ibibio', 'Efik', 'Idoma', 'Kongo', 'Luba', 'Lunda', 'Chokwe',
        'Zulu', 'Xhosa', 'Sotho', 'Tswana', 'Ndebele', 'Shona', 'Venda',
        'Maasai', 'Kikuyu', 'Luo', 'Meru', 'Amhara', 'Oromo', 'Tigray',
        'Somali', 'Afar', 'Beja', 'Nubian', 'Swahili',

        // Cultures
        'Nok', 'Benin', 'Ife', 'Kerma', 'Meroe',
    ];

    /**
     * General African terms (lower confidence)
     */
    protected array $generalTerms = [
        'Africa', 'African', 'Sub-Saharan Africa', 'North Africa',
        'West Africa', 'East Africa', 'Central Africa', 'Southern Africa',
        'Horn of Africa', 'Sahel', 'Maghreb', 'Nile Valley', 'Nile',
        'Sahara', 'Timbuktu', 'Djenné', 'Lalibela', 'Olduvai',

        // Art & Materials
        'African mask', 'Mask', 'Power figure', 'Nkisi',
        'Fetish figure', 'Terracotta', 'Bronze', 'Brass', 'Ivory',
        'Beadwork', 'Textile', 'Kente', 'Mud cloth', 'Bark cloth',

        // Religions & Traditions
        'Vodun', 'Vodou', 'Ifa', 'Orisha', 'Oshun', 'Shango',
        'Coptic', 'Ethiopian Orthodox', 'Ancestor worship',

        // Languages
        'Swahili', 'Ge\'ez', 'Amharic', 'Hausa', 'Yoruba', 'Igbo',
        'Tamasheq', 'Tamazight', 'Wolof', 'Bambara', 'Lingala', 'Kikongo',
        'Shona', 'Zulu',
    ];

    /**
     * Determine if an artifact is of African heritage.
     *
     * @param array $data The raw API response data
     * @return array ['is_african' => bool, 'confidence' => 'high'|'medium'|'low', 'matches' => array]
     */
    public function analyze(array $data): array
    {
        $matches = [];
        $highConfidenceMatches = [];
        $searchableText = $this->buildSearchableText($data);

        // Check high-confidence terms
        foreach ($this->highConfidenceTerms as $term) {
            if (stripos($searchableText, $term) !== false) {
                $highConfidenceMatches[] = $term;
                $matches[] = $term;
            }
        }

        // Check general terms
        foreach ($this->generalTerms as $term) {
            if (stripos($searchableText, $term) !== false) {
                $matches[] = $term;
            }
        }

        // Remove duplicates
        $matches = array_unique($matches);
        $highConfidenceMatches = array_unique($highConfidenceMatches);

        // Determine if African
        $hasHighConfidenceMatch = count($highConfidenceMatches) > 0;
        $hasGeneralMatch = count($matches) > 0;
        $hasCultureField = !empty($data['culture']) && $this->isLikelyAfrican($data['culture']);
        $hasDynastyField = !empty($data['dynasty']) && $this->isLikelyAfrican($data['dynasty']);
        $hasPeriodField = !empty($data['period']) && $this->isLikelyAfrican($data['period']);
        $hasCountryField = !empty($data['country']) && $this->isLikelyAfricanCountry($data['country']);
        $hasRegionField = !empty($data['region']) && $this->isLikelyAfrican($data['region']);

        // Decision logic
        $isAfrican = false;
        $confidence = 'low';
        $reason = '';

        if ($hasHighConfidenceMatch) {
            $isAfrican = true;
            $confidence = 'high';
            $reason = 'High-confidence match: ' . implode(', ', array_slice($highConfidenceMatches, 0, 3));
        } elseif ($hasCountryField && ($hasCultureField || $hasDynastyField || $hasPeriodField)) {
            $isAfrican = true;
            $confidence = 'high';
            $reason = "African country ({$data['country']}) with cultural context";
        } elseif ($hasCultureField && $hasGeneralMatch) {
            $isAfrican = true;
            $confidence = 'medium';
            $reason = "African culture ({$data['culture']}) with supporting terms";
        } elseif ($hasDynastyField && $hasGeneralMatch) {
            $isAfrican = true;
            $confidence = 'medium';
            $reason = "African dynasty ({$data['dynasty']}) with supporting terms";
        } elseif ($hasCountryField && !empty($data['title']) && $this->isLikelyAfrican($data['title'])) {
            $isAfrican = true;
            $confidence = 'medium';
            $reason = "African country ({$data['country']}) and title contains African term";
        } elseif ($hasGeneralMatch && ($hasCultureField || $hasDynastyField || $hasPeriodField || $hasRegionField)) {
            $isAfrican = true;
            $confidence = 'medium';
            $reason = 'General African terms with cultural/geographic context';
        } elseif ($hasGeneralMatch && count($matches) >= 3) {
            $isAfrican = true;
            $confidence = 'medium';
            $reason = 'Multiple general African terms found';
        } elseif ($hasGeneralMatch && !empty($data['objectName']) && $this->isLikelyAfrican($data['objectName'])) {
            $isAfrican = true;
            $confidence = 'low';
            $reason = 'African object name with general terms';
        } elseif ($hasGeneralMatch) {
            $isAfrican = false;
            $confidence = 'low';
            $reason = 'General terms but insufficient cultural/geographic context';
        } else {
            $reason = 'No African terms found';
        }

        return [
            'is_african' => $isAfrican,
            'confidence' => $confidence,
            'matches' => $matches,
            'high_confidence_matches' => $highConfidenceMatches,
            'reason' => $reason,
        ];
    }

    /**
     * Build a searchable text string from the artifact data.
     */
    protected function buildSearchableText(array $data): string
    {
        $fields = [
            $data['title'] ?? '',
            $data['culture'] ?? '',
            $data['dynasty'] ?? '',
            $data['period'] ?? '',
            $data['country'] ?? '',
            $data['region'] ?? '',
            $data['artistDisplayName'] ?? '',
            $data['artistNationality'] ?? '',
            $data['objectName'] ?? '',
            $data['classification'] ?? '',
            $data['medium'] ?? '',
            $data['department'] ?? '',
            $data['reign'] ?? '',
            collect($data['tags'] ?? [])->pluck('term')->implode(' '),
            $data['objectWikidata_URL'] ?? '',
            $data['objectURL'] ?? '',
        ];

        return implode(' ', array_filter($fields));
    }

    /**
     * Check if a string is likely African (contains African terms).
     */
    protected function isLikelyAfrican(string $text): bool
    {
        $allTerms = array_merge($this->highConfidenceTerms, $this->generalTerms);
        foreach ($allTerms as $term) {
            if (stripos($text, $term) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a country name is likely an African country.
     */
    protected function isLikelyAfricanCountry(string $country): bool
    {
        $africanCountries = [
            'Nigeria', 'Ghana', 'Benin', 'Togo', 'Mali', 'Burkina Faso',
            'Senegal', 'Cameroon', 'Ethiopia', 'Kenya', 'Uganda', 'Tanzania',
            'Zimbabwe', 'South Africa', 'Namibia', 'Botswana', 'Mozambique',
            'Angola', 'Sudan', 'Egypt', 'Morocco', 'Algeria', 'Tunisia',
            'Libya', 'Congo', 'Ivory Coast', 'Côte d\'Ivoire', 'Guinea',
            'Sierra Leone', 'Liberia', 'Niger', 'Chad', 'Central African Republic',
            'Gabon', 'Equatorial Guinea', 'Rwanda', 'Burundi', 'Malawi',
            'Zambia', 'Lesotho', 'Eswatini', 'Madagascar', 'South Sudan',
            'Eritrea', 'Djibouti', 'Somalia', 'Western Sahara',
            'Nigeria', 'Ghana', 'Benin', 'Togo', 'Mali',
        ];

        foreach ($africanCountries as $africanCountry) {
            if (stripos($country, $africanCountry) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get high-confidence terms for API searching.
    */
    public function getHighConfidenceTerms(): array
    {
        return $this->highConfidenceTerms;
    }

    /**
     * Get general terms for API searching.
     */
    public function getGeneralTerms(): array
    {
        return $this->generalTerms;
    }
}