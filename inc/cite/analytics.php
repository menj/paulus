<?php
/**
 * Analytics: DB management, cron, GDPR exporters/erasers, AJAX tracking.
 *
 * FIX #2  DB upgrade routine via PAULUS_CITE_DB_VERSION + admin_init hook.
 * FIX #3  Nonce removed from analytics AJAX (non-sensitive write, see WHY below).
 * FIX #6  paulus_cite_do_daily_purge reads get_option() not the global.
 * FIX #9  Privacy policy note about IP-hash mode.
 * FIX Bug Anonymous visitor transient key collision (empty identifier guard).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// ============================================================================
// PRIVACY POLICY CONTENT
// ============================================================================
add_action( 'admin_init', 'paulus_cite_add_privacy_policy_content' );
function paulus_cite_add_privacy_policy_content() {
    if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) { return; }
    $setting      = get_option( 'paulus_citepress', array() );
    $cooldown     = isset( $setting['analytics_cooldown_mode'] ) ? $setting['analytics_cooldown_mode'] : 'session';
    $ip_hash_note = '';
    if ( $cooldown === 'ip_hash' ) {
        $ip_hash_note = '<p><strong>' . esc_html__( 'Note:', 'paulus' ) . '</strong> '
            . esc_html__( 'This site uses IP-hash tracking mode. A one-way hash of your IP address may be stored to prevent duplicate counts. This hash cannot be reversed but may constitute personal data under GDPR Article 4 in some jurisdictions.', 'paulus' )
            . '</p>';
    }
    $content = sprintf(
        '<h2>%s</h2><p>%s</p><h3>%s</h3><p>%s</p><ul><li><strong>%s</strong> - %s</li><li><strong>%s</strong> - %s</li><li><strong>%s</strong> - %s</li></ul><h3>%s</h3><p>%s</p>%s<h3>%s</h3><p>%s</p><h3>%s</h3><p>%s</p>',
        esc_html__( 'Citation Analytics (Citation)', 'paulus' ),
        esc_html__( 'This website uses the theme to provide academic citation functionality. When citation analytics are enabled, we collect limited data about how visitors interact with citations.', 'paulus' ),
        esc_html__( 'What We Collect', 'paulus' ),
        esc_html__( 'When analytics are enabled, the following events may be recorded:', 'paulus' ),
        esc_html__( 'View', 'paulus' ), esc_html__( 'When a citation box is displayed on a page.', 'paulus' ),
        esc_html__( 'Copy', 'paulus' ), esc_html__( 'When a visitor copies a citation to their clipboard.', 'paulus' ),
        esc_html__( 'Export', 'paulus' ), esc_html__( 'When a visitor downloads a citation in BibTeX, RIS, EndNote, CSL-JSON, CFF, or Dublin Core format.', 'paulus' ),
        esc_html__( 'Consent Requirement', 'paulus' ),
        esc_html__( 'Analytics tracking requires explicit consent. No data is collected until consent is granted through our cookie consent mechanism. You may decline analytics tracking and still use all citation features.', 'paulus' ),
        $ip_hash_note,
        esc_html__( 'Data Retention', 'paulus' ),
        esc_html__( 'Analytics data is automatically deleted after the configured retention period (default: 90 days). No names or addresses are stored with the analytics. To limit repeat counts, a one-way hash of the visitor’s session, or of the address and browser when there is no session, is kept for at most a minute and is never stored with the analytics.', 'paulus' ),
        esc_html__( 'Opt-Out', 'paulus' ),
        esc_html__( "You can opt out of citation analytics at any time by declining cookies or using your browser's Do Not Track setting. Site administrators can also disable analytics entirely from the plugin settings.", 'paulus' )
    );
    wp_add_privacy_policy_content( 'Cite', wp_kses_post( $content ) );
}

// ============================================================================
// GDPR DATA EXPORTER
// ============================================================================
add_filter( 'wp_privacy_personal_data_exporters', 'paulus_cite_register_data_exporter' );
function paulus_cite_register_data_exporter( $exporters ) {
    $exporters['cite-analytics'] = array(
        'exporter_friendly_name' => __( 'Cite Analytics Data', 'paulus' ),
        'callback'               => 'paulus_cite_personal_data_exporter',
    );
    return $exporters;
}
function paulus_cite_personal_data_exporter( $email_address, $page = 1 ) {
    $export_items    = array();
    $user_identifier = apply_filters( 'paulus_cite_analytics_identity_resolver', null, $email_address );
    if ( empty( $user_identifier ) ) {
        return array( 'data' => $export_items, 'done' => true );
    }
    global $wpdb;
    $items_per_page = 100;
    $page           = max( 1, absint( $page ) );
    $offset         = ( $page - 1 ) * $items_per_page;
    if ( is_array( $user_identifier ) && isset( $user_identifier['post_ids'] ) ) {
        $post_ids = array_values( array_filter( array_map( 'absint', (array) $user_identifier['post_ids'] ) ) );
        if ( empty( $post_ids ) ) {
            return array( 'data' => $export_items, 'done' => true );
        }
        // All IDs are absint-cast (guaranteed integers) — safe to inline without prepare() escaping.
        // LIMIT/OFFSET use %d so PHPCS can verify the static placeholder count of 2.
        $in_ids  = implode( ', ', $post_ids );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'paulus_citepress_analytics` WHERE post_id IN (' . $in_ids . ') LIMIT %d OFFSET %d', $items_per_page, $offset ) );
        foreach ( $results as $row ) {
            $export_items[] = array(
                'group_id'    => 'cite-analytics',
                'group_label' => __( 'Citation Analytics', 'paulus' ),
                'item_id'     => 'cite-analytics-' . $row->id,
                'data'        => array(
                    array( 'name' => __( 'Post ID', 'paulus' ),        'value' => $row->post_id ),
                    array( 'name' => __( 'Citation Style', 'paulus' ), 'value' => $row->citation_style ),
                    array( 'name' => __( 'Action Type', 'paulus' ),    'value' => $row->action_type ),
                    array( 'name' => __( 'Timestamp', 'paulus' ),      'value' => $row->timestamp ),
                ),
            );
        }
        $done = count( $results ) < $items_per_page;
    } else {
        $done = true;
    }
    return array( 'data' => $export_items, 'done' => $done );
}

// ============================================================================
// GDPR DATA ERASER
// ============================================================================
add_filter( 'wp_privacy_personal_data_erasers', 'paulus_cite_register_data_eraser' );
function paulus_cite_register_data_eraser( $erasers ) {
    $erasers['cite-analytics'] = array(
        'eraser_friendly_name' => __( 'Cite Analytics Data', 'paulus' ),
        'callback'             => 'paulus_cite_personal_data_eraser',
    );
    return $erasers;
}
function paulus_cite_personal_data_eraser( $email_address, $page = 1 ) {
    $items_removed   = 0;
    $messages        = array();
    $user_identifier = apply_filters( 'paulus_cite_analytics_identity_resolver', null, $email_address );
    if ( empty( $user_identifier ) ) {
        $messages[] = __( 'the theme uses privacy-preserving session-based tracking. No personally identifiable analytics data is stored.', 'paulus' );
        return array( 'items_removed' => 0, 'items_retained' => 0, 'messages' => $messages, 'done' => true );
    }
    global $wpdb;
    if ( is_array( $user_identifier ) && isset( $user_identifier['post_ids'] ) ) {
        $post_ids = array_values( array_filter( array_map( 'absint', (array) $user_identifier['post_ids'] ) ) );
        if ( empty( $post_ids ) ) {
            return array( 'items_removed' => 0, 'items_retained' => 0, 'messages' => $messages, 'done' => true );
        }
        // All IDs are absint-cast (guaranteed integers) — safe to inline without prepare() escaping.
        $in_ids        = implode( ', ', $post_ids );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $items_removed = $wpdb->query( 'DELETE FROM `' . $wpdb->prefix . 'paulus_citepress_analytics` WHERE post_id IN (' . $in_ids . ')' );
        if ( $items_removed > 0 ) {
            /* translators: %d: number of analytics records deleted */
            $messages[] = sprintf( __( '%d citation analytics records were removed.', 'paulus' ), $items_removed );
        }
    }
    return array( 'items_removed' => $items_removed, 'items_retained' => 0, 'messages' => $messages, 'done' => true );
}

