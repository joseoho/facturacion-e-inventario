<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TasaCambioRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'moneda_id' => ['required', 'exists:monedas,id'],
            'tasa' => ['required', 'numeric', 'min:0.000001', 'max:999999.999999'],
            'fecha' => ['required', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'moneda_id.required' => 'Debes seleccionar una moneda.',
            'moneda_id.exists' => 'La moneda seleccionada no es válida.',
            'tasa.required' => 'La tasa de cambio es obligatoria.',
            'tasa.numeric' => 'La tasa debe ser un valor numérico.',
            'tasa.min' => 'La tasa debe ser mayor a 0.',
            'tasa.max' => 'La tasa no puede ser tan alta.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha no es válida.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
        ];
    }
}