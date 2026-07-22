<?php

namespace App\Services;

class AfricanHeritageFilter
{
    // High-confidence African terms for scoring
    protected array $highConfidenceTerms = [
        // Countries
        'Nigeria', 'Ghana', 'Benin', 'Togo', 'Mali', 'Burkina Faso',
        'Senegal', 'Cameroon', 'Ethiopia', 'Kenya', 'Uganda', 'Tanzania',
        'Zimbabwe', 'South Africa', 'Namibia', 'Botswana', 'Mozambique',
        'Angola', 'Sudan', 'Egypt', 'Morocco', 'Algeria', 'Tunisia',
        'Libya', 'Congo', 'Ivory Coast', 'Guinea', 'Sierra Leone',
        'Liberia', 'Niger', 'Chad', 'Central African Republic', 'Gabon',
        'Equatorial Guinea', 'Rwanda', 'Burundi', 'Malawi', 'Zambia',
        'Lesotho', 'Eswatini', 'Madagascar', 'South Sudan', 'Eritrea',
        'Djibouti', 'Somalia',
        // Empires & Kingdoms
        'Kingdom of Benin', 'Benin Kingdom', 'Oyo', 'Ife', 'Ile-Ife',
        'Asante', 'Ashanti', 'Dahomey', 'Mali Empire', 'Songhai Empire',
        'Songhai', 'Kanem-Bornu', 'Kanem', 'Bornu', 'Kush', 'Nubia',
        'Kerma', 'Meroe', 'Axum', 'Aksum', 'Great Zimbabwe', 'Mutapa',
        'Mapungubwe', 'Kongo Kingdom', 'Luba Kingdom', 'Lunda Kingdom',
        'Buganda', 'Ghana Empire',
        // Ethnic Groups
        'Yoruba', 'Igbo', 'Hausa', 'Fulani', 'Fulbe', 'Fula',
        'Akan', 'Ewe', 'Ga', 'Fon', 'Bambara', 'Mandinka', 'Mande',
        'Wolof', 'Serer', 'Dogon', 'Tuareg', 'Berber', 'Amazigh',
        'Kanuri', 'Tiv', 'Nupe', 'Edo', 'Urhobo', 'Ijaw', 'Ibibio',
        'Efik', 'Idoma', 'Kongo', 'Luba', 'Lunda', 'Chokwe', 'Zulu',
        'Xhosa', 'Sotho', 'Tswana', 'Ndebele', 'Shona', 'Venda',
        'Maasai', 'Kikuyu', 'Luo', 'Meru', 'Amhara', 'Oromo', 'Tigray',
        'Somali', 'Afar', 'Beja', 'Nubian', 'Swahili',
        // Cultures
        'Nok', 'Ife', 'Kerma', 'Meroe',
    ];

    // General terms for scoring (lower weight)
    protected array $generalTerms = [
        'Africa', 'African', 'Sub-Saharan', 'Sahel', 'Maghreb', 'Nile Valley',
        'Mask', 'Power figure', 'Nkisi', 'Fetish figure', 'Terracotta',
        'Bronze', 'Brass', 'Ivory', 'Beadwork', 'Textile', 'Kente',
        'Vodun', 'Vodou', 'Ifa', 'Orisha', 'Ancestor worship',
        'Swahili', 'Ge\'ez', 'Amharic',
    ];

    protected int $minScore = 100;

    /**
     * Analyze artifact using scoring system.
     * No blacklists – purely positive scoring.
     */
    public function analyze(array $data): array
    {
        $text = $this->buildSearchableText($data);
        $score = 0;
        $matches = [];
        $highMatches = [];

        // High-confidence terms: +100 each
        foreach ($this->highConfidenceTerms as $term) {
            if (stripos($text, $term) !== false) {
                $score += 100;
                $highMatches[] = $term;
                $matches[] = $term;
            }
        }

        // General terms: +50 each
        foreach ($this->generalTerms as $term) {
            if (stripos($text, $term) !== false) {
                $score += 50;
                $matches[] = $term;
            }
        }

        $matches = array_unique($matches);
        $highMatches = array_unique($highMatches);

        // Check official African fields
        $hasAfricanCulture = $this->isAfricanCulture($data['culture'] ?? null);
        $hasAfricanDynasty = $this->isAfricanDynasty($data['dynasty'] ?? null);
        $hasAfricanCountry = $this->isAfricanCountry($data['country'] ?? null);
        $hasAfricanRegion = $this->isAfricanRegion($data['region'] ?? null);
        $officialAfrican = $hasAfricanCulture || $hasAfricanDynasty || $hasAfricanCountry || $hasAfricanRegion;

        // Decision logic
        $hasHigh = count($highMatches) > 0;
        $isAfrican = false;
        $confidence = 'low';
        $reason = '';

        if ($hasHigh) {
            $isAfrican = true;
            $confidence = 'high';
            $reason = 'High-confidence match: ' . implode(', ', array_slice($highMatches, 0, 3));
        } elseif ($officialAfrican && $score >= $this->minScore) {
            $isAfrican = true;
            $confidence = 'high';
            $reason = "African field with score $score";
        } elseif ($officialAfrican && count($matches) >= 2) {
            $isAfrican = true;
            $confidence = 'medium';
            $reason = "African field with general terms";
        } elseif ($score >= $this->minScore + 50) {
            $isAfrican = true;
            $confidence = 'medium';
            $reason = "Very high score ($score)";
        } else {
            $reason = "Score $score, official field: " . ($officialAfrican ? 'yes' : 'no');
        }

        return [
            'is_african' => $isAfrican,
            'confidence' => $confidence,
            'score' => $score,
            'matches' => $matches,
            'high_confidence_matches' => $highMatches,
            'reason' => $reason,
        ];
    }

