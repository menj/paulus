<?php
/**
 * Single source of truth for option defaults.
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Named color schemes. Keys match CSS classes in assets/css/schemes.css.
 *
 * @return array<string,string>
 */
function paulus_schemes() {
	return array(
		'ochre'    => __( 'Ochre (book cover)', 'paulus' ),
		'vellum'   => __( 'Vellum', 'paulus' ),
		'oxblood'  => __( 'Oxblood', 'paulus' ),
		'ink'      => __( 'Ink', 'paulus' ),
		'paper'    => __( 'Paper', 'paulus' ),
		'graphite' => __( 'Graphite', 'paulus' ),
	);
}

/**
 * Default option values.
 *
 * @return array<string,mixed>
 */
function paulus_defaults() {
	return array(
		// Site.
		'site_name'        => 'Apostle of Doom',
		'footer_badges'    => '<a href="https://www.ai-visibility.org.uk/apostleofdoom-org/" title="AI Visibility: 10/10 Platinum"><img src="https://ai-visibility-directory.winter-cake-bf57.workers.dev/badge/apostleofdoom-org.svg?style=themed&theme=dark&size=compact" alt="AI Visibility: 10/10 Platinum" width="180" height="28" loading="lazy"></a>',
		'footer_blurb'     => 'A case file against Paul of Tarsus: the man, the charges, the witnesses and the verdict, drawn from the book by Mohd Elfie Nieshaem Juferi.',
		'site_seo_title'   => 'The case against Paul of Tarsus',
		'site_meta'        => 'Saul of Tarsus hunted the followers of ʿĪsā ibn Maryam, then claimed to speak for him. The evidence, in his own letters, is here.',
		'site_tagline'     => 'Paul of Tarsus and the corruption of the message of ʿĪsā ibn Maryam',
		// Book.
		'cat_title'        => 'Paulus : perosak risalah al-Masih : sejarah bagaimana ajaran Kristian dicipta sepenuhnya / Mohd Elfie Nieshaem Juferi',
		'cat_extent'       => 'xxvi, 193 pages : illustrations ; 21 cm',
		'cat_language'     => 'Malay; this edition includes text in Arabic',
		'cat_note'         => "In Malay, with quotations from the Qur'an, hadith, etc., in Arabic\nIncludes bibliographical references",
		'cat_subjects'     => "Paul, the Apostle, Saint -- Conversion\nPaul, the Apostle, Saint -- Influence\nChristianity -- History of doctrines\nIslam -- Apologetic works\nIslam -- Doctrines\nApostasy -- Islam\nReligions",
		'cat_isbn'         => "9786299613503 (paperback)\n6299613505 (paperback)",
		'cat_bib'          => '300162873',
		'cat_edition'      => 'Cetakan kedua',
		'cat_imprint'      => 'Seri Kembangan, Selangor : Langgam Fikir, 2025',
		'book_title'       => 'Paulus Perosak Risalah Al-Masih',
		'book_title_en'    => 'Apostle of Doom',
		'book_subtitle'    => 'Sejarah Bagaimana Ajaran Kristian Dicipta Sepenuhnya',
		'book_subtitle_en' => 'How Paul of Tarsus Undid Jesus',
		'book_author'      => 'Mohd Elfie Nieshaem Juferi',
		'book_language'    => 'Malay (Bahasa Melayu)',
		'book_isbn'        => '978-629-96135-0-3',
		'book_oclc'        => '1531949453',
		'book_first_pub'   => 'August 2025',
		'book_reprint'     => 'Second printing, October 2025',
		'book_pages'       => 'xxvi + 195 pages',
		'book_format'      => 'Paperback',
		'book_price'       => 'RM 45.00',
		'book_buy_url'     => 'https://langgamfikir.my/publications/paulus-perosak-risalah-al-masih/',
		'image_license'     => 'https://creativecommons.org/publicdomain/mark/1.0/',
		'image_license_page' => '',
		'author_home'      => 'https://menj.blog',
		'author_url'       => 'https://bismikaallahuma.org',
		'author_youtube'   => 'https://themuslimapologist.online',
		'social_links'     => '',
		'author_books'     => "Buddhism: A Muslim Primer (Jahabersa, 2005)\nWhen Apostates Become Shaykhs…: A Systematic Analysis of Their Dominant Ideas on the World Wide Web (Jahabersa, 2006)\nDua Dasawarsa: Sebuah Kumpulan Puisi 2005–2025 (Langgam Fikir, 2025)\nPolis Raja Di Malaysia: Power, Corruption & Control in Malaysia’s Police Force (Langgam Fikir, 2026)",
		'author_bio'       => 'Mohd Elfie Nieshaem Juferi is a Muslim apologist and an award-winning Malay-language author. He runs the apologetics site <a href="https://bismikaallahuma.org">Bismika Allahuma</a> and the YouTube channel <a href="https://themuslimapologist.online">The Muslim Apologist</a>, and publishes his work through <a href="https://langgamfikir.my">Langgam Fikir</a>.',
		'book_blurb'       => 'Adakah benar ajaran-ajaran agama Kristian pada hari ini berasal daripada Nabi Isa a.s., atau sebenarnya hasil manipulasi teologi oleh seorang Yahudi bernama Paulus dari Tarsus?',
		// Publisher.
		'pub_name'         => 'Langgam Fikir',
		'pub_reg'          => '202503137729',
		'pub_address'      => "50-02A, Jalan Juara 1/4\n43300 Seri Kembangan\nSelangor, Malaysia",
		'pub_phone'        => '011-7346 8081',
		'pub_email'        => 'langgam.fikir@gmail.com',
		'pub_url'          => 'https://langgamfikir.my',
		// Appearance.
		'scheme'           => 'ochre',
		'ornament'         => 1,
		'login_image'      => 'paul-portrait-face',
		'login_logo_url'   => '',
		'login_tagline'    => 'Authors and editors only.',
		'login_greek'      => 1,
		'login_app_passwords' => 0,
		'companion_sites'  => "Best of Islam | https://bestofislam.org/ | Every objection to Islam stated in its own terms, numbered, and answered from the Quran, hadith and history.\nThe Rightly-Guided Injil | https://injil.help/ | What the Injīl is, what the four gospels are, and the Qurʾān as their measure, with a section on Paul of Tarsus.",
		'companion_reading' => 1,
		'companion_footer'  => 1,
		'companion_author'  => 1,
		'companion_links'  => "who-was-paul-of-tarsus | Paul of Tarsus, the false apostle | https://injil.help/paul/\nwho-was-paul-of-tarsus | The seven letters | https://injil.help/paul/seven-letters/\ntarsus-and-a-persona-for-every-audience | Paul of Tarsus, the false apostle | https://injil.help/paul/\nthe-roman | Acts against Galatians | https://injil.help/paul/acts-galatians/\nthe-citizenship-and-the-appeal-to-caesar | Acts against Galatians | https://injil.help/paul/acts-galatians/\nsaul-the-persecutor | James and the Jerusalem church | https://injil.help/paul/james/\nthe-damascus-road | Acts against Galatians | https://injil.help/paul/acts-galatians/\nthe-damascus-road | Who saw Gabriel? Witnesses to revelation | https://bestofislam.org/who-saw-gabriel/\nthe-three-accounts-compared | Acts against Galatians | https://injil.help/paul/acts-galatians/\nthe-three-accounts-compared | Luke and his sources | https://injil.help/gospels/luke/\na-self-appointed-apostle | The false apostles | https://injil.help/paul/false-apostles/\na-self-appointed-apostle | Acts against Galatians | https://injil.help/paul/acts-galatians/\ncurses-for-dissenters | The false apostles | https://injil.help/paul/false-apostles/\ncurses-for-dissenters | James and the Jerusalem church | https://injil.help/paul/james/\nthe-purse | James and the Jerusalem church | https://injil.help/paul/james/\na-psychological-portrait-of-paul | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\nego-epilepsy-and-narcissism | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\nthe-character-of-paul | The false apostles | https://injil.help/paul/false-apostles/\nwomen-slaves-and-caesar | Slavery in Islam: 6 facts | https://bestofislam.org/slavery-in-islam/\nwomen-slaves-and-caesar | Quranic ethics that still hold today | https://bestofislam.org/quranic-ethics/\nthe-flesh | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\nthe-fruit-at-corinth | The seven letters | https://injil.help/paul/seven-letters/\nthe-measure | The measure: how injil.help weighs its sources | https://injil.help/method/\nthe-measure | Sources and standards | https://bestofislam.org/sources-and-standards/\nat-the-crossroads-of-the-message | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\nat-the-crossroads-of-the-message | Jesus in the Qurʾān | https://injil.help/quran/\nat-the-crossroads-of-the-message | 5 reasons why Muslims believe that Jesus is not God | https://bestofislam.org/jesus-not-god/\npaul-against-the-disciples | James and the Jerusalem church | https://injil.help/paul/james/\npaul-against-the-disciples | Acts against Galatians | https://injil.help/paul/acts-galatians/\nthe-parting-with-barnabas | James and the Jerusalem church | https://injil.help/paul/james/\nthe-parting-with-barnabas | Acts against Galatians | https://injil.help/paul/acts-galatians/\nby-their-fruits | The false apostles | https://injil.help/paul/false-apostles/\nthem-which-say-they-are-apostles | The false apostles | https://injil.help/paul/false-apostles/\nthem-which-say-they-are-apostles | The Ebionites | https://injil.help/paul/ebionites/\nthe-foundations-of-pauline-doctrine | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\nthe-foundations-of-pauline-doctrine | 5 reasons why Muslims believe that Jesus is not God | https://bestofislam.org/jesus-not-god/\nseven-doctrines | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\nseven-doctrines | 5 reasons why Muslims believe that Jesus is not God | https://bestofislam.org/jesus-not-god/\nseven-doctrines | The crucifixion and the Quran: 6 facts about Quran 4:157 | https://bestofislam.org/crucifixion-quran/\nseven-doctrines-the-law-and-the-covenant | Paul and the Law: 6 verses that sit badly with Matthew 5:17 | https://bestofislam.org/paul-and-the-law/\nseven-doctrines-the-law-and-the-covenant | Matthew and the law | https://injil.help/gospels/matthew/\nthe-letters-of-a-man | The seven letters | https://injil.help/paul/seven-letters/\nthe-letters-of-a-man | Written in Peter's name: 5 things a Catholic Bible says about 2 Peter | https://bestofislam.org/second-peter/\nthe-greek-body-and-the-jewish-bed | The seven letters | https://injil.help/paul/seven-letters/\nrevelation-or-borrowing | Who saw Gabriel? Witnesses to revelation | https://bestofislam.org/who-saw-gabriel/\nrevelation-or-borrowing | The Ebionites | https://injil.help/paul/ebionites/\nlord-before-the-spirit | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\nborrowings-from-the-rabbis | Talmud, midrash and apocrypha: 6 answers on borrowing claims | https://bestofislam.org/talmud-midrash-apocrypha-borrowing/\nsuffering-that-atones | The crucifixion and the Quran: 6 facts about Quran 4:157 | https://bestofislam.org/crucifixion-quran/\npaul-and-the-new-testament | The Five Gospels | https://injil.help/gospels/\npaul-and-the-new-testament | Manuscript transmission: 5 differences between the Quran and the New Testament | https://bestofislam.org/manuscript-transmission/\npaul-and-the-new-testament | Written in Peter's name: 5 things a Catholic Bible says about 2 Peter | https://bestofislam.org/second-peter/\nletters-that-contradict-themselves | The seven letters | https://injil.help/paul/seven-letters/\nletters-that-contradict-themselves | The discrepancy tables | https://injil.help/gospels/discrepancies/\nthe-gospels-and-the-new-covenant | The Five Gospels | https://injil.help/gospels/\nthe-gospels-and-the-new-covenant | Paul and the Law: 6 verses that sit badly with Matthew 5:17 | https://bestofislam.org/paul-and-the-law/\njustification-by-faith | Paul and the Law: 6 verses that sit badly with Matthew 5:17 | https://bestofislam.org/paul-and-the-law/\njustification-by-faith | Matthew and the law | https://injil.help/gospels/matthew/\ntwisting-the-scriptures | 7 verses Christians and Muslims read differently, and why | https://bestofislam.org/verses-read-differently/\ntypology | 7 verses Christians and Muslims read differently, and why | https://bestofislam.org/verses-read-differently/\nthe-tanakh-rewritten | 6 prophets both books share, and where the accounts diverge | https://bestofislam.org/shared-prophets/\npaul-the-father-of-a-new-religion | What the Quran says about Christians and Christianity | https://bestofislam.org/quran-on-christians/\npaul-the-father-of-a-new-religion | The Ebionites | https://injil.help/paul/ebionites/\nwhat-the-romans-saw | The Ebionites | https://injil.help/paul/ebionites/\nthe-failed-prophet | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\na-death-the-scriptures-never-record | The crucifixion and the Quran: 6 facts about Quran 4:157 | https://bestofislam.org/crucifixion-quran/\na-death-the-scriptures-never-record | The end of his earthly mission | https://injil.help/quran/raised/\nthe-religion-of-paul-today | What the Quran says about Christians and Christianity | https://bestofislam.org/quran-on-christians/\nthe-religion-of-paul-today | 6 reasons why Allah of the Quran is the same God as the God of the Bible | https://bestofislam.org/same-god/\nmalaysia-and-southeast-asia | What the Quran says about Christians and Christianity | https://bestofislam.org/quran-on-christians/\njames-the-brother | James and the Jerusalem church | https://injil.help/paul/james/\njames-the-brother | The Ebionites | https://injil.help/paul/ebionites/\nthe-first-witnesses | The Ebionites | https://injil.help/paul/ebionites/\nthe-first-witnesses | The false apostles | https://injil.help/paul/false-apostles/\nthe-quran-as-witness | Jesus in the Qurʾān | https://injil.help/quran/\nthe-quran-as-witness | The Islamic Dilemma, answered | https://injil.help/the-islamic-dilemma/\nthe-quran-as-witness | Where is the Injil? 5 answers to the question critics ask most | https://bestofislam.org/where-is-the-injil/\nthe-injil-and-the-letters | The Islamic Dilemma, answered | https://injil.help/the-islamic-dilemma/\nthe-injil-and-the-letters | Where is the Injil? 5 answers to the question critics ask most | https://bestofislam.org/where-is-the-injil/\nthe-injil-and-the-letters | The Gospel According to Islam | https://injil.help/gospel-according-to-islam/\npaul-in-the-islamic-tradition | The good news of Aḥmad | https://injil.help/quran/ahmad/\npaul-in-the-islamic-tradition | What the Quran says about Christians and Christianity | https://bestofislam.org/quran-on-christians/\npauls-own-titles | The good news of Aḥmad | https://injil.help/quran/ahmad/\nthe-early-islamic-record | What the Quran says about Christians and Christianity | https://bestofislam.org/quran-on-christians/\nfrom-khushaysh-to-al-shahrastani | What the Quran says about Christians and Christianity | https://bestofislam.org/quran-on-christians/\nibn-hazm-and-al-thalabi | 7 verses Christians and Muslims read differently, and why | https://bestofislam.org/verses-read-differently/\nmodern-voices-from-al-kairanawi-to-akhtar | 5 Western scholars of Islam and where Muslims differ with them | https://bestofislam.org/western-scholars-of-islam/\nthe-hostile-witnesses | The false apostles | https://injil.help/paul/false-apostles/\nan-epistle-to-the-churches-of-paul | An Epistle unto the Congregation of Paul of Tarsus | https://injil.help/bonus/epistle/\ndid-paul-ever-meet-jesus | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\ndale-b-martin-notes-published | Luke and his sources | https://injil.help/gospels/luke/\nanswers | Frequently asked questions on injil.help | https://injil.help/answers/\nanswers | The Islamic Dilemma: 5 assumptions the argument needs | https://bestofislam.org/islamic-dilemma/\nanswers | Frequently asked questions on Best of Islam | https://bestofislam.org/frequently-asked-questions/\nquestions | Paul of Tarsus, the false apostle | https://injil.help/paul/\nquestions | Frequently asked questions on injil.help | https://injil.help/answers/\npaul-at-a-glance | Paul of Tarsus, the false apostle | https://injil.help/paul/\npaul-at-a-glance | Timeline | https://injil.help/resources/timeline/\nthe-verdict | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\nthe-verdict | The Islamic Dilemma, answered | https://injil.help/the-islamic-dilemma/\nchronology | Timeline | https://injil.help/resources/timeline/\nglossary | Glossary of injil.help | https://injil.help/resources/glossary/\nglossary | Glossary of Best of Islam | https://bestofislam.org/glossary/\nsources | Sources of injil.help | https://injil.help/resources/sources/\nsources | Sources and standards | https://bestofislam.org/sources-and-standards/\nstudy-questions | Frequently asked questions on injil.help | https://injil.help/answers/\ndale-b-martin-luke-versus-paul | Luke and his sources | https://injil.help/gospels/luke/\ndale-b-martin-luke-versus-paul | The synoptic problem | https://injil.help/gospels/synoptic-problem/\nwhy-luke-does-not-know-pauls-letters | Luke and his sources | https://injil.help/gospels/luke/\nwhy-luke-does-not-know-pauls-letters | Acts against Galatians | https://injil.help/paul/acts-galatians/\npaul-in-the-churches | The Ebionites | https://injil.help/paul/ebionites/\nabout-the-author | About the author of injil.help | https://injil.help/about-the-author/\nabout-the-author | About Best of Islam | https://bestofislam.org/about/\nthe-book | The Gospel According to Islam | https://injil.help/gospel-according-to-islam/\nsection:the-man | Paul of Tarsus, the false apostle | https://injil.help/paul/\nsection:the-charges | Paul of Tarsus against Jesus | https://injil.help/paul/paul-against-jesus/\nsection:the-witnesses | Jesus in the Qurʾān | https://injil.help/quran/\n* | Paul of Tarsus, the false apostle | https://injil.help/paul/\n* | Objections to Islam, answered | https://bestofislam.org/",
		'journal_title'    => 'Journal',
		'journal_intro'    => 'Replies to missionary claims, notes on new sources and news of the book, dated as they are written.',
		'journal_meta'     => 'Dated replies to missionary claims, notes on new sources and news on the case against Paul of Tarsus. Read the latest entry.',
		'read_progress'    => 1,
		'read_progress_posts'   => 1,
		'read_progress_journal' => 1,
		'read_progress_pages'   => 0,
		'read_progress_position' => 'top',
		'read_progress_height'   => 3,
		'read_progress_fg'       => '',
		'read_progress_bg'       => '',
		'read_keys'        => 1,
		'read_memory'      => 1,
		'read_copy'        => 1,
		'read_top'         => 1,
		'media_no_sizes'   => 1,
		'media_no_scaled'  => 1,
		// Front page.
		'hero_kicker'      => 'The case against Paul of Tarsus',
		'hero_heading'     => 'Apostle of Doom',
		'hero_subheading'  => 'Paul of Tarsus and the corruption of the message of ʿĪsā ibn Maryam',
		'hero_lede'        => 'Saul of Tarsus hunted the followers of the Messiah, then claimed to speak for him. This website sets out the evidence, from his own letters and from fourteen centuries of Muslim scholarship.',
		'hero_image'       => 'paul-portrait',
	);
}

