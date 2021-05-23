'use strict';

/**
 * Conmuta una clase para el elemento recibido. La quita si la tiene o la pone
 * en caso de no tenerla aún.
 *
 * @param selector El selector al que se le cambia la clase.
 * @param name La clase que se asignará, por defecto 'hidden'.
 */
export function toggle(selector, name = 'hidden') {
    let elements = document.querySelectorAll(selector);

    if (elements && elements.length) {
        Array.from(elements).forEach((ele) => {
            let isHidden = ele.classList.contains(name);
            isHidden ? ele.classList.remove(name) : ele.classList.add(name);
        });
    }
}
