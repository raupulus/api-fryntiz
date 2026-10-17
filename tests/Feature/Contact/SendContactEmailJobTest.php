<?php

declare(strict_types=1);

namespace Tests\Feature\Contact;

use App\Jobs\SendContactEmailJob;
use App\Mail\ContactMail;
use App\Models\Email;
use Database\Seeders\LanguagesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A qué buzón va un mensaje de contacto que no es de un usuario concreto.
 *
 * `MAIL_ADMIN_ADDRESS` estaba en los .env de ejemplo, pero `config/mail.php`
 * no tenía la clave y nunca se leía: todo iba a `MAIL_FROM_ADDRESS`.
 */
class SendContactEmailJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new LanguagesTableSeeder)->run();
    }

    #[Test]
    public function the_admin_mailbox_comes_from_mail_admin_address(): void
    {
        $_ENV['MAIL_ADMIN_ADDRESS'] = $_SERVER['MAIL_ADMIN_ADDRESS'] = 'buzon@raupulus.test';

        try {
            $this->assertSame('buzon@raupulus.test', (require config_path('mail.php'))['admin_address'] ?? null);
        } finally {
            unset($_ENV['MAIL_ADMIN_ADDRESS'], $_SERVER['MAIL_ADMIN_ADDRESS']);
        }
    }

    #[Test]
    public function without_a_user_it_goes_to_the_admin_mailbox(): void
    {
        Mail::fake();
        Config::set('mail.admin_address', 'admin@raupulus.test');

        (new SendContactEmailJob($this->message()->id))->handle();

        Mail::assertQueued(ContactMail::class, fn (ContactMail $mail): bool => $mail->hasTo('admin@raupulus.test'));
    }

    #[Test]
    public function without_an_admin_mailbox_it_goes_to_the_from_address(): void
    {
        Mail::fake();
        Config::set('mail.admin_address', null);
        Config::set('mail.from.address', 'noreply@raupulus.test');

        (new SendContactEmailJob($this->message()->id))->handle();

        Mail::assertQueued(ContactMail::class, fn (ContactMail $mail): bool => $mail->hasTo('noreply@raupulus.test'));
    }

    private function message(): Email
    {
        return Email::query()->create([
            'email' => 'visitante@example.test',
            'subject' => 'Hola',
            'message' => 'Un mensaje de contacto de prueba.',
            'send' => true,
            // El idioma por defecto es el 1, y entre pruebas la secuencia sigue.
            'language_id' => DB::table('languages')->value('id'),
        ]);
    }
}
