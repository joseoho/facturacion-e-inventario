<?php

namespace App\Http\Requests;

use App\Models\CategoriaEgreso;
use App\Models\Egreso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEgresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real la hacen el middleware role:admin y la policy.
        return true;
    }

    public function rules(): array
    {
        return [
            'categoria_egreso_id' => ['required', 'integer', 'exists:categorias_egreso,id'],
            'proveedor_nombre' => ['nullable', 'string', 'max:200'],
            'moneda_id' => ['required', 'integer', 'exists:monedas,id'],
            'monto' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'fecha_egreso' => ['required', 'date'],
            'estado' => ['required', Rule::in([Egreso::ESTADO_PENDIENTE, Egreso::ESTADO_PAGADO])],
            'metodo_pago' => ['nullable', Rule::in(array_keys(Egreso::METODOS_PAGO))],
            'referencia_pago' => ['nullable', 'string', 'max:100'],
            'concepto' => ['required', 'string', 'max:1000'],
            'producto_id' => ['nullable', 'integer', 'exists:productos,id'],
            'cantidad_kg' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_egreso_id.required' => 'Selecciona una categoría de egreso.',
            'moneda_id.required' => 'Selecciona la moneda de pago.',
            'monto.required' => 'Indica el monto del egreso.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
            'fecha_egreso.required' => 'La fecha del egreso es obligatoria.',
            'concepto.required' => 'Escribe el concepto del egreso.',
        ];
    }

    /**
     * Si la categoría afecta inventario, producto y cantidad son obligatorios.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoria = CategoriaEgreso::find($this->input('categoria_egreso_id'));

            if ($categoria?->afecta_inventario) {
                if (! $this->input('producto_id')) {
                    $validator->errors()->add('producto_id', 'Esta categoría afecta inventario: selecciona el producto.');
                }

                if (! $this->input('cantidad_kg') || (float) $this->input('cantidad_kg') <= 0) {
                    $validator->errors()->add('cantidad_kg', 'Esta categoría afecta inventario: indica la cantidad en Kg.');
                }
            }
        });
    }
}
