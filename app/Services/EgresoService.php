<?php

namespace App\Services;

use App\Models\CategoriaEgreso;
use App\Models\Egreso;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\TasaCambio;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Lógica de negocio del módulo de egresos.
 *
 * Un egreso es un registro plano: no tiene líneas. Si hay que registrar
 * tres conceptos distintos, se registran tres egresos.
 */
class EgresoService
{
    public function crear(array $data, User $user): Egreso
    {
        return DB::transaction(function () use ($data, $user) {
            $moneda = Moneda::query()->lockForUpdate()->find($data['moneda_id'] ?? null);

            if (! $moneda) {
                throw new DomainException('La moneda seleccionada no existe.');
            }

            if (! $moneda->activo) {
                throw new DomainException('La moneda seleccionada está inactiva.');
            }

            if (! in_array($moneda->codigo, Egreso::MONEDAS_PAGO, true)) {
                throw new DomainException('Solo se permiten egresos en USD, BS o COP.');
            }

            $categoria = CategoriaEgreso::find($data['categoria_egreso_id'] ?? null);

            if (! $categoria) {
                throw new DomainException('La categoría de egreso no existe.');
            }

            if (! $categoria->activo) {
                throw new DomainException('La categoría de egreso está inactiva.');
            }

            $monto = round((float) ($data['monto'] ?? 0), 4);

            if ($monto <= 0) {
                throw new DomainException('El monto debe ser mayor a cero.');
            }

            [$tasaId, $tasaValor] = $this->resolverTasa($moneda);

            $productoId = $data['producto_id'] ?? null;
            $cantidad = isset($data['cantidad_kg']) ? round((float) $data['cantidad_kg'], 3) : null;

            if ($categoria->afecta_inventario) {
                if (! $productoId) {
                    throw new DomainException('Esta categoría afecta inventario: selecciona el producto.');
                }

                if (! $cantidad || $cantidad <= 0) {
                    throw new DomainException('Esta categoría afecta inventario: indica una cantidad mayor a cero.');
                }

                $producto = Producto::query()->lockForUpdate()->find($productoId);

                if (! $producto) {
                    throw new DomainException('El producto seleccionado no existe.');
                }

                if (! $producto->activo) {
                    throw new DomainException('El producto seleccionado está inactivo.');
                }

                $producto->update([
                    'stock_kg' => (float) $producto->stock_kg + $cantidad,
                ]);
            } else {
                // Si la categoría no afecta inventario, ignoramos producto/cantidad.
                $productoId = null;
                $cantidad = null;
            }

            $estado = $data['estado'] ?? Egreso::ESTADO_PENDIENTE;

            if (! in_array($estado, [Egreso::ESTADO_PENDIENTE, Egreso::ESTADO_PAGADO], true)) {
                throw new DomainException('Estado inicial inválido.');
            }

            $egreso = Egreso::create([
                'numero' => $this->siguienteNumero(),
                'categoria_egreso_id' => $categoria->id,
                'proveedor_nombre' => $data['proveedor_nombre'] ?? null,
                'producto_id' => $productoId,
                'cantidad_kg' => $cantidad,
                'user_id' => $user->id,
                'moneda_id' => $moneda->id,
                'moneda_pago' => $moneda->codigo,
                'tasa_cambio_id' => $tasaId,
                'tasa_cambio_usada' => $tasaValor,
                'monto' => $monto,
                'fecha_egreso' => $data['fecha_egreso'] ?? now()->toDateString(),
                'fecha_pago' => $estado === Egreso::ESTADO_PAGADO
                    ? ($data['fecha_pago'] ?? now()->toDateString())
                    : null,
                'estado' => $estado,
                'metodo_pago' => $data['metodo_pago'] ?? null,
                'referencia_pago' => $data['referencia_pago'] ?? null,
                'concepto' => $data['concepto'],
            ]);

            Log::info('Egreso creado', [
                'id' => $egreso->id,
                'numero' => $egreso->numero,
                'monto' => $egreso->monto,
                'moneda' => $moneda->codigo,
            ]);

            return $egreso->refresh()->load(['categoria', 'producto', 'moneda', 'user']);
        });
    }

