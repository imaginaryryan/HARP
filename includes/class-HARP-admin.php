<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HARP_Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function add_menu(): void {
        add_menu_page(
            'HARP',
            'Fault Reports',
            'administrator',
            'HARP-reports',
            [ __CLASS__, 'render_reports_page' ],
            'dashicons-shield',
            30
        );
        add_submenu_page(
            'HARP-reports',
            'All Reports',
            'All Reports',
            'administrator',
            'HARP-reports',
            [ __CLASS__, 'render_reports_page' ]
        );
        add_submenu_page(
            'HARP-reports',
            'Settings',
            'Settings',
            'administrator',
            'HARP-settings',
            [ 'HARP_Settings', 'render_settings_page' ]
        );
    }

    public static function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'HARP' ) === false ) return;
        wp_enqueue_style(  'HARP-admin-styles',  HARP_PLUGIN_URL . 'assets/css/HARP-admin.css',  [], HARP_VERSION );
        wp_enqueue_script( 'HARP-admin-scripts', HARP_PLUGIN_URL . 'assets/js/HARP-admin.js', [ 'jquery' ], HARP_VERSION, true );
        wp_localize_script( 'HARP-admin-scripts', 'HARP_Admin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'HARP_nonce' ),
        ] );
    }

    public static function render_reports_page(): void {
        // Handle single report view
        if ( isset( $_GET['view'] ) ) {
            self::render_single_report( intval( $_GET['view'] ) );
            return;
        }

        // Stats
        $total       = HARP_DB::count_reports();
        $pending     = HARP_DB::count_reports( 'Pending' );
        $in_progress = HARP_DB::count_reports( 'In Progress' );
        $resolved    = HARP_DB::count_reports( 'Resolved' );

        // Filter
        $filter_status = sanitize_text_field( $_GET['status'] ?? '' );
        $search        = sanitize_text_field( $_GET['search'] ?? '' );
        $reports       = HARP_DB::get_all_reports( [
            'status' => $filter_status,
            'search' => $search,
            'limit'  => 50,
        ] );

        ?>
        <div class="HARP-admin-wrap">
          <div class="HARP-admin-header">
            <div>
              <h1 class="HARP-admin-title">
                <span class="HARP-admin-title-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                HARP — Reports Dashboard
              </h1>
              <p class="HARP-admin-subtitle">Manage and respond to submitted reports</p>
            </div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=HARP-settings' ) ); ?>" class="HARP-admin-btn HARP-admin-btn-outline"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg> Settings</a>
          </div>

          <!-- Stats Row -->
          <div class="HARP-stats-grid">
            <?php
            $clip_url    = esc_url( HARP_PLUGIN_URL . 'public/clipboard-solid-full.svg' );
            $clock_url   = esc_url( HARP_PLUGIN_URL . 'public/clock-solid-full.svg' );
            $rotate_url  = esc_url( HARP_PLUGIN_URL . 'public/arrows-rotate-solid-full.svg' );
            $check_url   = esc_url( HARP_PLUGIN_URL . 'public/check-solid-full.svg' );
            $stats = [
                [ 'label' => 'Total Reports', 'value' => $total,       'color' => '#1e3a5f', 'icon_url' => $clip_url ],
                [ 'label' => 'Pending',       'value' => $pending,     'color' => '#d97706', 'icon_url' => $clock_url ],
                [ 'label' => 'In Progress',   'value' => $in_progress, 'color' => '#2563eb', 'icon_url' => $rotate_url ],
                [ 'label' => 'Resolved',      'value' => $resolved,    'color' => '#16a34a', 'icon_url' => $check_url ],
            ];
            foreach ( $stats as $s ) : ?>
            <div class="HARP-stat-card" style="--stat-color:<?php echo $s['color']; ?>">
              <div class="HARP-stat-icon"><img src="<?php echo $s['icon_url']; ?>" width="24" height="24" alt="" style="filter:brightness(0) invert(1);opacity:0.9;"></div>
              <div class="HARP-stat-value"><?php echo $s['value']; ?></div>
              <div class="HARP-stat-label"><?php echo esc_html( $s['label'] ); ?></div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Filters -->
          <div class="HARP-admin-filters">
            <input type="text" id="HARP-search" class="HARP-admin-search" placeholder="Search by code, category, location…" value="<?php echo esc_attr( $search ); ?>">
            <select id="HARP-filter-status" class="HARP-admin-select">
              <option value="">All Statuses</option>
              <?php foreach ( [ 'Pending', 'In Progress', 'Resolved', 'Closed' ] as $s ) : ?>
                <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $filter_status, $s ); ?>><?php echo esc_html( $s ); ?></option>
              <?php endforeach; ?>
            </select>
            <button id="HARP-apply-filter" class="HARP-admin-btn HARP-admin-btn-primary">Filter</button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=HARP-reports' ) ); ?>" class="HARP-admin-btn HARP-admin-btn-outline">Reset</a>
          </div>

          <!-- Reports Table -->
          <div class="HARP-table-wrap">
            <?php if ( empty( $reports ) ) : ?>
              <div class="HARP-admin-empty">
                <span class="HARP-empty-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 13V6a2 2 0 00-2-2H4a2 2 0 00-2 2v12c0 1.1.9 2 2 2h8"/><path d="M22 7l-10 7L2 7"/><path d="M16 19h6m-3-3v6"/></svg></span>
                <h3>No reports found</h3>
                <p>Reports submitted via the front-end form will appear here.</p>
              </div>
            <?php else : ?>
            <table class="HARP-admin-table">
              <thead>
                <tr>
                  <th>Tracking Code</th>
                  <th>Category</th>
                  <th>Urgency</th>
                  <th>Location</th>
                  <th>Anonymous</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ( $reports as $r ) : ?>
                <tr>
                  <td><code class="HARP-code"><?php echo esc_html( $r->tracking_code ); ?></code></td>
                  <td><?php echo esc_html( $r->category ); ?></td>
                  <td><?php echo self::urgency_badge( $r->urgency ); ?></td>
                  <td><?php echo esc_html( $r->location ); ?></td>
                  <td><?php echo $r->is_anonymous ? '<span class="HARP-badge HARP-badge-gray">Yes</span>' : '<span class="HARP-badge HARP-badge-blue">No — ' . esc_html( $r->reporter_name ) . '</span>'; ?></td>
                  <td><?php echo self::status_badge( $r->status ); ?></td>
                  <td><?php echo esc_html( date( 'M j, Y', strtotime( $r->submitted_at ) ) ); ?></td>
                  <td>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=HARP-reports&view=' . $r->id ) ); ?>" class="HARP-admin-btn HARP-admin-btn-sm HARP-admin-btn-primary">View</a>
                    <button type="button" class="HARP-admin-btn HARP-admin-btn-sm HARP-admin-btn-danger HARP-delete-report" data-id="<?php echo esc_attr( $r->id ); ?>">Delete</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>
        <?php
    }

    private static function render_single_report( int $id ): void {
        $report = HARP_DB::get_report_by_id( $id );
        if ( ! $report ) {
            echo '<div class="notice notice-error"><p>Report not found.</p></div>';
            return;
        }
        $comments = HARP_DB::get_comments( $id );
        ?>
        <div class="HARP-admin-wrap">
          <div class="HARP-admin-header">
            <div>
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=HARP-reports' ) ); ?>" class="HARP-back-link">← Back to Reports</a>
              <h1 class="HARP-admin-title">Report: <code><?php echo esc_html( $report->tracking_code ); ?></code></h1>
            </div>
            <?php echo self::status_badge( $report->status ); ?>
          </div>

          <div class="HARP-admin-two-col">
            <!-- Left: Report Details -->
            <div class="HARP-admin-col-main">
              <div class="HARP-admin-card">
                <h3 class="HARP-admin-card-title">Report Details</h3>
                <table class="HARP-detail-table">
                  <tr><td>Tracking Code</td><td><code><?php echo esc_html( $report->tracking_code ); ?></code></td></tr>
                  <tr><td>Category</td><td><?php echo esc_html( $report->category ); ?></td></tr>
                  <tr><td>Urgency</td><td><?php echo self::urgency_badge( $report->urgency ); ?></td></tr>
                  <tr><td>Location</td><td><?php echo esc_html( $report->location ); ?></td></tr>
                  <?php if ( $report->location_detail ) : ?>
                  <tr><td>Location Detail</td><td><?php echo esc_html( $report->location_detail ); ?></td></tr>
                  <?php endif; ?>
                  <tr><td>Submitted</td><td><?php echo esc_html( date( 'F j, Y \a\t g:i A', strtotime( $report->submitted_at ) ) ); ?></td></tr>
                  <tr><td>Anonymous</td><td><?php echo $report->is_anonymous ? '<span class="HARP-badge HARP-badge-green">Yes</span>' : '<span class="HARP-badge HARP-badge-red">No</span>'; ?></td></tr>
                  <?php if ( ! $report->is_anonymous ) : ?>
                  <tr><td>Reporter Name</td><td><?php echo esc_html( $report->reporter_name ); ?></td></tr>
                  <tr><td>Reporter Email</td><td><?php echo esc_html( $report->reporter_email ); ?></td></tr>
                  <?php endif; ?>
                </table>
              </div>

              <div class="HARP-admin-card">
                <h3 class="HARP-admin-card-title">Description</h3>
                <p class="HARP-description-text"><?php echo nl2br( esc_html( $report->description ) ); ?></p>
              </div>

              <?php if ( $report->evidence_path ) : ?>
              <div class="HARP-admin-card">
                <h3 class="HARP-admin-card-title">Evidence</h3>
                <?php if ( $report->evidence_type === 'image' ) : ?>
                  <a href="<?php echo esc_url( $report->evidence_path ); ?>" target="_blank">
                    <img src="<?php echo esc_url( $report->evidence_path ); ?>" class="HARP-evidence-preview" alt="Evidence image">
                  </a>
                <?php else : ?>
                  <video controls class="HARP-evidence-preview">
                    <source src="<?php echo esc_url( $report->evidence_path ); ?>">
                    Your browser does not support video.
                  </video>
                <?php endif; ?>
                <a href="<?php echo esc_url( $report->evidence_path ); ?>" target="_blank" class="HARP-admin-btn HARP-admin-btn-outline" style="margin-top:12px;display:inline-block;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download Evidence</a>
              </div>
              <?php endif; ?>

              <!-- Comments -->
              <div class="HARP-admin-card">
                <h3 class="HARP-admin-card-title">Admin Comments / Updates</h3>
                <div class="HARP-admin-comments" id="HARP-admin-comments-list">
                  <?php if ( empty( $comments ) ) : ?>
                    <p class="HARP-no-comments-admin">No comments yet. Add an update below.</p>
                  <?php else : ?>
                    <?php foreach ( $comments as $c ) : ?>
                    <div class="HARP-admin-comment">
                      <div class="HARP-admin-comment-meta">
                        <span class="HARP-admin-comment-author"><?php echo esc_html( $c->author ); ?></span>
                        <span class="HARP-admin-comment-date"><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $c->created_at ) ) ); ?></span>
                      </div>
                      <p><?php echo nl2br( esc_html( $c->comment ) ); ?></p>
                    </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
                <div class="HARP-add-comment-form" id="HARP-add-comment-form">
                  <textarea id="HARP-comment-text" rows="3" placeholder="Write an update or response…" class="HARP-admin-textarea"></textarea>
                  <button type="button" id="HARP-post-comment" class="HARP-admin-btn HARP-admin-btn-primary" data-id="<?php echo esc_attr( $id ); ?>">Post Comment</button>
                  <div class="HARP-admin-msg" id="HARP-comment-msg"></div>
                </div>
              </div>
            </div>

            <!-- Right: Status Update -->
            <div class="HARP-admin-col-aside">
              <div class="HARP-admin-card HARP-status-card">
                <h3 class="HARP-admin-card-title">Update Status</h3>
                <div class="HARP-field-group">
                  <label class="HARP-admin-label" for="HARP-admin-status">Status</label>
                  <select id="HARP-admin-status" class="HARP-admin-select">
                    <?php foreach ( [ 'Pending', 'In Progress', 'Resolved', 'Closed' ] as $s ) : ?>
                      <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $report->status, $s ); ?>><?php echo esc_html( $s ); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="HARP-field-group">
                  <label class="HARP-admin-label" for="HARP-admin-note">Admin Note (Optional)</label>
                  <p class="HARP-admin-hint">This note will be shown to the reporter on the tracking page<?php echo $report->is_anonymous ? '' : ' and sent via email'; ?>.</p>
                  <textarea id="HARP-admin-note" rows="4" class="HARP-admin-textarea" placeholder="Describe the action taken or current progress…"><?php echo esc_textarea( $report->admin_note ?? '' ); ?></textarea>
                </div>
                <button type="button" id="HARP-update-status" class="HARP-admin-btn HARP-admin-btn-primary HARP-btn-full" data-id="<?php echo esc_attr( $id ); ?>">
                  Update Status
                  <?php if ( ! $report->is_anonymous && $report->reporter_email ) : ?>
                    <span class="HARP-email-note"> + Send Email</span>
                  <?php endif; ?>
                </button>
                <div class="HARP-admin-msg" id="HARP-status-msg"></div>
              </div>

              <!-- Report Meta -->
              <div class="HARP-admin-card">
                <h3 class="HARP-admin-card-title">Quick Info</h3>
                <div class="HARP-quick-info">
                  <div class="HARP-qi-row"><span>ID</span><span>#<?php echo esc_html( $report->id ); ?></span></div>
                  <div class="HARP-qi-row"><span>Status</span><?php echo self::status_badge( $report->status ); ?></div>
                  <div class="HARP-qi-row"><span>Evidence</span><span><?php echo $report->evidence_path ? '<span class="HARP-badge HARP-badge-green">Attached</span>' : '<span style="color:#9ca3af;">—</span>'; ?></span></div>
                  <div class="HARP-qi-row"><span>Comments</span><span><?php echo count( $comments ); ?></span></div>
                  <div class="HARP-qi-row"><span>Last Updated</span><span><?php echo esc_html( date( 'M j, Y', strtotime( $report->updated_at ) ) ); ?></span></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php
    }

    public static function urgency_badge( string $u ): string {
        $map = [ 'Emergency' => 'red', 'High' => 'orange', 'Medium' => 'yellow', 'Low' => 'green' ];
        $cls = $map[ $u ] ?? 'gray';
        return '<span class="HARP-badge HARP-badge-' . $cls . '">' . esc_html( $u ) . '</span>';
    }

    public static function status_badge( string $s ): string {
        $map = [ 'Pending' => 'yellow', 'In Progress' => 'blue', 'Resolved' => 'green', 'Closed' => 'gray' ];
        $cls = $map[ $s ] ?? 'gray';
        return '<span class="HARP-badge HARP-badge-' . $cls . '">' . esc_html( $s ) . '</span>';
    }
}
