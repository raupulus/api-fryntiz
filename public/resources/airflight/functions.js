/**
 * Añade una o más clases al selector.
 * 
 * @param string selector Selector en formato css.
 * @param  {...string} classNames Lista de clases para añadir.
 */
function addClass(selector, ...classNames) {
    let elements = document.querySelectorAll(selector);

    Array.from(elements).forEach((ele) => {
        classNames.forEach((className) => {
            ele.classList.add(className);
        });
    });
}

/**
 * Añade los estilos al selector.
 * 
 * @param string selector Selector en formato css
 * @param object styles Objeto con parejas de clave valor: {'color': '#fff'}
 */
function addCss(selector, styles) {
    let elements = document.querySelectorAll(selector);

    Array.from(elements).forEach((ele) => {
        Object.keys(styles).forEach((style) => {
            ele.style[style] = styles[style];
        });
    });
}

/**
 * Añade los atributos al selector.
 * 
 * @param string selector Selector en formato css
 * @param object styles Objeto con parejas de clave valor: {'atributo': 'valor'}
 */
function addAtt(selector, atts) {
    let elements = document.querySelectorAll(selector);

    Array.from(elements).forEach((ele) => {
        Object.keys(atts).forEach((att) => {
            ele.setAttribute(att, atts[att]);
        });
    });
}