    public function anular(Egreso $egreso, User $user): Egreso
    {
        return DB::transaction(function () use ($egreso, $user) {
            $bloqueado = Egreso::query()->lockForUpdate()->find($egreso->id);

            if (! $bloqueado) {
                throw new DomainException('Egreso no encontrado.');
            }

            if ($bloqueado->estado === Egreso::ESTADO_ANULADO) {
                throw new DomainException('El egreso ya está anulado.');
            }

            if ($bloqueado->producto_id && $bloqueado->cantidad_kg) {
                $producto = Producto::query()->lockForUpdate()->find($bloqueado->producto_id);

                if ($producto) {
                    $cantidad = (float) $bloqueado->cantidad_kg;
                    $stock = (float) $producto->stock_kg;

                    if ($stock < $cantidad) {
                        throw new DomainException(
                            'No se puede anular: el stock de ese producto ya fue vendido. '.
                            "Stock actual {$stock} Kg, cantidad a revertir {$cantidad} Kg."
                        );
                    }

                    $producto->update(['stock_kg' => $stock - $cantidad]);
                }
            }

            $bloqueado->update(['estado' => Egreso::ESTADO_ANULADO]);

            Log::info('Egreso anulado', [
                'id' => $bloqueado->id,
                'numero' => $bloqueado->numero,
                'user_id' => $user->id,
            ]);

            return $bloqueado->refresh();
        });
    }

    public function pagar(Egreso $egreso, array $data, User $user): Egreso
    {
        return DB::transaction(function () use ($egreso, $data, $user) {
            $bloqueado = Egreso::query()->lockForUpdate()->find($egreso->id);

            if (! $bloqueado) {
                throw new DomainException('Egreso no encontrado.');
            }

            if ($bloqueado->estado === Egreso::ESTADO_ANULADO) {
                throw new DomainException('No se puede pagar un egreso anulado.');
            }

            if ($bloqueado->estado === Egreso::ESTADO_PAGADO) {
                throw new DomainException('El egreso ya está pagado.');
            }

            $bloqueado->update([
                'estado' => Egreso::ESTADO_PAGADO,
                'fecha_pago' => $data['fecha_pago'] ?? now()->toDateString(),
                'metodo_pago' => $data['metodo_pago'] ?? $bloqueado->metodo_pago,
                'referencia_pago' => $data['referencia_pago'] ?? $bloqueado->referencia_pago,
            ]);

            Log::info('Egreso pagado', [
                'id' => $bloqueado->id,
                'numero' => $bloqueado->numero,
                'user_id' => $user->id,
            ]);

            return $bloqueado->refresh();
        });
    }

    /**
     * @return array{0: int|null, 1: float|null}
     */
    private function resolverTasa(Moneda $moneda): array
    {
        if ($moneda->esBase()) {
            return [null, null];
        }

        $tasa = TasaCambio::query()
            ->where('moneda_id', $moneda->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();

        if (! $tasa || (float) $tasa->tasa <= 0) {
            throw new DomainException(
                "No hay tasa de cambio vigente para {$moneda->codigo}. Registra una tasa antes de crear el egreso."
            );
        }

        return [$tasa->id, (float) $tasa->tasa];
    }

    private function siguienteNumero(): string
    {
        $ultimo = Egreso::query()->orderByDesc('id')->lockForUpdate()->first();

        $siguiente = 1;

        if ($ultimo && preg_match('/^EGR-(\d+)$/', (string) $ultimo->numero, $coincidencias)) {
            $siguiente = (int) $coincidencias[1] + 1;
        }

        if ($siguiente > 99999999) {
            throw new DomainException('Se alcanzó el límite máximo de numeración de egresos.');
        }

        return 'EGR-'.str_pad((string) $siguiente, 8, '0', STR_PAD_LEFT);
    }
}
