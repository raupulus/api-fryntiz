@include('mail.layouts._mail_head')

<body>
    @yield('content')

    <br /> <hr /> <br />

    @include('mail.layouts._mail_footer')

    <br /> <hr /> <br />

    @include('mail.layouts._mail_legal')

    @include('mail.layouts._mail_footer_assets')
</body>
