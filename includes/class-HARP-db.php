<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HARP_DB {

    const TABLE_REPORTS  = 'HARP_reports';
    const TABLE_COMMENTS = 'HARP_comments';

    // ── Install ──────────────────────────────────────────────────────────────
    public static function install() {
        global $wpdb;
        $charset  = $wpdb->get_charset_collate();
        $reports  = $wpdb->prefix . self::TABLE_REPORTS;
        $comments = $wpdb->prefix . self::TABLE_COMMENTS;

        $sql_reports = "CREATE TABLE IF NOT EXISTS {$reports} (
            id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tracking_code   VARCHAR(20)  NOT NULL,
            category        VARCHAR(100) NOT NULL,
            urgency         VARCHAR(50)  NOT NULL,
            location        VARCHAR(100) NOT NULL,
            location_detail TEXT,
            description     LONGTEXT     NOT NULL,
            is_anonymous    TINYINT(1)   NOT NULL DEFAULT 1,
            reporter_name   VARCHAR(150),
            reporter_email  VARCHAR(150),
            evidence_path   VARCHAR(500),
            evidence_type   VARCHAR(20),
            status          VARCHAR(30)  NOT NULL DEFAULT 'Pending',
            admin_note      TEXT,
            submitted_at    DATETIME     NOT NULL,
            updated_at      DATETIME     NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY tracking_code (tracking_code),
            KEY status (status)
        ) {$charset};";

        $sql_comments = "CREATE TABLE IF NOT EXISTS {$comments} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id   BIGINT(20) UNSIGNED NOT NULL,
            author      VARCHAR(150) NOT NULL DEFAULT 'Admin',
            comment     TEXT NOT NULL,
            created_at  DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY report_id (report_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_reports );
        dbDelta( $sql_comments );

        update_option( 'HARP_db_version', HARP_VERSION );
        self::create_pages();
    }

    // Runs on every boot — creates tables if they were never created
    public static function maybe_install() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_REPORTS;
        // Check if table physically exists rather than trusting an option
        $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        if ( ! $exists ) {
            self::install();
        } elseif ( get_option( 'HARP_db_version' ) !== HARP_VERSION ) {
            self::install(); // run dbDelta for schema upgrades
        }
    }

    private static function create_pages() {
        $pages = [
            'HARP-submit-report' => [ 'title' => 'Submit Anonymous Report', 'content' => '[HARP_report_form]' ],
            'HARP-track-report'  => [ 'title' => 'Track Your Report',        'content' => '[HARP_track_report]' ],
        ];
        foreach ( $pages as $slug => $data ) {
            if ( ! get_page_by_path( $slug ) ) {
                wp_insert_post( [
                    'post_title'   => $data['title'],
                    'post_content' => $data['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_name'    => $slug,
                ] );
            }
        }
    }

    public static function deactivate() {}

    // ── insert_report ────────────────────────────────────────────────────────
    // Uses raw SQL via $wpdb->query() to avoid wpdb->insert() NULL-binding bugs.
    // NULL values are inserted as literal SQL NULL (not as bound %s parameters).
    public static function insert_report( array $data ) {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE_REPORTS;
        $now   = current_time( 'mysql' );

        // Pull values, defaulting absent keys to null
        $tracking_code   = $data['tracking_code'];
        $category        = $data['category'];
        $urgency         = $data['urgency'];
        $location        = $data['location'];
        $location_detail = isset( $data['location_detail'] ) ? $data['location_detail'] : null;
        $description     = $data['description'];
        $is_anonymous    = intval( $data['is_anonymous'] );
        $reporter_name   = isset( $data['reporter_name'] )  ? $data['reporter_name']  : null;
        $reporter_email  = isset( $data['reporter_email'] ) ? $data['reporter_email'] : null;
        $evidence_path   = isset( $data['evidence_path'] )  ? $data['evidence_path']  : null;
        $evidence_type   = isset( $data['evidence_type'] )  ? $data['evidence_type']  : null;
        $status          = isset( $data['status'] )         ? $data['status']          : 'Pending';

        // Build nullable fragments — pass SQL NULL directly, not through %s
        $ld_sql    = $location_detail  !== null ? $wpdb->prepare( '%s', $location_detail )  : 'NULL';
        $rname_sql = $reporter_name    !== null ? $wpdb->prepare( '%s', $reporter_name )    : 'NULL';
        $remail_sql= $reporter_email   !== null ? $wpdb->prepare( '%s', $reporter_email )   : 'NULL';
        $epath_sql = $evidence_path    !== null ? $wpdb->prepare( '%s', $evidence_path )    : 'NULL';
        $etype_sql = $evidence_type    !== null ? $wpdb->prepare( '%s', $evidence_type )    : 'NULL';

        $sql = $wpdb->prepare(
            "INSERT INTO `{$table}`
                (tracking_code, category, urgency, location, location_detail,
                 description, is_anonymous, reporter_name, reporter_email,
                 evidence_path, evidence_type, status, submitted_at, updated_at)
             VALUES
                (%s, %s, %s, %s, {$ld_sql},
                 %s, %d, {$rname_sql}, {$remail_sql},
                 {$epath_sql}, {$etype_sql}, %s, %s, %s)",
            $tracking_code,
            $category,
            $urgency,
            $location,
            $description,
            $is_anonymous,
            $status,
            $now,
            $now
        );

        $result = $wpdb->query( $sql );

        if ( $result === false ) {
            // Log to WP debug log if enabled
            if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                error_log( 'HARP insert_report failed. Error: ' . $wpdb->last_error );
                error_log( 'HARP last query: ' . $wpdb->last_query );
            }
            return false;
        }

        $new_id = (int) $wpdb->insert_id;
        return $new_id > 0 ? $new_id : false;
    }

    public static function get_report_by_code( $code ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}" . self::TABLE_REPORTS . "` WHERE tracking_code = %s LIMIT 1",
            $code
        ) );
    }

    public static function get_report_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}" . self::TABLE_REPORTS . "` WHERE id = %d LIMIT 1",
            (int) $id
        ) );
    }

    public static function get_all_reports( array $args = [] ): array {
        global $wpdb;
        $table  = $wpdb->prefix . self::TABLE_REPORTS;
        $where  = '1=1';
        $values = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $values[] = $args['status'];
        }
        if ( ! empty( $args['search'] ) ) {
            $where   .= ' AND (tracking_code LIKE %s OR category LIKE %s OR location LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $orderby  = sanitize_sql_orderby( $args['orderby'] ?? 'submitted_at DESC' ) ?: 'submitted_at DESC';
        $limit    = intval( $args['limit'] ?? 50 );
        $offset   = intval( $args['offset'] ?? 0 );
        $values[] = $limit;
        $values[] = $offset;

        $sql = "SELECT * FROM `{$table}` WHERE {$where} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        return $wpdb->get_results( $wpdb->prepare( $sql, $values ) ) ?: [];
    }

    public static function count_reports( string $status = '' ): int {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_REPORTS;
        if ( $status ) {
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE status = %s", $status ) );
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
    }

    public static function update_report( int $id, array $data ): bool {
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . self::TABLE_REPORTS,
            $data,
            [ 'id' => $id ],
            self::get_report_formats( $data ),
            [ '%d' ]
        );
        return $result !== false;
    }

    public static function delete_report( int $id ): bool {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . self::TABLE_COMMENTS, [ 'report_id' => $id ], [ '%d' ] );
        return (bool) $wpdb->delete( $wpdb->prefix . self::TABLE_REPORTS, [ 'id' => $id ], [ '%d' ] );
    }

    public static function insert_comment( int $report_id, string $author, string $comment ) {
        global $wpdb;
        $table  = $wpdb->prefix . self::TABLE_COMMENTS;
        $now    = current_time( 'mysql' );
        $result = $wpdb->query( $wpdb->prepare(
            "INSERT INTO `{$table}` (report_id, author, comment, created_at) VALUES (%d, %s, %s, %s)",
            $report_id, $author, $comment, $now
        ) );
        return $result ? (int) $wpdb->insert_id : false;
    }

    public static function get_comments( int $report_id ): array {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}" . self::TABLE_COMMENTS . "` WHERE report_id = %d ORDER BY created_at ASC",
            $report_id
        ) ) ?: [];
    }

    public static function generate_tracking_code(): string {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_REPORTS;
        do {
            $code   = 'SR-' . date( 'Y' ) . '-' . strtoupper( wp_generate_password( 6, false ) );
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE tracking_code = %s", $code ) );
        } while ( $exists );
        return $code;
    }

    // ── Diagnostic: returns table status + last DB error (admin only) ────────
    public static function get_diagnostics(): array {
        global $wpdb;
        $table  = $wpdb->prefix . self::TABLE_REPORTS;
        $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
        $cols   = $exists ? $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`" ) : [];
        return [
            'table_name'   => $table,
            'table_exists' => (bool) $exists,
            'columns'      => $cols,
            'last_error'   => $wpdb->last_error,
            'db_version'   => get_option( 'HARP_db_version' ),
            'wp_version'   => get_bloginfo( 'version' ),
            'php_version'  => PHP_VERSION,
        ];
    }

    private static function get_report_formats( array $data ): array {
        $map = [
            'id' => '%d', 'is_anonymous' => '%d',
            'tracking_code' => '%s', 'category' => '%s', 'urgency' => '%s',
            'location' => '%s', 'location_detail' => '%s', 'description' => '%s',
            'reporter_name' => '%s', 'reporter_email' => '%s',
            'evidence_path' => '%s', 'evidence_type' => '%s',
            'status' => '%s', 'admin_note' => '%s',
        ];
        $formats = [];
        foreach ( array_keys( $data ) as $key ) {
            $formats[] = $map[ $key ] ?? '%s';
        }
        return $formats;
    }
}
