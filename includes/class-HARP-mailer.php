<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HARP_Mailer {

    // ── Notify admin of new report ───────────────────────────────────────────
    public static function notify_admin_new_report( object $report ): void {
        $admin_email = get_option( 'HARP_admin_email', get_option( 'admin_email' ) );
        if ( ! $admin_email ) return;

        $subject = sprintf( '[HARP] New Report — %s', $report->tracking_code );

        $body  = self::header();
        $body .= '<h2 style="color:#1e3a5f;margin-bottom:8px;">New Report Submitted</h2>';
        $body .= '<p style="color:#555;margin-bottom:24px;">A new report has been submitted to the HIT Anonymous Reporting Portal.</p>';
        $body .= self::detail_row( 'Tracking Code', '<strong>' . esc_html( $report->tracking_code ) . '</strong>' );
        $body .= self::detail_row( 'Category',      esc_html( $report->category ) );
        $body .= self::detail_row( 'Urgency',        self::urgency_badge( $report->urgency ) );
        $body .= self::detail_row( 'Location',       esc_html( $report->location ) );
        if ( $report->location_detail ) {
            $body .= self::detail_row( 'Location Detail', esc_html( $report->location_detail ) );
        }
        $body .= self::detail_row( 'Anonymous',      $report->is_anonymous ? 'Yes' : 'No' );
        if ( ! $report->is_anonymous && $report->reporter_name ) {
            $body .= self::detail_row( 'Reporter',   esc_html( $report->reporter_name ) . ' &lt;' . esc_html( $report->reporter_email ) . '&gt;' );
        }
        $body .= self::detail_row( 'Submitted',      esc_html( $report->submitted_at ) );
        $body .= '<div style="background:#f8f9fa;border-left:4px solid #1e3a5f;padding:16px 20px;margin:20px 0;border-radius:0 8px 8px 0;">';
        $body .= '<strong style="color:#1e3a5f;">Description:</strong><br><p style="margin:8px 0 0;color:#333;">' . nl2br( esc_html( $report->description ) ) . '</p>';
        $body .= '</div>';

        $admin_url = admin_url( 'admin.php?page=HARP-reports&view=' . $report->id );
        $body .= '<a href="' . esc_url( $admin_url ) . '" style="display:inline-block;background:#1e3a5f;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;margin-top:8px;">View Report in Dashboard</a>';
        $body .= self::footer();

        self::send( $admin_email, $subject, $body );
    }

    // ── Notify reporter of status change ────────────────────────────────────
    public static function notify_reporter_status_change( object $report ): void {
        if ( $report->is_anonymous || ! $report->reporter_email ) return;

        $subject = sprintf( '[HARP] Your Report %s — Status Updated', $report->tracking_code );

        $body  = self::header();
        $body .= '<h2 style="color:#1e3a5f;margin-bottom:8px;">Report Status Updated</h2>';
        $body .= '<p style="color:#555;margin-bottom:24px;">The status of your report has been updated by our response team.</p>';
        $body .= self::detail_row( 'Tracking Code', '<strong>' . esc_html( $report->tracking_code ) . '</strong>' );
        $body .= self::detail_row( 'Category',      esc_html( $report->category ) );
        $body .= self::detail_row( 'New Status',    self::status_badge( $report->status ) );
        if ( $report->admin_note ) {
            $body .= '<div style="background:#f0f7ff;border-left:4px solid #3b82f6;padding:16px 20px;margin:20px 0;border-radius:0 8px 8px 0;">';
            $body .= '<strong style="color:#1e3a5f;">Admin Note:</strong><br><p style="margin:8px 0 0;color:#333;">' . nl2br( esc_html( $report->admin_note ) ) . '</p>';
            $body .= '</div>';
        }
        $track_url = get_permalink( get_page_by_path( 'HARP-track-report' ) );
        $body .= '<p style="color:#555;">You can track your report at any time using your tracking code: <strong>' . esc_html( $report->tracking_code ) . '</strong></p>';
        if ( $track_url ) {
            $body .= '<a href="' . esc_url( $track_url ) . '" style="display:inline-block;background:#1e3a5f;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;margin-top:8px;">Track Your Report</a>';
        }
        $body .= self::footer();

        self::send( $report->reporter_email, $subject, $body );
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    private static function send( string $to, string $subject, string $body ): bool {
        $from_name  = get_option( 'HARP_from_name',  get_option( 'blogname' ) );
        $from_email = get_option( 'HARP_from_email', get_option( 'admin_email' ) );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];
        return wp_mail( $to, $subject, $body, $headers );
    }

    private static function header(): string {
        return '
        <!DOCTYPE html>
        <html><head><meta charset="UTF-8"></head>
        <body style="margin:0;padding:0;font-family:\'Segoe UI\',Arial,sans-serif;background:#eef2f7;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:40px 0;">
          <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
              <tr><td style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);padding:32px 40px;">
                <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.5px;">HIT Anonymous Reporting Portal (HARP)</h1>
                <p style="margin:4px 0 0;color:rgba(255,255,255,0.7);font-size:13px;">Confidential Reporting System</p>
              </td></tr>
              <tr><td style="padding:36px 40px;">';
    }

    private static function footer(): string {
        return '
            </td></tr>
            <tr><td style="background:#f8f9fa;padding:20px 40px;border-top:1px solid #e5e7eb;">
              <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">
                This is an automated message from the HIT Anonymous Reporting Portal.<br>
                Please do not reply directly to this email.
              </p>
            </td></tr>
          </table>
          </td></tr>
        </table>
        </body></html>';
    }

    private static function detail_row( string $label, string $value ): string {
        return '
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
          <tr>
            <td width="160" style="color:#6b7280;font-size:13px;font-weight:600;padding:8px 0;vertical-align:top;">' . esc_html( $label ) . '</td>
            <td style="color:#111827;font-size:14px;padding:8px 0;vertical-align:top;">' . $value . '</td>
          </tr>
        </table>';
    }

    private static function urgency_badge( string $urgency ): string {
        $colors = [
            'Emergency' => '#dc2626',
            'High'      => '#ea580c',
            'Medium'    => '#d97706',
            'Low'       => '#16a34a',
        ];
        $color = $colors[ $urgency ] ?? '#6b7280';
        return '<span style="display:inline-block;background:' . $color . ';color:#fff;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;">' . esc_html( $urgency ) . '</span>';
    }

    private static function status_badge( string $status ): string {
        $colors = [
            'Pending'     => '#d97706',
            'In Progress' => '#2563eb',
            'Resolved'    => '#16a34a',
            'Closed'      => '#6b7280',
        ];
        $color = $colors[ $status ] ?? '#6b7280';
        return '<span style="display:inline-block;background:' . $color . ';color:#fff;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;">' . esc_html( $status ) . '</span>';
    }
}
