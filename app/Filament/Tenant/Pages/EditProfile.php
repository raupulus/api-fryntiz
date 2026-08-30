<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Filament\Concerns\EditsOwnProfile;
use BackedEnum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * «Mis datos» del panel de usuario.
 *
 * Es la mitad de lo que faltaba del panel `tenant`: los dos grupos de
 * navegación —«Mi Cuenta» y «Dispositivos»— estaban declarados desde el
 * principio y no los había desarrollado nadie. El panel existía con una única
 * página vacía.
 *
 * Mismo formulario que el del panel de administración, por el trait.
 */
class EditProfile extends Page implements HasForms
{
    use EditsOwnProfile;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Mi Cuenta';

    protected static ?string $title = 'Mis datos';

    protected static ?string $slug = 'mis-datos';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.admin.pages.profile';

    /**
     * Delega en el trait. Ver `EditsOwnProfile::profileSchema()` para el
     * porqué de que el trait no defina `form()` directamente.
     */
    public function form(Schema $schema): Schema
    {
        return $this->profileSchema($schema);
    }
}
