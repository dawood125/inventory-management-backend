<?php

namespace App\Modules\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:purchase,sale'],
            'supplier_id' => ['required_if:type,purchase', 'nullable', 'exists:suppliers,id'],
            'customer_name' => ['required_if:type,sale', 'nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Order type is required',
            'type.in' => 'Order type must be purchase or sale',
            'supplier_id.required_if' => 'Supplier is required for purchase orders',
            'supplier_id.exists' => 'Selected supplier does not exist',
            'customer_name.required_if' => 'Customer name is required for sale orders',
            'items.required' => 'Order must have at least one item',
            'items.min' => 'Order must have at least one item',
            'items.*.product_id.required' => 'Product is required for each item',
            'items.*.product_id.exists' => 'Selected product does not exist',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.min' => 'Quantity must be at least 1',
        ];
    }

    /**
     * Handle failed validation
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}