/**
 * Read one option, falling back to the default.
 *
 * @param string $key Option key.
 * @return mixed
 */
function paulus_option( $key ) {
	// Read dozens of times a page; the defaults (eighty translated strings)
	// are built once a request, not once a read.
	static $defaults = null;
	$opts = get_option( 'paulus_options', array() );
	if ( is_array( $opts ) && array_key_exists( $key, $opts ) ) {
		return $opts[ $key ];
	}
	if ( null === $defaults ) {
		$defaults = paulus_defaults();
	}
	return $defaults[ $key ] ?? '';
}

/**
 * Hero image URL. Uses the transparent cutout of an illustration when one
 * is bundled (assets/images/<slug>-cutout.avif), so the figure sits on the
 * page background in every color scheme instead of in a square of its own.
 *
 * @param string $slug Image slug.
 * @return string
 */
function paulus_hero_image_url( $slug ) {
	$slug = array_key_exists( $slug, paulus_images() ) ? $slug : 'paul-portrait';
	$file = sanitize_file_name( $slug ) . '-cutout.avif';
	if ( file_exists( PAULUS_DIR . '/assets/images/' . $file ) ) {
		return PAULUS_URI . '/assets/images/' . $file;
	}
	return paulus_image_url( $slug );
}

/**
 * srcset for a hero image: the 480 and 720 widths, when they exist beside the
 * full file: the full file is AVIF, the smaller widths WebP (name-480.webp,
 * name-720.webp).
 *
 * @param string $url Full-size image URL.
 * @return string srcset attribute value, or empty.
 */
