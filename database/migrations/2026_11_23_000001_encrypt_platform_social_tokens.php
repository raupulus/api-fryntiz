<?php

declare(strict_types=1);

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amplía las columnas de credenciales sociales a TEXT para alojar el payload
     * cifrado de Laravel y cifra de forma idempotente los tokens en claro existentes.
     */
    public function up(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->text('twitter_token')->nullable()->comment('Token cifrado de publicación para Twitter/X')->change();
            $table->text('mastodon_token')->nullable()->comment('Token cifrado de publicación para Mastodon')->change();
        });

        DB::table('platforms')
            ->select(['id', 'twitter_token', 'mastodon_token'])
            ->where(function ($query): void {
                $query->whereNotNull('twitter_token')
                    ->orWhereNotNull('mastodon_token');
            })
            ->orderBy('id')
            ->each(function (object $platform): void {
                $updates = [];

                foreach (['twitter_token', 'mastodon_token'] as $column) {
                    $value = $platform->{$column};

                    if (! is_string($value) || $value === '') {
                        continue;
                    }

                    try {
                        Crypt::decryptString($value);
                    } catch (DecryptException) {
                        $updates[$column] = Crypt::encryptString($value);
                    }
                }

                if ($updates !== []) {
                    DB::table('platforms')->where('id', $platform->id)->update($updates);
                }
            });
    }

    /**
     * Revierte el tipo de columna sin descifrar credenciales sensibles a texto plano.
     */
    public function down(): void
    {
        // No se descifran credenciales en el rollback por seguridad; el tipo TEXT es compatible.
    }
};
