<?php
/**
 * Photographs of Paul in the churches, from Wikimedia Commons, bundled at
 * web size in assets/images/church/ with their licences. Each is shown as a
 * captioned figure with its credit by [paulus_figure].
 *
 * @package Paulus
 */

defined( 'ABSPATH' ) || exit;

/**
 * Figure registry: file slug => alt text, author, licence, licence URL and
 * the Commons file page.
 *
 * @return array<string, array<string, string>>
 */
function paulus_figures() {
	return array(
		'caravaggio-conversion' => array( 'alt' => __( 'Caravaggio painting of Saul fallen on his back beneath a horse, arms raised, in a pool of light', 'paulus' ), 'author' => 'Caravaggio', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Caravaggio-The_Conversion_on_the_Way_to_Damascus.jpg' ),
		'traditio-legis' => array( 'alt' => __( 'Apse mosaic of Christ standing on a hill between two apostles, with sheep and palms below', 'paulus' ), 'author' => 'José Luiz', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Traditio_Legis_mosaic_-_Santa_Costanza_-_Rome_2016.jpg' ),
		'catacomb-christ-peter-paul' => array( 'alt' => __( 'Catacomb fresco of Christ enthroned between Peter and Paul, with four martyrs and the Lamb below', 'paulus' ), 'author' => 'Unknown painter, 4th century', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:%C2%B7_Jes%C3%BAs_Cristo_flanqueado_por_San_Pedro_y_San_Pablo_%C2%B7_Catacumbas_de_los_Santos_Pedro_y_Marcelino,_Roma,_s._IV_d._JC._%C2%B7.jpg' ),
		'tre-fontane-beheading' => array( 'alt' => __( 'Marble high relief of Paul kneeling before an executioner with a raised sword', 'paulus' ), 'author' => 'Yong Woo Park', 'license' => 'CC BY 4.0', 'license_url' => 'https://creativecommons.org/licenses/by/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Beheading_of_Saint_Paul_relief_-_Abbazia_delle_Tre_Fontane_Rome.jpg' ),
		'tomb-of-paul' => array( 'alt' => __( 'Marble sarcophagus beneath an altar, seen through a bronze grille', 'paulus' ), 'author' => 'StPaul.jpg', 'license' => 'CC BY 4.0', 'license_url' => 'https://creativecommons.org/licenses/by/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:St._Paul%27s_Tomb.jpg' ),
		'lateran-ciborium' => array( 'alt' => __( 'Gothic canopy over the high altar of the Lateran basilica, with a grilled upper chamber', 'paulus' ), 'author' => 'Jastrow', 'license' => 'CC BY 2.5', 'license_url' => 'https://creativecommons.org/licenses/by/2.5', 'source' => 'https://commons.wikimedia.org/wiki/File:Ciborium_San_Giovanni_in_Laterano_2006-09-07.jpg' ),
		'tadolini-statue' => array( 'alt' => __( 'Marble statue of Paul holding a sword and a book, before the facade of Saint Peter\'s', 'paulus' ), 'author' => 'AngMoKio', 'license' => 'CC BY-SA 2.5', 'license_url' => 'https://creativecommons.org/licenses/by-sa/2.5', 'source' => 'https://commons.wikimedia.org/wiki/File:Vatican_StPaul_Statue.jpg' ),
		'byzantine-medallion' => array( 'alt' => __( 'Gold cloisonné enamel medallion of Paul, bald and bearded, holding a book, named in Greek', 'paulus' ), 'author' => 'The Metropolitan Museum of Art', 'license' => 'CC0', 'license_url' => 'http://creativecommons.org/publicdomain/zero/1.0/deed.en', 'source' => 'https://commons.wikimedia.org/wiki/File:Medallion_with_Saint_Paul_from_an_Icon_Frame_MET_DT2810.jpg' ),
		'tarsus-cleopatra-gate' => array( 'alt' => __( 'A Roman stone gateway with a single arch in a park in Tarsus, with palm trees', 'paulus' ), 'author' => 'Nedim Ardoğa', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Cleopatra%27s_Gate,_Tarsus,_Mersin_Province.jpg' ),
		'rembrandt-stephen' => array( 'alt' => __( 'Rembrandt painting of a crowd stoning a kneeling man in red, with onlookers on horseback', 'paulus' ), 'author' => 'Rembrandt', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Rembrandt_Harmensz._van_Rijn_150.jpg' ),
		'straight-street' => array( 'alt' => __( 'Colour photograph of about 1900 of a narrow Damascus street lined with houses and shops', 'paulus' ), 'author' => 'Detroit Photographic Company', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Street-called-Straight-Detroit.jpeg' ),
		'bab-kisan' => array( 'alt' => __( 'A stone city gate of Damascus with a small arched door, the Chapel of Saint Paul', 'paulus' ), 'author' => 'Heretiq', 'license' => 'CC BY-SA 3.0', 'license_url' => 'http://creativecommons.org/licenses/by-sa/3.0/', 'source' => 'https://commons.wikimedia.org/wiki/File:Damascus-Bab_Kisan.jpg' ),
		'paul-before-felix' => array( 'alt' => __( 'Engraving of Paul standing and preaching before the seated governor Felix and his court', 'paulus' ), 'author' => 'William Hogarth', 'license' => 'CC0', 'license_url' => 'http://creativecommons.org/publicdomain/zero/1.0/deed.en', 'source' => 'https://commons.wikimedia.org/wiki/File:William_Hogarth,_Paul_before_Felix,_1752,_NGA_30440.jpg' ),
		'rembrandt-prison' => array( 'alt' => __( 'Rembrandt painting of Paul seated on a bed in a cell, pen in hand, a sword beside him', 'paulus' ), 'author' => 'Rembrandt', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:1627_Rembrandt_Paulus_im_Gef%C3%A4ngnis_Staatsgalerie_Stuttgart_anagoria.JPG' ),
		'rembrandt-self-paul' => array( 'alt' => __( 'Rembrandt self-portrait in a turban with a sword hilt at his chest, holding papers, as the Apostle Paul', 'paulus' ), 'author' => 'Rembrandt', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Self_Portrait_as_Apostle..._-_Rembrandt_Harmenszoon_van_Rijn.png' ),
		'raphael-elymas' => array( 'alt' => __( 'Raphael tapestry cartoon of Paul raising his arm as the sorcerer Elymas gropes blindly before the proconsul', 'paulus' ), 'author' => 'Raphael', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:V%26A_-_Raphael,_The_Conversion_of_the_Proconsul_(1515).jpg' ),
		'nero-bust' => array( 'alt' => __( 'Marble portrait bust of the emperor Nero', 'paulus' ), 'author' => 'cjh1452000', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Nero_1.JPG' ),
		'corinth-apollo' => array( 'alt' => __( 'Doric columns of the Temple of Apollo at Ancient Corinth under a pale sky', 'paulus' ), 'author' => 'Rabe!', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Korinth_-_Apollon-Tempel.jpg' ),
		'minaret-isa' => array( 'alt' => __( 'The tall stone Minaret of Jesus rising above the wall of the Umayyad Mosque in Damascus', 'paulus' ), 'author' => 'Heretiq', 'license' => 'CC BY-SA 3.0', 'license_url' => 'http://creativecommons.org/licenses/by-sa/3.0/', 'source' => 'https://commons.wikimedia.org/wiki/File:Umayyad_Mosque_Jesus_Minaret.jpg' ),
		'temple-model' => array( 'alt' => __( 'Model of the Second Temple and its courts in the model of ancient Jerusalem at the Israel Museum', 'paulus' ), 'author' => 'Berthold Werner', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Jerusalem_Modell_BW_2.JPG' ),
		'celsus-library' => array( 'alt' => __( 'The two-storey columned facade of the Library of Celsus at Ephesus', 'paulus' ), 'author' => 'Benh LIEU SONG', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Ephesus_Celsus_Library_Fa%C3%A7ade.jpg' ),
		'raphael-lystra' => array( 'alt' => __( 'Raphael tapestry cartoon of priests leading an ox to sacrifice before Paul and Barnabas', 'paulus' ), 'author' => 'Raphael', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:V%26A_-_Raphael,_The_Sacrifice_at_Lystra_(1515).jpg' ),
		'papyrus-46' => array( 'alt' => __( 'Two facing papyrus leaves covered in Greek script, from the oldest manuscript of Paul\'s letters', 'paulus' ), 'author' => 'Unknown scribe', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Bifolio_from_Paul%27s_Letter_to_the_Romans,_the_end_of_Paul%27s_Letter_to_the_Philippians_and_the_beginning_of_Paul%27s_Letter_to_the_Colossians.jpg' ),
		'nicaea-icon' => array( 'alt' => __( 'Icon of the First Council of Nicaea, with the emperor enthroned among rows of bishops', 'paulus' ), 'author' => 'Michael Damaskinos', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:First_Council_of_Nicaea_Michael_Damaskinos.png' ),
		'poussin-ecstasy' => array( 'alt' => __( 'Poussin painting of Paul in a red robe carried upward by angels through the clouds', 'paulus' ), 'author' => 'Nicolas Poussin', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Nicolas_Poussin_-_Le_Ravissement_de_saint_Paul,_1650_(vers).jpg' ),
		'munich-talmud' => array( 'alt' => __( 'A page of the Munich Talmud manuscript, dense Hebrew script in columns', 'paulus' ), 'author' => 'Solomon ben Samson (scribe)', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Bavli_Kiddushin_Munich_95.jpg' ),
		'nag-hammadi' => array( 'alt' => __( 'Leather-bound papyrus codex from Nag Hammadi opened to a page of Coptic script', 'paulus' ), 'author' => 'Unknown scribe', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Nag_Hammadi_Codex_II.jpg' ),
		'isaiah-scroll' => array( 'alt' => __( 'A section of the Great Isaiah Scroll, Hebrew script in columns on parchment', 'paulus' ), 'author' => 'Photograph: Ardon Bar Hama', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Great_Isaiah_Scroll_Ch53.jpg' ),
		'dome-of-the-rock' => array( 'alt' => __( 'The Dome of the Rock with its golden dome and blue tiled walls, with the Dome of the Chain before it', 'paulus' ), 'author' => 'Godot13', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Jerusalem-2013-Temple_Mount-Dome_of_the_Rock_%26_Chain_02.jpg' ),
		'pella-ruins' => array( 'alt' => __( 'Fallen stone columns among ruins on a hillside at Pella in Jordan', 'paulus' ), 'author' => 'Mohammad hajeer', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Archaeological_Ruins_of_Tabaqat_Fahl_(Pella)_18.jpg' ),
		'benei-hezir' => array( 'alt' => __( 'Black-and-white photograph of rock-cut tombs with columned facades in the Kidron Valley', 'paulus' ), 'author' => 'Unknown photographer, 1930s', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Tomb_of_Zechariah_anf_Tomb_of_Benei_Hezir,_Jerusalem,_the_1930s_(138).jpg' ),
		'umayyad-interior' => array( 'alt' => __( 'The carpeted prayer hall of the Umayyad Mosque in Damascus, with columns and arches', 'paulus' ), 'author' => 'Frank Kidner', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Umayyad_Mosque,_Damascus_(%D8%AF%D9%85%D8%B4%D9%82),_Syria_-_Interior_of_prayer_hall_-_PHBZ024_2016_1388_-_Dumbarton_Oaks.jpg' ),
		'cordoba-mosque' => array( 'alt' => __( 'Double arches of red and white voussoirs over rows of columns in the Great Mosque of Córdoba', 'paulus' ), 'author' => 'Alvaro.vinuela.carnicero', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Mezquita_cordoba_arcos_flotantes.jpg' ),
		'birmingham-quran' => array( 'alt' => __( 'Two parchment leaves of the Birmingham Qurʾān manuscript in early Arabic script', 'paulus' ), 'author' => 'Unknown scribe', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Birmingham_Quran_manuscript.jpg' ),
		'bukhari-ms' => array( 'alt' => __( 'An illuminated opening of a fourteenth-century manuscript of Ṣaḥīḥ al-Bukhārī', 'paulus' ), 'author' => 'Unknown copyist, 14th century', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Al-jami_al-sahih_14th_century_Marinid_Morocco_manuscript.png' ),
		'codex-alexandrinus' => array( 'alt' => __( 'A page of Codex Alexandrinus with Greek uncial script in two columns', 'paulus' ), 'author' => 'Unknown scribe', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Codex_Alexandrinus_f41v_-_Luke.jpg' ),
		'valentin-paul-writing' => array( 'alt' => __( 'Painting of Paul seated at a table writing by candlelight, a sword beside him', 'paulus' ), 'author' => 'Attributed to Valentin de Boulogne', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Probably_Valentin_de_Boulogne_-_Saint_Paul_Writing_His_Epistles_-_Google_Art_Project.jpg' ),
		'malacca-st-pauls' => array( 'alt' => __( 'The roofless stone ruins of St Paul\'s Church on St Paul\'s Hill in Malacca', 'paulus' ), 'author' => 'Natalie.Thoo', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:1521_St_Paul%27s_Church_(Ruins)_Front_View.jpg' ),
		'map-journeys' => array( 'alt' => __( 'A decorated 1695 map of the eastern Mediterranean tracing the journeys of Paul', 'paulus' ), 'author' => 'Daniël Stopendaal', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:1695_map_of_the_Eastern_Mediterranean_showing_the_travels_of_St._Paul.jpg' ),
		'marcion-woodcut' => array( 'alt' => __( 'Late fifteenth-century woodcut of a hooded, bearded man holding a closed book, headed Marcion hereticus', 'paulus' ), 'author' => 'Rijksmuseum', 'license' => 'CC0', 'license_url' => 'http://creativecommons.org/publicdomain/zero/1.0/deed.en', 'source' => 'https://commons.wikimedia.org/wiki/File:Marcion_van_Sinope_Marcion_hereticus_(titel_op_object)_Liber_Chronicarum_(serietitel),_RP-P-2016-49-78-9.jpg' ),
		'alexamenos-graffito' => array( 'alt' => __( 'Line drawing of an ancient graffito: a man raising one hand towards a crucified figure with the head of a donkey, above a Greek inscription', 'paulus' ), 'author' => 'Unknown draughtsman, 1904', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Graffito_d%27Alexamenos.jpg' ),
		'spong-2006' => array( 'alt' => __( 'Photograph of an elderly bishop in a clerical collar and purple shirt speaking at a lectern', 'paulus' ), 'author' => 'Scott Griessel', 'license' => 'CC BY-SA 2.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Bishop_John_Shelby_Spong_speaking_2006.jpg' ),
		'herodium' => array( 'alt' => __( 'Aerial photograph of a flat-topped conical hill crowned by the round ruins of a fortress, with roads winding around its base', 'paulus' ), 'author' => 'Asaf T.', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Herodium_from_above.jpg' ),
		'arch-of-titus-spoils' => array( 'alt' => __( 'Weathered marble relief of Roman soldiers carrying a seven-branched lampstand in procession', 'paulus' ), 'author' => 'José Luiz', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Spoils_from_Jerusalem_-_Arch_of_Titus_-_Rome_2008.jpg' ),
		'caravaggio-odescalchi' => array( 'alt' => __( 'Caravaggio painting of Saul fallen on his back with his hands over his eyes, a soldier and a horse above him and a winged figure reaching down', 'paulus' ), 'author' => 'Caravaggio', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Caravaggio_-_Conversione_di_San_Paolo_(Odescalchi).jpg' ),
		'epiphanius-ohrid' => array( 'alt' => __( 'Byzantine fresco of a bearded bishop with a halo, in a vestment patterned with black crosses, named in Greek', 'paulus' ), 'author' => 'Michael and Eutychios', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Agios_Epiphanios_Peribleptos.jpg' ),
		'el-greco-peter-paul' => array( 'alt' => __( 'Painting of two apostles side by side, one grey-bearded in yellow and one dark-bearded in red with an open book', 'paulus' ), 'author' => 'El Greco', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Greco,_El_-_Sts_Peter_and_Paul.jpg' ),
		'areopagus' => array( 'alt' => __( 'Rocky hill rising among trees below the city of Athens, seen from above', 'paulus' ), 'author' => 'Jebulon', 'license' => 'CC0', 'license_url' => 'http://creativecommons.org/publicdomain/zero/1.0/deed.en', 'source' => 'https://commons.wikimedia.org/wiki/File:Areopagus_hill_Saint_Paul_from_Acropolis_Athens.jpg' ),
		'rembrandt-moses' => array( 'alt' => __( 'Rembrandt painting of a bearded man raising two stone tablets inscribed in Hebrew above his head', 'paulus' ), 'author' => 'Rembrandt', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Rembrandt_-_Moses_with_the_Ten_Commandments_-_Google_Art_Project.jpg' ),
		'la-hyre-malta' => array( 'alt' => __( 'Painting of a man shaking a snake from his hand into a fire on a shore, watched by islanders and shipwrecked men', 'paulus' ), 'author' => 'Laurent de La Hyre', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Laurent_de_La_Hyre%27s_Saint_Paul_Shipwrecked_on_Malta.jpg' ),
		'guercino-hagar' => array( 'alt' => __( 'Painting of an old man in a turban sending away a young woman who weeps, holding a boy, while another woman turns away', 'paulus' ), 'author' => 'Guercino', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Guercino_Abramo_ripudia_Agar.jpg' ),
		'etchmiadzin-thecla' => array( 'alt' => __( 'Stone relief of two framed figures in arches set into a church wall', 'paulus' ), 'author' => 'Rita Willaert', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Etchmiadzin_Cathedral_relief_Saint_Thecla_and_Paul_the_Apostle.jpg' ),
		'bloemaert-rock' => array( 'alt' => __( 'Painting of a crowd drinking water that gushes from a rock struck by a man with a staff', 'paulus' ), 'author' => 'The Metropolitan Museum of Art', 'license' => 'CC0', 'license_url' => 'http://creativecommons.org/publicdomain/zero/1.0/deed.en', 'source' => 'https://commons.wikimedia.org/wiki/File:Moses_Striking_the_Rock_MET_DP145411.jpg' ),
		'habib-neccar' => array( 'alt' => __( 'Courtyard arcade and entrance of a stone mosque with a dome', 'paulus' ), 'author' => 'Nedim Ardoğa', 'license' => 'CC BY-SA 4.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Habib%27i_Neccar_Mosque.jpg' ),
		'kufa-1915' => array( 'alt' => __( 'Black-and-white aerial photograph of a large walled mosque compound in a flat desert town', 'paulus' ), 'author' => 'Unknown photographer, 1915', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Kufa_Mosque,_1915.jpg' ),
		'tughrul-rey' => array( 'alt' => __( 'Tall cylindrical brick tower with a fluted wall rising behind trees', 'paulus' ), 'author' => 'David Stanley', 'license' => 'CC BY 2.0', 'license_url' => 'https://creativecommons.org/licenses/by/2.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Tughrul_Tower_(8753491871).jpg' ),
		'faruqi-grave' => array( 'alt' => __( 'Grey granite headstone lettered AL FARUQI with two names and dates, set in grass', 'paulus' ), 'author' => 'Ibn Juferi', 'license' => 'CC BY 4.0', 'license_url' => 'https://creativecommons.org/licenses/by/4.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Faruqi-grave-tombstone.png' ),
		'penang-st-george' => array( 'alt' => __( 'White neoclassical church with a columned portico and a spire, beside a small domed pavilion', 'paulus' ), 'author' => 'Gryffindor', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:St._George%27s_Church_Penang_Dec_2006_001.jpg' ),
		'sinaiticus' => array( 'alt' => __( 'Page of an ancient Greek manuscript written in four narrow columns of capital letters', 'paulus' ), 'author' => 'Unknown scribe, 4th century', 'license' => 'Public domain', 'license_url' => '', 'source' => 'https://commons.wikimedia.org/wiki/File:Codex_Sinaiticus_Matthew_6,4-32.JPG' ),
		'judaea-diploma' => array( 'alt' => __( 'Bronze tablet engraved in Latin capitals, with a small hole at the top corner', 'paulus' ), 'author' => 'אור פ', 'license' => 'CC BY-SA 3.0', 'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0', 'source' => 'https://commons.wikimedia.org/wiki/File:Roman_military_diploma-_90_-_judaea.jpg' ),
		'al-faruqi-portrait' => array( 'alt' => __( 'Black-and-white portrait photograph of Isma\'il R. al Faruqi in a pinstriped suit and houndstooth tie', 'paulus' ), 'author' => 'Unknown photographer', 'license' => 'Public domain', 'license_url' => '', 'source' => '' ),
	);
}

/**
 * [paulus_figure name="slug"]Caption[/paulus_figure]
 *
 * @param array  $atts    Attributes: name, and wide="1" for a full-column figure.
 * @param string $caption Caption text (may contain <em>).
 * @return string
 */
function paulus_sc_figure( $atts, $caption = '' ) {
	$atts = shortcode_atts( array( 'name' => '', 'wide' => '0' ), $atts, 'paulus_figure' );
	$figs = paulus_figures();
	if ( ! isset( $figs[ $atts['name'] ] ) ) {
		return '';
	}
	$f    = $figs[ $atts['name'] ];
	$file = PAULUS_DIR . '/assets/images/church/' . $atts['name'] . '.webp';
	$size = file_exists( $file ) ? getimagesize( $file ) : array( 1400, 1050 );
	$full = PAULUS_URI . '/assets/images/church/' . $atts['name'] . '.webp';
	$mid      = PAULUS_DIR . '/assets/images/church/' . $atts['name'] . '-720.webp';
	$mid_size = file_exists( $mid ) ? getimagesize( $mid ) : false;
	// The mid-size variant's own width, not an assumed 720: several of
	// the portrait figures were resized to a fixed height instead, so
	// their actual width differs and a hard-coded "720w" descriptor would
	// tell the browser the wrong thing to pick from.
	$set = $mid_size ? PAULUS_URI . '/assets/images/church/' . $atts['name'] . '-720.webp ' . (int) $mid_size[0] . 'w, ' . $full . ' ' . (int) $size[0] . 'w' : '';
	$tall = $size[1] > $size[0];
	$lic  = $f['license_url'] ? '<a href="' . esc_url( $f['license_url'] ) . '" rel="license noopener" target="_blank">' . esc_html( $f['license'] ) . '</a>' : esc_html( $f['license'] );
	// Figures from Wikimedia Commons credit and link their file page; a
	// figure supplied from elsewhere (an empty source) gives author and
	// licence only.
	$cred = $f['source'] ? sprintf(
		/* translators: 1: author, 2: licence, 3: source link. */
		__( 'Image: %1$s, %2$s, via %3$s.', 'paulus' ),
		esc_html( $f['author'] ),
		$lic,
		'<a href="' . esc_url( $f['source'] ) . '" rel="noopener" target="_blank">Wikimedia Commons</a>'
	) : sprintf(
		/* translators: 1: author, 2: licence. */
		__( 'Image: %1$s, %2$s.', 'paulus' ),
		esc_html( $f['author'] ),
		$lic
	);
	return '<figure class="paulus-figure' . ( $tall ? ' paulus-figure--tall' : '' ) . ( '1' === $atts['wide'] ? ' paulus-figure--wide' : '' ) . '">'
		. '<a class="paulus-figure__zoom" href="' . esc_url( $full ) . '" data-lightbox="paulus-figures" data-title="' . esc_attr( wp_strip_all_tags( $caption ) . ' ' . wp_strip_all_tags( $cred ) ) . '" aria-label="' . esc_attr__( 'Open the full-size image', 'paulus' ) . '">'
		. '<img src="' . esc_url( $full ) . '"' . ( $set ? ' srcset="' . esc_attr( $set ) . '" sizes="(max-width: 760px) 100vw, 720px"' : '' ) . ' alt="' . esc_attr( $f['alt'] ) . '" width="' . (int) $size[0] . '" height="' . (int) $size[1] . '" loading="lazy" decoding="async">'
		. '</a>'
		. '<figcaption>' . wp_kses( $caption, array( 'em' => array(), 'a' => array( 'href' => true ) ) ) . ' <span class="paulus-figure__credit">' . $cred . '</span></figcaption>'
		. '</figure>';
}
add_shortcode( 'paulus_figure', 'paulus_sc_figure' );
