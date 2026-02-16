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
        // 1. Common Rules (Apply to everyone)
        $rules = [
            'category' => ['required', Rule::in(['Self-Employed', 'Trade'])],
            'full_name' => 'required|string|max:255',
            'address' => 'required|string',
            'district' => 'required|string', // TODO: Add Rule::in([...districts...])
            'ds_division' => 'required|string',
            'gn_division' => 'required|string',
            'contact_number' => 'required|digits:10',
            'whatsapp_number' => 'nullable|digits:10',
            'email' => 'nullable|email',
        ];

        // 2. Category-Specific Rules
        if ($category === 'Self-Employed') {
            $rules['age'] = 'required|integer|min:18|max:100';
            $rules['field_of_work'] = ['required', Rule::in([
                'Agriculture & Fishing', 'Textile & Garments', 'Construction',
                'IT & Modern Services', 'Food & Beverages', 'Manufacturing',
                'Tourism & Hospitality', 'Transportation', 'Retail & Wholesale',
                'Other Services'
            ])];
            $rules['employees_count'] = 'required|integer|min:0';
            
            // Forbidden fields for Self-Employed (must be null/empty)
            $rules['contact_person'] = 'prohibited';
            $rules['members_count'] = 'prohibited';

        } elseif ($category === 'Trade') {
            $rules['contact_person'] = 'required|string|max:255';
            $rules['members_count'] = 'required|integer|min:0';

            // Forbidden fields for Trade
            $rules['age'] = 'prohibited';
            $rules['field_of_work'] = 'prohibited';
            $rules['employees_count'] = 'prohibited';
        }

        return $rules;
    }
}
