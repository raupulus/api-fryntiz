<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modernización del módulo de galerías:
 * - Añade relación de aspecto (aspect_ratio) a galleries.
 * - Añade orden (order) y pie de foto (caption) a gallery_images.
 * - Elimina la tabla fija content_galleries y crea la tabla polimórfica galleryables.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ampliar galleries con relación de aspecto
        Schema::table('galleries', function (Blueprint $table) {
            $table->string('aspect_ratio', 10)
                ->default('16:9')
                ->comment('Relación de aspecto requerida para las imágenes de la galería (16:9, 4:3, 1:1, free)')
                ->index();
        });

        // 2. Ampliar gallery_images con orden y pie de foto
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->unsignedInteger('order')
                ->default(0)
                ->comment('Orden numérico de visualización dentro de la galería')
                ->index();
            $table->string('caption', 511)
                ->nullable()
                ->comment('Pie de foto o descripción corta de la imagen');
        });

        // 3. Eliminar tabla fija y vacía content_galleries
        Schema::dropIfExists('content_galleries');

        // 4. Crear tabla polimórfica galleryables
        Schema::create('galleryables', function (Blueprint $table) {
            $table->comment('Asociaciones polimórficas entre galerías y entidades de la plataforma (contenidos, páginas, hardware, etc.).');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('gallery_id')->comment('Clave foránea de la galería');
            $table->foreign('gallery_id')
                ->references('id')
                ->on('galleries')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->string('galleryable_type', 255)->comment('Tipo de modelo relacionado (polimórfico)');
            $table->unsignedBigInteger('galleryable_id')->comment('ID del modelo relacionado (polimórfico)');
            $table->unsignedInteger('order')->default(0)->comment('Orden de la galería respecto a la entidad asociada');

            $table->timestamps();

            $table->unique(['gallery_id', 'galleryable_type', 'galleryable_id'], 'galleryables_unique');
            $table->index(['galleryable_type', 'galleryable_id'], 'galleryables_morph_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galleryables');

        Schema::create('content_galleries', function (Blueprint $table) {
            $table->comment('Galerías de imágenes asociadas al contenido');
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('gallery_id')->nullable()->comment('FK a la galería que pertenezca');
            $table->foreign('gallery_id')->references('id')->on('galleries')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->unsignedBigInteger('content_id')->nullable()->comment('FK al contenido que se asocia con la galería');
            $table->foreign('content_id')->references('id')->on('contents')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('gallery_images', function (Blueprint $table) {
            $table->dropIndex(['order']);
            $table->dropColumn(['order', 'caption']);
        });

        Schema::table('galleries', function (Blueprint $table) {
            $table->dropIndex(['aspect_ratio']);
            $table->dropColumn('aspect_ratio');
        });
    }
};
