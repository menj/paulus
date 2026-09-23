<?php
/**
 * Data charts, drawn in the theme's own style from published figures.
 *
 * Each chart is plain HTML (labelled bars with their values written out), so
 * it takes the active colour scheme, reads well in print and screen readers,
 * and needs no script. Articles place one with {{chart:name}}, which build.py
 * turns into [paulus_chart id="name"].
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * The charts: title, note, source and rows. Values are as published.
 *
 * @return array<string, array>
 */
function paulus_charts() {
	$pew_2015 = array( 'Pew Research Center, "The Future of World Religions: Population Growth Projections, 2010–2050," 2 April 2015', 'https://www.pewresearch.org/religion/2015/04/02/religious-projections-2010-2050/' );
	return array(
		'pew-share-2050'     => array(
			'type'   => 'paired',
			'title'  => __( 'Share of the world’s population, 2010 and projected 2050', 'paulus' ),
			'note'   => __( 'Per cent', 'paulus' ),
			'series' => array( '2010', '2050' ),
			'unit'   => '%',
			'dp'     => 1,
			'max'    => 35,
			'rows'   => array(
				array( __( 'Christians', 'paulus' ), array( 31.4, 31.4 ), true ),
				array( __( 'Muslims', 'paulus' ), array( 23.2, 29.7 ), true ),
				array( __( 'Unaffiliated', 'paulus' ), array( 16.4, 13.2 ), false ),
				array( __( 'Hindus', 'paulus' ), array( 15.0, 14.9 ), false ),
				array( __( 'Buddhists', 'paulus' ), array( 7.1, 5.2 ), false ),
			),
			'source' => $pew_2015,
		),
		'pew-switching'      => array(
			'type'   => 'diverging',
			'title'  => __( 'Net change through religious switching, 2010–2050', 'paulus' ),
			'note'   => __( 'Millions of people, projected', 'paulus' ),
			'unit'   => 'M',
			'dp'     => 2,
			'max'    => 70,
			'rows'   => array(
				array( __( 'Unaffiliated', 'paulus' ), 61.49, false ),
				array( __( 'Muslims', 'paulus' ), 3.22, true ),
				array( __( 'Folk religions', 'paulus' ), 2.61, false ),
				array( __( 'Other religions', 'paulus' ), 1.88, false ),
				array( __( 'Hindus', 'paulus' ), 0.01, false ),
				array( __( 'Jews', 'paulus' ), -0.31, false ),
				array( __( 'Buddhists', 'paulus' ), -2.85, false ),
				array( __( 'Christians', 'paulus' ), -66.05, true ),
			),
			'source' => $pew_2015,
		),
		'pew-christian-regions' => array(
			'type'   => 'paired',
			'title'  => __( 'Where the world’s Christians live, 2010 and projected 2050', 'paulus' ),
			'note'   => __( 'Per cent of all Christians', 'paulus' ),
			'series' => array( '2010', '2050' ),
			'unit'   => '%',
			'dp'     => 1,
			'max'    => 40,
			'rows'   => array(
				array( __( 'Sub-Saharan Africa', 'paulus' ), array( 23.9, 38.1 ), true ),
				array( __( 'Latin America and the Caribbean', 'paulus' ), array( 24.5, 22.8 ), false ),
				array( __( 'Europe', 'paulus' ), array( 25.5, 15.6 ), true ),
				array( __( 'Asia and the Pacific', 'paulus' ), array( 13.2, 13.1 ), false ),
				array( __( 'North America', 'paulus' ), array( 12.3, 9.8 ), false ),
				array( __( 'Middle East and North Africa', 'paulus' ), array( 0.6, 0.6 ), true ),
			),
			'source' => array( 'Pew Research Center, "The Future of World Religions: Population Growth Projections, 2010–2050: Christians," 2 April 2015', 'https://www.pewresearch.org/religion/2015/04/02/christians/' ),
		),
		'pew-growth-2060'    => array(
			'type'   => 'bars',
			'title'  => __( 'Projected change in population size, 2015–2060', 'paulus' ),
			'note'   => __( 'Per cent; the line marks growth of the world’s population, 32 per cent', 'paulus' ),
			'unit'   => '%',
			'dp'     => 0,
			'min'    => -10,
			'max'    => 75,
			'mark'   => 32,
			'rows'   => array(
				array( __( 'Muslims', 'paulus' ), 70, true ),
				array( __( 'Christians', 'paulus' ), 34, true ),
				array( __( 'Hindus', 'paulus' ), 27, false ),
				array( __( 'Jews', 'paulus' ), 15, false ),
				array( __( 'Folk religions', 'paulus' ), 5, false ),
				array( __( 'Unaffiliated', 'paulus' ), 3, false ),
				array( __( 'Other religions', 'paulus' ), 0, false ),
				array( __( 'Buddhists', 'paulus' ), -7, false ),
			),
			'source' => array( 'Michael Lipka and Conrad Hackett, "Why Muslims Are the World’s Fastest-Growing Religious Group," Pew Research Center, 6 April 2017', 'https://www.pewresearch.org/short-reads/2017/04/06/why-muslims-are-the-worlds-fastest-growing-religious-group/' ),
		),
	);
}

/**
 * A value as printed on a bar: to the decimals the source prints, a sign
 * on changes, and the unit.
 *
 * @param float  $v      Value.
 * @param string $unit   "%" or "M".
 * @param bool   $signed Whether to show a plus sign.
 * @param int    $dp     Decimal places.
 * @return string
 */
