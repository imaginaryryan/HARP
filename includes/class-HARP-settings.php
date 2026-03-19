<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HARP_Settings {

    public static function init(): void {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_settings(): void {
        register_setting( 'HARP_settings_group', 'HARP_admin_email',   [ 'sanitize_callback' => 'sanitize_email' ] );
        register_setting( 'HARP_settings_group', 'HARP_from_name',     [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'HARP_settings_group', 'HARP_from_email',    [ 'sanitize_callback' => 'sanitize_email' ] );
        register_setting( 'HARP_settings_group', 'HARP_max_file_size', [ 'sanitize_callback' => 'intval' ] );
    }

    public static function render_settings_page(): void {
        if ( isset( $_POST['HARP_settings_nonce'] ) && wp_verify_nonce( $_POST['HARP_settings_nonce'], 'HARP_save_settings' ) ) {
            update_option( 'HARP_admin_email',   sanitize_email( $_POST['HARP_admin_email'] ?? '' ) );
            update_option( 'HARP_from_name',     sanitize_text_field( $_POST['HARP_from_name'] ?? '' ) );
            update_option( 'HARP_from_email',    sanitize_email( $_POST['HARP_from_email'] ?? '' ) );
            update_option( 'HARP_max_file_size', intval( $_POST['HARP_max_file_size'] ?? 10 ) );
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
        }

        $admin_email   = get_option( 'HARP_admin_email', get_option( 'admin_email' ) );
        $from_name     = get_option( 'HARP_from_name', get_option( 'blogname' ) );
        $from_email    = get_option( 'HARP_from_email', get_option( 'admin_email' ) );
        $max_file_size = get_option( 'HARP_max_file_size', 10 );

        // Get shortcode pages
        $submit_page = get_page_by_path( 'HARP-submit-report' );
        $track_page  = get_page_by_path( 'HARP-track-report' );
        ?>
        <div class="HARP-admin-wrap">
          <div class="HARP-admin-header">
            <h1 class="HARP-admin-title"><span class="HARP-admin-title-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg></span> Plugin Settings</h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=HARP-reports' ) ); ?>" class="HARP-admin-btn HARP-admin-btn-outline">← Back to Reports</a>
          </div>

          <div class="HARP-settings-grid">
            <!-- Settings Form -->
            <div class="HARP-admin-col-main">
              <div class="HARP-admin-card">
                <h3 class="HARP-admin-card-title">Email Configuration</h3>
                <form method="post">
                  <?php wp_nonce_field( 'HARP_save_settings', 'HARP_settings_nonce' ); ?>
                  <div class="HARP-field-group">
                    <label class="HARP-admin-label" for="HARP_admin_email">Admin Notification Email</label>
                    <p class="HARP-admin-hint">New reports will be sent to this address.</p>
                    <input type="email" name="HARP_admin_email" id="HARP_admin_email" class="HARP-admin-input" value="<?php echo esc_attr( $admin_email ); ?>">
                  </div>
                  <div class="HARP-field-group">
                    <label class="HARP-admin-label" for="HARP_from_name">From Name</label>
                    <input type="text" name="HARP_from_name" id="HARP_from_name" class="HARP-admin-input" value="<?php echo esc_attr( $from_name ); ?>">
                  </div>
                  <div class="HARP-field-group">
                    <label class="HARP-admin-label" for="HARP_from_email">From Email</label>
                    <input type="email" name="HARP_from_email" id="HARP_from_email" class="HARP-admin-input" value="<?php echo esc_attr( $from_email ); ?>">
                  </div>
                  <div class="HARP-field-group">
                    <label class="HARP-admin-label" for="HARP_max_file_size">Max Evidence File Size (MB)</label>
                    <input type="number" name="HARP_max_file_size" id="HARP_max_file_size" class="HARP-admin-input" value="<?php echo esc_attr( $max_file_size ); ?>" min="1" max="50">
                  </div>
                  <button type="submit" class="HARP-admin-btn HARP-admin-btn-primary">Save Settings</button>
                </form>
              </div>
            </div>

            <!-- Shortcodes & Info -->
            <div class="HARP-admin-col-aside">
              <div class="HARP-admin-card">
                <h3 class="HARP-admin-card-title">Page Shortcodes</h3>
                <div class="HARP-shortcode-item">
                  <label>Report Submission Form</label>
                  <code>[HARP_report_form]</code>
                  <?php if ( $submit_page ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $submit_page ) ); ?>" target="_blank" class="HARP-admin-btn HARP-admin-btn-sm HARP-admin-btn-outline">View Page →</a>
                  <?php endif; ?>
                </div>
                <div class="HARP-shortcode-item">
                  <label>Report Tracking Page</label>
                  <code>[HARP_track_report]</code>
                  <?php if ( $track_page ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $track_page ) ); ?>" target="_blank" class="HARP-admin-btn HARP-admin-btn-sm HARP-admin-btn-outline">View Page →</a>
                  <?php endif; ?>
                </div>
              </div>

              <div class="HARP-admin-card">
                <h3 class="HARP-admin-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Plugin Info</h3>
                <div class="HARP-quick-info">
                  <div class="HARP-qi-row"><span>Version</span><span><?php echo HARP_VERSION; ?></span></div>
                  <div class="HARP-qi-row"><span>PHP</span><span><?php echo PHP_VERSION; ?></span></div>
                  <?php $diag = HARP_DB::get_diagnostics(); ?>
                  <div class="HARP-qi-row">
                    <span>DB Table</span>
                    <span style="color:<?php echo $diag['table_exists'] ? '#16a34a' : '#dc2626'; ?>;font-weight:600;">
                      <?php echo $diag['table_exists'] ? 'OK' : 'MISSING'; ?>
                    </span>
                  </div>
                  <div class="HARP-qi-row"><span>Total Reports</span><span><?php echo HARP_DB::count_reports(); ?></span></div>
                  <div class="HARP-qi-row"><span>Pending</span><span><?php echo HARP_DB::count_reports( 'Pending' ); ?></span></div>
                  <div class="HARP-qi-row"><span>Resolved</span><span><?php echo HARP_DB::count_reports( 'Resolved' ); ?></span></div>
                </div>
                <?php if ( ! $diag['table_exists'] ) : ?>
                <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px;margin-top:12px;">
                  <strong style="color:#dc2626;">DB table missing!</strong>
                  <p style="margin:6px 0 8px;font-size:13px;color:#7f1d1d;">The reports table does not exist. Click below to create it:</p>
                  <button type="button" id="HARP-create-tables" class="HARP-admin-btn HARP-admin-btn-primary" style="font-size:13px;">
                    Create Database Tables
                  </button>
                  <div id="HARP-create-tables-msg" style="margin-top:8px;font-size:13px;"></div>
                </div>
                <script>
                document.getElementById('HARP-create-tables').addEventListener('click', function() {
                  var btn = this;
                  btn.disabled = true; btn.textContent = 'Creating…';
                  fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=HARP_create_tables&nonce=<?php echo wp_create_nonce("HARP_nonce"); ?>'
                  }).then(r => r.json()).then(d => {
                    document.getElementById('HARP-create-tables-msg').innerHTML =
                      '<span style="color:' + (d.success ? '#16a34a' : '#dc2626') + ';">' + (d.data ? d.data.message : 'Done. Please reload.') + '</span>';
                    if (d.success) setTimeout(() => location.reload(), 1200);
                  });
                });
                </script>
                <?php endif; ?>
                <?php if ( $diag['last_error'] ) : ?>
                <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px;margin-top:12px;">
                  <strong style="color:#dc2626;">Last DB error:</strong>
                  <code style="display:block;margin-top:6px;font-size:12px;word-break:break-all;"><?php echo esc_html( $diag['last_error'] ); ?></code>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php
    }
}
