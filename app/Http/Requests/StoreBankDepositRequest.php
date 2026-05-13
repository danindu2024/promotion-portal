<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankDepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'nic' => ['required', 'string', 'regex:/^(?:[0-9]{9}[vVxX]|[0-9]{12})$/'],
            'mobile' => ['required', 'string', 'regex:/^0\d{9}$/'],
            'address' => 'required|string',
            'enrollment_number' => 'required|numeric',
            'amount' => 'required|numeric|min:0',
            'deposit_date' => 'required|date|before_or_equal:today',
            'branch' => 'required|string|max:255',
            'receipt_reference_number' => 'required|string|max:255|unique:bank_deposits,receipt_reference_number',
            'slip' => 'required|image|max:5120', // 5MB max
            'remarks' => 'nullable|string',
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'nic.regex' => 'The NIC format is invalid. Use 9 digits with V/X or 12 digits.',
            'mobile.regex' => 'The mobile number must be 10 digits starting with 0.',
            'deposit_date.before_or_equal' => 'The deposit date cannot be in the future.',
            'receipt_reference_number.unique' => 'A deposit with this receipt reference number has already been recorded.',
            'amount.min' => 'The amount must be a positive value.',
        ];
    }
}

