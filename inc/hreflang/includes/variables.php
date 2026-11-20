<?php
/**
 * Hreflang – Language Variables
 *
 * @package Hreflang
 * @since   3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Build the master language list.
 *
 * Merges WordPress core translations with additional locales
 * commonly needed for hreflang tagging (regional English,
 * non-regional base languages, etc.).
 */
function paulus_hreflang_build_language_list() {

    // Start with WP core translations if available.
    $languages = array();
    // The function lives in a core file that is not loaded on every request.
    $translation_file = ABSPATH . 'wp-admin/includes/translation-install.php';
    if ( is_readable( $translation_file ) ) {
        require_once $translation_file;
    }
    if ( function_exists( 'wp_get_available_translations' ) ) {
        $languages = wp_get_available_translations();
    }

    // ── Additional / override entries ────────────────────────────────
    $extra = array(
        // Base (non-regional) languages.
        'en'      => array( 'english_name' => 'English' ),
        'en_US'   => array( 'english_name' => 'English (United States)' ),
        'en_GB'   => array( 'english_name' => 'English (United Kingdom)' ),
        'en_AU'   => array( 'english_name' => 'English (Australia)' ),
        'en_CA'   => array( 'english_name' => 'English (Canada)' ),
        'en_NZ'   => array( 'english_name' => 'English (New Zealand)' ),
        'en_IN'   => array( 'english_name' => 'English (India)' ),
        'en_IE'   => array( 'english_name' => 'English (Ireland)' ),
        'en_SG'   => array( 'english_name' => 'English (Singapore)' ),
        'en_MY'   => array( 'english_name' => 'English (Malaysia)' ),
        'en_PH'   => array( 'english_name' => 'English (Philippines)' ),
        'en_HK'   => array( 'english_name' => 'English (Hong Kong)' ),
        'en_ID'   => array( 'english_name' => 'English (Indonesia)' ),
        'en_AE'   => array( 'english_name' => 'English (United Arab Emirates)' ),
        'en_SA'   => array( 'english_name' => 'English (Saudi Arabia)' ),
        'en_PK'   => array( 'english_name' => 'English (Pakistan)' ),
        'en_KE'   => array( 'english_name' => 'English (Kenya)' ),
        'en_BE'   => array( 'english_name' => 'English (Belgium)' ),
        'en_CH'   => array( 'english_name' => 'English (Switzerland)' ),
        'en_FR'   => array( 'english_name' => 'English (France)' ),
        'en_NL'   => array( 'english_name' => 'English (Netherlands)' ),
        'en_TH'   => array( 'english_name' => 'English (Thailand)' ),
        'en_RO'   => array( 'english_name' => 'English (Romania)' ),
        'en_CN'   => array( 'english_name' => 'English (China)' ),
        'en_MM'   => array( 'english_name' => 'English (Myanmar)' ),
        'en_VN'   => array( 'english_name' => 'English (Viet Nam)' ),
        'en_KR'   => array( 'english_name' => 'English (Korea)' ),
        'en_BN'   => array( 'english_name' => 'English (Brunei)' ),
        'en_KH'   => array( 'english_name' => 'English (Cambodia)' ),
        'en_LA'   => array( 'english_name' => 'English (Laos)' ),
        'en_MU'   => array( 'english_name' => 'English (Mauritius)' ),
        'en_MT'   => array( 'english_name' => 'English (Malta)' ),
        'en_GI'   => array( 'english_name' => 'English (Gibraltar)' ),
        'en_BH'   => array( 'english_name' => 'English (Bahrain)' ),
        'en_EG'   => array( 'english_name' => 'English (Egypt)' ),
        'en_IQ'   => array( 'english_name' => 'English (Iraq)' ),
        'en_JO'   => array( 'english_name' => 'English (Jordan)' ),
        'en_KW'   => array( 'english_name' => 'English (Kuwait)' ),
        'en_LB'   => array( 'english_name' => 'English (Lebanon)' ),
        'en_LY'   => array( 'english_name' => 'English (Libya)' ),
        'en_MA'   => array( 'english_name' => 'English (Morocco)' ),
        'en_OM'   => array( 'english_name' => 'English (Oman)' ),
        'en_QA'   => array( 'english_name' => 'English (Qatar)' ),
        'en_SY'   => array( 'english_name' => 'English (Syria)' ),
        'en_TN'   => array( 'english_name' => 'English (Tunisia)' ),
        'en_YE'   => array( 'english_name' => 'English (Yemen)' ),

        // European.
        'fr'      => array( 'english_name' => 'French' ),
        'fr_CH'   => array( 'english_name' => 'French (Switzerland)' ),
        'fr_LU'   => array( 'english_name' => 'French (Luxembourg)' ),
        'de'      => array( 'english_name' => 'German' ),
        'de_DE'   => array( 'english_name' => 'German (Germany)' ),
        'de_AT'   => array( 'english_name' => 'German (Austria)' ),
        'es'      => array( 'english_name' => 'Spanish' ),
        'es_US'   => array( 'english_name' => 'Spanish (USA)' ),
        'es_EC'   => array( 'english_name' => 'Spanish (Ecuador)' ),
        'es_PY'   => array( 'english_name' => 'Spanish (Paraguay)' ),
        'es_PA'   => array( 'english_name' => 'Spanish (Panama)' ),
        'es_UY'   => array( 'english_name' => 'Spanish (Uruguay)' ),
        'es_NI'   => array( 'english_name' => 'Spanish (Nicaragua)' ),
        'es_PR'   => array( 'english_name' => 'Spanish (Puerto Rico)' ),
        'es_BO'   => array( 'english_name' => 'Spanish (Bolivia)' ),
        'es_DO'   => array( 'english_name' => 'Spanish (Dominican Republic)' ),
        'es_HN'   => array( 'english_name' => 'Spanish (Honduras)' ),
        'es_SV'   => array( 'english_name' => 'Spanish (El Salvador)' ),
        'it'      => array( 'english_name' => 'Italian' ),
        'it_IT'   => array( 'english_name' => 'Italian (Italy)' ),
        'pt'      => array( 'english_name' => 'Portuguese' ),
        'nl'      => array( 'english_name' => 'Dutch' ),
        'nl_NL'   => array( 'english_name' => 'Dutch (Netherlands)' ),
        'nl_BE'   => array( 'english_name' => 'Dutch (Belgium)' ),
        'no'      => array( 'english_name' => 'Norwegian' ),
        'no_NO'   => array( 'english_name' => 'Norwegian (Norway)' ),
        'sv'      => array( 'english_name' => 'Swedish' ),
        'sv_SE'   => array( 'english_name' => 'Swedish (Sweden)' ),
        'sv_FI'   => array( 'english_name' => 'Swedish (Finland)' ),
        'pl'      => array( 'english_name' => 'Polish' ),
        'pl_PL'   => array( 'english_name' => 'Polish (Poland)' ),
        'cs'      => array( 'english_name' => 'Czech' ),
        'cs_CZ'   => array( 'english_name' => 'Czech (Czechia)' ),
        'tr'      => array( 'english_name' => 'Turkish' ),
        'tr_TR'   => array( 'english_name' => 'Turkish (Turkey)' ),
        'mt'      => array( 'english_name' => 'Maltese' ),
        'mt_MT'   => array( 'english_name' => 'Maltese (Malta)' ),
        'he'      => array( 'english_name' => 'Hebrew' ),
        'he_IL'   => array( 'english_name' => 'Hebrew (Israel)' ),
        'he_US'   => array( 'english_name' => 'Hebrew (United States)' ),
        'he_GB'   => array( 'english_name' => 'Hebrew (United Kingdom)' ),
        'he_FR'   => array( 'english_name' => 'Hebrew (France)' ),
        'he_DE'   => array( 'english_name' => 'Hebrew (Germany)' ),
        'he_CA'   => array( 'english_name' => 'Hebrew (Canada)' ),
        'he_AU'   => array( 'english_name' => 'Hebrew (Australia)' ),
        'he_AR'   => array( 'english_name' => 'Hebrew (Argentina)' ),
        'he_BR'   => array( 'english_name' => 'Hebrew (Brazil)' ),
        'he_ZA'   => array( 'english_name' => 'Hebrew (South Africa)' ),
        'he_RU'   => array( 'english_name' => 'Hebrew (Russia)' ),

        // Arabic.
        'ar'      => array( 'english_name' => 'Arabic' ),
        'ar_SA'   => array( 'english_name' => 'Arabic (Saudi Arabia)' ),
        'ar_AE'   => array( 'english_name' => 'Arabic (United Arab Emirates)' ),
        'ar_EG'   => array( 'english_name' => 'Arabic (Egypt)' ),
        'ar_IQ'   => array( 'english_name' => 'Arabic (Iraq)' ),
        'ar_JO'   => array( 'english_name' => 'Arabic (Jordan)' ),
        'ar_KW'   => array( 'english_name' => 'Arabic (Kuwait)' ),
        'ar_LB'   => array( 'english_name' => 'Arabic (Lebanon)' ),
        'ar_LY'   => array( 'english_name' => 'Arabic (Libya)' ),
        'ar_MA'   => array( 'english_name' => 'Arabic (Morocco)' ),
        'ar_OM'   => array( 'english_name' => 'Arabic (Oman)' ),
        'ar_QA'   => array( 'english_name' => 'Arabic (Qatar)' ),
        'ar_SY'   => array( 'english_name' => 'Arabic (Syria)' ),
        'ar_TN'   => array( 'english_name' => 'Arabic (Tunisia)' ),
        'ar_YE'   => array( 'english_name' => 'Arabic (Yemen)' ),
        'ar_BH'   => array( 'english_name' => 'Arabic (Bahrain)' ),
        'ar_DZ'   => array( 'english_name' => 'Arabic (Algeria)' ),
        'ar_SD'   => array( 'english_name' => 'Arabic (Sudan)' ),
        'ar_PS'   => array( 'english_name' => 'Arabic (Palestine)' ),
        'ar_SO'   => array( 'english_name' => 'Arabic (Somalia)' ),
        'ar_MR'   => array( 'english_name' => 'Arabic (Mauritania)' ),
        'ar_DJ'   => array( 'english_name' => 'Arabic (Djibouti)' ),
        'ar_KM'   => array( 'english_name' => 'Arabic (Comoros)' ),
        'ar_TD'   => array( 'english_name' => 'Arabic (Chad)' ),
        'ar_ER'   => array( 'english_name' => 'Arabic (Eritrea)' ),

        // Malay.
        'ms'      => array( 'english_name' => 'Malay' ),
        'ms_MY'   => array( 'english_name' => 'Malay (Malaysia)' ),
        'ms_BN'   => array( 'english_name' => 'Malay (Brunei)' ),
        'ms_SG'   => array( 'english_name' => 'Malay (Singapore)' ),
        'ms_ID'   => array( 'english_name' => 'Malay (Indonesia)' ),
        'ms_CC'   => array( 'english_name' => 'Malay (Cocos Islands)' ),
        'ms_CX'   => array( 'english_name' => 'Malay (Christmas Island)' ),
        'ms_TH'   => array( 'english_name' => 'Malay (Thailand)' ),

        // Asian.
        'zh_Hans' => array( 'english_name' => 'Chinese (Simplified)' ),
        'zh_Hant' => array( 'english_name' => 'Chinese (Traditional)' ),
        'zh_FR'   => array( 'english_name' => 'Chinese (France)' ),
        'th_TH'   => array( 'english_name' => 'Thai (Thailand)' ),

        // Central Asian.
        'kk'      => array( 'english_name' => 'Kazakh' ),
        'tk'      => array( 'english_name' => 'Turkmen' ),
        'uz'      => array( 'english_name' => 'Uzbek' ),
        'uz_UZ'   => array( 'english_name' => 'Uzbek (Uzbekistan)' ),
        'tg'      => array( 'english_name' => 'Tajik' ),

        // Slavic.
        'ru'      => array( 'english_name' => 'Russian' ),
        'ru_RU'   => array( 'english_name' => 'Russian (Russia)' ),

        // Special.
        'x-default' => array( 'english_name' => 'x-default (Fallback)' ),
    );

    foreach ( $extra as $code => $data ) {
        if ( ! isset( $languages[ $code ] ) || ! isset( $languages[ $code ]['english_name'] ) ) {
            $languages[ $code ] = $data;
        }
    }

    // Remove informal/formal duplicates.
    unset( $languages['de_CH_informal'], $languages['de_DE_formal'] );

    // Sort alphabetically by english_name.
    uasort( $languages, function ( $a, $b ) {
        $na = isset( $a['english_name'] ) ? $a['english_name'] : '';
        $nb = isset( $b['english_name'] ) ? $b['english_name'] : '';
        return strcasecmp( $na, $nb );
    } );

    return $languages;
}

global $paulus_hreflang_languages;
$paulus_hreflang_languages = paulus_hreflang_build_language_list();
