<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareType;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListHardwareEnergies extends ListRecords
{
    protected static string $resource = HardwareEnergyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Las pestañas del listado.
     *
     * Una tabla plana con los ocho elementos mezclados no deja ver nada: el
     * mismo controlador solar sale tres veces —panel, batería y salida de
     * carga— entre los consumos sueltos de los demás aparatos.
     *
     * **Ninguna pestaña esconde nada**: «Todos» sigue enseñando el listado
     * entero y las demás son vistas del mismo. Un elemento de un controlador
     * solar sale en «Energía solar» *y* en la de su papel, que es donde se le
     * busca cuando lo que se quiere comparar son los generadores.
     *
     * Sólo aparece la pestaña que tiene algo dentro: con una instalación sin
     * baterías, «Baterías» sobra.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $total = $this->contar();

        $pestanas = [
            'todos' => Tab::make('Todos')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->badge($total),
        ];

        $solares = $this->contar(self::deControladorSolar(...));

        if ($solares > 0) {
            $pestanas['solar'] = Tab::make('Energía solar')
                ->icon(Heroicon::OutlinedSun)
                ->badge($solares)
                ->modifyQueryUsing(self::deControladorSolar(...));
        }

        foreach (HardwareEnergy::ROLE_LABELS_PLURAL as $papel => $etiqueta) {
            $cuantos = $this->contar(
                static fn (Builder $query): Builder => $query->where('role', $papel),
            );

            if ($cuantos === 0) {
                continue;
            }

            $pestanas[$papel] = Tab::make($etiqueta)
                ->icon(self::ICONOS_POR_PAPEL[$papel])
                ->badge($cuantos)
                ->modifyQueryUsing(
                    static fn (Builder $query): Builder => $query->where('role', $papel),
                );
        }

        return $pestanas;
    }

    /**
     * Elementos medidos por un controlador solar.
     *
     * El criterio es el **tipo del aparato que mide**, no el tipo de fuente:
     * hoy casi todos los elementos reales tienen fuente «Fotovoltaica» y ésa no
     * separa nada.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    private static function deControladorSolar(Builder $query): Builder
    {
        return $query->whereHas(
            'hardwareDevice',
            static fn (Builder $aparato) => $aparato->whereHas(
                'type',
                static fn (Builder $tipo) => $tipo->where('slug', HardwareType::SOLAR_CONTROLLER_SLUG),
            ),
        );
    }

    /**
     * @var array<string, Heroicon>
     */
    private const ICONOS_POR_PAPEL = [
        HardwareEnergy::ROLE_GENERATOR => Heroicon::OutlinedBolt,
        HardwareEnergy::ROLE_LOAD => Heroicon::OutlinedArrowTrendingDown,
        HardwareEnergy::ROLE_BATTERY => Heroicon::OutlinedBattery100,
    ];

    /**
     * Cuenta sobre la consulta del recurso, que ya lleva el filtro por
     * propietario: si no, los números de las pestañas contarían elementos que
     * la tabla no enseña.
     *
     * @param  (callable(Builder<covariant \Illuminate\Database\Eloquent\Model>): Builder<covariant \Illuminate\Database\Eloquent\Model>)|null  $filtro
     */
    private function contar(?callable $filtro = null): int
    {
        $query = static::getResource()::getEloquentQuery();

        return ($filtro === null ? $query : $filtro($query))->count();
    }
}
