/**
 * Añado efecto difuminado al cerrar alert.
 */
$('.alert-dismissable').click(function (e) {
    $(this).fadeOut('slow');
});