function paulus_hero_srcset( $url ) {
	$file = basename( wp_parse_url( $url, PHP_URL_PATH ) );
	if ( ! preg_match( '/^(.+)\.(avif|webp|jpg|png)$/', $file, $m ) ) {
		return '';
	}
	$set = array();
	foreach ( array( 480, 720 ) as $w ) {
		$name = $m[1] . '-' . $w . '.webp';
		if ( file_exists( PAULUS_DIR . '/assets/images/' . $name ) ) {
			$set[] = PAULUS_URI . '/assets/images/' . $name . ' ' . $w . 'w';
		}
	}
	if ( ! $set ) {
		return '';
	}
	$set[] = $url . ' 962w';
	return implode( ', ', $set );
}

/**
 * Bundled images: slug => label.
 *
 * @return array<string,string>
 */
function paulus_images() {
	return array(
		'paul-portrait' => __( 'Portrait (cover art)', 'paulus' ),
		'paul-halo-2023' => __( 'Giuseppe Franchi, Saint Paul (the 2023 edition)', 'paulus' ),
		'met-ring-key' => __( 'Roman ring key (The Met)', 'paulus' ),
		'met-diploma' => __( 'Roman military diploma (The Met)', 'paulus' ),
		'met-tiberius-ring' => __( 'Ring with a portrait of Tiberius (The Met)', 'paulus' ),
		'met-papyrus-letter' => __( 'Greek papyrus letter (The Met)', 'paulus' ),
		'met-inkwell-stylus' => __( 'Roman inkwell and stylus (The Met)', 'paulus' ),
		'author-photo' => __( 'The author (photograph)', 'paulus' ),
		'paul-portrait-face' => __( 'Portrait (cover art), close-up', 'paulus' ),
		'paul-portrait-hands' => __( 'Portrait (cover art), hands and scroll', 'paulus' ),
		'paul-portrait-ink' => __( 'Portrait (cover art), ink duotone', 'paulus' ),
		'paul-portrait-oxblood' => __( 'Portrait (cover art), oxblood duotone', 'paulus' ),
		'paul-portrait-mirror' => __( 'Portrait (cover art), mirrored', 'paulus' ),
		'paul-portrait-stone' => __( 'Portrait (cover art), limestone duotone', 'paulus' ),
		'paul-portrait-mirror-stone' => __( 'Portrait (cover art), mirrored, limestone duotone', 'paulus' ),
		'paul-gladius-painted' => __( 'Portrait with gladius, painted rendering', 'paulus' ),
		'paul-bastard-stone' => __( 'Cap lettered BASTARD, limestone duotone', 'paulus' ),
		'paul-horned-stone' => __( 'Horned figure, limestone duotone', 'paulus' ),
		'paul-decayed' => __( 'Decayed saint', 'paulus' ),
		'paul-decayed-face' => __( 'Decayed saint, close-up', 'paulus' ),
		'paul-decayed-hands' => __( 'Decayed saint, hands and scroll', 'paulus' ),
		'paul-decayed-ink' => __( 'Decayed saint, ink duotone', 'paulus' ),
		'paul-decayed-oxblood' => __( 'Decayed saint, oxblood duotone', 'paulus' ),
		'paul-decayed-mirror' => __( 'Decayed saint, mirrored', 'paulus' ),
		'paul-decayed-stone' => __( 'Decayed saint, limestone duotone', 'paulus' ),
		'paul-horned' => __( 'Horned', 'paulus' ),
		'paul-horned-face' => __( 'Horned, close-up', 'paulus' ),
		'paul-horned-hands' => __( 'Horned, hands and scroll', 'paulus' ),
		'paul-horned-ink' => __( 'Horned, ink duotone', 'paulus' ),
		'paul-horned-oxblood' => __( 'Horned, oxblood duotone', 'paulus' ),
		'paul-horned-mirror' => __( 'Horned, mirrored', 'paulus' ),
		'paul-dunce' => __( 'Dunce', 'paulus' ),
		'paul-dunce-face' => __( 'Dunce, close-up', 'paulus' ),
		'paul-dunce-hands' => __( 'Dunce, hands and scroll', 'paulus' ),
		'paul-dunce-ink' => __( 'Dunce, ink duotone', 'paulus' ),
		'paul-dunce-oxblood' => __( 'Dunce, oxblood duotone', 'paulus' ),
		'paul-bastard' => __( 'Bastard cap', 'paulus' ),
		'paul-bastard-face' => __( 'Bastard cap, close-up', 'paulus' ),
		'paul-bastard-hands' => __( 'Bastard cap, hands and scroll', 'paulus' ),
		'paul-bastard-ink' => __( 'Bastard cap, ink duotone', 'paulus' ),
		'paul-bastard-oxblood' => __( 'Bastard cap, oxblood duotone', 'paulus' ),
		'paul-horned-bastard' => __( 'Horned, bastard cap', 'paulus' ),
		'paul-horned-bastard-face' => __( 'Horned, bastard cap, close-up', 'paulus' ),
		'paul-horned-bastard-hands' => __( 'Horned, bastard cap, hands and scroll', 'paulus' ),
		'paul-horned-bastard-ink' => __( 'Horned, bastard cap, ink duotone', 'paulus' ),
		'paul-horned-bastard-oxblood' => __( 'Horned, bastard cap, oxblood duotone', 'paulus' ),
		'paul-horned-bastard-stone' => __( 'Horned, bastard cap, limestone duotone', 'paulus' ),
		'paul-horned-grin-stone' => __( 'Horned, grinning, limestone duotone', 'paulus' ),
		'paul-horned-grin' => __( 'Horned, grinning', 'paulus' ),
		'paul-horned-grin-face' => __( 'Horned, grinning, close-up', 'paulus' ),
		'paul-horned-grin-hands' => __( 'Horned, grinning, hands and scroll', 'paulus' ),
		'paul-horned-grin-ink' => __( 'Horned, grinning, ink duotone', 'paulus' ),
		'paul-horned-grin-oxblood' => __( 'Horned, grinning, oxblood duotone', 'paulus' ),
	);
}

