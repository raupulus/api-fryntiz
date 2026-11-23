@extends('layouts.app')

@section('title', 'Gestión de suscripción | Newsletter | Api Raupulus')
@section('noindex', '1')

@section('content')
    <section class="pt-28 pb-16 px-4 sm:px-6 bg-surface min-h-[60svh]">
        <div class="mx-auto max-w-xl rounded-xl border border-outline-variant/25 bg-surface-container-lowest p-8 shadow-lg">

            @if (session('newsletter_status') === 'verified')
                <h1 class="mb-4 text-2xl font-bold text-success">Suscripción confirmada</h1>
                <p class="text-on-surface-variant mb-6">Ya estás en la lista. Gracias.</p>
                <x-button :href="route('home')">Volver a la portada</x-button>

            @elseif (session('newsletter_status') === 'unsubscribed')
                <h1 class="mb-4 text-2xl font-bold text-on-surface">Te has dado de baja</h1>
                <p class="text-on-surface-variant mb-6">No volverás a recibir correos de la newsletter.</p>
                <x-button :href="route('home')">Volver a la portada</x-button>

            @elseif (session('newsletter_status') === 'invalid_token' || $subscription === null)
                <h1 class="mb-4 text-2xl font-bold text-on-surface">Enlace no válido</h1>
                <p class="text-on-surface-variant mb-6">
                    Este enlace ha caducado o ya se ha usado. Si querías darte de baja y sigues
                    recibiendo correos, usa el enlace del último que te haya llegado.
                </p>
                <x-button :href="route('home')">Volver a la portada</x-button>

            @else
                <h1 class="mb-4 text-2xl font-bold text-on-surface">Tu suscripción</h1>
                <p class="mb-6 text-on-surface-variant break-all">
                    {{ $subscription->email }}
                </p>

                {{--
                    Estos dos botones son POST a propósito. Si fueran enlaces, el
                    antivirus del correo los seguiría solo y haría la acción sin que
                    nadie la pidiera.
                --}}
                <div class="flex flex-wrap gap-3" x-data="{ submitting: false }">
                    @if ($isVerificationLink && ! $subscription->is_verified)
                        <form action="{{ route('newsletter.confirm', ['token' => $token]) }}" method="POST" @submit="submitting = true">
                            @csrf
                            <x-button type="submit" variant="success" ::disabled="submitting">
                                <span x-text="submitting ? 'Procesando...' : 'Confirmar suscripción'">Confirmar suscripción</span>
                            </x-button>
                        </form>
                    @endif

                    <form action="{{ route('newsletter.unsubscribe', ['token' => $token]) }}" method="POST" @submit="submitting = true">
                        @csrf
                        <x-button type="submit" variant="outline" ::disabled="submitting">
                            <span x-text="submitting ? 'Procesando...' : 'Darme de baja'">Darme de baja</span>
                        </x-button>
                    </form>
                </div>
            @endif
        </div>
    </section>
@endsection
