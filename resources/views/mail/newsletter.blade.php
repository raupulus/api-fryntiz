@extends('mail.layouts._mail_base')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">

        @if($type === 'verification')
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2c3e50; margin-bottom: 15px;">
                    ¡Confirma tu suscripción!
                </h2>

                <p style="color: #34495e; font-size: 16px; line-height: 1.6;">
                    Hola{{ $newsletter->name ? ' ' . $newsletter->name : '' }},
                </p>

                <p style="color: #34495e; font-size: 16px; line-height: 1.6;">
                    Gracias por suscribirte a la newsletter de <strong>{{ config('app.name') }}</strong>.
                    Para completar tu suscripción, necesitamos que confirmes tu dirección de email.
                </p>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $actionUrl }}"
                       style="background-color: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                        {{ $actionText }}
                    </a>
                </div>

                <p style="color: #7f8c8d; font-size: 14px; line-height: 1.6;">
                    Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:
                </p>

                <p style="color: #3498db; font-size: 14px; word-break: break-all;">
                    {{ $actionUrl }}
                </p>
            </div>

        @elseif($type === 'unsubscribe')
            <div style="background-color: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #856404; margin-bottom: 15px;">
                    ¿Deseas desuscribirte?
                </h2>

                <p style="color: #856404; font-size: 16px; line-height: 1.6;">
                    Hola{{ $newsletter->name ? ' ' . $newsletter->name : '' }},
                </p>

                <p style="color: #856404; font-size: 16px; line-height: 1.6;">
                    Hemos recibido una solicitud para desuscribirte de la newsletter de <strong>{{ config('app.name') }}</strong>.
                </p>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $actionUrl }}"
                       style="background-color: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                        {{ $actionText }}
                    </a>
                </div>

                <p style="color: #6c757d; font-size: 14px; line-height: 1.6;">
                    Si no solicitaste esta desuscripción, puedes ignorar este email.
                </p>
            </div>

        @elseif($type === 'content')
            <!-- Aquí irá el contenido de la newsletter en el futuro -->
            <div style="background-color: #e8f5e8; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h2 style="color: #2e7d32; margin-bottom: 15px;">
                    Newsletter de {{ config('app.name') }}
                </h2>

                <p style="color: #2e7d32; font-size: 16px; line-height: 1.6;">
                    Hola{{ $newsletter->name ? ' ' . $newsletter->name : '' }},
                </p>

                <div style="color: #2e7d32; font-size: 16px; line-height: 1.6;">
                    <!-- Contenido dinámico de la newsletter -->
                    @if(isset($content))
                        {!! $content !!}
                    @else
                        <p>Aquí irá el contenido de la newsletter.</p>
                    @endif
                </div>
            </div>
        @endif

        <!-- Información adicional -->
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
            <p style="color: #6c757d; font-size: 14px; margin-bottom: 10px;">
                <strong>Detalles de la suscripción:</strong>
            </p>
            <ul style="color: #6c757d; font-size: 14px; margin: 0; padding-left: 20px;">
                <li>Email: {{ $newsletter->email }}</li>
                <li>Plataforma: {{ $newsletter->platform->name ?? 'No especificada' }}</li>
                <li>Idioma: {{ strtoupper($newsletter->language) }}</li>
                <li>Fecha de suscripción: {{ $newsletter->created_at->format('d/m/Y H:i') }}</li>
            </ul>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p style="color: #6c757d; font-size: 12px; margin-bottom: 10px;">
                Este es un mensaje automático de <strong>{{ config('app.name') }}</strong>
            </p>

            <p style="color: #6c757d; font-size: 12px; margin-bottom: 10px;">
                <a href="{{ config('app.url') }}" style="color: #3498db; text-decoration: none;">
                    {{ config('app.url') }}
                </a>
            </p>

            @if($type !== 'unsubscribe')
                <p style="color: #6c757d; font-size: 12px;">
                    Si no deseas recibir más emails, puedes
                    <a href="{{ $newsletter->getUnsubscribeUrl() }}" style="color: #3498db; text-decoration: none;">
                        desuscribirte aquí
                    </a>
                </p>
            @endif
        </div>
    </div>
@endsection