/**
 * Alt text for each bundled image, describing what is drawn.
 *
 * @return array<string,string>
 */
function paulus_image_alts() {
	return array(
		'met-ring-key' => __( 'Roman bronze ring key, a small key worn on the finger, third or fourth century CE', 'paulus' ),
		'met-diploma' => __( 'Roman bronze military diploma of about 149 CE, a tablet engraved in Latin with the grant of Antoninus Pius', 'paulus' ),
		'met-tiberius-ring' => __( 'Roman gold ring set with a carnelian intaglio portrait of the emperor Tiberius, 14 to 37 CE', 'paulus' ),
		'met-papyrus-letter' => __( 'Papyrus letter written in Greek, from Roman Egypt, early third century CE', 'paulus' ),
		'met-inkwell-stylus' => __( 'Roman terracotta inkwell, first or second century CE', 'paulus' ),
		'author-photo' => __( 'Photograph of Mohd Elfie Nieshaem Juferi', 'paulus' ),
		'paul-portrait' => __( 'Engraved portrait of Paul of Tarsus, bearded, in a red robe, holding a Roman short sword (gladius) and a scroll before a red cross', 'paulus' ),
		'paul-portrait-face' => __( 'Engraved portrait of Paul of Tarsus, bearded, in a red robe, holding a Roman short sword (gladius) and a scroll before a red cross, close-up of the face', 'paulus' ),
		'paul-portrait-hands' => __( 'Engraved portrait of Paul of Tarsus, bearded, in a red robe, holding a Roman short sword (gladius) and a scroll before a red cross, close-up of the hands and scroll', 'paulus' ),
		'paul-portrait-ink' => __( 'Engraved portrait of Paul of Tarsus, bearded, in a red robe, holding a Roman short sword (gladius) and a scroll before a red cross, in dark ink duotone', 'paulus' ),
		'paul-portrait-oxblood' => __( 'Engraved portrait of Paul of Tarsus, bearded, in a red robe, holding a Roman short sword (gladius) and a scroll before a red cross, in oxblood duotone', 'paulus' ),
		'paul-portrait-mirror' => __( 'Engraved portrait of Paul of Tarsus, bearded, in a red robe, holding a Roman short sword (gladius) and a scroll before a red cross, mirrored', 'paulus' ),
		'paul-portrait-stone' => __( 'Engraved portrait of Paul of Tarsus, bearded, in a red robe, holding a Roman short sword (gladius) and a scroll before a red cross, in limestone duotone', 'paulus' ),
		'paul-portrait-mirror-stone' => __( 'Engraved portrait of Paul of Tarsus, bearded, in a robe, holding a Roman short sword (gladius) and a scroll before a cross, mirrored, in limestone duotone', 'paulus' ),
		'paul-gladius-painted' => __( 'Painted portrait of Paul of Tarsus, bearded, in a red robe, holding a Roman short sword (gladius) and a scroll before a red cross', 'paulus' ),
		'paul-bastard-stone' => __( 'Paul of Tarsus in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, in limestone duotone', 'paulus' ),
		'paul-horned-stone' => __( 'Paul of Tarsus drawn as a horned figure in a robe, holding a sword and a scroll, in limestone duotone', 'paulus' ),
		'paul-decayed' => __( 'Paul of Tarsus as a weathered saint with a cracked halo, holding a sword and a decaying scroll', 'paulus' ),
		'paul-decayed-face' => __( 'Paul of Tarsus as a weathered saint with a cracked halo, holding a sword and a decaying scroll, close-up of the face', 'paulus' ),
		'paul-decayed-hands' => __( 'Paul of Tarsus as a weathered saint with a cracked halo, holding a sword and a decaying scroll, close-up of the hands and scroll', 'paulus' ),
		'paul-decayed-ink' => __( 'Paul of Tarsus as a weathered saint with a cracked halo, holding a sword and a decaying scroll, in dark ink duotone', 'paulus' ),
		'paul-decayed-oxblood' => __( 'Paul of Tarsus as a weathered saint with a cracked halo, holding a sword and a decaying scroll, in oxblood duotone', 'paulus' ),
		'paul-decayed-mirror' => __( 'Paul of Tarsus as a weathered saint with a cracked halo, holding a sword and a decaying scroll, mirrored', 'paulus' ),
		'paul-decayed-stone' => __( 'Paul of Tarsus as a weathered saint with a cracked halo, holding a sword and a decaying scroll, in limestone duotone', 'paulus' ),
		'paul-horned' => __( 'Paul of Tarsus drawn as a horned, red-skinned figure in a robe, holding a sword and a scroll', 'paulus' ),
		'paul-horned-face' => __( 'Paul of Tarsus drawn as a horned, red-skinned figure in a robe, holding a sword and a scroll, close-up of the face', 'paulus' ),
		'paul-horned-hands' => __( 'Paul of Tarsus drawn as a horned, red-skinned figure in a robe, holding a sword and a scroll, close-up of the hands and scroll', 'paulus' ),
		'paul-horned-ink' => __( 'Paul of Tarsus drawn as a horned, red-skinned figure in a robe, holding a sword and a scroll, in dark ink duotone', 'paulus' ),
		'paul-horned-oxblood' => __( 'Paul of Tarsus drawn as a horned, red-skinned figure in a robe, holding a sword and a scroll, in oxblood duotone', 'paulus' ),
		'paul-horned-mirror' => __( 'Paul of Tarsus drawn as a horned, red-skinned figure in a robe, holding a sword and a scroll, mirrored', 'paulus' ),
		'paul-dunce' => __( 'Paul of Tarsus in a dunce cap, holding a toy water pistol and a scroll', 'paulus' ),
		'paul-dunce-face' => __( 'Paul of Tarsus in a dunce cap, holding a toy water pistol and a scroll, close-up of the face', 'paulus' ),
		'paul-dunce-hands' => __( 'Paul of Tarsus in a dunce cap, holding a toy water pistol and a scroll, close-up of the hands and scroll', 'paulus' ),
		'paul-dunce-ink' => __( 'Paul of Tarsus in a dunce cap, holding a toy water pistol and a scroll, in dark ink duotone', 'paulus' ),
		'paul-dunce-oxblood' => __( 'Paul of Tarsus in a dunce cap, holding a toy water pistol and a scroll, in oxblood duotone', 'paulus' ),
		'paul-bastard' => __( 'Paul of Tarsus in a conical cap lettered BASTARD, holding a toy water pistol and a scroll', 'paulus' ),
		'paul-bastard-face' => __( 'Paul of Tarsus in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, close-up of the face', 'paulus' ),
		'paul-bastard-hands' => __( 'Paul of Tarsus in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, close-up of the hands and scroll', 'paulus' ),
		'paul-bastard-ink' => __( 'Paul of Tarsus in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, in dark ink duotone', 'paulus' ),
		'paul-bastard-oxblood' => __( 'Paul of Tarsus in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, in oxblood duotone', 'paulus' ),
		'paul-horned-bastard' => __( 'Paul of Tarsus as a horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll', 'paulus' ),
		'paul-horned-bastard-face' => __( 'Paul of Tarsus as a horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, close-up of the face', 'paulus' ),
		'paul-horned-bastard-hands' => __( 'Paul of Tarsus as a horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, close-up of the hands and scroll', 'paulus' ),
		'paul-horned-bastard-ink' => __( 'Paul of Tarsus as a horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, in dark ink duotone', 'paulus' ),
		'paul-horned-bastard-oxblood' => __( 'Paul of Tarsus as a horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, in oxblood duotone', 'paulus' ),
		'paul-horned-bastard-stone' => __( 'Paul of Tarsus as a horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, in limestone duotone', 'paulus' ),
		'paul-horned-grin-stone' => __( 'Paul of Tarsus as a grinning horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, in limestone duotone', 'paulus' ),
		'paul-horned-grin' => __( 'Paul of Tarsus as a grinning horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll', 'paulus' ),
		'paul-horned-grin-face' => __( 'Paul of Tarsus as a grinning horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, close-up of the face', 'paulus' ),
		'paul-horned-grin-hands' => __( 'Paul of Tarsus as a grinning horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, close-up of the hands and scroll', 'paulus' ),
		'paul-horned-grin-ink' => __( 'Paul of Tarsus as a grinning horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, in dark ink duotone', 'paulus' ),
		'paul-horned-grin-oxblood' => __( 'Paul of Tarsus as a grinning horned figure in a conical cap lettered BASTARD, holding a toy water pistol and a scroll, in oxblood duotone', 'paulus' ),
		'book-cover'    => __( 'Front cover of Paulus Perosak Risalah Al-Masih by Mohd Elfie Nieshaem Juferi', 'paulus' ),
	);
}