    private function buildSearchableText(array $data): string
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
        ];
        return implode(' ', array_filter($fields));
    }

    private function isAfricanCulture(?string $culture): bool
    {
        if (empty($culture)) return false;
        $african = ['Yoruba', 'Igbo', 'Hausa', 'Fulani', 'Akan', 'Fon', 'Dogon', 'Tuareg', 'Zulu', 'Xhosa', 'Shona', 'Maasai', 'Kikuyu', 'Luo', 'Amhara', 'Oromo', 'Tigray', 'Somali', 'Nubian', 'Swahili', 'Kongo', 'Luba', 'Lunda', 'Chokwe', 'Nok', 'Benin', 'Ife', 'Kerma', 'Meroe', 'Bantu', 'Nilotic', 'Cushitic', 'Khoisan'];
        foreach ($african as $term) {
            if (stripos($culture, $term) !== false) return true;
        }
        return false;
    }

    private function isAfricanDynasty(?string $dynasty): bool
    {
        if (empty($dynasty)) return false;
        $african = ['Benin', 'Oyo', 'Ife', 'Asante', 'Dahomey', 'Songhai', 'Mali', 'Kanem', 'Bornu', 'Kush', 'Nubia', 'Axum', 'Aksum', 'Great Zimbabwe', 'Kongo', 'Luba', 'Lunda', 'Buganda', 'Ghana', 'Wagadou'];
        foreach ($african as $term) {
            if (stripos($dynasty, $term) !== false) return true;
        }
        return false;
    }

    private function isAfricanCountry(?string $country): bool
    {
        if (empty($country)) return false;
        $countries = ['Nigeria', 'Ghana', 'Benin', 'Togo', 'Mali', 'Burkina Faso', 'Senegal', 'Cameroon', 'Ethiopia', 'Kenya', 'Uganda', 'Tanzania', 'Zimbabwe', 'South Africa', 'Namibia', 'Botswana', 'Mozambique', 'Angola', 'Sudan', 'Egypt', 'Morocco', 'Algeria', 'Tunisia', 'Libya', 'Congo', "Côte d'Ivoire", 'Guinea', 'Sierra Leone', 'Liberia', 'Niger', 'Chad', 'Central African Republic', 'Gabon', 'Equatorial Guinea', 'Rwanda', 'Burundi', 'Malawi', 'Zambia', 'Lesotho', 'Eswatini', 'Madagascar', 'South Sudan', 'Eritrea', 'Djibouti', 'Somalia', 'Western Sahara', 'Cabo Verde', 'São Tomé and Príncipe', 'Comoros', 'Mauritius', 'Seychelles'];
        foreach ($countries as $c) {
            if (stripos($country, $c) !== false) return true;
        }
        return false;
    }

    private function isAfricanRegion(?string $region): bool
    {
        if (empty($region)) return false;
        $african = ['West Africa', 'East Africa', 'Central Africa', 'Southern Africa', 'North Africa', 'Sub-Saharan Africa', 'Horn of Africa', 'Sahel', 'Maghreb', 'Nile Valley', 'Great Lakes', 'Congo Basin', 'Zambezi', 'Niger Delta', 'Guinea Coast', 'Swahili Coast', 'Gold Coast', 'Slave Coast', 'Pepper Coast'];
        foreach ($african as $term) {
            if (stripos($region, $term) !== false) return true;
        }
        return false;
    }

    public function getSearchTerms(): array
    {
        return $this->highConfidenceTerms;
    }
}