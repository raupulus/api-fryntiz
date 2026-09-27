<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\ContentSectionPage;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\SavesFromTheHeader;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * «Visibilidad» (E7 de la auditoría de contenidos; F7 del plan del
 * 2026-09-24): todos los interruptores, con un nombre claro y una línea de
 * ayuda. La API los manda a las webs (F9), también los de comentarios, que irán
 * en el contrato aunque todavía no haya comentarios.
 */
class EditContentVisibility extends EditRecord
{
    use ContentSectionPage;
    use SavesFromTheHeader;

    protected static string $resource = ContentResource::class;

    protected static ?string $navigationLabel = 'Visibilidad';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    public static bool $formActionsAreSticky = true;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Publicación')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_active')->label('Activo')
                        ->helperText('A las webs sólo va lo publicado y activo. Desmarcarlo retira un contenido publicado sin borrarlo.'),
                    Toggle::make('is_featured')->label('Destacado')
                        ->helperText('Sale entre los destacados de su plataforma.'),
                    DateTimePicker::make('published_at')->label('Publicado el')
                        ->disabled()
                        ->helperText('Se pone sola al publicar: a mano, con la acción «Publicar» o al llegar la fecha programada.'),
                ]),

            Section::make('Dónde se enseña')
                ->description('Cada web decide qué hace con cada uno.')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_visible_on_home')->label('En la portada')
                        ->helperText('En la página de inicio de la web.'),
                    Toggle::make('is_visible_on_menu')->label('En el menú')
                        ->helperText('Enlazado desde el menú principal.'),
                    Toggle::make('is_visible_on_footer')->label('En el pie')
                        ->helperText('Enlazado desde el pie de página.'),
                    Toggle::make('is_visible_on_sidebar')->label('En la barra lateral')
                        ->helperText('En la columna lateral, si la web la tiene.'),
                    Toggle::make('is_visible_on_search')->label('En el buscador')
                        ->helperText('Aparece al buscar dentro de la web.'),
                    Toggle::make('is_visible_on_archive')->label('En el archivo')
                        ->helperText('En los listados por fecha, categoría o etiqueta.'),
                    Toggle::make('is_visible_on_rss')->label('En el RSS')
                        ->helperText('En el canal RSS de la web.'),
                    Toggle::make('is_visible_on_sitemap')->label('En el sitemap')
                        ->helperText('En el mapa del sitio para los buscadores.'),
                    Toggle::make('is_visible_on_sitemap_news')->label('En el sitemap de noticias')
                        ->helperText('En el de Google Noticias: sólo para noticias recientes.'),
                ]),

            Section::make('Comentarios y derechos')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_comment_enabled')->label('Permitir comentarios')
                        ->helperText('Va en la API; los comentarios llegarán más adelante.'),
                    Toggle::make('is_comment_anonymous')->label('Comentarios anónimos')
                        ->helperText('Sin iniciar sesión, si se permiten comentarios.'),
                    // Tres estados: la columna vacía es «sin comprobar», y un
                    // interruptor la convertiría en «no» al guardar.
                    ToggleButtons::make('is_copyright_valid')->label('Derechos de autor')
                        ->boolean('Comprobados, sin problemas', 'Hay material con derechos de otros')
                        ->grouped()
                        ->columnSpanFull()
                        ->helperText('Sin marcar: no se ha comprobado.'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [$this->saveOnTopAction(), $this->previewAction()];
    }
}
