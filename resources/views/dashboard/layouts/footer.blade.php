
<div class="container my-auto">
    <div class="copyright text-center my-auto">
        <span>
            Copyright © Api Fryntiz 2019 por
            <a href="https://fryntiz.es" title="Web de Raúl Caro Pastorino">
                Raúl Caro Pastorino
            </a>
        </span>
    </div>
</div>


<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#app">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                {{-- Title --}}
                <h5 class="modal-title" id="exampleModalLabel">
                    ¿Seguro que quieres desconectar?
                </h5>

                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            {{-- Message --}}
            <div class="modal-body">
                Pulsa el botón "<strong>Salir</strong>" a continuación si está
                listo para finalizar la sesión actual.
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary"
                        type="button"
                        data-dismiss="modal">
                    <i class="fa fa-close"></i>
                    Cancelar
                </button>

                {!! Buttom::logout([
                    'class' => 'btn btn-primary',
                    'text' => 'Salir',
                ]) !!}
            </div>
        </div>
    </div>
</div>
