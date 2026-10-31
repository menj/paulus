<?php
/**
 * Self-test for inc/security-detector.php. Run: php tests/security-detector-test.php
 * Exit code 0 when every item the theme ships (posts, pages, Journal entries
 * and sections in content/manifest.json, with their texts in content/articles)
 * scores zero, and every sample of the October 2026 spam is caught.
 *
 * @package Paulus
 */

define( 'PAULUS_SECURITY_TEST', true );
define( 'ABSPATH', __DIR__ . '/' );
if ( ! function_exists( '__' ) ) {
	/**
	 * Stand-in for the WordPress translation call the manifest uses.
	 *
	 * @param string $s Text.
	 * @return string
	 */
	function __( $s ) {
		return $s;
	}
}
require dirname( __DIR__ ) . '/inc/security-detector.php';

$root     = dirname( __DIR__ );
$manifest = json_decode( (string) file_get_contents( $root . '/content/manifest.json' ), true );
$fail     = 0;
$n        = 0;

/**
 * Body of a shipped content file.
 *
 * @param string $root Theme folder.
 * @param string $file File name.
 * @return string
 */
function paulus_test_body( $root, $file ) {
	$path = $root . '/content/articles/' . basename( (string) $file );
	if ( '' === (string) $file || ! is_readable( $path ) ) {
		return '';
	}
	$json = json_decode( (string) file_get_contents( $path ), true );
	return is_array( $json ) && isset( $json['content'] ) ? (string) $json['content'] : '';
}

foreach ( array( 'posts', 'pages', 'journal' ) as $group ) {
	foreach ( (array) ( $manifest[ $group ] ?? array() ) as $item ) {
		$text = paulus_test_body( $root, $item['file'] ?? '' );
		foreach ( array( 'excerpt', 'meta', 'seo_title', 'label', 'series_title' ) as $k ) {
			$text .= ' ' . ( isset( $item[ $k ] ) && is_string( $item[ $k ] ) ? $item[ $k ] : '' );
		}
		$r = paulus_spam_score( $item['title'] ?? '', $item['slug'] ?? '', $text );
		++$n;
		if ( $r['score'] > 0 ) {
			++$fail;
			echo 'FALSE POSITIVE (score ' . $r['score'] . '): ' . $group . ' ' . ( $item['slug'] ?? '' ) . ' ' . implode( '; ', $r['reasons'] ) . "\n";
		}
	}
}
foreach ( (array) ( $manifest['parts'] ?? array() ) as $part ) {
	$r = paulus_spam_score( $part['name'] ?? '', $part['slug'] ?? '', ( $part['description'] ?? '' ) . ' ' . ( $part['meta'] ?? '' ) );
	++$n;
	if ( $r['score'] > 0 ) {
		++$fail;
		echo 'FALSE POSITIVE section: ' . ( $part['slug'] ?? '' ) . ' ' . implode( '; ', $r['reasons'] ) . "\n";
	}
}
// Every content file, including ones no manifest entry names yet.
foreach ( glob( $root . '/content/articles/*.json' ) as $path ) {
	$r = paulus_spam_score( '', '', paulus_test_body( $root, basename( $path ) ) );
	++$n;
	if ( $r['score'] > 0 ) {
		++$fail;
		echo 'FALSE POSITIVE file: ' . basename( $path ) . ' ' . implode( '; ', $r['reasons'] ) . "\n";
	}
}
echo "Shipped items, sections and files checked: $n\n";

$clean = array( 'Bethlehem and Bethel in Genesis', 'The Hebrew alphabet: aleph, bet, gimel', 'Beth and the house of God', 'Between the Testaments', 'A better reading of Romans', 'What is at stake in Galatians', 'Stake', 'A review of the evidence', 'Bonus: a note on injil.help', 'A time slot in Acts', 'The crown of righteousness', 'Elizabeth and Bethany', 'Paul at the Areopagus', 'Pin up the letters of Paul' );
foreach ( $clean as $t ) {
	$r = paulus_spam_score( $t, '', '' );
	if ( $r['spam'] ) {
		++$fail;
		echo "FALSE POSITIVE sample: $t (" . implode( '; ', $r['reasons'] ) . ")\n";
	}
}

