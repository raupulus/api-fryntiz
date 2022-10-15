// --------------------------------------------------------
//
// This file is to configure the configurable settings.
// Load this file before script.js file at gmap.html.
//
// --------------------------------------------------------

// -- Title Settings --------------------------------------
// Show number of aircraft and/or messages per second in the page title
PlaneCountInTitle = true;
MessageRateInTitle = false;

// -- Output Settings -------------------------------------
// Show metric values
// The Metric setting controls whether metric (m, km, km/h) or
// imperial (ft, NM, knots) units are used in the plane table
// and in the detailed plane info. If ShowOtherUnits is true,
// then the other unit will also be shown in the detailed plane
// info.
Metric = false;
ShowOtherUnits = true;

// -- Map settings ----------------------------------------
// These settings are overridden by any position information
// provided by dump1090 itself. All positions are in decimal
// degrees.

// Default center of the map.
DefaultCenterLat = 36.7381;
DefaultCenterLon = -6.4301;
// The google maps zoom level, 0 - 16, lower is further out
DefaultZoomLvl   = 7;

// Center marker. If dump1090 provides a receiver location,
// that location is used and these settings are ignored.

SiteShow    = true;           // true to show a center marker
SiteLat     = 36.7381;            // position of the marker
SiteLon     = -6.4301;
SiteName    = "Vuelos en Chipiona"; // tooltip of the marker

// -- Marker settings -------------------------------------

// These settings control the coloring of aircraft by altitude.
// All color values are given as Hue (0-359) / Saturation (0-100) / Lightness (0-100)
ColorByAlt = {
        // HSL for planes with unknown altitude:
        unknown : { h: 0,   s: 0,   l: 40 },

        // HSL for planes that are on the ground:
        ground  : { h: 120, s: 100, l: 30 },

        air : {
                // These define altitude-to-hue mappings
                // at particular altitudes; the hue
                // for intermediate altitudes that lie
                // between the provided altitudes is linearly
                // interpolated.
                //
                // Mappings must be provided in increasing
                // order of altitude.
                //
                // Altitudes below the first entry use the
                // hue of the first entry; altitudes above
                // the last entry use the hue of the last
                // entry.
                h: [ { alt: 2000,  val: 20 },    // orange
                     { alt: 10000, val: 140 },   // light green
                     { alt: 40000, val: 300 } ], // magenta
                s: 85,
                l: 50,
        },

        // Changes added to the color of the currently selected plane
        selected : { h: 0, s: -10, l: +20 },

        // Changes added to the color of planes that have stale position info
        stale :    { h: 0, s: -10, l: +30 },

        // Changes added to the color of planes that have positions from mlat
        mlat :     { h: 0, s: -10, l: -10 }
};

// For a monochrome display try this:
// ColorByAlt = {
//         unknown :  { h: 0, s: 0, l: 40 },
//         ground  :  { h: 0, s: 0, l: 30 },
//         air :      { h: [ { alt: 0, val: 0 } ], s: 0, l: 50 },
//         selected : { h: 0, s: 0, l: +30 },
//         stale :    { h: 0, s: 0, l: +30 },
//         mlat :     { h: 0, s: 0, l: -10 }
// };

// Outline color for aircraft icons with an ADS-B position
OutlineADSBColor = '#000000';

// Outline color for aircraft icons with a mlat position
OutlineMlatColor = '#4040FF';

SiteCircles = true; // true to show circles (only shown if the center marker is shown)
// In nautical miles or km (depending settings value 'Metric')
SiteCirclesDistances = new Array(20, 50, 100);

// Controls page title, righthand pane when nothing is selected
PageName = "Vuelos en Chipiona";

// Show country flags by ICAO addresses?
ShowFlags = true;

// Path to country flags (can be a relative or absolute URL; include a trailing /)
FlagPath = "https://api.fryntiz.dev/resources/airflight/flags-tiny/";

// Set to true to enable the ChartBundle base layers (US coverage only)
ChartBundleLayers = true;

var urlAircrafts = 'https://api.fryntiz.dev/api/airflight/v1/get/aircrafts/json';
var urlHistory = 'https://api.fryntiz.dev/api/airflight/v1/get/history/json';
var urlFlags = 'https://api.fryntiz.dev/resources/airflight/flags-tiny/';
var urlReceiver = 'https://api.fryntiz.dev/api/airflight/v1/get/receiver/json';
var urlUpInTheHair = 'https://api.fryntiz.dev/api/airflight/v1/upintheair.json';
var urlDb = 'https://api.fryntiz.dev/api/airflight/v1/get/db/json';


// Mensajes
var messageProblemAjax = 'Se está demorando demasiado en sincronizar los vuelos, está conectado a internet?'


// Tiempo hasta borrar historial.
var historyTimeToDelete = 600;

// Indica si activo el modo DEBUG
const DEBUG = true;


var Dump1090Version = "unknown version";
var RefreshInterval = 5000;
