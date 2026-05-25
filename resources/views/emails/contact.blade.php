<h2>Nuevo mensaje de contacto</h2>
<p><strong>Nombre:</strong> {{ $contactData['name'] ?? 'No proporcionado' }}</p>
<p><strong>Email:</strong> {{ $contactData['email'] ?? 'No proporcionado' }}</p>
<p><strong>Asunto:</strong> {{ $contactData['subject'] ?? 'Sin asunto' }}</p>
<p><strong>Mensaje:</strong></p>
<p>{{ $contactData['message'] ?? '' }}</p>
