<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegistryValidator
{
    /**
     * Validate a single row of registry data.
     * 
     * @param array $data The raw row data
     * @return \Illuminate\Validation\Validator
     */
    public static function validate(array $data)
    {
        $rules = self::getRules($data);
        
        return Validator::make($data, $rules);
    }

    /**
     * Get the validation rules based on category.
     */
    protected static function getRules(array $data): array
    {
        $category = $data['category'] ?? null;
        // Common Rules (Apply to everyone)
        $rules = [
            'category' => ['required', Rule::in(['Self-Employed', 'Trade'])],
            'full_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'province' => ['required', Rule::in(config('srilanka.provinces'))],
            'district' => ['required', Rule::in(config('srilanka.districts')), function ($attribute, $value, $fail) use ($data) { // custom function to check the district belongs to the province
                $province = $data['province'] ?? null;
                if ($province) {
                    $hierarchy = config('srilanka.hierarchy');
                    $validDistricts = isset($hierarchy[$province]) ? array_keys($hierarchy[$province]) : [];
                    if (!in_array($value, $validDistricts)) {
                        $fail("The selected district does not belong to the province {$province}.");
                    }
                }
            }],
            'ds_division' => ['required', Rule::in(config('srilanka.ds_divisions')), function ($attribute, $value, $fail) use ($data) {
                $district = $data['district'] ?? null;
                if ($district) {
                    $hierarchy = config('srilanka.hierarchy');
                    $validDivisions = [];
                    foreach ($hierarchy as $districts) {
                        if (isset($districts[$district])) {
                            $validDivisions = $districts[$district];
                            break;
                        }
                    }
                    if (!in_array($value, $validDivisions)) {
                        $fail("The selected DS division does not belong to the district {$district}.");
                    }
                }
            }],

            'contact_number' => ['required', 'string', 'regex:/^0\d{9}$/'],
            'whatsapp_number' => ['nullable', 'string', 'regex:/^0\d{9}$/'],
            'email' => 'nullable|email',
            'national_id_number' => ['nullable', 'string', 'regex:/^(?:[0-9]{9}[vVxX]|[0-9]{12})$/'],
        ];

        // Category-Specific Rules
        if ($category === 'Self-Employed') {
            $rules = array_merge($rules, [
                'age' => 'nullable|integer|min:16|max:110',
                'field_of_work' => ['required', Rule::in([
                    'Agriculture and Fisheries Entrepreneurs', 'Cottage Industries / Small Industries',
                    'Transport and Technical Services', 'Construction Services', 'Trade and Service Enterprises',
                    'Tourism Industry', 'Arts, Cultural, and Beauty Services', 'Information Technology and Modern Services',
                    'Educational Services', 'Small-scale Trading'
                ])],
                'employees_count' => 'nullable|integer|min:0',
                
                // Forbidden fields for Self-Employed (must be null/empty)
                'contact_person' => 'prohibited',
                'members_count' => 'prohibited',
            ]);

        } elseif ($category === 'Trade') {
            $rules = array_merge($rules, [
                'contact_person' => 'nullable|string|max:255',
                'members_count' => 'nullable|integer|min:0',

                // Forbidden fields for Trade
                'age' => 'prohibited',
                'field_of_work' => 'prohibited',
                'employees_count' => 'prohibited',
            ]);
        }

        return $rules;
    }
}