$spam = array(
	array( 'Bet Big Win Big with Bodog Sports Edge', '' ),
	array( 'Exclusive Coolbet Bonus Code Unlocks Big Wins', '' ),
	array( 'Скачать Пинап КЗ: Официальный сайт', '' ),
	array( 'Krok po kroku: Kompleksowy przewodnik po Pelican Casino', '' ),
	array( 'Live-Roulette bei Mr Pacho', '' ),
	array( 'Crash-Games als neuer Trend', '' ),
	array( 'Persoonlijke ervaringen met top 10 online casino’s in Nederland 2026', '' ),
	array( 'Panduan Lengkap 1xBet Download', '' ),
	array( 'Pusulabet 2026 Giriş Adresi', '' ),
	array( 'Sweepstakes Casinos Explained', '' ),
	array( 'Stake Casino Australia', '' ),
	array( 'Sportwetten-Analyse vor dem Spiel', '' ),
	array( 'Connection check jk5g5zw7', '' ),
	array( 'cl-probe', '' ),
	array( 'Mostbet giriş', '' ),
	array( 'Surfplay review', '' ),
	array( 'Crownplay bonus', '' ),
	array( 'Trino Casino', '' ),
	array( 'Syndicate Casino login', '' ),
	array( 'Stellar Spins', '' ),
	array( 'Starda официальный сайт', '' ),
	array( 'Slotspalace', '' ),
	array( 'Sportaza', '' ),
	array( 'Spybet', '' ),
	array( 'Squeenaud', '' ),
	array( 'Spinjo', '' ),
	array( 'Spellwin', '' ),
	array( 'Spinsup', '' ),
	array( 'Spinch', '' ),
	array( 'Spin Samurai', '' ),
	array( 'Slots Gallery', '' ),
	array( 'Najboljše igralne avtomate v Sloveniji', '' ),
	array( 'Casino Oyunlarında Bankroll Yönetimi', '' ),
	array( 'Glücksspiel in der Schweiz', '' ),
	array( 'Ausländische Online Casinos', '' ),
	array( 'Untitled', 'casinos-online-mas-seguros-espana-2026' ),
	array( 'Untitled', 'zahranicne-stavkove-kancelarie-na-slovensku-3' ),
	array( 'Untitled', 'pin-ap-kazino-oficialnyj-sajt' ),
	array( 'Untitled', 'kto-brasil-cassino-online-e-apostas' ),
	array( 'Vavada зеркало', '' ),
	array( 'Melbet app', '' ),
	array( 'Betify login', '' ),
	array( 'Casinozer avis', '' ),
	array( 'AureonPlay', '' ),
	array( 'PlatinCasino', '' ),
	array( 'True Blue Casino', '' ),
	array( 'SydSpin', '' ),
	array( 'TCL99', '' ),
	array( 'Vegas Stars', '' ),
	array( 'Surge Casino', '' ),
	array( '1win', '' ),
);
foreach ( $spam as $s ) {
	$r = paulus_spam_score( $s[0], $s[1], '' );
	if ( ! $r['spam'] ) {
		++$fail;
		echo "MISSED: {$s[0]} {$s[1]} (score {$r['score']})\n";
	}
}
$r = paulus_spam_score( 'A guide', '', '<p>See <a href="https://best-casino.example/">here</a></p>' );
if ( ! $r['spam'] ) {
	++$fail;
	echo "MISSED: gambling link\n";
}
echo 'Spam samples checked: ' . ( count( $spam ) + 1 ) . "\n";
echo $fail ? "FAILED: $fail\n" : "PASS\n";
exit( $fail ? 1 : 0 );
