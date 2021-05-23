'use strict';

import {toggle} from "./functions";

/**
 * Prepara las acciones del navbar.
 */
function prepareNavbar() {
    let btnsMenu = document.getElementsByClassName('btn-toggle-nav-menu ');

    // Añade evento al pulsar para desplegar el menú.
    Array.from(btnsMenu).forEach((ele) => {
        ele.addEventListener('click', () => {
                toggle('.box-nav-menu-mobile');
            }
        );
    });
}

window.addEventListener('DOMContentLoaded', () => {
    prepareNavbar();
});
