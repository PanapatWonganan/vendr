<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DbdApiService
{
    protected string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.dbd.base_url', 'https://openapi.dbd.go.th/api/v1');
        $this->token = config('services.dbd.token');
    }

    /**
     * Fetch juristic person data by 13-digit tax ID
     *
     * @param string $taxId 13-digit juristic person ID
     * @return array|null Parsed data or null on failure
     */
    public function getJuristicPerson(string $taxId): ?array
    {
        $taxId = $this->normalizeTaxId($taxId);

        if (!$this->isValidTaxId($taxId)) {
            Log::warning("DBD API: Invalid tax ID format", ['tax_id' => $taxId]);
            return null;
        }

        // Cache results for 24 hours to avoid hitting API repeatedly
        $cacheKey = "dbd_juristic_{$taxId}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($taxId) {
            return $this->fetchFromApi($taxId);
        });
    }

    /**
     * Fetch data from DBD Open API
     */
    protected function fetchFromApi(string $taxId): ?array
    {
        try {
            $url = "{$this->baseUrl}/juristic_person/{$taxId}";

            $request = Http::timeout(30)
                ->acceptJson();

            // Add token if available
            if ($this->token) {
                $request = $request->withToken($this->token);
            }

            $response = $request->get($url);

            if ($response->successful()) {
                $data = $response->json();

                Log::info("DBD API: Successfully fetched data", [
                    'tax_id' => $taxId,
                    'status' => $response->status(),
                ]);

                return $this->parseResponse($data, $taxId);
            }

            // If the main API fails, try the Open Data CKAN API
            Log::info("DBD API: Primary API returned {$response->status()}, trying CKAN fallback", [
                'tax_id' => $taxId,
            ]);

            return $this->fetchFromCkanApi($taxId);

        } catch (\Exception $e) {
            Log::error("DBD API: Request failed", [
                'tax_id' => $taxId,
                'error' => $e->getMessage(),
            ]);

            // Try CKAN fallback
            return $this->fetchFromCkanApi($taxId);
        }
    }

    /**
     * Fallback: Fetch from DBD CKAN Open Data API
     */
    protected function fetchFromCkanApi(string $taxId): ?array
    {
        try {
            $resourceId = 'bc6ed576-dee1-4b72-b144-e11c8d122e26';
            $url = "https://opendata.dbd.go.th/api/3/action/datastore_search";

            $response = Http::timeout(30)
                ->acceptJson()
                ->get($url, [
                    'resource_id' => $resourceId,
                    'q' => $taxId,
                    'limit' => 5,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $records = $data['result']['records'] ?? [];

                if (!empty($records)) {
                    // Find exact match
                    $match = collect($records)->first(function ($record) use ($taxId) {
                        $id = $record['juristicID'] ?? $record['JuristicID'] ?? $record['juristic_id'] ?? '';
                        return $this->normalizeTaxId($id) === $taxId;
                    });

                    if ($match) {
                        Log::info("DBD CKAN API: Found match", ['tax_id' => $taxId]);
                        return $this->parseCkanResponse($match, $taxId);
                    }
                }
            }

            Log::warning("DBD CKAN API: No results found", ['tax_id' => $taxId]);
            return null;

        } catch (\Exception $e) {
            Log::error("DBD CKAN API: Request failed", [
                'tax_id' => $taxId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parse the main API response into a standardized format
     *
     * DBD Open API returns data in this structure:
     * { "status": { "code": "1000" }, "data": [{ "cd:OrganizationJuristicPerson": { ... } }] }
     */
    protected function parseResponse(array $data, string $taxId): array
    {
        // Check for the actual DBD Open API format: data[0]["cd:OrganizationJuristicPerson"]
        $dataArray = $data['data'] ?? [];
        if (!empty($dataArray) && is_array($dataArray)) {
            $first = $dataArray[0] ?? null;
            if ($first && isset($first['cd:OrganizationJuristicPerson'])) {
                return $this->parseDbdOpenApiResponse($first['cd:OrganizationJuristicPerson'], $data, $taxId);
            }
        }

        // Fallback: try generic structure
        $record = $data['data'] ?? $data['result'] ?? $data;

        return [
            'tax_id' => $taxId,
            'status' => $record['juristicStatus'] ?? $record['status'] ?? null,
            'entity_type' => $record['juristicType'] ?? $record['entityType'] ?? null,
            'registered_capital' => $this->parseCapital($record['registeredCapital'] ?? $record['capital'] ?? null),
            'name_th' => $record['juristicNameTH'] ?? $record['nameTH'] ?? null,
            'name_en' => $record['juristicNameEN'] ?? $record['nameEN'] ?? null,
            'address' => $record['address'] ?? $record['Address'] ?? null,
            'registration_date' => $this->parseDate($record['registrationDate'] ?? $record['registerDate'] ?? null),
            'objectives' => $this->parseObjectives($record['objectives'] ?? $record['objective'] ?? null),
            'raw_data' => $data,
        ];
    }

    /**
     * Parse the actual DBD Open API response format (cd:OrganizationJuristicPerson)
     */
    protected function parseDbdOpenApiResponse(array $jp, array $rawData, string $taxId): array
    {
        // Parse address from nested structure
        $address = null;
        $addrData = $jp['cd:OrganizationJuristicAddress']['cr:AddressType'] ?? null;
        if ($addrData) {
            $parts = array_filter([
                $addrData['cd:Address'] ?? null,
                isset($addrData['cd:Building']) ? "อาคาร {$addrData['cd:Building']}" : null,
                isset($addrData['cd:Floor']) ? "ชั้น {$addrData['cd:Floor']}" : null,
                isset($addrData['cd:Soi']) ? "ซอย {$addrData['cd:Soi']}" : null,
                isset($addrData['cd:Road']) ? "ถนน {$addrData['cd:Road']}" : null,
                $addrData['cd:CitySubDivision']['cr:CitySubDivisionTextTH'] ?? null,
                $addrData['cd:City']['cr:CityTextTH'] ?? null,
                $addrData['cd:CountrySubDivision']['cr:CountrySubDivisionTextTH'] ?? null,
            ]);
            $address = implode(' ', $parts);
        }

        // Parse objectives
        $objectives = null;
        $objData = $jp['cd:OrganizationJuristicObjective'] ?? null;
        if ($objData) {
            $obj = $objData['td:JuristicObjective'] ?? null;
            if ($obj) {
                // Could be single or array
                if (isset($obj['td:JuristicObjectiveCode'])) {
                    $objectives = [[
                        'code' => $obj['td:JuristicObjectiveCode'],
                        'text_th' => $obj['td:JuristicObjectiveTextTH'] ?? null,
                        'text_en' => $obj['td:JuristicObjectiveTextEN'] ?? null,
                    ]];
                } else {
                    $objectives = collect($obj)->map(fn($o) => [
                        'code' => $o['td:JuristicObjectiveCode'] ?? null,
                        'text_th' => $o['td:JuristicObjectiveTextTH'] ?? null,
                        'text_en' => $o['td:JuristicObjectiveTextEN'] ?? null,
                    ])->toArray();
                }
            }
        }

        return [
            'tax_id' => $taxId,
            'status' => $jp['cd:OrganizationJuristicStatus'] ?? null,
            'entity_type' => $jp['cd:OrganizationJuristicType'] ?? null,
            'registered_capital' => $this->parseCapital($jp['cd:OrganizationJuristicRegisterCapital'] ?? null),
            'name_th' => $jp['cd:OrganizationJuristicNameTH'] ?? null,
            'name_en' => $jp['cd:OrganizationJuristicNameEN'] ?? null,
            'address' => $address,
            'registration_date' => $this->parseDate($jp['cd:OrganizationJuristicRegisterDate'] ?? null),
            'objectives' => $objectives,
            'raw_data' => $rawData,
        ];
    }

    /**
     * Parse CKAN API response into standardized format
     */
    protected function parseCkanResponse(array $record, string $taxId): array
    {
        return [
            'tax_id' => $taxId,
            'status' => $record['juristicStatus'] ?? $record['JuristicStatus'] ?? $record['juristic_status'] ?? null,
            'entity_type' => $record['juristicType'] ?? $record['JuristicType'] ?? $record['juristic_type'] ?? null,
            'registered_capital' => $this->parseCapital($record['registeredCapital'] ?? $record['RegisteredCapital'] ?? $record['registered_capital'] ?? null),
            'name_th' => $record['juristicNameTH'] ?? $record['JuristicNameTH'] ?? $record['juristic_name_th'] ?? null,
            'name_en' => $record['juristicNameEN'] ?? $record['JuristicNameEN'] ?? $record['juristic_name_en'] ?? null,
            'address' => $record['address'] ?? $record['Address'] ?? $record['head_office_address'] ?? null,
            'registration_date' => $this->parseDate($record['registrationDate'] ?? $record['RegisterDate'] ?? $record['register_date'] ?? null),
            'objectives' => $this->parseObjectives($record['objectives'] ?? $record['Objectives'] ?? $record['objective'] ?? null),
            'raw_data' => $record,
        ];
    }

    /**
     * Normalize tax ID — remove dashes and spaces, ensure 13 digits
     */
    protected function normalizeTaxId(string $taxId): string
    {
        return preg_replace('/[^0-9]/', '', $taxId);
    }

    /**
     * Validate 13-digit tax ID format
     */
    public function isValidTaxId(string $taxId): bool
    {
        $taxId = $this->normalizeTaxId($taxId);
        return strlen($taxId) === 13 && ctype_digit($taxId);
    }

    /**
     * Parse capital amount to float
     */
    protected function parseCapital($value): ?float
    {
        if ($value === null) return null;

        $cleaned = preg_replace('/[^0-9.]/', '', (string) $value);
        return $cleaned ? (float) $cleaned : null;
    }

    /**
     * Parse date string to Y-m-d format
     * Handles: YYYYMMDD (DBD Open API), DD/MM/YYYY (Thai format), ISO format
     */
    protected function parseDate($value): ?string
    {
        if (!$value) return null;

        try {
            $value = trim((string) $value);

            // Format: YYYYMMDD (from DBD Open API, e.g. "20190802")
            if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $matches)) {
                $year = (int) $matches[1];
                $month = (int) $matches[2];
                $day = (int) $matches[3];

                // Convert Buddhist year to Christian year if needed
                if ($year > 2400) {
                    $year -= 543;
                }

                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }

            // Format: DD/MM/YYYY (Thai format)
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $value, $matches)) {
                $year = (int) $matches[3];
                if ($year > 2400) {
                    $year -= 543;
                }
                return sprintf('%04d-%02d-%02d', $year, (int) $matches[2], (int) $matches[1]);
            }

            // ISO date format or any other format PHP can parse
            $ts = strtotime($value);
            return $ts ? date('Y-m-d', $ts) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse business objectives
     */
    protected function parseObjectives($value): ?array
    {
        if ($value === null) return null;

        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded ?: [$value];
        }

        return null;
    }

    /**
     * Clear cache for a specific tax ID
     */
    public function clearCache(string $taxId): void
    {
        $taxId = $this->normalizeTaxId($taxId);
        Cache::forget("dbd_juristic_{$taxId}");
    }
}
