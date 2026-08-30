@extends('layouts.app')

@section('title', 'Newsletter | Api Raupulus')

@section('content')
    <div class="mx-auto my-16 max-w-xl rounded-lg border border-gray-200 p-8 shadow-sm dark:border-gray-700">

        @if (session('newsletter_status') === 'verified')
            <h1 class="mb-4 text-2xl font-bold text-green-700 dark:text-green-400">Suscripción confirmada</h1>
            <p class="text-gray-700 dark:text-gray-300">Ya estás en la lista. Gracias.</p>

        @elseif (session('newsletter_status') === 'unsubscribed')
            <h1 class="mb-4 text-2xl font-bold text-gray-900 dark:text-gray-100">Te has dado de baja</h1>
            <p class="text-gray-700 dark:text-gray-300">No volverás a recibir correos de la newsletter.</p>

        @elseif (session('newsletter_status') === 'invalid_token' || $subscription === null)
            <h1 class="mb-4 text-2xl font-bold text-gray-900 dark:text-gray-100">Enlace no válido</h1>
            <p class="text-gray-700 dark:text-gray-300">
                Este enlace ha caducado o ya se ha usado. Si querías darte de baja y sigues
                recibiendo correos, usa el enlace del último que te haya llegado.
            </p>

        @else
            <h1 class="mb-4 text-2xl font-bold text-gray-900 dark:text-gray-100">Tu suscripción</h1>
            <p class="mb-6 text-gray-700 dark:text-gray-300">
                {{ $subscription->email }}
            </p>

            {{--
                Estos dos botones son POST a propósito. Si fueran enlaces, el
                antivirus del correo los seguiría solo y haría la acción sin que
                nadie la pidiera.
            --}}
            <div class="flex flex-wrap gap-3">
                @if ($isVerificationLink && ! $subscription->is_verified)
                    <form action="{{ route('newsletter.confirm', ['token' => $token]) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="rounded bg-green-600 px-5 py-2 font-semibold text-white hover:bg-green-700">
                            Confirmar suscripción
                        </button>
                    </form>
                @endif

                <form action="{{ route('newsletter.unsubscribe', ['token' => $token]) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="rounded border border-gray-300 px-5 py-2 font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        Darme de baja
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection
