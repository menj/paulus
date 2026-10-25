<?php
/**
 * Structured data in depth. Every page and every component of the site is
 * described with the most specific schema.org type that is true of it, and
 * the page's main nodes are deepened, whether the theme or Rank Math built
 * them. Nothing is declared that the page does not contain: a type that
 * misdescribes content breaks Google's structured-data policy and puts a
 * false statement into the site's data.
 *
 * Entry point: paulus_schema_deepen( $nodes ), applied to the theme's own
 * graph (paulus_print_schema_graph) and to Rank Math's (its json_ld filter).
 *
 * Linked-data identifiers (Wikipedia, Wikidata) were each verified against
 * Wikipedia's API on 24 September 2026.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stable node identifiers for the site-wide entities.
 *
 * @param string $key Entity key.
 * @return string
 */
function paulus_sid( $key ) {
	// The author and publisher take the identifiers of the nodes that
	// already describe them on the page (Rank Math names its own), so the
	// graph never holds two unconnected copies of either.
	if ( ! empty( $GLOBALS['paulus_sd_ids'][ $key ] ) ) {
		return $GLOBALS['paulus_sd_ids'][ $key ];
	}
	// The book is the node the book page describes (paulus_book_schema).
	if ( 'book' === $key ) {
		$page = get_page_by_path( 'the-book' );
		if ( $page && 'publish' === $page->post_status ) {
			return get_permalink( $page ) . '#book';
		}
	}
	return home_url( '/' ) . '#' . $key;
}

/**
 * Whether a graph refers to an identifier.
 *
 * @param mixed  $graph Nodes.
 * @param string $id    Identifier.
 * @return bool
 */
function paulus_schema_refers( $graph, $id ) {
	$json = (string) wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	return false !== strpos( $json, '"' . $id . '"' );
}

/**
 * Types of a node as a list.
 *
 * @param array $node Node.
 * @return string[]
 */
function paulus_node_types( $node ) {
	$t = $node['@type'] ?? array();
	return is_array( $t ) ? $t : array( $t );
}

/**
 * Whether a node carries one of the given types.
 *
 * @param array    $node  Node.
 * @param string[] $types Types.
 * @return bool
 */
function paulus_node_is( $node, $types ) {
	return (bool) array_intersect( paulus_node_types( $node ), $types );
}

/**
 * Add a type to a node, keeping its existing ones.
 *
 * @param array  $node Node.
 * @param string $type Type.
 * @return array
 */
function paulus_node_add_type( $node, $type ) {
	$types = paulus_node_types( $node );
	if ( ! in_array( $type, $types, true ) ) {
		$types[] = $type;
	}
	$node['@type'] = 1 === count( $types ) ? $types[0] : array_values( $types );
	return $node;
}

/**
 * Set a property only where the node has none.
 *
 * @param array  $node  Node.
 * @param string $prop  Property.
 * @param mixed  $value Value.
 * @return array
 */
function paulus_node_default( $node, $prop, $value ) {
	if ( ! isset( $node[ $prop ] ) && null !== $value && '' !== $value && array() !== $value ) {
		$node[ $prop ] = $value;
	}
	return $node;
}

/**
 * The subjects the site treats, as linked entities.
 *
 * @return array<string, array>
 */
function paulus_schema_entities() {
	$entities = array(
		'paul'   => array(
			'@type'         => 'Person',
			'@id'           => paulus_sid( 'paul-of-tarsus' ),
			'name'          => 'Paul of Tarsus',
			'alternateName' => array( 'Saul of Tarsus', 'Paul the Apostle', 'Paulus' ),
			'birthPlace'    => array(
				'@type'  => 'Place',
				'name'   => 'Tarsus, Cilicia',
				'sameAs' => array( 'https://en.wikipedia.org/wiki/Tarsus,_Mersin', 'https://www.wikidata.org/wiki/Q134287' ),
			),
			'sameAs'        => array( 'https://en.wikipedia.org/wiki/Paul_the_Apostle', 'https://www.wikidata.org/wiki/Q9200' ),
		),
		'isa'    => array(
			'@type'         => 'Person',
			'@id'           => paulus_sid( 'isa-ibn-maryam' ),
			'name'          => 'ʿĪsā ibn Maryam',
			'alternateName' => array( 'Jesus', 'Jesus son of Mary' ),
			'sameAs'        => array( 'https://en.wikipedia.org/wiki/Jesus_in_Islam', 'https://www.wikidata.org/wiki/Q51664', 'https://www.wikidata.org/wiki/Q302' ),
		),
		'acts'   => array(
			'@type'  => 'Book',
			'@id'    => paulus_sid( 'acts-of-the-apostles' ),
			'name'   => 'The Acts of the Apostles',
			'sameAs' => array( 'https://en.wikipedia.org/wiki/Acts_of_the_Apostles', 'https://www.wikidata.org/wiki/Q40309' ),
		),
		'kjv'    => array(
			'@type'  => 'Book',
			'@id'    => paulus_sid( 'king-james-version' ),
			'name'   => 'The Holy Bible, King James Version',
			'sameAs' => array( 'https://en.wikipedia.org/wiki/King_James_Version', 'https://www.wikidata.org/wiki/Q623398' ),
		),
		'pew'    => array(
			'@type'  => 'ResearchOrganization',
			'@id'    => paulus_sid( 'pew-research-center' ),
			'name'   => 'Pew Research Center',
			'url'    => 'https://www.pewresearch.org/',
			'sameAs' => array( 'https://en.wikipedia.org/wiki/Pew_Research_Center', 'https://www.wikidata.org/wiki/Q1635722' ),
		),
		'martin' => array(
			'@type'       => 'Person',
			'@id'         => paulus_sid( 'dale-b-martin' ),
			'name'        => 'Dale B. Martin',
			'affiliation' => array( '@type' => 'CollegeOrUniversity', 'name' => 'Yale University' ),
			'sameAs'      => array( 'https://en.wikipedia.org/wiki/Dale_Martin_(scholar)', 'https://www.wikidata.org/wiki/Q26923442' ),
		),
	);
	foreach ( paulus_schema_named_things() as $key => $t ) {
		$node = array(
			'@type'  => $t[0],
			'@id'    => paulus_sid( $key ),
			'name'   => $t[1],
			'sameAs' => array_values( array_filter( array( 'https://en.wikipedia.org/wiki/' . $t[3], $t[4] ? 'https://www.wikidata.org/wiki/' . $t[4] : '' ) ) ),
			'__re'   => $t[5],
		);
		if ( $t[2] ) {
			$node['alternateName'] = $t[2];
		}
		$entities[ $key ] = $node;
	}
	$entities['isa']['__re']   = '/ʿĪsā|\\bJesus\\b|\\bChrist\\b/u';
	$entities['acts']['__re']  = '/Acts of the Apostles|\\bActs \\d/u';
	$entities['paul']['__re']  = '';
	return $entities;
}

/**
 * More people, places and works the text names, each as a linked entity: type,
 * name, other names, Wikipedia page, Wikidata item where known, and the
 * pattern that finds it in a page's text. A page mentions an entity (and
 * the entity's own node joins the page's graph) only when its text names it.
 *
 * @return array<string, array>
 */
function paulus_schema_named_things() {
	$p = 'Person';
	$l = 'Place';
	$c = 'City';
	$a = 'AdministrativeArea';
	$n = 'Country';
	$b = 'Book';
	$o = 'Organization';
	$e = 'Event';
	return array(
		'muhammad'    => array( $p, 'Muhammad', array( 'The Prophet Muhammad' ), 'Muhammad', 'Q9458', '/Mu[hḥ]ammad|ﷺ/u' ),
		'peter'       => array( $p, 'Peter the Apostle', array( 'Simon Peter', 'Cephas' ), 'Saint_Peter', 'Q33923', '/\b(Peter|Cephas)\b/u' ),
		'james'       => array( $p, 'James, brother of Jesus', array( 'James the Just' ), 'James,_brother_of_Jesus', '', '/James,? (the )?(Lord’s |Lord\'s )?brother|James the Just|brother of the Lord/u' ),
		'barnabas'    => array( $p, 'Barnabas', array(), 'Barnabas', '', '/\bBarnabas\b/u' ),
		'gamaliel'    => array( $p, 'Gamaliel the Elder', array( 'Gamaliel' ), 'Gamaliel', '', '/\bGamaliel\b/u' ),
		'stephen'     => array( $p, 'Stephen the Protomartyr', array( 'Saint Stephen' ), 'Saint_Stephen', '', '/\bStephen\b/u' ),
		'luke'        => array( $p, 'Luke the Evangelist', array( 'Luke' ), 'Luke_the_Evangelist', '', '/\bLuke\b/u' ),
		'clement'     => array( $p, 'Clement of Rome', array( 'Pope Clement I' ), 'Pope_Clement_I', '', '/Clement of Rome|1 Clement|First Clement/u' ),
		'eusebius'    => array( $p, 'Eusebius of Caesarea', array(), 'Eusebius', '', '/\bEusebius\b/u' ),
		'tertullian'  => array( $p, 'Tertullian', array(), 'Tertullian', '', '/\bTertullian\b/u' ),
		'nero'        => array( $p, 'Nero', array( 'Nero Claudius Caesar' ), 'Nero', '', '/\bNero\b/u' ),
		'albiruni'    => array( $p, 'Abū Rayḥān al-Bīrūnī', array( 'al-Biruni' ), 'Al-Biruni', '', '/B[iī]r[uū]n[iī]/u' ),
		'akyol'       => array( $p, 'Mustafa Akyol', array(), 'Mustafa_Akyol', '', '/\\bAkyol\\b/u' ),
		'ibnhazm'     => array( $p, 'Ibn Ḥazm', array( 'Ibn Hazm' ), 'Ibn_Hazm', '', '/Ibn Ḥ?azm/u' ),
		'tarsus'      => array( $c, 'Tarsus', array(), 'Tarsus,_Mersin', '', '/\bTarsus\b/u' ),
		'damascus'    => array( $c, 'Damascus', array(), 'Damascus', '', '/\bDamascus\b/u' ),
		'jerusalem'   => array( $c, 'Jerusalem', array(), 'Jerusalem', '', '/\b(Jerusalem|Islamicjerusalem)\b/u' ),
		'rome'        => array( $c, 'Rome', array(), 'Rome', '', '/\bRome\b/u' ),
		'antioch'     => array( $c, 'Antioch', array(), 'Antioch', '', '/\bAntioch\b/u' ),
		'corinth'     => array( $c, 'Corinth', array(), 'Corinth', '', '/\bCorinth\b/u' ),
		'ephesus'     => array( $c, 'Ephesus', array(), 'Ephesus', '', '/\bEphesus\b/u' ),
		'philippi'    => array( $c, 'Philippi', array(), 'Philippi', '', '/\bPhilippi\b/u' ),
		'thessalonica' => array( $c, 'Thessalonica', array( 'Thessaloniki' ), 'Thessaloniki', '', '/\bThessalonica\b/u' ),
		'athens'      => array( $c, 'Athens', array(), 'Athens', '', '/\bAthens\b/u' ),
		'cyprus'      => array( $n, 'Cyprus', array(), 'Cyprus', '', '/\bCyprus\b/u' ),
		'galatia'     => array( $a, 'Galatia', array(), 'Galatia', '', '/\bGalatia\b/u' ),
		'caesarea'    => array( $c, 'Caesarea Maritima', array( 'Caesarea' ), 'Caesarea_Maritima', '', '/\bCaesarea\b/u' ),
		'romans'      => array( $b, 'Epistle to the Romans', array( 'Romans' ), 'Epistle_to_the_Romans', '', '/Epistle to the Romans|letter to the Romans|\bRomans \d/u' ),
		'1corinthians' => array( $b, 'First Epistle to the Corinthians', array( '1 Corinthians' ), 'First_Epistle_to_the_Corinthians', '', '/1 Corinthians|First Corinthians/u' ),
		'2corinthians' => array( $b, 'Second Epistle to the Corinthians', array( '2 Corinthians' ), 'Second_Epistle_to_the_Corinthians', '', '/2 Corinthians|Second Corinthians/u' ),
		'galatians'   => array( $b, 'Epistle to the Galatians', array( 'Galatians' ), 'Epistle_to_the_Galatians', '', '/Epistle to the Galatians|letter to the Galatians|\bGalatians \d/u' ),
		'philippians' => array( $b, 'Epistle to the Philippians', array( 'Philippians' ), 'Epistle_to_the_Philippians', '', '/Epistle to the Philippians|\bPhilippians \d/u' ),
		'1thessalonians' => array( $b, 'First Epistle to the Thessalonians', array( '1 Thessalonians' ), 'First_Epistle_to_the_Thessalonians', '', '/1 Thessalonians|First Thessalonians/u' ),
		'philemon'    => array( $b, 'Epistle to Philemon', array( 'Philemon' ), 'Epistle_to_Philemon', '', '/\bPhilemon\b/u' ),
		'matthew'     => array( $b, 'Gospel of Matthew', array(), 'Gospel_of_Matthew', '', '/Gospel of Matthew|\bMatthew \d/u' ),
		'mark'        => array( $b, 'Gospel of Mark', array(), 'Gospel_of_Mark', '', '/Gospel of Mark|\bMark \d/u' ),
		'lukegospel'  => array( $b, 'Gospel of Luke', array(), 'Gospel_of_Luke', '', '/Gospel of Luke|\bLuke \d/u' ),
		'john'        => array( $b, 'Gospel of John', array(), 'Gospel_of_John', '', '/Gospel of John|\bJohn \d/u' ),
		'quran'       => array( $b, 'The Quran', array( 'Qurʾān', 'Koran' ), 'Quran', '', '/Qur[ʾ’\']?[aā]n|\bKoran\b/u' ),
		'torah'       => array( $b, 'The Torah', array(), 'Torah', '', '/\bTorah\b/u' ),
		'tanakh'      => array( $b, 'The Tanakh', array( 'Hebrew Bible' ), 'Hebrew_Bible', '', '/\bTanakh\b|Hebrew Bible/u' ),
		// More people the text names.
		'philo'       => array( $p, 'Philo of Alexandria', array( 'Philo Judaeus' ), 'Philo', 'Q131434', '/\bPhilo\b/u' ),
		'josephus'    => array( $p, 'Flavius Josephus', array( 'Josephus' ), 'Josephus', 'Q134461', '/\bJosephus\b/u' ),
		'tacitus'     => array( $p, 'Tacitus', array(), 'Tacitus', 'Q2161', '/\bTacitus\b/u' ),
		'marcion'     => array( $p, 'Marcion of Sinope', array( 'Marcion' ), 'Marcion_of_Sinope', 'Q295144', '/\bMarcion\b/u' ),
		'epiphanius'  => array( $p, 'Epiphanius of Salamis', array( 'Epiphanius' ), 'Epiphanius_of_Salamis', '', '/\bEpiphanius\b/u' ),
		'irenaeus'    => array( $p, 'Irenaeus', array(), 'Irenaeus', '', '/\bIrenaeus\b/u' ),
		'origen'      => array( $p, 'Origen', array(), 'Origen', '', '/\bOrigen\b/u' ),
		'ignatius'    => array( $p, 'Ignatius of Antioch', array(), 'Ignatius_of_Antioch', '', '/Ignatius of Antioch|\bIgnatius\b/u' ),
		'hegesippus'  => array( $p, 'Hegesippus', array(), 'Hegesippus_(chronicler)', '', '/\bHegesippus\b/u' ),
		'augustine'   => array( $p, 'Augustine of Hippo', array( 'Saint Augustine' ), 'Augustine_of_Hippo', '', '/\bAugustine\b/u' ),
		'constantine' => array( $p, 'Constantine the Great', array( 'Constantine' ), 'Constantine_the_Great', '', '/\bConstantine\b/u' ),
		'felix'       => array( $p, 'Antonius Felix', array( 'Felix' ), 'Antonius_Felix', '', '/\bFelix\b/u' ),
		'festus'      => array( $p, 'Porcius Festus', array( 'Festus' ), 'Porcius_Festus', '', '/\bFestus\b/u' ),
		'agrippa'     => array( $p, 'Herod Agrippa II', array( 'Agrippa' ), 'Herod_Agrippa_II', '', '/\bAgrippa\b/u' ),
		'gallio'      => array( $p, 'Lucius Junius Gallio Annaeanus', array( 'Gallio' ), 'Lucius_Junius_Gallio_Annaeanus', '', '/\bGallio\b/u' ),
		'ananias'     => array( $p, 'Ananias of Damascus', array( 'Ananias' ), 'Ananias_of_Damascus', '', '/\bAnanias\b/u' ),
		'silas'       => array( $p, 'Silas', array( 'Silvanus' ), 'Silas', '', '/\bSilas\b/u' ),
		'apollos'     => array( $p, 'Apollos', array(), 'Apollos', '', '/\bApollos\b/u' ),
		'luther'      => array( $p, 'Martin Luther', array(), 'Martin_Luther', 'Q9554', '/\bLuther\b/u' ),
		'calvin'      => array( $p, 'John Calvin', array(), 'John_Calvin', '', '/\bCalvin\b/u' ),
		'jefferson'   => array( $p, 'Thomas Jefferson', array(), 'Thomas_Jefferson', 'Q11812', '/\bJefferson\b/u' ),
		'nietzsche'   => array( $p, 'Friedrich Nietzsche', array(), 'Friedrich_Nietzsche', 'Q9358', '/\bNietzsche\b/u' ),
		'baur'        => array( $p, 'Ferdinand Christian Baur', array( 'F. C. Baur' ), 'Ferdinand_Christian_Baur', '', '/\bBaur\b/u' ),
		'spong'       => array( $p, 'John Shelby Spong', array(), 'John_Shelby_Spong', '', '/\bSpong\b/u' ),
		'faruqi'      => array( $p, 'Ismaʿil Raji al-Faruqi', array( 'Ismail al-Faruqi' ), 'Ismail_al-Faruqi', '', '/F[aā]r[uū]q[iī]/u' ),
		'shahrastani' => array( $p, 'al-Shahrastānī', array( 'Al-Shahrastani' ), 'Al-Shahrastani', '', '/Shahrast[aā]n[iī]/u' ),
		'thalabi'     => array( $p, 'al-Thaʿlabī', array( 'Al-Tha\'labi' ), 'Al-Tha%27labi', '', '/Tha[ʿ\']?lab[iī]/u' ),
		'tabari'      => array( $p, 'al-Ṭabarī', array( 'Al-Tabari' ), 'Al-Tabari', '', '/Ṭabar[iī]|\bTabari\b/u' ),
		'ibnkathir'   => array( $p, 'Ibn Kathīr', array( 'Ibn Kathir' ), 'Ibn_Kathir', '', '/Ibn Kath[iī]r/u' ),
		'ibntaymiyya' => array( $p, 'Ibn Taymiyyah', array( 'Ibn Taymiyya' ), 'Ibn_Taymiyya', '', '/Ibn Taym/u' ),
		'kairanawi'   => array( $p, 'Rahmatullah Kairanawi', array( 'al-Kairanawī' ), 'Rahmatullah_Kairanawi', '', '/Kairanaw/u' ),
		'akhtar'      => array( $p, 'Shabbir Akhtar', array(), 'Shabbir_Akhtar', '', '/\bAkhtar\b/u' ),
		// More places, each with its own kind.
		'cilicia'     => array( $a, 'Cilicia', array(), 'Cilicia', '', '/\bCilicia\b/u' ),
		'macedonia'   => array( $a, 'Macedonia', array(), 'Macedonia_(Roman_province)', '', '/\bMacedonia\b/u' ),
		'syria'       => array( $a, 'Syria', array(), 'Roman_Syria', '', '/\bSyria\b/u' ),
		'arabia'      => array( $l, 'Arabia', array(), 'Arabian_Peninsula', '', '/\bArabia\b/u' ),
		'egypt'       => array( $n, 'Egypt', array(), 'Egypt', 'Q79', '/\bEgypt\b/u' ),
		'malta'       => array( $n, 'Malta', array(), 'Malta', 'Q233', '/\bMalta\b/u' ),
		'malaysia'    => array( $n, 'Malaysia', array(), 'Malaysia', 'Q833', '/\bMalaysia\b/u' ),
		'lystra'      => array( $c, 'Lystra', array(), 'Lystra', '', '/\bLystra\b/u' ),
		'kufa'        => array( $c, 'Kufa', array(), 'Kufa', '', '/\bKufa\b/u' ),
		'cordoba'     => array( $c, 'Córdoba', array( 'Cordoba' ), 'C%C3%B3rdoba,_Spain', '', '/C[oó]rdoba/u' ),
		// More works.
		'ephesians'   => array( $b, 'Epistle to the Ephesians', array( 'Ephesians' ), 'Epistle_to_the_Ephesians', '', '/Epistle to the Ephesians|\bEphesians \d/u' ),
		'colossians'  => array( $b, 'Epistle to the Colossians', array( 'Colossians' ), 'Epistle_to_the_Colossians', '', '/Epistle to the Colossians|\bColossians \d/u' ),
		'2thessalonians' => array( $b, 'Second Epistle to the Thessalonians', array( '2 Thessalonians' ), 'Second_Epistle_to_the_Thessalonians', '', '/2 Thessalonians|Second Thessalonians/u' ),
		'1timothy'    => array( $b, 'First Epistle to Timothy', array( '1 Timothy' ), 'First_Epistle_to_Timothy', '', '/1 Timothy|First Timothy/u' ),
		'2timothy'    => array( $b, 'Second Epistle to Timothy', array( '2 Timothy' ), 'Second_Epistle_to_Timothy', '', '/2 Timothy|Second Timothy/u' ),
		'titusepistle' => array( $b, 'Epistle to Titus', array( 'Titus' ), 'Epistle_to_Titus', '', '/Epistle to Titus|\bTitus \d/u' ),
		'hebrews'     => array( $b, 'Epistle to the Hebrews', array( 'Hebrews' ), 'Epistle_to_the_Hebrews', '', '/Epistle to the Hebrews|\bHebrews \d/u' ),
		'2peter'      => array( $b, 'Second Epistle of Peter', array( '2 Peter' ), 'Second_Epistle_of_Peter', '', '/2 Peter|Second Peter/u' ),
		'thomas'      => array( $b, 'Gospel of Thomas', array(), 'Gospel_of_Thomas', '', '/Gospel of Thomas/u' ),
		'talmud'      => array( $b, 'The Talmud', array(), 'Talmud', 'Q43290', '/\bTalmud\b/u' ),
		'bukhari'     => array( $b, 'Ṣaḥīḥ al-Bukhārī', array( 'Sahih al-Bukhari' ), 'Sahih_al-Bukhari', '', '/Bukh[aā]r[iī]/u' ),
		'sahihmuslim' => array( $b, 'Ṣaḥīḥ Muslim', array( 'Sahih Muslim' ), 'Sahih_Muslim', '', '/Ṣaḥīḥ Muslim|Sahih Muslim/u' ),
		'tirmidhi'    => array( $b, 'Jāmiʿ al-Tirmidhī', array( 'Jami al-Tirmidhi' ), 'Jami%27_al-Tirmidhi', '', '/Tirmidh[iī]/u' ),
		'musnad'      => array( $b, 'Musnad Aḥmad ibn Ḥanbal', array( 'Musnad Ahmad' ), 'Musnad_Ahmad_ibn_Hanbal', '', '/Musnad A[hḥ]mad/u' ),
		'sblgnt'      => array( $b, 'SBL Greek New Testament', array( 'SBLGNT' ), 'SBL_Greek_New_Testament', '', '/SBLGNT|SBL Greek/u' ),
		// Groups, and councils.
		'ebionites'   => array( $o, 'Ebionites', array(), 'Ebionites', '', '/\bEbionit/u' ),
		'nazarenes'   => array( $o, 'Nazarenes', array( 'Nazarene sect' ), 'Nazarene_(sect)', '', '/\bNazarenes?\b/u' ),
		'nicaea'      => array( $e, 'First Council of Nicaea', array( 'Council of Nicaea' ), 'First_Council_of_Nicaea', '', '/Council of Nicaea|\bNicaea\b/u' ),
		'council'     => array( $e, 'Council of Jerusalem', array( 'Jerusalem Council' ), 'Council_of_Jerusalem', '', '/Jerusalem Council|Council of Jerusalem/u' ),
	);
}

/**
 * The book the site is drawn from, as a reference node.
 *
 * @return array
 */
function paulus_schema_book_ref() {
	$book = array(
		'@type'      => 'Book',
		'@id'        => paulus_sid( 'book' ),
		'name'       => (string) paulus_option( 'book_title' ),
		'author'     => array( '@id' => paulus_sid( 'author' ) ),
		'inLanguage' => 'ms',
	);
	if ( paulus_option( 'book_isbn' ) ) {
		$book['isbn'] = (string) paulus_option( 'book_isbn' );
	}
	$page = get_page_by_path( 'the-book' );
	if ( $page && 'publish' === $page->post_status ) {
		$book['url'] = get_permalink( $page );
	}
	return $book;
}

/**
 * Plain text from markup, collapsed.
 *
 * @param string $html Markup.
 * @return string
 */
function paulus_schema_text( $html ) {
	$html = preg_replace( '#<sup>.*?</sup>#s', '', (string) $html );
	$html = preg_replace( '#\[/?paulus_[^\]]*\]#', '', $html );
	return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

/**
 * The page's own stored content.
 *
 * @return string
 */
function paulus_schema_content() {
	return is_singular() ? (string) get_post_field( 'post_content', get_queried_object_id() ) : '';
}

/**
 * The footnotes of the current text, as citations.
 *
 * @param string $content Content.
 * @return string[]
 */
function paulus_schema_citations( $content ) {
	if ( ! preg_match( '#<ol class="footnotes"[^>]*>(.*?)</ol>#s', $content, $m ) ) {
		return array();
	}
	preg_match_all( '#<li[^>]*>(.*?)</li>#s', $m[1], $li );
	$out = array();
	foreach ( $li[1] as $note ) {
		$note = preg_replace( '#<a [^>]*aria-label="Back to text"[^>]*>.*?</a>#s', '', $note );
		$text = paulus_schema_text( $note );
		if ( '' !== $text ) {
			$out[] = mb_substr( $text, 0, 400 );
		}
	}
	return array_slice( array_values( array_unique( $out ) ), 0, 100 );
}

/**
 * The footnotes of the current text as citation nodes: each note's text, its
 * place in the list, and the address of the note on the page.
 *
 * @param string $content Content.
 * @param string $base    Page address.
 * @return array
 */
function paulus_schema_citation_nodes( $content, $base ) {
	if ( ! preg_match( '#<ol class="footnotes"[^>]*>(.*?)</ol>#s', $content, $m ) ) {
		return array();
	}
	preg_match_all( '#<li([^>]*)>(.*?)</li>#s', $m[1], $li, PREG_SET_ORDER );
	$out = array();
	foreach ( $li as $i => $note ) {
		$text = paulus_schema_text( preg_replace( '#<a [^>]*aria-label="Back to text"[^>]*>.*?</a>#s', '', $note[2] ) );
		if ( '' === $text ) {
			continue;
		}
		$node = array(
			'@type'    => 'CreativeWork',
			'text'     => mb_substr( $text, 0, 400 ),
			'position' => $i + 1,
		);
		if ( preg_match( '#\bid="([^"]+)"#', $note[1], $id ) ) {
			$node['@id'] = $base . '#' . $id[1];
			$node['url'] = $base . '#' . $id[1];
		}
		$out[] = $node;
	}
	return array_slice( $out, 0, 100 );
}

/**
 * Record the headings the page really shows, after every other content
 * filter has run, so the structured data links only to anchors that exist.
 * The page body renders before wp_head in a block theme, so what is recorded
 * here is there when the structured data is built.
 *
 * @param string $content Rendered content.
 * @return string
 */
function paulus_schema_capture_headings( $content ) {
	if ( is_singular() && in_the_loop() && is_main_query() && preg_match_all( '#<h2(\s[^>]*)?>(.*?)</h2>#s', $content, $m, PREG_SET_ORDER ) ) {
		$seen = array();
		foreach ( $m as $h ) {
			$seen[] = array(
				'id'   => preg_match( '#\bid=["\']([^"\']+)["\']#', $h[1] ?? '', $e ) ? $e[1] : '',
				'text' => paulus_schema_text( preg_replace( '#<a class="paulus-anchor".*?</a>#s', '', $h[2] ) ),
			);
		}
		$GLOBALS['paulus_schema_headings'] = $seen;
	}
	return $content;
}
add_filter( 'the_content', 'paulus_schema_capture_headings', 99 );

/**
 * The sections of a text: each h2 heading as a WebPageElement with the
 * address of its anchor, numbered in reading order. Anchors follow the
 * theme's own rule (paulus_page_contents): the heading's id, else its slug.
 *
 * @param string $content Content.
 * @param string $base    Page address.
 * @return array
 */
function paulus_schema_sections( $content, $base ) {
	// The headings as the page rendered them, with their real ids.
	if ( ! empty( $GLOBALS['paulus_schema_headings'] ) ) {
		$out = array();
		foreach ( $GLOBALS['paulus_schema_headings'] as $i => $h ) {
			if ( '' === $h['text'] ) {
				continue;
			}
			$el = array(
				'@type'    => 'WebPageElement',
				'name'     => $h['text'],
				'position' => $i + 1,
			);
			if ( '' !== $h['id'] ) {
				$el['@id'] = $base . '#' . $h['id'];
				$el['url'] = $base . '#' . $h['id'];
			}
			$out[] = $el;
		}
		return array_slice( $out, 0, 60 );
	}
	// Articles and Journal entries take the ids the rail links to (s- and the
	// slug), which paulus_section_ids() writes into the page.
	if ( is_single() && function_exists( 'paulus_article_sections' ) ) {
		$out = array();
		foreach ( paulus_article_sections( get_queried_object_id() ) as $i => $sec ) {
			if ( '' === trim( $sec['text'] ) ) {
				continue;
			}
			$out[] = array(
				'@type'    => 'WebPageElement',
				'@id'      => $base . '#' . $sec['id'],
				'name'     => $sec['text'],
				'url'      => $base . '#' . $sec['id'],
				'position' => $i + 1,
			);
		}
		return array_slice( $out, 0, 60 );
	}
	if ( ! preg_match_all( '#<h2(\s[^>]*)?>(.*?)</h2>#s', $content, $m, PREG_SET_ORDER ) ) {
		return array();
	}
	$seen = array();
	$out  = array();
	foreach ( $m as $i => $h ) {
		$name = paulus_schema_text( $h[2] );
		$stored = (bool) preg_match( '#\bid=["\']([^"\']+)["\']#', $h[1] ?? '', $e );
		if ( $stored ) {
			$id = $e[1];
		} else {
			$slug = sanitize_title( remove_accents( wp_strip_all_tags( $h[2] ) ) ) ?: 'section';
			$n    = $seen[ $slug ] = ( $seen[ $slug ] ?? 0 ) + 1;
			$id   = $slug . ( $n > 1 ? '-' . $n : '' );
		}
		if ( '' === $name ) {
			continue;
		}
		$el = array(
			'@type'    => 'WebPageElement',
			'name'     => $name,
			'position' => $i + 1,
		);
		// A heading with no id of its own is named without an address: the
		// theme gives it one only while rendering, and the rendered
		// headings (above) are the ones to link to.
		if ( $stored ) {
			$el['@id'] = $base . '#' . $id;
			$el['url'] = $base . '#' . $id;
		}
		$out[] = $el;
	}
	return array_slice( $out, 0, 60 );
}

/**
 * The people, places and works a text names, as references to their entities.
 *
 * @param string $content Content.
 * @return array
 */
function paulus_schema_mentions( $content ) {
	// Asked for the same text twice a page (the page node and the article
	// node); the fifty regular expressions over the whole text run once.
	static $cache = array();
	$key = md5( (string) $content );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$text = paulus_schema_text( $content );
	$out  = array();
	foreach ( paulus_schema_entities() as $e ) {
		if ( ! empty( $e['__re'] ) && preg_match( $e['__re'], $text ) ) {
			$out[] = array( '@id' => $e['@id'] );
		}
	}
	$cache[ $key ] = $out;
	return $out;
}

/**
 * The scripture quoted in the current text, as Quotation nodes: the King
 * James wording of each block, from the English paragraph beneath the Greek.
 *
 * @param string $content Content.
 * @param string $base    Page address.
 * @return array
 */
function paulus_schema_quotations( $content, $base ) {
	if ( ! preg_match_all( '#<blockquote class="scripture[^"]*">(.*?)</blockquote>#s', $content, $m ) ) {
		return array();
	}
	$out = array();
	foreach ( $m[1] as $i => $block ) {
		$english = '';
		foreach ( preg_split( '#</p>#', $block ) as $p ) {
			if ( false === strpos( $p, 'class="greek"' ) && '' !== paulus_schema_text( $p ) ) {
				$english .= ' ' . paulus_schema_text( preg_replace( '#<span class="v">\d+</span>#', '', $p ) );
			}
		}
		$english = trim( $english );
		if ( '' === $english ) {
			continue;
		}
		$out[] = array(
			'@type'     => 'Quotation',
			'@id'       => $base . '#quotation-' . ( $i + 1 ),
			'text'      => mb_substr( $english, 0, 1000 ),
			'isBasedOn' => array( '@id' => paulus_sid( 'king-james-version' ) ),
		);
	}
	return array_slice( $out, 0, 40 );
}

/**
 * The header, footer and navigation of every page.
 *
 * @return array
 */
function paulus_schema_page_elements() {
	$nav   = array();
	$items = array();
	foreach ( paulus_parts() as $part ) {
		$items[] = array( $part->name, get_term_link( $part ) );
	}
	$verdict = get_page_by_path( 'the-verdict' );
	if ( $verdict && 'publish' === $verdict->post_status ) {
		$items[] = array( __( 'The Verdict', 'paulus' ), get_permalink( $verdict ) );
	}
	$book = get_page_by_path( 'the-book' );
	if ( $book && 'publish' === $book->post_status ) {
		$items[] = array( get_the_title( $book ), get_permalink( $book ) );
	}
	if ( function_exists( 'paulus_journal_url' ) && post_type_exists( 'paulus_journal' ) && wp_count_posts( 'paulus_journal' )->publish ) {
		$items[] = array( (string) paulus_option( 'journal_title' ), paulus_journal_url() );
	}
	$header_parts = array();
	foreach ( $items as $i => $item ) {
		if ( is_wp_error( $item[1] ) ) {
			continue;
		}
		$id             = paulus_sid( 'nav-main-' . ( $i + 1 ) );
		$nav[]          = array( '@type' => 'SiteNavigationElement', '@id' => $id, 'name' => $item[0], 'url' => $item[1] );
		$header_parts[] = array( '@id' => $id );
	}
	$footer_parts = array();
	$legal        = array( 'about-the-author', 'terms-of-use', 'privacy-policy', 'dmca', 'contact' );
	foreach ( $legal as $i => $slug ) {
		$page = 'privacy-policy' === $slug && (int) get_option( 'wp_page_for_privacy_policy' ) ? get_post( (int) get_option( 'wp_page_for_privacy_policy' ) ) : get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$id             = paulus_sid( 'nav-footer-' . ( $i + 1 ) );
			$nav[]          = array( '@type' => 'SiteNavigationElement', '@id' => $id, 'name' => 'contact' === $slug ? __( 'Contact Us', 'paulus' ) : get_the_title( $page ), 'url' => get_permalink( $page ) );
			$footer_parts[] = array( '@id' => $id );
		}
	}
	$map = function_exists( 'paulus_sitemap_page' ) ? paulus_sitemap_page() : null;
	if ( $map && 'publish' === $map->post_status ) {
		$id             = paulus_sid( 'nav-footer-sitemap' );
		$nav[]          = array( '@type' => 'SiteNavigationElement', '@id' => $id, 'name' => get_the_title( $map ), 'url' => get_permalink( $map ) );
		$footer_parts[] = array( '@id' => $id );
	}
	$nodes   = $nav;
	$nodes[] = array( '@type' => 'WPHeader', '@id' => paulus_sid( 'header' ), 'name' => get_bloginfo( 'name' ), 'hasPart' => $header_parts );
	$footer  = array(
		'@type'           => 'WPFooter',
		'@id'             => paulus_sid( 'footer' ),
		'hasPart'         => $footer_parts,
		'copyrightHolder' => array( '@id' => paulus_sid( 'author' ) ),
	);
	if ( paulus_option( 'footer_blurb' ) ) {
		$footer['description'] = wp_strip_all_tags( (string) paulus_option( 'footer_blurb' ) );
	}
	$nodes[] = $footer;
	return $nodes;
}

/**
 * A list of pages as an ItemList.
 *
 * @param string    $id    Node identifier.
 * @param string    $name  Name.
 * @param WP_Post[] $pages Pages.
 * @return array
 */
function paulus_schema_page_itemlist( $id, $name, $pages ) {
	$items = array();
	foreach ( array_values( $pages ) as $i => $p ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => get_the_title( $p ), 'url' => get_permalink( $p ) );
	}
	return array( '@type' => 'ItemList', '@id' => $id, 'name' => $name, 'numberOfItems' => count( $items ), 'itemListElement' => $items );
}

/**
 * Nodes that describe the components of the current page.
 *
 * @return array
 */
function paulus_schema_component_nodes() {
	$nodes   = array();
	$content = paulus_schema_content();
	$url     = is_singular() ? get_permalink() : '';
	$slug    = is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : '';

	// The front page: its three sections.
	if ( is_front_page() ) {
		$items = array();
		foreach ( array_values( paulus_parts() ) as $i => $part ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $part->name, 'url' => get_term_link( $part ) );
		}
		if ( $items ) {
			$nodes[] = array( '@type' => 'ItemList', '@id' => paulus_sid( 'sections' ), 'name' => __( 'The case', 'paulus' ), 'numberOfItems' => count( $items ), 'itemListElement' => $items );
		}
	}

	// The front page's questions: an FAQPage of exactly the questions and
	// answers its section shows, each pointing to the full, referenced
	// answer on the Answers page. The Answers page keeps its own FAQPage.
	if ( is_front_page() ) {
		$faq = paulus_schema_front_faq();
		if ( $faq ) {
			$nodes[] = $faq;
		}
	}

	// Reference, Appendices and the Sitemap: the pages they index.
	if ( in_array( $slug, array( 'reference', 'appendices' ), true ) ) {
		$nodes[] = paulus_schema_page_itemlist( $url . '#pages', get_the_title(), get_pages( array( 'parent' => get_queried_object_id(), 'sort_column' => 'menu_order' ) ) );
	}
	if ( in_array( $slug, array( 'sitemap', 'site-map' ), true ) ) {
		$pages = array();
		foreach ( paulus_parts() as $part ) {
			foreach ( paulus_chapters( $part->term_id ) as $post ) {
				$pages[] = $post;
			}
		}
		foreach ( get_pages( array( 'sort_column' => 'menu_order' ) ) as $p ) {
			if ( ! in_array( $p->post_name, array( 'sitemap', 'site-map' ), true ) ) {
				$pages[] = $p;
			}
		}
		$nodes[] = paulus_schema_page_itemlist( $url . '#contents', __( 'Every article and page on the site', 'paulus' ), $pages );
	}

	// The timeline: each dated entry as an event in Paul's life.
	if ( 'chronology' === $slug && preg_match_all( '#<li><strong>(.*?)</strong>(.*?)</li>#s', $content, $m, PREG_SET_ORDER ) ) {
		$items     = array();
		$all_years = array();
		foreach ( $m as $i => $row ) {
			$when = paulus_schema_text( $row[1] );
			$what = paulus_schema_text( preg_replace( '#<span class="paulus-timeline__links">.*?</span>#s', '', $row[2] ) );
			if ( '' === $what ) {
				continue;
			}
			$event = array(
				'@type'       => 'Event',
				'@id'         => $url . '#event-' . ( $i + 1 ),
				'name'        => mb_substr( $what, 0, 110 ),
				'description' => $when . ': ' . $what,
				'about'       => array( '@id' => paulus_sid( 'paul-of-tarsus' ) ),
			);
			// The place of the event: the first place the entry names that the
			// site knows, by its position in the text, not the order of the list.
			$first = null;
			foreach ( paulus_schema_named_things() as $key => $t ) {
				if ( in_array( $t[0], array( 'City', 'Country', 'AdministrativeArea', 'Place' ), true ) && preg_match( $t[5], $what, $pm, PREG_OFFSET_CAPTURE ) && ( null === $first || $pm[0][1] < $first[1] ) ) {
					$first = array( $key, $pm[0][1] );
				}
			}
			if ( $first ) {
				$event['location'] = array( '@id' => paulus_sid( $first[0] ) );
			}
			// The years: every number in a date given in the Common Era, so a
			// range such as "c. 5–10 CE" gives its start and its end.
			$years = false !== strpos( $when, 'CE' ) && preg_match_all( '/\b(\d{1,4})\b/', $when, $y ) ? array_map( 'intval', $y[1] ) : array();
			if ( $years ) {
				$event['startDate'] = sprintf( '%04d', min( $years ) );
				if ( max( $years ) > min( $years ) ) {
					$event['endDate'] = sprintf( '%04d', max( $years ) );
				}
				$all_years[] = min( $years );
				$all_years[] = max( $years );
			}
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'item' => $event );
		}
		$list = array( '@type' => 'ItemList', '@id' => $url . '#timeline', 'name' => get_the_title(), 'numberOfItems' => count( $items ), 'itemListElement' => $items );
		if ( ! empty( $all_years ) ) {
			$list['temporalCoverage'] = sprintf( '%04d/%04d', min( $all_years ), max( $all_years ) );
		}
		$nodes[] = $list;
	}

	// The glossary: a set of defined terms.
	if ( 'glossary' === $slug && preg_match_all( '#<dt[^>]*>(.*?)</dt>\s*<dd[^>]*>(.*?)</dd>#s', $content, $m, PREG_SET_ORDER ) ) {
		$set   = $url . '#glossary';
		$terms = array();
		foreach ( $m as $row ) {
			$name    = paulus_schema_text( $row[1] );
			$terms[] = array(
				'@type'            => 'DefinedTerm',
				'@id'              => $url . '#term-' . sanitize_title( remove_accents( $name ) ),
				'name'             => $name,
				'description'      => paulus_schema_text( $row[2] ),
				'inDefinedTermSet' => array( '@id' => $set ),
			);
		}
		$nodes[] = array( '@type' => 'DefinedTermSet', '@id' => $set, 'name' => get_the_title(), 'url' => $url, 'hasDefinedTerm' => $terms );
	}

	// The study guide: its questions, open-ended, grouped by article.
	if ( 'study-questions' === $slug && preg_match_all( '#<h2[^>]*>(.*?)</h2>\s*<ol[^>]*>(.*?)</ol>#s', $content, $m, PREG_SET_ORDER ) ) {
		$questions = array();
		$n         = 0;
		foreach ( $m as $group ) {
			preg_match_all( '#<li>(.*?)</li>#s', $group[2], $li );
			foreach ( $li[1] as $q ) {
				++$n;
				$questions[] = array(
					'@type'           => 'Question',
					'@id'             => $url . '#question-' . $n,
					'name'            => paulus_schema_text( $q ),
					'eduQuestionType' => 'Open ended',
					'about'           => array( '@type' => 'Thing', 'name' => paulus_schema_text( $group[1] ) ),
				);
			}
		}
		$nodes[] = array(
			'@type'               => 'Quiz',
			'@id'                 => $url . '#study-guide',
			'name'                => get_the_title(),
			'url'                 => $url,
			'about'               => array( '@id' => paulus_sid( 'paul-of-tarsus' ) ),
			'educationalUse'      => 'discussion',
			'learningResourceType' => 'study guide',
			'audience'            => array( '@type' => 'EducationalAudience', 'educationalRole' => 'student' ),
			'inLanguage'          => paulus_site_language(),
			'teaches'             => array_values( array_unique( array_map( static function ( $g ) {
				return paulus_schema_text( $g[1] );
			}, $m ) ) ),
			'hasPart'             => $questions,
		);
	}

	// The sources: the bibliography, grouped under its headings.
	if ( 'sources' === $slug && preg_match_all( '#<h2[^>]*>(.*?)</h2>\s*<ul[^>]*>(.*?)</ul>#s', $content, $m, PREG_SET_ORDER ) ) {
		$items = array();
		foreach ( $m as $group ) {
			preg_match_all( '#<li>(.*?)</li>#s', $group[2], $li );
			$genre = paulus_schema_text( $group[1] );
			foreach ( $li[1] as $entry ) {
				$text = paulus_schema_text( $entry );
				// The kind of work, from the list it sits in and its form: the
				// photographs are photographs, an address is a web page, a
				// title in quotation marks is an article, the rest are books.
				if ( false !== stripos( $genre, 'Photograph' ) ) {
					$type = 'Photograph';
				} elseif ( preg_match( '/\\bMS\\.? ?\\d|\\bmanuscript\\b|\\bcodex\\b|\\bpapyrus\\b/i', $text ) ) {
					$type = 'Manuscript';
				} elseif ( preg_match( '#https?://#', $text ) ) {
					$type = 'WebPage';
				} elseif ( preg_match( '/[“"].+[”"]/u', $text ) ) {
					$type = 'ScholarlyArticle';
				} else {
					$type = 'Book';
				}
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => count( $items ) + 1,
					'item'     => array( '@type' => $type, 'name' => mb_substr( $text, 0, 400 ), 'genre' => $genre ),
				);
			}
		}
		$nodes[] = array( '@type' => 'ItemList', '@id' => $url . '#bibliography', 'name' => get_the_title(), 'numberOfItems' => count( $items ), 'itemListElement' => $items );
	}

	// The charts on a page: each a dataset drawn from its source.
	if ( function_exists( 'paulus_charts' ) && preg_match_all( '#\[paulus_chart id="([a-z0-9-]+)"#', $content, $m ) ) {
		$charts = paulus_charts();
		foreach ( array_unique( $m[1] ) as $id ) {
			if ( empty( $charts[ $id ] ) ) {
				continue;
			}
			$c     = $charts[ $id ];
			$rows  = array();
			foreach ( $c['rows'] as $row ) {
				$vals   = (array) $row[1];
				$pairs  = array();
				foreach ( $vals as $k => $v ) {
					$pairs[] = ( isset( $c['series'][ $k ] ) ? $c['series'][ $k ] . ' ' : '' ) . $v . ( $c['unit'] ?? '' );
				}
				$rows[] = $row[0] . ': ' . implode( ', ', $pairs );
			}
			$data = array(
				'@type'           => 'Dataset',
				'@id'             => $url . '#chart-' . $id,
				'name'            => $c['title'],
				'description'     => $c['title'] . ( ! empty( $c['note'] ) ? ' (' . $c['note'] . ')' : '' ) . '. ' . implode( '; ', $rows ) . '.',
				'creator'         => array( '@id' => paulus_sid( 'pew-research-center' ) ),
				'variableMeasured' => $c['title'],
				'isAccessibleForFree' => true,
			);
			if ( ! empty( $c['series'] ) ) {
				$years = array_filter( (array) $c['series'], static function ( $s ) {
					return (bool) preg_match( '/^\d{4}$/', (string) $s );
				} );
				if ( count( $years ) >= 2 ) {
					$data['temporalCoverage'] = min( $years ) . '/' . max( $years );
				}
			}
			if ( ! empty( $c['source'][1] ) ) {
				$data['isBasedOn'] = $c['source'][1];
				$data['citation']  = $c['source'][0];
			}
			$nodes[] = $data;
		}
	}

	// Martin's notes: the manuscript itself, and its file.
	if ( 'dale-b-martin-luke-versus-paul' === $slug ) {
		$pdf = PAULUS_URI . '/assets/docs/dale-b-martin-luke-versus-paul.pdf';
		$nodes[] = array(
			'@type'               => 'Manuscript',
			'@id'                 => $url . '#manuscript',
			'name'                => '“Luke” versus Paul',
			'description'         => __( 'Unfinished notes by Dale B. Martin on the Paul of the Acts of the Apostles and the Paul of his own letters, published with his permission.', 'paulus' ),
			'author'              => array( '@id' => paulus_sid( 'dale-b-martin' ) ),
			'dateCreated'         => '2019',
			'inLanguage'          => 'en',
			'about'               => array( array( '@id' => paulus_sid( 'paul-of-tarsus' ) ), array( '@id' => paulus_sid( 'acts-of-the-apostles' ) ) ),
			'publisher'           => array( '@id' => paulus_sid( 'author' ) ),
			'datePublished'       => get_the_date( 'c' ),
			'isAccessibleForFree' => true,
			'url'                 => $url,
			'encoding'            => array( '@type' => 'MediaObject', 'contentUrl' => $pdf, 'encodingFormat' => 'application/pdf', 'name' => __( 'The notes as received (PDF)', 'paulus' ) ),
		);
	}

	// The Journal: a blog and its latest posts.
	if ( function_exists( 'paulus_journal_url' ) && is_post_type_archive( 'paulus_journal' ) ) {
		$posts = array();
		foreach ( get_posts( array( 'post_type' => 'paulus_journal', 'post_status' => 'publish', 'numberposts' => 20 ) ) as $e ) {
			$posts[] = array( '@type' => 'BlogPosting', '@id' => get_permalink( $e ) . '#entry', 'headline' => get_the_title( $e ), 'url' => get_permalink( $e ), 'datePublished' => get_the_date( 'c', $e ), 'author' => array( '@id' => paulus_sid( 'author' ) ) );
		}
		$nodes[] = array(
			'@type'       => 'Blog',
			'@id'         => paulus_journal_url() . '#blog',
			'name'        => (string) paulus_option( 'journal_title' ),
			'description' => (string) paulus_option( 'journal_intro' ),
			'url'         => paulus_journal_url(),
			'author'      => array( '@id' => paulus_sid( 'author' ) ),
			'publisher'   => array( '@id' => paulus_sid( 'publisher' ) ),
			'inLanguage'  => paulus_site_language(),
			'blogPost'    => $posts,
		);
	}

	// The essays among the pages (The Verdict, the answer to Martin's
	// question): an article node, where nothing on the page describes one
	// (the theme's own path; Rank Math supplies its own). paulus_schema_deepen()
	// then deepens it like every article.
	if ( in_array( $slug, array( 'the-verdict', 'why-luke-does-not-know-pauls-letters' ), true ) && empty( $GLOBALS['paulus_sd_has_article'] ) && function_exists( 'paulus_article_schema' ) ) {
		$nodes[] = array( '@id' => $url . '#article' ) + paulus_article_schema( array( '@id' => paulus_sid( 'author' ) ), array( '@id' => paulus_sid( 'publisher' ) ), array( '@id' => home_url( '/' ) . '#website' ) );
	}

	// Photographed works of art in the text: each a VisualArtwork, a Painting,
	// a Sculpture, a Drawing, a Manuscript or a Map.
	foreach ( paulus_schema_artworks( $content ) as $work ) {
		$nodes[] = $work;
	}
	// Photographed places: churches, mosques, landmarks, each with its picture.
	foreach ( paulus_schema_places( $content ) as $place ) {
		$nodes[] = $place;
	}
	// The side rail of an article: its contents, a sidebar of the page.
	if ( is_singular( 'post' ) && function_exists( 'paulus_article_sections' ) && paulus_article_sections( get_queried_object_id() ) ) {
		$nodes[] = array( '@type' => 'WPSideBar', '@id' => $url . '#rail', 'name' => __( 'Contents', 'paulus' ), 'hasPart' => array_map( static function ( $s ) use ( $url ) {
			return array( '@id' => $url . '#' . $s['id'] );
		}, paulus_article_sections( get_queried_object_id() ) ) );
	}
	// The 2023 edition: the English book in progress that the site began
	// as, an edition of the work, with its date of publication.
	if ( 'the-2023-edition' === $slug ) {
		$nodes[] = array(
			'@type'         => 'Book',
			'@id'           => $url . '#edition',
			'name'          => (string) paulus_option( 'book_title_en' ),
			'bookEdition'   => '2023 edition (English, in progress)',
			'datePublished' => '2023-07-30',
			'inLanguage'    => 'en',
			'author'        => array( '@id' => paulus_sid( 'author' ) ),
			'exampleOfWork' => array( '@id' => paulus_sid( 'book' ) ),
			'url'           => $url,
		);
	}
	// The book page: the chapters of the book, each its own node.
	if ( 'the-book' === $slug ) {
		foreach ( paulus_schema_chapters() as $chapter ) {
			$nodes[] = paulus_schema_chapter_node( $chapter );
		}
	}
	return $nodes;
}

/**
 * The art form of a figure, read from its description.
 *
 * @param string $alt Description.
 * @return string
 */
function paulus_schema_artform( $alt ) {
	$forms = array(
		'painting'  => 'Painting',
		'self-portrait' => 'Painting',
		'fresco'    => 'Fresco',
		'mosaic'    => 'Mosaic',
		'relief'    => 'Sculpture',
		'statue'    => 'Sculpture',
		'bust'      => 'Sculpture',
		'graffito'  => 'Graffito',
		'engraving' => 'Engraving',
		'enamel'    => 'Enamel',
		'cartoon'   => 'Drawing',
		'icon'      => 'Icon',
		'manuscript' => 'Manuscript',
		'codex'     => 'Manuscript',
		'papyrus'   => 'Manuscript',
		'scroll'    => 'Manuscript',
		'woodcut'   => 'Woodcut',
		'map '      => 'Map',
	);
	foreach ( $forms as $needle => $form ) {
		if ( false !== stripos( $alt, $needle ) ) {
			return $form;
		}
	}
	return '';
}

/**
 * The works of art photographed in the current text.
 *
 * @param string $content Content.
 * @return array
 */
function paulus_schema_artworks( $content ) {
	if ( ! function_exists( 'paulus_figures' ) || ! preg_match_all( '#\[paulus_figure name="([a-z0-9-]+)"[^\]]*\](.*?)\[/paulus_figure\]#s', $content, $m, PREG_SET_ORDER ) ) {
		return array();
	}
	$figs = paulus_figures();
	$out  = array();
	foreach ( $m as $row ) {
		$f = $figs[ $row[1] ] ?? null;
		if ( ! $f ) {
			continue;
		}
		$form = paulus_schema_artform( $f['alt'] );
		if ( '' === $form ) {
			continue;
		}
		$img   = PAULUS_URI . '/assets/images/church/' . $row[1] . '.avif';
		$cap   = paulus_schema_text( $row[2] );
		// schema.org's own kinds of work where it has one (Painting,
		// Sculpture, Drawing, Manuscript, Map); the rest are VisualArtwork
		// with the form named.
		$kinds = array( 'Painting' => 'Painting', 'Fresco' => 'Painting', 'Sculpture' => 'Sculpture', 'Drawing' => 'Drawing', 'Manuscript' => 'Manuscript', 'Map' => 'Map' );
		$type  = $kinds[ $form ] ?? 'VisualArtwork';
		$same  = paulus_schema_figure_links()[ $row[1] ] ?? '';
		$out[] = array_filter(
			array(
				'@type'       => $type,
				'@id'         => $img . '#work',
				'name'        => mb_substr( rtrim( $cap, '.' ), 0, 160 ),
				'description' => $f['alt'],
				'artform'     => $form,
				'image'       => array( '@id' => $img . '#image' ),
				'sameAs'      => $same ? array( 'https://en.wikipedia.org/wiki/' . $same ) : null,
			)
		) + array(
			'about' => false !== stripos( $cap . $f['alt'], 'Paul' ) ? array( '@id' => paulus_sid( 'paul-of-tarsus' ) ) : null,
		);
		$last = count( $out ) - 1;
		if ( null === $out[ $last ]['about'] ) {
			unset( $out[ $last ]['about'] );
		}
	}
	return $out;
}

/**
 * Deepen one node in the context of the current page.
 *
 * @param array $node Node.
 * @return array
 */
function paulus_schema_deepen_node( $node ) {
	if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
		return $node;
	}
	$content = paulus_schema_content();
	$url     = is_singular() ? get_permalink() : '';
	$slug    = is_page() ? get_post_field( 'post_name', get_queried_object_id() ) : '';
	$paul    = array( '@id' => paulus_sid( 'paul-of-tarsus' ) );
	$pub     = (string) paulus_option( 'pub_name' );
	$author  = (string) paulus_option( 'book_author' );

	// The publisher: contact point and published policies.
	if ( paulus_node_is( $node, array( 'Organization' ) ) && ( $node['name'] ?? '' ) === $pub ) {
		$email = sanitize_email( (string) paulus_option( 'pub_email' ) );
		if ( $email ) {
			$node = paulus_node_default( $node, 'email', $email );
			$node = paulus_node_default( $node, 'contactPoint', array( '@type' => 'ContactPoint', 'contactType' => 'editorial', 'email' => $email, 'availableLanguage' => array( 'en', 'ms' ) ) );
		}
		$terms   = get_page_by_path( 'terms-of-use' );
		$contact = get_page_by_path( 'contact' );
		if ( $terms && 'publish' === $terms->post_status ) {
			$node = paulus_node_default( $node, 'publishingPrinciples', get_permalink( $terms ) );
		}
		if ( $contact && 'publish' === $contact->post_status ) {
			$node = paulus_node_default( $node, 'correctionsPolicy', get_permalink( $contact ) );
		}
		if ( paulus_option( 'pub_reg' ) ) {
			$node = paulus_node_default( $node, 'identifier', array( '@type' => 'PropertyValue', 'propertyID' => 'Company registration number', 'value' => (string) paulus_option( 'pub_reg' ) ) );
		}
		return $node;
	}

	// The author: the same person on every page, with profiles. Rank Math's
	// author node is recognised by its identifier (see paulus_schema_deepen).
	$is_author = ( $node['name'] ?? '' ) === $author || ( ! empty( $node['@id'] ) && ( $GLOBALS['paulus_sd_ids']['author'] ?? '' ) === $node['@id'] );
	if ( paulus_node_is( $node, array( 'Person' ) ) && $is_author && function_exists( 'paulus_person_schema' ) ) {
		$person = paulus_person_schema();
		foreach ( $person as $k => $v ) {
			if ( '@type' !== $k ) {
				$node = paulus_node_default( $node, $k, $v );
			}
		}
		// Rank Math fills sameAs from the user's profile (the website field,
		// often this site itself), which would keep the author's profiles out:
		// the two lists are merged, the site's own address dropped.
		if ( ! empty( $person['sameAs'] ) ) {
			$same = array();
			foreach ( array_merge( $person['sameAs'], (array) ( $node['sameAs'] ?? array() ) ) as $paulus_url ) {
				$paulus_key = untrailingslashit( strtolower( (string) $paulus_url ) );
				if ( '' !== $paulus_key && untrailingslashit( strtolower( home_url( '/' ) ) ) !== $paulus_key && ! isset( $same[ $paulus_key ] ) ) {
					$same[ $paulus_key ] = $paulus_url;
				}
			}
			$node['sameAs'] = array_values( $same );
		}
		// Rank Math gives the author the address of the author archive, which
		// the theme redirects to the author's page: the node takes that page.
		if ( ! empty( $node['url'] ) && paulus_is_author_archive_url( $node['url'] ) ) {
			$about       = get_page_by_path( 'about-the-author' );
			$node['url'] = $about && 'publish' === $about->post_status ? get_permalink( $about ) : home_url( '/' );
		}
		$node = paulus_node_default( $node, 'knowsLanguage', array( 'ms', 'en' ) );
		return paulus_node_default( $node, 'knowsAbout', array( $paul, 'Christian origins', 'Islamic apologetics' ) );
	}

	// The site.
	if ( paulus_node_is( $node, array( 'WebSite' ) ) ) {
		$node = paulus_node_default( $node, 'about', $paul );
		$node = paulus_node_default( $node, 'inLanguage', paulus_site_language() );
		$node = paulus_node_default( $node, 'copyrightHolder', array( '@id' => paulus_sid( 'author' ) ) );
		$node = paulus_node_default( $node, 'audience', array( '@type' => 'Audience', 'audienceType' => __( 'Readers of Christian origins and Islamic apologetics', 'paulus' ) ) );
		return paulus_node_default( $node, 'isBasedOn', array( '@id' => paulus_sid( 'book' ) ) );
	}

	// The page node: its most specific kind, its subject and its frame.
	if ( paulus_node_is( $node, array( 'WebPage', 'CollectionPage', 'ItemPage', 'FAQPage', 'SearchResultsPage', 'ContactPage', 'AboutPage', 'ProfilePage', 'ImageGallery' ) )
		&& ! paulus_node_is( $node, array( 'Article', 'BlogPosting', 'ScholarlyArticle' ) ) ) {
		$kind = array(
			'contact'              => 'ContactPage',
			'about-the-author'     => 'ProfilePage',
			'paul-in-the-churches' => 'ImageGallery',
			'reference'            => 'CollectionPage',
			'appendices'           => 'CollectionPage',
			'sitemap'              => 'CollectionPage',
		);
		if ( isset( $kind[ $slug ] ) && ! paulus_node_is( $node, array( $kind[ $slug ] ) ) ) {
			$node = paulus_node_add_type( $node, $kind[ $slug ] );
		}
		if ( ! is_404() && ! is_search() ) {
			$node = paulus_node_default( $node, 'about', $paul );
		}
		$node = paulus_node_default( $node, 'hasPart', array_merge( array( array( '@id' => paulus_sid( 'header' ) ), array( '@id' => paulus_sid( 'footer' ) ) ), is_singular() && ! is_404() ? paulus_schema_sections( $content, $url ) : array() ) );
		if ( is_singular() && ! is_404() ) {
			$mentions = paulus_schema_mentions( $content );
			if ( $mentions ) {
				$node = paulus_node_default( $node, 'mentions', $mentions );
			}
		}
		$main = array(
			'glossary'                       => '#glossary',
			'study-questions'                => '#study-guide',
			'sources'                        => '#bibliography',
			'chronology'                     => '#timeline',
			'dale-b-martin-luke-versus-paul' => '#manuscript',
			'reference'                      => '#pages',
			'appendices'                     => '#pages',
			'sitemap'                        => '#contents',
		);
		if ( isset( $main[ $slug ] ) ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => $url . $main[ $slug ] ) );
		}
		if ( 'contact' === $slug ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => paulus_sid( 'publisher' ) ) );
		}
		if ( 'about-the-author' === $slug ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => paulus_sid( 'author' ) ) );
		}
		if ( is_front_page() ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => paulus_sid( 'sections' ) ) );
		}
		if ( function_exists( 'paulus_journal_url' ) && is_post_type_archive( 'paulus_journal' ) ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => paulus_journal_url() . '#blog' ) );
			// A year or month of the Journal covers that period.
			$year  = (int) get_query_var( 'journal_year' );
			$month = (int) get_query_var( 'journal_monthnum' );
			if ( $year ) {
				$node = paulus_node_default( $node, 'temporalCoverage', $month ? sprintf( '%04d-%02d', $year, $month ) : sprintf( '%04d', $year ) );
			}
		}
		if ( 'the-2023-edition' === $slug ) {
			$node = paulus_node_default( $node, 'mainEntity', array( '@id' => $url . '#edition' ) );
		}
		if ( 'paul-in-the-churches' === $slug && preg_match_all( '#\[paulus_figure name="([a-z0-9-]+)"#', $content, $fm ) ) {
			$node = paulus_node_default( $node, 'associatedMedia', array_map( static function ( $f ) {
				return array( '@id' => PAULUS_URI . '/assets/images/church/' . $f . '.avif#image' );
			}, array_values( array_unique( $fm[1] ) ) ) );
		}
		return $node;
	}

	// The article node, on articles, Journal entries and pages.
	if ( paulus_node_is( $node, array( 'Article', 'BlogPosting', 'NewsArticle', 'ScholarlyArticle', 'TechArticle' ) ) && is_singular() ) {
		$post = get_queried_object();
		// An image nested in the article (its featured image) is credited as
		// any image node is: a registered painting takes its painter's credit.
		if ( ! empty( $node['image'] ) && is_array( $node['image'] ) ) {
			if ( isset( $node['image']['@type'] ) ) {
				$node['image'] = paulus_schema_deepen_node( $node['image'] );
			} elseif ( array_keys( $node['image'] ) === range( 0, count( $node['image'] ) - 1 ) ) {
				$node['image'] = array_map( static function ( $img ) {
					return is_array( $img ) && isset( $img['@type'] ) ? paulus_schema_deepen_node( $img ) : $img;
				}, $node['image'] );
			}
		}
		if ( is_singular( 'paulus_journal' ) ) {
			$node = paulus_node_add_type( $node, 'BlogPosting' );
			$node = paulus_node_default( $node, 'isPartOf', array( '@id' => paulus_journal_url() . '#blog' ) );
		} elseif ( is_singular( 'post' ) || 'why-luke-does-not-know-pauls-letters' === $slug ) {
			// Footnoted argument from the sources: a scholarly article. An SEO
			// plugin's default BlogPosting gives way: these are not blog posts.
			$node['@type'] = array( 'Article', 'ScholarlyArticle' );
		}
		$node = paulus_node_default( $node, 'about', 'why-luke-does-not-know-pauls-letters' === $slug ? array( $paul, array( '@id' => paulus_sid( 'acts-of-the-apostles' ) ) ) : $paul );
		$mentions = paulus_schema_mentions( $content );
		if ( $mentions ) {
			$node = paulus_node_default( $node, 'mentions', $mentions );
		}
		if ( is_singular( 'post' ) ) {
			// The book, and the chapter of it the article is drawn from: the
			// chapter joins whatever the node already names as its basis.
			$node    = paulus_node_default( $node, 'isBasedOn', array( '@id' => paulus_sid( 'book' ) ) );
			$chapter = paulus_schema_chapter_of( $post->ID );
			if ( $chapter ) {
				$based = isset( $node['isBasedOn']['@type'] ) || isset( $node['isBasedOn']['@id'] ) ? array( $node['isBasedOn'] ) : (array) $node['isBasedOn'];
				if ( ! paulus_schema_refers( $based, 'Chapter' ) ) {
					$based[]           = paulus_schema_chapter_node( $chapter );
					$node['isBasedOn'] = array_values( $based );
				}
			}
		}
		$node = paulus_node_default( $node, 'potentialAction', array( '@type' => 'ReadAction', 'target' => $url ) );
		if ( 'why-luke-does-not-know-pauls-letters' === $slug ) {
			$archive = get_page_by_path( 'appendices/dale-b-martin-luke-versus-paul' );
			if ( $archive ) {
				$node = paulus_node_default( $node, 'isBasedOn', array( '@id' => get_permalink( $archive ) . '#manuscript' ) );
			}
		}
		$words = function_exists( 'paulus_word_count' ) ? paulus_word_count( $content ) : 0;
		$node  = paulus_node_default( $node, 'wordCount', $words ?: null );
		if ( $words ) {
			$node = paulus_node_default( $node, 'timeRequired', 'PT' . max( 1, (int) round( $words / 230 ) ) . 'M' );
		}
		$node = paulus_node_default( $node, 'inLanguage', paulus_site_language() );
		$node = paulus_node_default( $node, 'isAccessibleForFree', true );
		$node = paulus_node_default( $node, 'copyrightHolder', array( '@id' => paulus_sid( 'author' ) ) );
		$node = paulus_node_default( $node, 'copyrightYear', (int) get_the_date( 'Y', $post ) );
		$node = paulus_node_default( $node, 'speakable', array( '@type' => 'SpeakableSpecification', 'cssSelector' => array( '.paulus-hero-panel__title', '.paulus-hero-panel__standfirst' ) ) );
		if ( is_singular( 'post' ) ) {
			$part = paulus_part_of( $post->ID );
			if ( $part ) {
				$label = (string) get_post_meta( $post->ID, '_paulus_label', true );
				$node  = paulus_node_default( $node, 'articleSection', $part->name . ( $label ? ', ' . $label : '' ) );
			}
			$series = function_exists( 'paulus_series_parts' ) ? paulus_series_parts( $post->ID ) : null;
			if ( $series && ! isset( $node['position'] ) ) {
				$node['position'] = (int) get_post_meta( $post->ID, '_paulus_part_no', true );
				$node['isPartOf'] = array_values( array_filter( array_merge( (array) ( isset( $node['isPartOf'] ) && ! isset( $node['isPartOf']['@type'] ) ? array( $node['isPartOf'] ) : array() ), array( array( '@type' => 'CreativeWorkSeries', 'name' => $series['title'] ) ) ) ) );
			}
		}
		$cites = paulus_schema_citation_nodes( $content, $url );
		if ( $cites ) {
			$node = paulus_node_default( $node, 'citation', $cites );
		}
		$parts = array_merge( paulus_schema_sections( $content, $url ), paulus_schema_quotations( $content, $url ), paulus_schema_source_quotations( $content, $url ), paulus_schema_tables( $content, $url ) );
		if ( $parts ) {
			$node = paulus_node_default( $node, 'hasPart', $parts );
		}
		return $node;
	}

	// The book, on its page: how to buy it, and its chapters.
	if ( paulus_node_is( $node, array( 'Book' ) ) && is_page( 'the-book' ) && ( $node['name'] ?? '' ) === (string) paulus_option( 'book_title' ) ) {
		if ( paulus_option( 'book_buy_url' ) ) {
			$node = paulus_node_default( $node, 'potentialAction', array( '@type' => 'BuyAction', 'target' => (string) paulus_option( 'book_buy_url' ) ) );
		}
		$chapters = paulus_schema_chapters();
		if ( $chapters ) {
			$node = paulus_node_default( $node, 'hasPart', array_map( static function ( $c ) {
				return array( '@id' => paulus_sid( 'book' ) . '-chapter-' . $c['position'] );
			}, array_values( $chapters ) ) );
		}
		return $node;
	}

	// The site's own illustrations (portraits, featured images, the cover):
	// image metadata crediting the author, with the Terms of Use as the
	// licence and the Contact page as the place to ask permission.
	if ( paulus_node_is( $node, array( 'ImageObject' ) ) ) {
		$src = (string) ( $node['contentUrl'] ?? $node['url'] ?? $node['@id'] ?? '' );
		// A featured image that is someone else's work (a painting also
		// registered as a figure) takes that work's credit and licence.
		$key  = preg_replace( '/(-[0-9a-f]{8})?(-\d+x\d+|-scaled)?\.(avif|jpe?g|png|webp)$/i', '', basename( (string) wp_parse_url( $src, PHP_URL_PATH ) ) );
		$figs = function_exists( 'paulus_figures' ) ? paulus_figures() : array();
		if ( '' !== $src && isset( $figs[ $key ] ) && false === strpos( $src, '/assets/images/church/' ) ) {
			$f    = $figs[ $key ];
			$lic  = 'Public domain' === $f['license'] ? 'https://creativecommons.org/publicdomain/mark/1.0/' : (string) $f['license_url'];
			$node['creditText'] = $f['author'] . ', ' . $f['license'];
			unset( $node['copyrightNotice'], $node['copyrightHolder'], $node['creator'], $node['acquireLicensePage'] );
			if ( $lic ) {
				$node['license'] = $lic;
			}
			return $node;
		}
		if ( '' !== $src && false === strpos( $src, '/assets/images/church/' ) && ( false !== strpos( $src, '/wp-content/uploads/' ) || false !== strpos( $src, '/assets/images/' ) ) && empty( $node['license'] ) ) {
			$name  = (string) paulus_option( 'book_author' );
			$terms = get_page_by_path( 'terms-of-use' );
			$ask   = get_page_by_path( 'contact' );
			$node  = paulus_node_default( $node, 'creator', array( '@id' => paulus_sid( 'author' ) ) );
			$node  = paulus_node_default( $node, 'creditText', $name . ', ' . get_bloginfo( 'name' ) );
			$node  = paulus_node_default( $node, 'copyrightNotice', '© ' . $name );
			$node  = paulus_node_default( $node, 'copyrightHolder', array( '@id' => paulus_sid( 'author' ) ) );
			if ( $terms && 'publish' === $terms->post_status ) {
				$node = paulus_node_default( $node, 'license', get_permalink( $terms ) );
			}
			if ( $ask && 'publish' === $ask->post_status ) {
				$node = paulus_node_default( $node, 'acquireLicensePage', get_permalink( $ask ) );
			}
			return $node;
		}
	}

	// Photographs of works of art point to the works themselves.
	if ( paulus_node_is( $node, array( 'ImageObject' ) ) && ! empty( $node['@id'] ) && false !== strpos( $node['@id'], '/assets/images/church/' ) ) {
		$key = preg_replace( '/\.(avif|webp)$/', '', basename( (string) wp_parse_url( $node['@id'], PHP_URL_PATH ) ) );
		$f   = function_exists( 'paulus_figures' ) ? ( paulus_figures()[ $key ] ?? null ) : null;
		if ( $f && '' !== paulus_schema_artform( $f['alt'] ) ) {
			$node = paulus_node_default( $node, 'about', array( '@id' => preg_replace( '/#image$/', '#work', $node['@id'] ) ) );
		}
		return $node;
	}
	return $node;
}

/**
 * Deepen a graph and add the nodes it lacks. Keys are kept, so Rank Math's
 * keyed entities pass through its own entity linking unchanged.
 *
 * @param array $nodes Graph nodes (a list, or Rank Math's keyed array).
 * @return array
 */
function paulus_schema_deepen( $nodes ) {
	if ( ! is_array( $nodes ) || is_admin() || is_feed() ) {
		return $nodes;
	}
	// Find the nodes that already describe the author and the publisher.
	$GLOBALS['paulus_sd_ids'] = array();
	foreach ( $nodes as $node ) {
		if ( ! is_array( $node ) || empty( $node['@id'] ) ) {
			continue;
		}
		// The author is known by name, or, in Rank Math's graph, by the
		// address it gives its author node (the author archive), since Rank
		// Math names that node after the WordPress user, not the book's author.
		if ( paulus_node_is( $node, array( 'Person' ) ) && empty( $GLOBALS['paulus_sd_ids']['author'] )
			&& ( ( $node['name'] ?? '' ) === (string) paulus_option( 'book_author' ) || paulus_is_author_archive_url( $node['@id'] ) || paulus_is_author_archive_url( $node['url'] ?? '' ) ) ) {
			$GLOBALS['paulus_sd_ids']['author'] = $node['@id'];
		}
		if ( paulus_node_is( $node, array( 'Organization' ) ) && ( $node['name'] ?? '' ) === (string) paulus_option( 'pub_name' ) && empty( $GLOBALS['paulus_sd_ids']['publisher'] ) ) {
			$GLOBALS['paulus_sd_ids']['publisher'] = $node['@id'];
		}
	}
	$GLOBALS['paulus_sd_has_article'] = false;
	foreach ( $nodes as $node ) {
		if ( is_array( $node ) && paulus_node_is( $node, array( 'Article', 'BlogPosting', 'ScholarlyArticle', 'NewsArticle', 'TechArticle' ) ) ) {
			$GLOBALS['paulus_sd_has_article'] = true;
		}
	}
	$ids = array();
	foreach ( $nodes as $k => $node ) {
		$nodes[ $k ] = paulus_schema_deepen_node( $node );
		if ( is_array( $nodes[ $k ] ) && ! empty( $nodes[ $k ]['@id'] ) ) {
			$ids[ $nodes[ $k ]['@id'] ] = true;
		}
	}
	// Page elements and components, then every site entity the graph now
	// refers to, each as a node of its own. The deepened graph is encoded
	// once for all the lookups below; only the short list of added nodes,
	// which grows as they are found, is encoded again.
	$extra = array_merge( paulus_schema_page_elements(), array_map( 'paulus_schema_deepen_node', paulus_schema_component_nodes() ) );
	$json  = (string) wp_json_encode( $nodes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$refers = static function ( $id ) use ( $json, &$extra ) {
		return false !== strpos( $json, '"' . $id . '"' ) || paulus_schema_refers( $extra, $id );
	};
	foreach ( paulus_schema_entities() as $entity ) {
		unset( $entity['__re'] );
		if ( $refers( $entity['@id'] ) ) {
			$extra[] = $entity;
		}
	}
	if ( $refers( paulus_sid( 'book' ) ) && ! isset( $ids[ paulus_sid( 'book' ) ] ) ) {
		$extra[] = paulus_schema_book_ref();
	}
	// The author and publisher, where no node on the page describes them.
	if ( empty( $GLOBALS['paulus_sd_ids']['author'] ) && ! isset( $ids[ paulus_sid( 'author' ) ] ) && $refers( paulus_sid( 'author' ) ) ) {
		$extra[] = paulus_schema_deepen_node( array( '@id' => paulus_sid( 'author' ) ) + paulus_person_schema() );
	}
	if ( empty( $GLOBALS['paulus_sd_ids']['publisher'] ) && ! isset( $ids[ paulus_sid( 'publisher' ) ] ) && $refers( paulus_sid( 'publisher' ) ) ) {
		$extra[] = paulus_schema_deepen_node( array( '@id' => paulus_sid( 'publisher' ) ) + paulus_org_schema() );
	}
	// Nodes on other pages that this page points to: named, with their address.
	if ( function_exists( 'paulus_journal_url' ) && $refers( paulus_journal_url() . '#blog' ) && ! is_post_type_archive( 'paulus_journal' ) ) {
		$extra[] = array( '@type' => 'Blog', '@id' => paulus_journal_url() . '#blog', 'name' => (string) paulus_option( 'journal_title' ), 'url' => paulus_journal_url() );
	}
	$archive = get_page_by_path( 'appendices/dale-b-martin-luke-versus-paul' );
	if ( $archive && ! is_page( $archive->ID ) && $refers( get_permalink( $archive ) . '#manuscript' ) ) {
		$extra[] = array( '@type' => 'Manuscript', '@id' => get_permalink( $archive ) . '#manuscript', 'name' => '“Luke” versus Paul', 'author' => array( '@id' => paulus_sid( 'dale-b-martin' ) ), 'url' => get_permalink( $archive ) );
		// Martin's own node follows unless the graph describes him already
		// (the entity loop above adds it when the text names him).
		if ( ! isset( $ids[ paulus_sid( 'dale-b-martin' ) ] ) && ! paulus_schema_refers( $extra, paulus_sid( 'dale-b-martin' ) ) ) {
			$extra[] = array_diff_key( paulus_schema_entities()['martin'], array( '__re' => 1 ) );
		}
	}
	$i = 0;
	foreach ( $extra as $node ) {
		if ( ! empty( $node['@id'] ) && isset( $ids[ $node['@id'] ] ) ) {
			continue;
		}
		if ( ! empty( $node['@id'] ) ) {
			$ids[ $node['@id'] ] = true;
		}
		$nodes[ 'paulus_deep_' . ( $i++ ) ] = $node;
	}
	return $nodes;
}

/**
 * The FAQPage of the front page's questions, from what the section showed.
 *
 * @return array
 */
function paulus_schema_front_faq() {
	$shown = $GLOBALS['paulus_front_faq'] ?? null;
	if ( ! $shown && function_exists( 'paulus_answers' ) ) {
		$page  = get_page_by_path( 'answers' );
		$shown = $page ? array( 'url' => get_permalink( $page ), 'items' => array_slice( paulus_answers(), 0, 6 ) ) : null;
	}
	if ( empty( $shown['items'] ) ) {
		return array();
	}
	$questions = array();
	foreach ( $shown['items'] as $item ) {
		// The answer as the reader sees it, without the "Full evidence" line.
		// Typography as WordPress displays it (curly apostrophes and quotes),
		// so the marked-up answer is word for word the one on the page.
		$answer = paulus_schema_text( wptexturize( preg_replace( '#<p class="paulus-readmore">.*?</p>#s', '', (string) $item['answer'] ) ) );
		if ( '' === $answer ) {
			continue;
		}
		$questions[] = array(
			'@type'          => 'Question',
			'@id'            => home_url( '/' ) . '#faq-' . $item['id'],
			'name'           => paulus_schema_text( wptexturize( $item['question'] ) ),
			'url'            => $shown['url'] . '#' . $item['id'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
				'url'   => $shown['url'] . '#' . $item['id'],
			),
		);
	}
	if ( ! $questions ) {
		return array();
	}
	return array(
		'@type'      => 'FAQPage',
		'@id'        => home_url( '/' ) . '#faq',
		'name'       => __( 'Answers to missionary claims', 'paulus' ),
		'url'        => home_url( '/' ),
		'inLanguage' => paulus_site_language(),
		'about'      => array( '@id' => paulus_sid( 'paul-of-tarsus' ) ),
		'isPartOf'   => array( '@id' => home_url( '/' ) . '#website' ),
		'mainEntity' => $questions,
	);
}

/**
 * The Wikipedia page of a photographed work or manuscript, by figure slug,
 * where the work is a known one.
 *
 * @return array<string,string>
 */
function paulus_schema_figure_links() {
	return array(
		'parmigianino-conversion'    => 'Conversion_of_Saint_Paul_(Parmigianino)',
		'caravaggio-conversion'      => 'Conversion_on_the_Way_to_Damascus',
		'caravaggio-odescalchi'      => 'Conversion_of_Saint_Paul_(Caravaggio)',
		'rembrandt-stephen'          => 'The_Stoning_of_Saint_Stephen',
		'rembrandt-prison'           => 'Saint_Paul_in_Prison',
		'rembrandt-self-paul'        => 'Self-Portrait_as_the_Apostle_Paul',
		'raphael-elymas'             => 'The_Conversion_of_the_Proconsul',
		'raphael-lystra'             => 'The_Sacrifice_at_Lystra',
		'alexamenos-graffito'        => 'Alexamenos_graffito',
		'papyrus-46'                 => 'Papyrus_46',
		'sinaiticus'                 => 'Codex_Sinaiticus',
		'codex-alexandrinus'         => 'Codex_Alexandrinus',
		'isaiah-scroll'              => 'Isaiah_Scroll',
		'nag-hammadi'                => 'Nag_Hammadi_library',
		'birmingham-quran'           => 'Birmingham_Quran_manuscript',
		'arch-of-titus-spoils'       => 'Arch_of_Titus',
	);
}

/**
 * The places photographed in the figures: each with schema.org's own kind
 * of place (a church, a mosque, a landmark), its name, and its Wikipedia
 * page where it has one.
 *
 * @return array<string,array{0:string,1:string,2:string}>
 */
function paulus_schema_figure_places() {
	$lm = 'LandmarksOrHistoricalBuildings';
	return array(
		'tarsus-cleopatra-gate' => array( $lm, 'Cleopatra’s Gate, Tarsus', 'Cleopatra%27s_Gate' ),
		'straight-street'       => array( $lm, 'The Street called Straight, Damascus', 'Straight_Street' ),
		'bab-kisan'             => array( $lm, 'Bab Kisan, Damascus', 'Bab_Kisan' ),
		'corinth-apollo'        => array( $lm, 'Temple of Apollo, Corinth', 'Temple_of_Apollo_(Corinth)' ),
		'minaret-isa'           => array( 'Mosque', 'Umayyad Mosque, Damascus', 'Umayyad_Mosque' ),
		'umayyad-interior'      => array( 'Mosque', 'Umayyad Mosque, Damascus', 'Umayyad_Mosque' ),
		'celsus-library'        => array( $lm, 'Library of Celsus, Ephesus', 'Library_of_Celsus' ),
		'tomb-of-paul'          => array( 'Church', 'Basilica of Saint Paul Outside the Walls, Rome', 'Basilica_of_Saint_Paul_Outside_the_Walls' ),
		'lateran-ciborium'      => array( 'Church', 'Archbasilica of Saint John Lateran, Rome', 'Archbasilica_of_Saint_John_Lateran' ),
		'monnot-lateran-statue' => array( 'Church', 'Archbasilica of Saint John Lateran, Rome', 'Archbasilica_of_Saint_John_Lateran' ),
		'tadolini-statue'       => array( $lm, 'Saint Peter’s Square, Rome', 'St._Peter%27s_Square' ),
		'tre-fontane-beheading' => array( 'Church', 'Abbey of Tre Fontane, Rome', 'Tre_Fontane_Abbey' ),
		'grotto-rabat-malta'    => array( 'Church', 'Saint Paul’s Grotto, Rabat, Malta', 'St._Paul%27s_Grotto' ),
		'traditio-legis'        => array( 'Church', 'Santa Costanza, Rome', 'Santa_Costanza' ),
		'catacomb-christ-peter-paul' => array( $lm, 'Catacombs of Marcellinus and Peter, Rome', 'Catacombs_of_Marcellinus_and_Peter' ),
		'dome-of-the-rock'      => array( 'PlaceOfWorship', 'Dome of the Rock', 'Dome_of_the_Rock' ),
		'pella-ruins'           => array( $lm, 'Pella, Jordan', 'Pella,_Jordan' ),
		'benei-hezir'           => array( $lm, 'Tomb of Benei Hezir, Kidron Valley', 'Tomb_of_Benei_Hezir' ),
		'cordoba-mosque'        => array( 'PlaceOfWorship', 'Mosque–Cathedral of Córdoba', 'Mosque%E2%80%93Cathedral_of_C%C3%B3rdoba' ),
		'malacca-st-pauls'      => array( 'Church', 'St Paul’s Church, Malacca', 'St._Paul%27s_Church,_Malacca' ),
		'herodium'              => array( $lm, 'Herodium', 'Herodium' ),
		'arch-of-titus-spoils'  => array( $lm, 'Arch of Titus, Rome', 'Arch_of_Titus' ),
		'areopagus'             => array( $lm, 'Areopagus, Athens', 'Areopagus' ),
		'etchmiadzin-thecla'    => array( 'Church', 'Etchmiadzin Cathedral', 'Etchmiadzin_Cathedral' ),
		'habib-neccar'          => array( 'Mosque', 'Habib-i Neccar Mosque, Antakya', 'Habib-i_Neccar_Mosque' ),
		'kufa-1915'             => array( 'Mosque', 'Great Mosque of Kufa', 'Great_Mosque_of_Kufa' ),
		'tughrul-rey'           => array( $lm, 'Tughrul Tower, Rey', 'Tughrul_Tower' ),
		'penang-st-george'      => array( 'Church', 'St George’s Church, Penang', 'St._George%27s_Church,_Penang' ),
		'faruqi-grave'          => array( 'Cemetery', 'The grave of Ismaʿil al-Faruqi', '' ),
		'temple-model'          => array( 'Museum', 'Israel Museum, Jerusalem (the model of the Second Temple)', 'Israel_Museum' ),
	);
}

/**
 * The places photographed in the current text, each a node of its kind with
 * the photograph as its picture.
 *
 * @param string $content Content.
 * @return array
 */
function paulus_schema_places( $content ) {
	if ( ! preg_match_all( '#\[paulus_figure name="([a-z0-9-]+)"#', $content, $m ) ) {
		return array();
	}
	$map  = paulus_schema_figure_places();
	$out  = array();
	$seen = array();
	foreach ( $m[1] as $slug ) {
		if ( empty( $map[ $slug ] ) || isset( $seen[ $map[ $slug ][1] ] ) ) {
			continue;
		}
		list( $type, $name, $wiki ) = $map[ $slug ];
		$seen[ $name ] = true;
		$node = array(
			'@type' => $type,
			'@id'   => home_url( '/' ) . '#place-' . sanitize_title( remove_accents( $name ) ),
			'name'  => $name,
			'photo' => array( '@id' => PAULUS_URI . '/assets/images/church/' . $slug . '.avif#image' ),
		);
		if ( '' !== $wiki ) {
			$node['sameAs'] = 'https://en.wikipedia.org/wiki/' . $wiki;
		}
		$out[] = $node;
	}
	return $out;
}

/**
 * The tables in a text, each a Table element named by its column heads.
 *
 * @param string $content Content.
 * @param string $base    Page address.
 * @return array
 */
function paulus_schema_tables( $content, $base ) {
	if ( ! preg_match_all( '#<table[^>]*>(.*?)</table>#s', $content, $m ) ) {
		return array();
	}
	$out = array();
	foreach ( $m[1] as $i => $table ) {
		preg_match_all( '#<th[^>]*>(.*?)</th>#s', $table, $th );
		$heads = array_values( array_filter( array_map( 'paulus_schema_text', $th[1] ) ) );
		$rows  = preg_match_all( '#<tr[^>]*>#', $table ) - ( $heads ? 1 : 0 );
		$out[] = array(
			'@type'       => 'Table',
			'@id'         => $base . '#table-' . ( $i + 1 ),
			'name'        => $heads ? implode( ' / ', $heads ) : sprintf( 'Table %d', $i + 1 ),
			'description' => sprintf( '%d rows', max( 0, $rows ) ),
			'position'    => $i + 1,
		);
	}
	return array_slice( $out, 0, 20 );
}

/**
 * The quotations in other languages: the Greek of each scripture block
 * (SBL Greek New Testament), the Qurʾān in Arabic, and the hadith reports.
 *
 * @param string $content Content.
 * @param string $base    Page address.
 * @return array
 */
function paulus_schema_source_quotations( $content, $base ) {
	$out = array();
	if ( preg_match_all( '#<p class="greek" lang="grc">(.*?)</p>#s', $content, $m ) ) {
		foreach ( $m[1] as $i => $greek ) {
			$text = paulus_schema_text( preg_replace( '#<span class="v">\d+</span>#', '', $greek ) );
			if ( '' === $text ) {
				continue;
			}
			$out[] = array(
				'@type'      => 'Quotation',
				'@id'        => $base . '#greek-' . ( $i + 1 ),
				'text'       => mb_substr( $text, 0, 1000 ),
				'inLanguage' => 'grc',
				'isBasedOn'  => array( '@id' => paulus_sid( 'sblgnt' ) ),
			);
		}
	}
	foreach ( array( 'quran' => paulus_sid( 'quran' ), 'hadith' => '' ) as $class => $source ) {
		if ( ! preg_match_all( '#<(p|span|blockquote|div)\b[^>]*class="[^"]*\b' . $class . '\b[^"]*"[^>]*>(.*?)</\1>#s', $content, $m ) ) {
			continue;
		}
		foreach ( $m[2] as $i => $block ) {
			$text = paulus_schema_text( $block );
			if ( '' === $text || ! preg_match( '/\p{Arabic}/u', $text ) ) {
				continue;
			}
			$node = array(
				'@type'      => 'Quotation',
				'@id'        => $base . '#' . $class . '-' . ( $i + 1 ),
				'text'       => mb_substr( $text, 0, 1000 ),
				'inLanguage' => 'ar',
			);
			if ( $source ) {
				$node['isBasedOn'] = array( '@id' => $source );
			}
			$out[] = $node;
		}
	}
	return array_slice( $out, 0, 60 );
}

/**
 * The chapters of the book, as the manifest records them: each article's
 * source file is named for its chapter (c05-…), so a chapter is known by
 * number and by the articles drawn from it.
 *
 * @return array<int,array{position:int,name:string,slugs:string[]}>
 */
function paulus_schema_chapters() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$chapters = array();
	foreach ( (array) ( paulus_manifest()['posts'] ?? array() ) as $post ) {
		if ( empty( $post['file'] ) || ! preg_match( '/^c(\d\d)-/', (string) $post['file'], $m ) ) {
			continue;
		}
		$n = (int) $m[1];
		if ( ! isset( $chapters[ $n ] ) ) {
			$chapters[ $n ] = array( 'position' => $n, 'name' => (string) ( $post['series_title'] ?? $post['title'] ?? '' ), 'slugs' => array() );
		}
		$chapters[ $n ]['slugs'][] = (string) $post['slug'];
	}
	ksort( $chapters );
	return $cache = $chapters;
}