// ============================================================================
// DATABASE CREATION & UPGRADE  (FIX #2)
// ============================================================================

/**
 * Create or upgrade the analytics table.
 * Called on activation AND on admin_init whenever PAULUS_CITE_DB_VERSION differs
 * from the stored value. dbDelta() is additive-safe.
 */
function paulus_cite_create_analytics_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'paulus_citepress_analytics';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        citation_style varchar(50) NOT NULL,
        action_type varchar(20) NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY citation_style (citation_style),
        KEY action_type (action_type)
    ) {$charset_collate};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    update_option( 'paulus_citepress_db', PAULUS_CITE_DB_VERSION );
}

add_action( 'admin_init', 'paulus_cite_maybe_upgrade_db' );
function paulus_cite_maybe_upgrade_db() {
    if ( get_option( 'paulus_citepress_db' ) !== PAULUS_CITE_DB_VERSION ) {
        paulus_cite_create_analytics_table();
    }
}

// ============================================================================
// CRON — DAILY RETENTION PURGE  (FIX #6)
// ============================================================================
add_action( 'wp', 'paulus_cite_schedule_purge' );
function paulus_cite_schedule_purge() {
    if ( ! wp_next_scheduled( 'paulus_cite_daily_purge_event' ) ) {
        wp_schedule_event( time(), 'daily', 'paulus_cite_daily_purge_event' );
    }
}

