@extends('mail.layouts._mail_base')

@section('content')
    <div id="box-primary">
        Este es un mensaje automático de la aplicación web:
        <span style="color: #f99900; text-shadow: 1px 1px 1px #000;
        text-shadow: -1px 1px 1px #000; text-shadow: 1px -1px 1px #000;
        text-shadow: -1px -1px 1px #000;">
            <strong>
                {{config('app.name')}}
            </strong>
        </span>
        desde la dirección:
        <strong>
            <a href="{{config('app.url')}}" title="{{config('app.name')}}">
                {{config('app.url')}}
            </a>
        </strong>

        <br />

        Has recibido este mensaje desde el formulario de contacto.
    </div>

    <hr />

    <div id="box-content">
        <h2 style="margin: auto">Mensaje</h2>

        <div>
            {{ $data['message'] }}
        </div>
    </div>

    <hr />

    <div id="box-info">
        <div>
            <h2>Información adicional sobre el contacto</h2>
        </div>

        <div>
            <table cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Dato</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Nombre</td>
                        <td>{{ $data['name'] }}</td>
                    </tr>

                    <tr>
                        <td>Email</td>
                        <td>
                            <a href="mailto:{{ $data['email'] }}">
                                {{ $data['email'] }}
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>Teléfono</td>
                        <td>{{ $data['phone'] }}</td>
                    </tr>

                    <tr>
                        <td>¿Quiere que se contacte?</td>
                        <td>{{ $data['contactme'] == 'on' ? 'Sí' : 'No' }}</td>
                    </tr>

                    <tr>
                        <td>Ip servidor</td>
                        <td>{{ $data['server_ip'] }}</td>
                    </tr>

                    <tr>
                        <td>Ip cliente</td>
                        <td>{{ $data['client_ip'] }}</td>
                    </tr>

                    <tr>
                        <td>Puntuación confianza ReCaptchaV3</td>
                        <td>{{ $data['recaptcha_score'] }}</td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>
@endsection
