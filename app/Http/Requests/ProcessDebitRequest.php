<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessDebitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->branch_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gift_card_id' => 'required|exists:gift_cards,id',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'gift_card_id.required' => 'El QR es requerido',
            'gift_card_id.exists' => 'El QR no existe',
            'amount.required' => 'El monto es requerido',
            'amount.numeric' => 'El monto debe ser un número',
            'amount.min' => 'El monto debe ser mayor a 0',
            'reference.required' => 'La referencia es requerida',
            'reference.max' => 'La referencia no puede exceder 255 caracteres',
            'description.max' => 'La descripción no puede exceder 500 caracteres',
        ];
    }
}
