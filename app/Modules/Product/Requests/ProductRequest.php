<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ProductRequest extends FormRequest
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
        $productId = $this->route('id');

        return [
            'sku' => [
                'required',
                'string',
                'max:50',
                $productId ? "unique:products,sku,{$productId}" : 'unique:products,sku'
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'min_stock' => ['sometimes', 'integer', 'min:0'],
            'max_stock' => ['sometimes', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'in:active,inactive,discontinued'],
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'SKU is required',
            'sku.unique' => 'This SKU already exists',
            'name.required' => 'Product name is required',
            'category_id.required' => 'Category is required',
            'category_id.exists' => 'Selected category does not exist',
            'supplier_id.required' => 'Supplier is required',
            'supplier_id.exists' => 'Selected supplier does not exist',
            'price.required' => 'Selling price is required',
            'price.min' => 'Price cannot be negative',
            'cost_price.required' => 'Cost price is required',
            'cost_price.min' => 'Cost price cannot be negative',
            'quantity.min' => 'Quantity cannot be negative',
            'status.in' => 'Status must be active, inactive, or discontinued',
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