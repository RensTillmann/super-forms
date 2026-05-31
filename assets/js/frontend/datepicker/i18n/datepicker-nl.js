/* Dutch (UTF-8) initialisation for the jQuery UI date picker plugin. */
/* Written by Mathias Bynens <http://mathiasbynens.be/> */
( function( factory ) {
	// eslint-disable-next-line no-undef
	if ( typeof define === "function" && define.amd ) {
		// AMD. Register as an anonymous module.
		// eslint-disable-next-line no-undef
		define( [ "../widgets/datepicker" ], factory );
	} else {
		// Browser globals
		// eslint-disable-next-line no-undef
		factory( jQuery.datepicker );
	}
}( function( datepicker ) {

datepicker.regional.nl = {
	closeText: "Sluiten",
	prevText: "←",
	nextText: "→",
	currentText: "Vandaag",
	monthNames: [ "januari", "februari", "maart", "april", "mei", "juni",
	"juli", "augustus", "september", "oktober", "november", "december" ],
	monthNamesShort: [ "jan", "feb", "mrt", "apr", "mei", "jun",
	"jul", "aug", "sep", "okt", "nov", "dec" ],
	dayNames: [ "zondag", "maandag", "dinsdag", "woensdag", "donderdag", "vrijdag", "zaterdag" ],
	dayNamesShort: [ "zon", "maa", "din", "woe", "don", "vri", "zat" ],
	dayNamesMin: [ "zo", "ma", "di", "wo", "do", "vr", "za" ],
	weekHeader: "Wk",
	dateFormat: "dd-mm-yy",
	firstDay: 1,
	isRTL: false,
	showMonthAfterYear: false,
	yearSuffix: "" };
datepicker.setDefaults( datepicker.regional.nl );

return datepicker.regional.nl;

} ) );