/**
 * A chapter of the book as a node.
 *
 * @param array $chapter Chapter record.
 * @return array
 */
function paulus_schema_chapter_node( $chapter ) {
	return array(
		'@type'    => 'Chapter',
		'@id'      => paulus_sid( 'book' ) . '-chapter-' . $chapter['position'],
		'name'     => sprintf( 'Chapter %d: %s', $chapter['position'], $chapter['name'] ),
		'position' => $chapter['position'],
		'isPartOf' => array( '@id' => paulus_sid( 'book' ) ),
	);
}

/**
 * The chapter an article is drawn from, or null.
 *
 * @param int $post_id Article.
 * @return array|null
 */
function paulus_schema_chapter_of( $post_id ) {
	$slug = get_post_field( 'post_name', $post_id );
	foreach ( paulus_schema_chapters() as $chapter ) {
		if ( in_array( $slug, $chapter['slugs'], true ) ) {
			return $chapter;
		}
	}
	return null;
}

/**
 * Whether an address is an author archive, which the theme redirects to the
 * author's page (paulus_author_archive_redirect).
 *
 * @param mixed $url Address.
 * @return bool
 */
function paulus_is_author_archive_url( $url ) {
	if ( ! is_string( $url ) || '' === $url || 0 !== strpos( $url, home_url( '/' ) ) ) {
		return false;
	}
	$base = isset( $GLOBALS['wp_rewrite'] ) && $GLOBALS['wp_rewrite']->author_base ? $GLOBALS['wp_rewrite']->author_base : 'author';
	return false !== strpos( $url, '/' . $base . '/' ) || false !== strpos( $url, '?author=' );
}
