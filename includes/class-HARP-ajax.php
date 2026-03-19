<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HARP_Ajax {

    public static function init(): void {
        add_action( 'wp_ajax_HARP_submit_report',        [ __CLASS__, 'submit_report' ] );
        add_action( 'wp_ajax_nopriv_HARP_submit_report', [ __CLASS__, 'submit_report' ] );

        add_action( 'wp_ajax_HARP_track_report',         [ __CLASS__, 'track_report' ] );
        add_action( 'wp_ajax_nopriv_HARP_track_report',  [ __CLASS__, 'track_report' ] );

        add_action( 'wp_ajax_HARP_update_status',        [ __CLASS__, 'update_status' ] );
        add_action( 'wp_ajax_HARP_add_comment',          [ __CLASS__, 'add_comment' ] );
        add_action( 'wp_ajax_HARP_delete_report',        [ __CLASS__, 'delete_report' ] );

        // Diagnostic endpoint — admin only, helps debug insert failures
        add_action( 'wp_ajax_HARP_diagnostics',          [ __CLASS__, 'diagnostics' ] );
    }

    // ── Submit Report ────────────────────────────────────────────────────────
    public static function submit_report(): void {
        self::verify_nonce();

        $category        = sanitize_text_field( $_POST['category'] ?? '' );
        $urgency         = sanitize_text_field( $_POST['urgency']  ?? '' );
        $location        = sanitize_text_field( $_POST['location'] ?? '' );
        $location_detail = sanitize_textarea_field( $_POST['location_detail'] ?? '' );
        $description     = sanitize_textarea_field( $_POST['description'] ?? '' );
        $is_anonymous    = intval( $_POST['is_anonymous'] ?? 1 );
        $reporter_name   = $is_anonymous ? null : sanitize_text_field( $_POST['reporter_name'] ?? '' );
        $reporter_email  = $is_anonymous ? null : sanitize_email( $_POST['reporter_email'] ?? '' );

        // Server-side validation
        $errors = [];
        if ( ! $category )    $errors[] = 'Report category is required.';
        if ( ! $urgency )     $errors[] = 'Urgency level is required.';
        if ( ! $location )    $errors[] = 'Location is required.';
        if ( mb_strlen( trim( $description ) ) < 10 ) {
            $errors[] = 'Please provide a description of at least 10 characters.';
        }
        if ( ! $is_anonymous ) {
            if ( ! $reporter_name )  $errors[] = 'Your name is required.';
            if ( ! is_email( $reporter_email ) ) $errors[] = 'A valid email address is required.';
        }
        if ( $errors ) {
            wp_send_json_error( [ 'message' => implode( ' ', $errors ) ] );
        }

        // Evidence upload
        $evidence_path = null;
        $evidence_type = null;
        if ( ! empty( $_FILES['evidence']['name'] ) ) {
            $upload = self::handle_evidence_upload( $_FILES['evidence'] );
            if ( is_wp_error( $upload ) ) {
                wp_send_json_error( [ 'message' => $upload->get_error_message() ] );
            }
            $evidence_path = $upload['url'];
            $evidence_type = $upload['type'];
        }

        $tracking_code = HARP_DB::generate_tracking_code();

        $insert_data = [
            'tracking_code'   => $tracking_code,
            'category'        => $category,
            'urgency'         => $urgency,
            'location'        => $location,
            'location_detail' => $location_detail ?: null,
            'description'     => $description,
            'is_anonymous'    => $is_anonymous,
            'reporter_name'   => $reporter_name,
            'reporter_email'  => $reporter_email,
            'evidence_path'   => $evidence_path,
            'evidence_type'   => $evidence_type,
            'status'          => 'Pending',
        ];

        $report_id = HARP_DB::insert_report( $insert_data );

        if ( ! $report_id ) {
            global $wpdb;
            // Always return the real DB error so the admin can diagnose
            $db_err = $wpdb->last_error ? $wpdb->last_error : 'No wpdb error captured.';
            wp_send_json_error( [
                'message' => 'Failed to save report. Database error: ' . $db_err,
            ] );
        }

        $report = HARP_DB::get_report_by_id( $report_id );
        if ( $report ) {
            HARP_Mailer::notify_admin_new_report( $report );
        }

        wp_send_json_success( [
            'message'       => 'Report submitted successfully.',
            'tracking_code' => $tracking_code,
        ] );
    }

    // ── Track Report ─────────────────────────────────────────────────────────
    public static function track_report(): void {
        self::verify_nonce();

        $code = strtoupper( sanitize_text_field( $_POST['tracking_code'] ?? '' ) );
        if ( ! $code ) {
            wp_send_json_error( [ 'message' => 'Please enter a tracking code.' ] );
        }
        if ( ! preg_match( '/^SR-\d{4}-[A-Z0-9]{6}$/', $code ) ) {
            wp_send_json_error( [ 'message' => 'Invalid format. Expected: SR-YYYY-XXXXXX' ] );
        }

        $report = HARP_DB::get_report_by_code( $code );
        if ( ! $report ) {
            wp_send_json_error( [ 'message' => 'No report found with that tracking code.' ] );
        }

        $comments      = HARP_DB::get_comments( $report->id );
        $comments_html = '';
        foreach ( $comments as $c ) {
            $comments_html .= '<div class="HARP-comment">'
                . '<div class="HARP-comment-meta">'
                . '<span class="HARP-comment-author">' . esc_html( $c->author ) . '</span>'
                . '<span class="HARP-comment-date">' . esc_html( date( 'M j, Y \a\t g:i A', strtotime( $c->created_at ) ) ) . '</span>'
                . '</div>'
                . '<p class="HARP-comment-text">' . nl2br( esc_html( $c->comment ) ) . '</p>'
                . '</div>';
        }

        wp_send_json_success( [
            'tracking_code'   => $report->tracking_code,
            'category'        => $report->category,
            'urgency'         => $report->urgency,
            'location'        => $report->location,
            'location_detail' => $report->location_detail,
            'status'          => $report->status,
            'admin_note'      => $report->admin_note,
            'submitted_at'    => date( 'F j, Y \a\t g:i A', strtotime( $report->submitted_at ) ),
            'updated_at'      => date( 'F j, Y \a\t g:i A', strtotime( $report->updated_at ) ),
            'has_evidence'    => ! empty( $report->evidence_path ),
            'comments_html'   => $comments_html,
            'comment_count'   => count( $comments ),
        ] );
    }

    // ── Admin: Update Status ─────────────────────────────────────────────────
    public static function update_status(): void {
        self::verify_admin();

        $report_id  = intval( $_POST['report_id'] ?? 0 );
        $status     = sanitize_text_field( $_POST['status'] ?? '' );
        $admin_note = sanitize_textarea_field( $_POST['admin_note'] ?? '' );

        if ( ! in_array( $status, [ 'Pending', 'In Progress', 'Resolved', 'Closed' ], true ) ) {
            wp_send_json_error( [ 'message' => 'Invalid status.' ] );
        }
        $report = HARP_DB::get_report_by_id( $report_id );
        if ( ! $report ) wp_send_json_error( [ 'message' => 'Report not found.' ] );

        HARP_DB::update_report( $report_id, [ 'status' => $status, 'admin_note' => $admin_note ] );

        $updated = HARP_DB::get_report_by_id( $report_id );
        if ( $updated && $updated->reporter_email ) {
            HARP_Mailer::notify_reporter_status_change( $updated );
        }
        wp_send_json_success( [ 'message' => 'Status updated successfully.' ] );
    }

    // ── Admin: Add Comment ───────────────────────────────────────────────────
    public static function add_comment(): void {
        self::verify_admin();

        $report_id = intval( $_POST['report_id'] ?? 0 );
        $comment   = sanitize_textarea_field( $_POST['comment'] ?? '' );
        if ( ! $comment ) wp_send_json_error( [ 'message' => 'Comment cannot be empty.' ] );

        $report = HARP_DB::get_report_by_id( $report_id );
        if ( ! $report ) wp_send_json_error( [ 'message' => 'Report not found.' ] );

        $user   = wp_get_current_user();
        $author = $user->display_name ? $user->display_name : 'Admin';
        $id     = HARP_DB::insert_comment( $report_id, $author, $comment );
        if ( ! $id ) wp_send_json_error( [ 'message' => 'Failed to save comment.' ] );

        wp_send_json_success( [
            'message' => 'Comment added.',
            'author'  => $author,
            'comment' => nl2br( esc_html( $comment ) ),
            'date'    => date( 'M j, Y \a\t g:i A' ),
        ] );
    }

    // ── Admin: Delete Report ─────────────────────────────────────────────────
    public static function delete_report(): void {
        self::verify_admin();

        $report_id = intval( $_POST['report_id'] ?? 0 );
        $report    = HARP_DB::get_report_by_id( $report_id );
        if ( ! $report ) wp_send_json_error( [ 'message' => 'Report not found.' ] );

        if ( $report->evidence_path ) {
            $upload_dir = wp_upload_dir();
            $local_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $report->evidence_path );
            if ( file_exists( $local_path ) ) @unlink( $local_path );
        }

        HARP_DB::delete_report( $report_id );
        wp_send_json_success( [ 'message' => 'Report deleted.' ] );
    }

    // ── Admin Diagnostic ─────────────────────────────────────────────────────
    public static function diagnostics(): void {
        self::verify_admin();
        wp_send_json_success( HARP_DB::get_diagnostics() );
    }

    // ── Evidence Upload ──────────────────────────────────────────────────────
    private static function handle_evidence_upload( array $file ) {
        $allowed_mime = [ 'image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime' ];
        $max_size     = 10 * 1024 * 1024;

        $finfo = new finfo( FILEINFO_MIME_TYPE );
        $mime  = $finfo->file( $file['tmp_name'] );

        if ( ! in_array( $mime, $allowed_mime, true ) ) {
            return new WP_Error( 'invalid_type', 'Only images (JPG, PNG, GIF) and videos (MP4, MOV) are allowed.' );
        }
        if ( $file['size'] > $max_size ) {
            return new WP_Error( 'file_too_large', 'Evidence file must be under 10 MB.' );
        }
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        add_filter( 'upload_dir', [ __CLASS__, 'custom_upload_dir' ] );
        $result = wp_handle_upload( $file, [ 'test_form' => false ] );
        remove_filter( 'upload_dir', [ __CLASS__, 'custom_upload_dir' ] );

        if ( isset( $result['error'] ) ) {
            return new WP_Error( 'upload_failed', $result['error'] );
        }
        $type = ( strpos( $mime, 'video/' ) === 0 ) ? 'video' : 'image';
        return [ 'url' => $result['url'], 'type' => $type ];
    }

    public static function custom_upload_dir( array $dirs ): array {
        $dirs['subdir'] = '/HARP-evidence';
        $dirs['path']   = $dirs['basedir'] . '/HARP-evidence';
        $dirs['url']    = $dirs['baseurl'] . '/HARP-evidence';
        return $dirs;
    }

    // ── Security ─────────────────────────────────────────────────────────────
    private static function verify_nonce(): void {
        if ( ! check_ajax_referer( 'HARP_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed. Please refresh the page and try again.' ], 403 );
        }
    }

    private static function verify_admin(): void {
        self::verify_nonce();
        if ( ! current_user_can( 'administrator' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorised.' ], 403 );
        }
    }
}


// Registered outside the class — create tables via AJAX (admin only)
add_action( 'wp_ajax_HARP_create_tables', function() {
    if ( ! check_ajax_referer( 'HARP_nonce', 'nonce', false ) || ! current_user_can( 'administrator' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorised.' ] );
    }
    HARP_DB::install();
    $diag = HARP_DB::get_diagnostics();
    if ( $diag['table_exists'] ) {
        wp_send_json_success( [ 'message' => 'Tables created successfully! Reloading…' ] );
    } else {
        wp_send_json_error( [ 'message' => 'Still failed: ' . $diag['last_error'] ] );
    }
} );