/**
 * URL of a bundled image.
 *
 * @param string $slug Image slug.
 * @return string
 */
function paulus_image_url( $slug ) {
	if ( 'book-cover' !== $slug && ! array_key_exists( $slug, paulus_images() ) ) {
		$slug = 'paul-portrait';
	}
	return PAULUS_URI . '/assets/images/' . sanitize_file_name( $slug ) . '.avif';
}

/**
 * A JPEG copy of an illustration for link previews (Open Graph, X), where
 * AVIF is not shown: assets/social/<slug>.jpg.
 *
 * @param string $slug Image slug.
 * @return string URL, or empty when there is no copy.
 */
function paulus_social_image_url( $slug ) {
	$slug = sanitize_file_name( (string) $slug );
	return file_exists( PAULUS_DIR . '/assets/social/' . $slug . '.jpg' ) ? PAULUS_URI . '/assets/social/' . $slug . '.jpg' : '';
}

/**
 * The social copy for an image address of the theme's: a bundled
 * illustration, or a featured image the theme imported into the media
 * library (named <slug>.avif, <slug>-<hash>.avif, or a sub-size of either).
 *
 * @param string $url Image address.
 * @return string Social copy's URL, or empty.
 */
function paulus_social_for_url( $url ) {
	$base = basename( (string) wp_parse_url( (string) $url, PHP_URL_PATH ) );
	if ( ! preg_match( '/^(.+?)(-[0-9a-f]{8})?(-\d+x\d+)?\.(avif|jpe?g|webp|png)$/i', $base, $m ) ) {
		return '';
	}
	return array_key_exists( $m[1], paulus_images() ) || 'book-cover' === $m[1] ? paulus_social_image_url( $m[1] ) : '';
}

/**
 * An image's width and height, from assets/images/sizes.json (written when
 * the images are made), so no page depends on the server's PHP reading AVIF
 * (getimagesize() reads it only from PHP 8.2). Falls back to getimagesize().
 *
 * @param string $path Absolute path under assets/images.
 * @return array|false Width and height, or false.
 */
function paulus_image_size( $path ) {
	static $sizes = null;
	if ( null === $sizes ) {
		$json  = PAULUS_DIR . '/assets/images/sizes.json';
		$sizes = file_exists( $json ) ? (array) json_decode( (string) file_get_contents( $json ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	$key = ltrim( str_replace( PAULUS_DIR . '/assets/images/', '', (string) $path ), '/' );
	if ( isset( $sizes[ $key ] ) ) {
		return array( (int) $sizes[ $key ][0], (int) $sizes[ $key ][1] );
	}
	return file_exists( $path ) ? @getimagesize( $path ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
}
