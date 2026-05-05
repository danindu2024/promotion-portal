<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\NormalizesData;

class SaveRegistryRequest extends FormRequest
{
    use NormalizesData;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Actual permission checks are handled in the controller (location-based)
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $data = $this->all();

        // 1. Server-side trim
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        // 2. Normalize NIC
        if (isset($data['national_id_number'])) {
            $data['national_id_number'] = $this->normalizeNationalId($data['national_id_number']);
        }

        // 3. Normalize Locations
        $data['province'] = $this->normalizeLocationName($data['province'] ?? '');
        $data['district'] = $this->normalizeLocationName($data['district'] ?? '');
        $data['ds_division'] = $this->normalizeLocationName($data['ds_division'] ?? '');

        // 4. Strip cross-category fields to prevent 'prohibited' rule from firing on empty fields
        // This is necessary because the frontend might send empty strings for these fields.
        $category = $data['category'] ?? null;
        if ($category === 'Self-Employed') {
            if (empty($data['contact_person'])) unset($data['contact_person']);
            if (empty($data['members_count'])) unset($data['members_count']);
        } elseif ($category === 'Trade') {
            if (empty($data['field_of_work'])) unset($data['field_of_work']);
            if (empty($data['age'])) unset($data['age']);
            if (empty($data['employees_count'])) unset($data['employees_count']);
        }

        $this->replace($data);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return \App\Services\RegistryValidator::getRules($this->all());
    }
}
