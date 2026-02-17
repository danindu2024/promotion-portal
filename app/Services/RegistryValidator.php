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
        $rules = self::getRules($data['category'] ?? null); // Use category to determine rules
        
        return Validator::make($data, $rules);
    }

    /**
     * Get the validation rules based on category.
     */
    protected static function getRules(?string $category): array
    {
        // Common Rules (Apply to everyone)
        $rules = [
            'category' => ['required', Rule::in(['Self-Employed', 'Trade'])],
            'full_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'province' => ['required', Rule::in(config('srilanka.provinces'))],
            'district' => ['required', Rule::in(config('srilanka.districts'))],
            'ds_division' => ['required', Rule::in(config('srilanka.ds_divisions'))],

            'contact_number' => 'required|digits:10',
            'whatsapp_number' => 'nullable|digits:10',
            'email' => 'nullable|email',
        ];

        // Category-Specific Rules
        if ($category === 'Self-Employed') {
            $rules['age'] = 'nullable|integer|min:16|max:110';
            $rules['field_of_work'] = ['required', Rule::in([
                'Agriculture and Fisheries Entrepreneurs', 'Cottage Industries / Small Industries',
                'Transport and Technical Services', 'Construction Services', 'Trade and Service Enterprises',
                'Tourism Industry', 'Arts, Cultural, and Beauty Services', 'Information Technology and Modern Services',
                'Educational Services', 'Small-scale Trading'
            ])];
            $rules['employees_count'] = 'nullable|integer|min:0';
            
            // Forbidden fields for Self-Employed (must be null/empty)
            $rules['contact_person'] = 'prohibited';
            $rules['members_count'] = 'prohibited';

        } elseif ($category === 'Trade') {
            $rules['contact_person'] = 'nullable|string|max:255';
            $rules['members_count'] = 'nullable|integer|min:0';

            // Forbidden fields for Trade
            $rules['age'] = 'prohibited';
            $rules['field_of_work'] = 'prohibited';
            $rules['employees_count'] = 'prohibited';
        }

        return $rules;
    }
}
