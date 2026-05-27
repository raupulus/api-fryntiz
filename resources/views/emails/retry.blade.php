<div style="font-family:Arial,sans-serif">
    <p>{!! nl2br(e($email->message)) !!}</p>
    <hr/>
    <small>Enviado desde {{ $email->app_name }} ({{ $email->app_domain }})</small>
</div>