function paulus_chart_value( $v, $unit, $signed = false, $dp = 1 ) {
	$text = number_format_i18n( $v, $dp );
	$text = str_replace( '-', '−', $text );
	if ( $signed && $v > 0 ) {
		$text = '+' . $text;
	}
	return $text . ( '%' === $unit ? '%' : ' M' );
}

/**
 * [paulus_chart id="…"] One chart from paulus_charts().
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function paulus_sc_chart( $atts ) {
	$atts   = shortcode_atts( array( 'id' => '' ), $atts, 'paulus_chart' );
	$charts = paulus_charts();
	if ( ! isset( $charts[ $atts['id'] ] ) ) {
		return '';
	}
	$c    = $charts[ $atts['id'] ];
	$pct  = static function ( $v, $max ) {
		return round( max( 0, min( 100, abs( $v ) / $max * 100 ) ), 2 ) . '%';
	};
	$rows = '';
	foreach ( $c['rows'] as $r ) {
		list( $label, $value, $key ) = $r;
		$cls   = 'paulus-chart__row' . ( $key ? ' is-key' : '' );
		$rows .= '<div class="' . $cls . '"><span class="paulus-chart__label">' . esc_html( $label ) . '</span><span class="paulus-chart__track">';
		if ( 'paired' === $c['type'] ) {
			foreach ( $value as $i => $v ) {
				$rows .= '<span class="paulus-chart__bar paulus-chart__bar--' . ( $i + 1 ) . '" style="--w:' . esc_attr( $pct( $v, $c['max'] ) ) . '"><span class="paulus-chart__val"><span class="screen-reader-text">' . esc_html( $c['series'][ $i ] ) . ': </span>' . esc_html( paulus_chart_value( $v, $c['unit'], false, $c['dp'] ) ) . '</span></span>';
			}
		} elseif ( 'diverging' === $c['type'] ) {
			$side  = $value < 0 ? 'neg' : 'pos';
			$rows .= '<span class="paulus-chart__half paulus-chart__half--neg">' . ( 'neg' === $side ? '<span class="paulus-chart__bar" style="--w:' . esc_attr( $pct( $value, $c['max'] ) ) . '"></span><span class="paulus-chart__val">' . esc_html( paulus_chart_value( $value, $c['unit'], true, $c['dp'] ) ) . '</span>' : '' ) . '</span>'
				. '<span class="paulus-chart__half paulus-chart__half--pos">' . ( 'pos' === $side ? '<span class="paulus-chart__bar" style="--w:' . esc_attr( $pct( $value, $c['max'] ) ) . '"></span><span class="paulus-chart__val">' . esc_html( paulus_chart_value( $value, $c['unit'], true, $c['dp'] ) ) . '</span>' : '' ) . '</span>';
		} else {
			$span  = $c['max'] - $c['min'];
			$zero  = round( -$c['min'] / $span * 100, 2 );
			$left  = $value < 0 ? $zero - abs( $value ) / $span * 100 : $zero;
			$width = abs( $value ) / $span * 100;
			$rows .= '<span class="paulus-chart__zero" style="--x:' . esc_attr( $zero ) . '%" aria-hidden="true"></span>'
				. ( isset( $c['mark'] ) ? '<span class="paulus-chart__mark" style="--x:' . esc_attr( round( ( $c['mark'] - $c['min'] ) / $span * 100, 2 ) ) . '%" aria-hidden="true"></span>' : '' )
				. '<span class="paulus-chart__bar" style="--x:' . esc_attr( round( $left, 2 ) ) . '%;--w:' . esc_attr( round( $width, 2 ) ) . '%"></span><span class="paulus-chart__val' . ( $value < 0 ? ' is-neg' : '' ) . '" style="--x:' . esc_attr( round( $value < 0 ? $left : $left + $width, 2 ) ) . '%">' . esc_html( paulus_chart_value( $value, $c['unit'], true, $c['dp'] ) ) . '</span>';
		}
		$rows .= '</span></div>';
	}
	$legend = '';
	if ( 'paired' === $c['type'] ) {
		$legend = '<p class="paulus-chart__legend" aria-hidden="true"><span class="paulus-chart__key paulus-chart__key--1">' . esc_html( $c['series'][0] ) . '</span><span class="paulus-chart__key paulus-chart__key--2">' . esc_html( $c['series'][1] ) . '</span></p>';
	}
	return '<figure class="paulus-chart paulus-chart--' . esc_attr( $c['type'] ) . '" id="chart-' . esc_attr( $atts['id'] ) . '">'
		. '<figcaption class="paulus-chart__head"><span class="paulus-chart__title">' . esc_html( $c['title'] ) . '</span><span class="paulus-chart__note">' . esc_html( $c['note'] ) . '</span></figcaption>'
		. $legend
		. '<div class="paulus-chart__body">' . $rows . '</div>'
		/* translators: %s: source citation. */
		. '<p class="paulus-chart__source">' . sprintf( esc_html__( 'Source: %s.', 'paulus' ), '<a href="' . esc_url( $c['source'][1] ) . '" rel="noopener" target="_blank">' . esc_html( $c['source'][0] ) . '</a>' ) . '</p>'
		. '</figure>';
}
add_shortcode( 'paulus_chart', 'paulus_sc_chart' );
