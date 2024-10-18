// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global AW, automatewooDashboardLocalizeScript, _, HTMLElement */

( function ( $ ) {
	const self = {
		$el: $( '.automatewoo-dashboard-widgets' ),

		params: {},

		init() {
			self.params = automatewooDashboardLocalizeScript;
			self.initMasonry();
		},

		initMasonry() {
			self.$el.masonry( {
				itemSelector: '.automatewoo-dashboard-widget',
				columnWidth: '.automatewoo-dashboard-widget-sizer',
				percentPosition: true,
				gutter: 20,
				transitionDuration: '0.2s',
			} );
		},
		/**
		 * Calls `drawGraph` with data translated from WC Analytics API reponse.
		 *
		 * @param {string|HTMLElement} container Chart container element or id, to be forwarded to drawGraph.
		 * @param {Array<object>}      intervals Intervals data object returned from Reports API.
		 * @param {Array<string>}      fields    Fields/metrices to be extracted from the reponse.
		 * @param {Array}              params    Parameters to be forwarded, to be forwarded to drawGraph.
		 */
		drawGraphFromAnalyticsAPI( container, intervals, fields, params ) {
			const translated = [];
			for ( const field of fields ) {
				translated.push(
					intervals.map( ( row ) => [
						new Date( row.interval ).getTime(),
						row.subtotals[ field ],
					] )
				);
			}
			return self.drawGraph( container, translated, params );
		},

		drawGraph( container, data, params ) {
			const $chart = $(
				typeof container === 'string' ? '#' + container : container
			);
			const sets = [];
			const setColors = [ '#3498db', '#d0a0e4', '#72c9b2' ];

			_.each( data, function ( values, index ) {
				const set = {
					label: '',
					data: values,
					color: setColors.shift(),
					points: {
						show: true,
						radius: 3,
						lineWidth: 2,
						fillColor: '#ffffff',
						fill: true,
					},
					lines: {
						show: true,
						lineWidth: 2,
						fill: true,
						fillColor: {
							colors: [ { opacity: 0.02 }, { opacity: 0.16 } ],
						},
					},
					shadowSize: 0,
					isCurrency:
						params.is_currency === true ||
						( params.is_currency && params.is_currency[ index ] ),
				};

				if ( _.size( data ) > 1 ) {
					set.lines.fill = false;
				}

				sets.push( set );
			} );

			const options = {
				legend: {
					show: false,
				},
				grid: {
					color: '#aaa',
					borderColor: 'transparent',
					borderWidth: 0,
					hoverable: true,
				},
				xaxis: {
					color: '#e5e5e5',
					position: 'bottom',
					tickColor: 'transparent',
					mode: 'time',
					monthNames: AW.params.locale.month_abbrev,
					tickLength: 1,
					font: {
						color: '#aaa',
					},
				},
				yaxis: {
					color: '#fff',
					font: {
						color: '#fff',
					},
				},
			};

			if ( Number( params.interval ) === 30 ) {
				options.xaxis.minTickSize = [ 4, 'day' ];
			}

			$.plot( $chart, sets, options );

			$chart.on( 'plothover', function ( event, pos, item ) {
				const $wrap = $chart.parents(
					'.automatewoo-dashboard-chart:first'
				);
				const $tooltip = $chart.siblings(
					'.automatewoo-dashboard-chart__tooltip:first'
				);
				const wrapOffset = $wrap.offset();

				if ( item && item.series.points.show ) {
					let content = item.datapoint[ 1 ];

					if ( item.series.isCurrency ) {
						content = AW.price( content );
					}

					$tooltip
						.html( content )
						.css( {
							top: item.pageY - 9 - wrapOffset.top,
							left: item.pageX + 12 - wrapOffset.left,
						} )
						.fadeIn( 200 );
				} else {
					$tooltip.hide();
				}
			} );
		},
	};

	AW.Dashboard = self;
	self.init();

	// This is overly simplified implementation. Follows the behavior of the previous jQuery widget.
	// The element
	//  - Don't observe nor react to attribute changes.
	//  - Assumes upgrade scenario - attributes are already set once the element is connected;
	//		entire light DOM content is well structured and already present.
	//  - Do not stop, abort, or bother about `fetch` races if the element is reconnected.
	window.customElements.define(
		'automatewoo-dashboard-chart',
		class AWDashboardChart extends HTMLElement {
			connectedCallback() {
				this.setAttribute( 'aw-loading', '' );

				const fields = this.getAttribute( 'fields' ).split( ',' );
				const isCurrency = JSON.parse(
					`[${ this.getAttribute( 'is-currency' ) || '' }]`
				);

				const requestParams = new URLSearchParams( {
					interval: 'day',
					after: this.getAttribute( 'after' ),
					before: this.getAttribute( 'before' ),
					fields,
					per_page: 100,
				} );
				wp.apiFetch( {
					path:
						this.getAttribute( 'endpoint' ) +
						'?' +
						requestParams.toString(),
				} ).then( ( response ) => {
					this.removeAttribute( 'aw-loading' );
					// Draw chart.
					AW.Dashboard.drawGraphFromAnalyticsAPI(
						this.querySelector(
							'automatewoo-dashboard-chart__flot'
						),
						response.intervals,
						fields,
						{
							interval: this.getAttribute( 'interval' ),
							is_currency: isCurrency,
						}
					);
					// Fill totals.
					for ( let index = 0; index < fields.length; index++ ) {
						const key = fields[ index ];
						this.querySelector(
							`automatewoo-dashboard-chart__header-figure[name=${ key }]`
						).innerHTML = isCurrency[ index ]
							? AW.price( response.totals[ key ] )
							: response.totals[ key ];
					}
				} );
			}
		}
	);
} )( jQuery );