add_action( 'paulus_cite_daily_purge_event', 'paulus_cite_do_daily_purge' );
/**
 * FIX #6: Use get_option() directly. $paulus_cite_setting global may be absent in
 * WP-Cron pseudo-requests triggered by a server crontab calling wp-cron.php.
 */
function paulus_cite_do_daily_purge() {
    global $wpdb;
    $setting = get_option( 'paulus_citepress', array() );
    $days    = isset( $setting['analytics_retention_days'] ) ? intval( $setting['analytics_retention_days'] ) : 90;
    if ( $days > 0 ) {
        $wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            'DELETE FROM `' . $wpdb->prefix . 'paulus_citepress_analytics` WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)',
            $days
        ) );
    }
}

// ============================================================================
// ADMIN — MANUAL PURGE HANDLER
// ============================================================================
add_action( 'admin_init', 'paulus_cite_handle_analytics_purge' );
function paulus_cite_handle_analytics_purge() {
    if ( ! isset( $_POST['paulus_cite_purge_analytics'] ) ) { return; }
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    if ( ! isset( $_POST['paulus_cite_purge_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['paulus_cite_purge_nonce'] ) ), 'paulus_cite_purge_analytics_action' ) ) { return; }
    global $wpdb;
    $table_name   = $wpdb->prefix . 'paulus_citepress_analytics';
    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    if ( $table_exists ) {
        $wpdb->query( 'DELETE FROM `' . $wpdb->prefix . 'paulus_citepress_analytics`' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        wp_cache_delete( 'paulus_cite_analytics_stats', 'paulus' );
        wp_cache_delete( 'paulus_cite_analytics_totals', 'paulus' );
    }
    wp_safe_redirect( add_query_arg( array( 'page' => 'paulus-cite', 'paulus_cite_purged' => '1' ), admin_url( 'admin.php' ) ) );
    exit;
}

// ============================================================================
// AJAX ANALYTICS TRACKING  (FIX #3)
// ============================================================================
add_action( 'wp_ajax_paulus_cite_track_analytics',        'paulus_cite_track_analytics' );
add_action( 'wp_ajax_nopriv_paulus_cite_track_analytics', 'paulus_cite_track_analytics' );

/**
 * FIX #3 — Nonce removed.
 *
 * WHY: wp_create_nonce() output lives in cached HTML. Full-page caches
 * (WP Rocket, LiteSpeed, W3TC) serve the same nonce to every visitor long
 * after it expires, silently breaking analytics for all anonymous visitors.
 *
 * SECURITY RATIONALE: This endpoint only writes non-sensitive aggregated
 * counters (post ID + style + action_type). There is no read path, no account
 * change, no payment — CSRF protection has no attack surface to protect here.
 * Validation (post ID exists, allowed action, allowed style) and per-visitor
 * rate-limiting via transients are retained.
 */
// phpcs:disable WordPress.Security.NonceVerification.Missing
function paulus_cite_track_analytics() {
    global $wpdb, $paulus_cite_setting;

    if ( ! isset( $paulus_cite_setting['enable_analytics'] ) || $paulus_cite_setting['enable_analytics'] !== 'yes' ) {
        wp_send_json_success( array( 'message' => 'Analytics disabled' ) );
    }
    if ( apply_filters( 'paulus_cite_analytics_optout_active', false ) ) {
        wp_send_json_success( array( 'message' => 'Tracking blocked: Opt-out active' ) );
    }
    // The privacy text promises that a Do Not Track signal is honoured.
    if ( isset( $_SERVER['HTTP_DNT'] ) && '1' === $_SERVER['HTTP_DNT'] ) {
        wp_send_json_success( array( 'message' => 'Tracking blocked: Do Not Track' ) );
    }
    if ( isset( $paulus_cite_setting['require_consent_for_analytics'] ) && $paulus_cite_setting['require_consent_for_analytics'] === 'yes' ) {
        $consent_granted = apply_filters( 'paulus_cite_analytics_consent_granted', null );
        if ( $consent_granted === null && function_exists( 'wp_has_consent' ) ) {
            // The theme's own consent API, when a consent plugin feeds it.
            $consent_granted = wp_has_consent( 'statistics' ) ? true : null;
        }
        if ( $consent_granted === null ) { $consent_granted = paulus_cite_detect_consent_from_cookies(); }
        if ( ! $consent_granted ) {
            wp_send_json_success( array( 'message' => 'Tracking blocked: Consent not granted' ) );
        }
    }

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) { wp_send_json_error( array( 'message' => 'Invalid post ID' ), 400 ); }
    if ( ! get_post( $post_id ) ) { wp_send_json_error( array( 'message' => 'Post not found' ), 404 ); }

    $citation_style = isset( $_POST['citation_style'] ) ? sanitize_text_field( wp_unslash( $_POST['citation_style'] ) ) : '';
    $action_type    = isset( $_POST['action_type'] )    ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) )    : '';

    if ( $action_type === 'export' ) {
        $allowed_formats = array( 'bibtex', 'ris', 'endnote', 'csl-json', 'cff', 'dublin-core' );
        if ( ! in_array( $citation_style, $allowed_formats, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid export format' ), 400 );
        }
    } else {
        $allowed_styles = array_keys( paulus_cite_get_citation_styles() );
        if ( ! in_array( $citation_style, $allowed_styles, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid citation style' ), 400 );
        }
    }
    $allowed_actions = array( 'view', 'copy', 'export' );
    if ( ! in_array( $action_type, $allowed_actions, true ) ) {
        wp_send_json_error( array( 'message' => 'Invalid action type' ), 400 );
    }

    // --- Rate-limiting: FIX Bug (empty identifier guard) --------------------
    $cooldown_mode = isset( $paulus_cite_setting['analytics_cooldown_mode'] ) ? $paulus_cite_setting['analytics_cooldown_mode'] : 'session';
    $identifier    = '';

    if ( $cooldown_mode === 'session' ) {
        $identifier = wp_get_session_token(); // empty for anonymous users
        if ( ! $identifier ) {
            // Fall back to IP+UA hash so rate-limiting works for anonymous visitors.
            $ip  = isset( $_SERVER['REMOTE_ADDR'] )     ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )     : '';
            $ua  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
            $identifier = md5( $ip . $ua );
        }
    } elseif ( $cooldown_mode === 'ip_hash' ) {
        $ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $identifier = md5( $ip );
    }

    if ( $cooldown_mode !== 'none' && $identifier ) {
        $transient_key = 'paulus_cite_track_' . $action_type . '_' . $post_id . '_' . $identifier;
        if ( get_transient( $transient_key ) ) {
            wp_send_json_success( array( 'message' => 'Already tracked' ) );
        }
        $cooldown = ( $action_type === 'view' ) ? MINUTE_IN_SECONDS : 10;
        set_transient( $transient_key, 1, $cooldown );
    }

    $table_name = $wpdb->prefix . 'paulus_citepress_analytics';
    $result     = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $table_name,
        array( 'post_id' => $post_id, 'citation_style' => $citation_style, 'action_type' => $action_type ),
        array( '%d', '%s', '%s' )
    );
    if ( false === $result ) {
        wp_send_json_error( array( 'message' => 'Failed to track analytics' ), 500 );
    }

    // Invalidate the cached dashboard stats so the admin view reflects the new
    // row immediately rather than waiting for the five-minute TTL to expire.
    wp_cache_delete( 'paulus_cite_analytics_stats', 'paulus' );
        wp_cache_delete( 'paulus_cite_analytics_totals', 'paulus' );

    wp_send_json_success( array( 'message' => 'Analytics tracked successfully' ) );
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

// ============================================================================
// CONSENT COOKIE DETECTION
// ============================================================================

/**
 * Detect user consent from common cookie consent plugin cookies.
 *
 * Each plugin stores consent in a different format — string values, substrings,
 * or JSON objects. Generic value matching (yes/true/1) does not work for most
 * of them. Each plugin is therefore handled specifically, with a generic
 * fallback at the end for plugins not explicitly listed here.
 *
 * @return bool True if analytics consent is detected, false otherwise.
 */
function paulus_cite_detect_consent_from_cookies() {

    // CookieYes / Cookie Law Info — value like "analytics:yes,..."
    if ( isset( $_COOKIE['cookieyes-consent'] ) ) {
        $v = sanitize_text_field( wp_unslash( $_COOKIE['cookieyes-consent'] ) );
        return ( strpos( $v, 'analytics:yes' ) !== false );
    }

    // Complianz — separate per-category cookie; value is 'allow' or 'deny'
    if ( isset( $_COOKIE['cmplz_statistics'] ) ) {
        $v = sanitize_text_field( wp_unslash( $_COOKIE['cmplz_statistics'] ) );
        return ( $v === 'allow' );
    }

    // CookieBot — value like "{...,statistics:true,...}"
    if ( isset( $_COOKIE['CookieConsent'] ) ) {
        $v = sanitize_text_field( wp_unslash( $_COOKIE['CookieConsent'] ) );
        return ( strpos( $v, 'statistics:true' ) !== false );
    }

    // Moove GDPR / GDPR Cookie Compliance — JSON value
    if ( isset( $_COOKIE['moove_gdpr_popup'] ) ) {
        $raw   = wp_unslash( $_COOKIE['moove_gdpr_popup'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $moove = json_decode( stripslashes( $raw ), true );
        return ( isset( $moove['thirdparty'] ) && $moove['thirdparty'] === '1' );
    }

    // Borlabs Cookie — value 'true' or 'false'
    if ( isset( $_COOKIE['borlabs-cookie'] ) ) {
        $v = sanitize_text_field( wp_unslash( $_COOKIE['borlabs-cookie'] ) );
        return ( strpos( $v, '"statistics":true' ) !== false );
    }

    // Generic fallback: simple yes/true/1/accepted/allow for unknown plugins
    $generic_cookies = array(
        'cookielawinfo-checkbox-analytics',
        'cookie_notice_accepted',
        'euconsent-v2',
        'cmplz_consented_services',
    );
    foreach ( $generic_cookies as $cookie_name ) {
        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            $v = strtolower( sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) );
            if ( in_array( $v, array( 'yes', 'true', '1', 'accepted', 'allow' ), true ) ) {
                return true;
            }
            if ( strpos( $v, '"analytics":true' ) !== false ) {
                return true;
            }
        }
    }

    // Consent is required (this function runs only then) and no recognised
    // consent cookie says yes: no consent, so nothing is recorded.
    return false;
}
