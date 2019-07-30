/*
require( 'jszip' );
require( 'pdfmake' );
 */
//require( 'datatables.net-bs4' )();
//require( 'datatables.net-editor-bs4' )();
/*
require( 'datatables.net-autofill-bs4' )();
require( 'datatables.net-buttons-bs4' )();
require( 'datatables.net-buttons/js/buttons.colVis.js' )();
require( 'datatables.net-buttons/js/buttons.flash.js' )();
require( 'datatables.net-buttons/js/buttons.html5.js' )();
require( 'datatables.net-buttons/js/buttons.print.js' )();
require( 'datatables.net-colreorder-bs4' )();
require( 'datatables.net-fixedcolumns-bs4' )();
require( 'datatables.net-fixedheader-bs4' )();
require( 'datatables.net-keytable-bs4' )();
require( 'datatables.net-responsive-bs4' )();
require( 'datatables.net-rowgroup-bs4' )();
require( 'datatables.net-rowreorder-bs4' )();
require( 'datatables.net-scroller-bs4' )();
require( 'datatables.net-select-bs4' )();
*/
//var dt = require( 'datatables.net-bs4' );

try {
  window.$ = window.jQuery = require('jquery');
  window.Popper = require('popper.js').default;

  require('bootstrap');

  window.JSZip = require("jszip");
  require( "pdfmake" );
  window.DataTable = require( 'datatables.net' );
  require( 'datatables.net-bs4' );
  require( 'datatables.net-buttons-bs4' );
  require( 'datatables.net-buttons/js/buttons.colVis.js' );
  require( 'datatables.net-buttons/js/buttons.flash.js' );
  require( 'datatables.net-buttons/js/buttons.html5.js' );
  require( 'datatables.net-buttons/js/buttons.print.js' );
  require( 'datatables.net-colreorder-bs4' );
  require( 'datatables.net-fixedcolumns-bs4' );
  require( 'datatables.net-responsive-bs4' );
  require( 'datatables.net-rowreorder-bs4' );
  require( 'datatables.net-scroller-bs4' );
  require( 'datatables.net-keytable' );
  require( 'datatables.net-rowgroup' );

} catch (e) {}
