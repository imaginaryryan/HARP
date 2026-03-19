<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class HARP_Shortcodes {

    public static function init(): void {
        add_shortcode( 'HARP_report_form',  [ __CLASS__, 'render_report_form' ] );
        add_shortcode( 'HARP_track_report', [ __CLASS__, 'render_track_report' ] );
    }

    // ── [HARP_report_form] ────────────────────────────────────────────────────
    public static function render_report_form(): string {
        $categories = [
            'Sexual Harassment',
            'Bullying',
            'Theft',
            'Vandalism',
            'Drug / Substance Abuse',
            'Other',
        ];
        $urgency_levels = [ 'Emergency', 'High', 'Medium', 'Low' ];
        $locations = [
            'Main Campus',
            'Library',
            'Canteen',
            'Sports Complex',
            'Lecture Halls',
            'Admin Block',
            'Hostels',
            'Parking Lot',
            'Laboratory',
            'Other',
        ];

        ob_start(); ?>
        <div class="HARP-wrapper" id="HARP-report-wrapper">
          <div class="HARP-card">
            <div class="HARP-card-header">
              <div class="HARP-header-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
              </div>
              <h1 class="HARP-card-title">Submit Anonymous Report</h1>
              <p class="HARP-card-subtitle">Your report is confidential. We take every submission seriously.</p>
            </div>

            <div class="HARP-card-body">
              <!-- Success State -->
              <div class="HARP-success-state" id="HARP-success-state" style="display:none;">
                <div class="HARP-success-icon">
                  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                </div>
                <h2>Report Submitted Successfully!</h2>
                <p>Your report has been received and our team has been notified.</p>
                <div class="HARP-tracking-code-display">
                  <span class="HARP-tracking-label">Your Tracking Code</span>
                  <span class="HARP-tracking-value" id="HARP-new-tracking-code"></span>
                  <button type="button" class="HARP-copy-btn" id="HARP-copy-code" title="Copy to clipboard">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                    </svg>
                  </button>
                </div>
                <p class="HARP-tracking-note"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;color:#d97706;"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> <strong>Save this code.</strong> You'll need it to track the status of your report.</p>
                <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'HARP-track-report' ) ) ); ?>" class="HARP-btn HARP-btn-outline">Track My Report</a>
              </div>

              <!-- Form -->
              <div id="HARP-form-container">
                <div class="HARP-alert HARP-alert-error" id="HARP-form-error" style="display:none;"></div>

                <!-- Report Category -->
                <div class="HARP-field-group">
                  <label class="HARP-label" for="HARP-category">Report Category <span class="HARP-required">*</span></label>
                  <div class="HARP-select-wrapper">
                    <select id="HARP-category" name="category" class="HARP-select" required>
                      <option value="">Select an option</option>
                      <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat ); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <svg class="HARP-select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                  </div>
                </div>

                <!-- Urgency Level -->
                <div class="HARP-field-group">
                  <label class="HARP-label" for="HARP-urgency">Urgency Level <span class="HARP-required">*</span></label>
                  <div class="HARP-select-wrapper">
                    <select id="HARP-urgency" name="urgency" class="HARP-select" required>
                      <?php foreach ( $urgency_levels as $level ) : ?>
                        <option value="<?php echo esc_attr( $level ); ?>" <?php selected( $level, 'Emergency' ); ?>><?php echo esc_html( $level ); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <svg class="HARP-select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                  </div>
                </div>

                <!-- Location -->
                <div class="HARP-field-group">
                  <label class="HARP-label">Location</label>
                  <label class="HARP-sublabel" for="HARP-location">Where did the incident occur? <span class="HARP-required">*</span></label>
                  <div class="HARP-select-wrapper">
                    <select id="HARP-location" name="location" class="HARP-select" required>
                      <option value="">Select location</option>
                      <?php foreach ( $locations as $loc ) : ?>
                        <option value="<?php echo esc_attr( $loc ); ?>"><?php echo esc_html( $loc ); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <svg class="HARP-select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                  </div>
                </div>

                <!-- Specific Location Details -->
                <div class="HARP-field-group">
                  <label class="HARP-label" for="HARP-location-detail">Specific Location Details <span class="HARP-optional">(Optional)</span></label>
                  <p class="HARP-sublabel">Any relevant location details</p>
                  <input type="text" id="HARP-location-detail" name="location_detail" class="HARP-input" placeholder="e.g., second table to the right using the West wing of the canteen">
                </div>

                <!-- Detailed Description -->
                <div class="HARP-field-group">
                  <label class="HARP-label" for="HARP-description">Detailed Description <span class="HARP-required">*</span></label>
                  <p class="HARP-sublabel">Your detailed report helps us take appropriate action. Include any witnesses or evidence you're aware of.</p>
                  <textarea id="HARP-description" name="description" class="HARP-textarea" rows="5" placeholder="Please provide as much information as possible. You are advised to include who was involved, what happened or any information you find relevant" required></textarea>
                </div>

                <!-- Evidence Upload -->
                <div class="HARP-field-group">
                  <label class="HARP-label">Evidence <span class="HARP-optional">(Optional)</span></label>
                  <p class="HARP-sublabel">Attach a photo or video of the incident. Max 10 MB.</p>
                  <div class="HARP-file-drop" id="HARP-file-drop">
                    <input type="file" id="HARP-evidence" name="evidence" class="HARP-file-input" accept="image/jpeg,image/png,image/gif,video/mp4,video/quicktime">
                    <div class="HARP-file-drop-content" id="HARP-file-drop-content">
                      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                      <p><strong>Click to upload</strong> or drag and drop</p>
                      <p class="HARP-file-hint">JPG, PNG, GIF, MP4, MOV — max 10 MB</p>
                    </div>
                    <div class="HARP-file-preview" id="HARP-file-preview" style="display:none;"></div>
                  </div>
                </div>

                <!-- Anonymous Toggle -->
                <div class="HARP-field-group">
                  <div class="HARP-toggle-row">
                    <div class="HARP-toggle-info">
                      <span class="HARP-toggle-label">Submit Anonymously</span>
                      <span class="HARP-toggle-desc">(Recommended) Your identity will be completely protected. You'll receive a tracking ID to check the status of your report.</span>
                    </div>
                    <label class="HARP-toggle" for="HARP-anonymous">
                      <input type="checkbox" id="HARP-anonymous" name="is_anonymous" checked>
                      <span class="HARP-toggle-slider"></span>
                    </label>
                  </div>
                </div>

                <!-- Reporter Details (shown when not anonymous) -->
                <div class="HARP-reporter-fields" id="HARP-reporter-fields" style="display:none;">
                  <div class="HARP-reporter-notice">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Your identity will be recorded. You will receive email updates on your report status.
                  </div>
                  <div class="HARP-field-group HARP-field-group--inline">
                    <div class="HARP-field-half">
                      <label class="HARP-label" for="HARP-reporter-name">Full Name <span class="HARP-required">*</span></label>
                      <input type="text" id="HARP-reporter-name" name="reporter_name" class="HARP-input" placeholder="Your full name">
                    </div>
                    <div class="HARP-field-half">
                      <label class="HARP-label" for="HARP-reporter-email">Email Address <span class="HARP-required">*</span></label>
                      <input type="email" id="HARP-reporter-email" name="reporter_email" class="HARP-input" placeholder="your@email.com">
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <button type="button" id="HARP-submit-btn" class="HARP-btn HARP-btn-primary HARP-btn-full">
                  <span class="HARP-btn-text">Submit Report</span>
                  <span class="HARP-btn-spinner" style="display:none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="HARP-spin"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>
                    Submitting…
                  </span>
                </button>
              </div><!-- /#HARP-form-container -->
            </div><!-- /.HARP-card-body -->
          </div><!-- /.HARP-card -->
        </div><!-- /.HARP-wrapper -->
        <?php
        return ob_get_clean();
    }

    // ── [HARP_track_report] ───────────────────────────────────────────────────
    public static function render_track_report(): string {
        ob_start(); ?>
        <div class="HARP-wrapper HARP-track-wrapper" id="HARP-track-wrapper">
          <div class="HARP-track-hero">
            <div class="HARP-track-icon-wrap">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
            </div>
            <h1 class="HARP-track-title">Track Your Report</h1>
            <p class="HARP-track-subtitle">Enter your report ID to check the status of your submission and see any updates from our response team.</p>
            <div class="HARP-track-input-row">
              <input type="text" id="HARP-track-code" class="HARP-input HARP-track-input" placeholder="Enter Report ID (e.g., SR-2026-XXXXXX)" maxlength="20">
              <button type="button" id="HARP-track-btn" class="HARP-btn HARP-btn-primary">
                <span class="HARP-btn-text">Track Status</span>
                <span class="HARP-btn-spinner" style="display:none;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="HARP-spin"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/></svg>
                </span>
              </button>
            </div>
            <div class="HARP-alert HARP-alert-error" id="HARP-track-error" style="display:none;"></div>
          </div>

          <!-- Results -->
          <div class="HARP-track-results" id="HARP-track-results" style="display:none;">
            <div class="HARP-result-card">
              <div class="HARP-result-header">
                <div class="HARP-result-code-wrap">
                  <span class="HARP-result-code-label">Report ID</span>
                  <span class="HARP-result-code" id="HARP-result-code"></span>
                </div>
                <span class="HARP-status-badge" id="HARP-result-status"></span>
              </div>

              <div class="HARP-result-grid">
                <div class="HARP-result-item">
                  <span class="HARP-result-item-label">Category</span>
                  <span class="HARP-result-item-value" id="HARP-result-category"></span>
                </div>
                <div class="HARP-result-item">
                  <span class="HARP-result-item-label">Urgency</span>
                  <span class="HARP-result-item-value" id="HARP-result-urgency"></span>
                </div>
                <div class="HARP-result-item">
                  <span class="HARP-result-item-label">Location</span>
                  <span class="HARP-result-item-value" id="HARP-result-location"></span>
                </div>
                <div class="HARP-result-item">
                  <span class="HARP-result-item-label">Submitted</span>
                  <span class="HARP-result-item-value" id="HARP-result-submitted"></span>
                </div>
                <div class="HARP-result-item">
                  <span class="HARP-result-item-label">Last Updated</span>
                  <span class="HARP-result-item-value" id="HARP-result-updated"></span>
                </div>
              </div>

              <div class="HARP-admin-note-block" id="HARP-admin-note-block" style="display:none;">
                <h4><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Response Note</h4>
                <p id="HARP-admin-note-text"></p>
              </div>

              <!-- Comments -->
              <div class="HARP-comments-section" id="HARP-comments-section">
                <h4 class="HARP-comments-heading">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                  Updates from Response Team <span class="HARP-comment-count" id="HARP-comment-count"></span>
                </h4>
                <div class="HARP-comments-list" id="HARP-comments-list">
                  <p class="HARP-no-comments">No updates yet. Check back later.</p>
                </div>
              </div>

              <!-- Status Steps -->
              <div class="HARP-status-steps">
                <div class="HARP-step" data-step="Pending">
                  <div class="HARP-step-dot"></div>
                  <span>Pending</span>
                </div>
                <div class="HARP-step-line"></div>
                <div class="HARP-step" data-step="In Progress">
                  <div class="HARP-step-dot"></div>
                  <span>In Progress</span>
                </div>
                <div class="HARP-step-line"></div>
                <div class="HARP-step" data-step="Resolved">
                  <div class="HARP-step-dot"></div>
                  <span>Resolved</span>
                </div>
                <div class="HARP-step-line"></div>
                <div class="HARP-step" data-step="Closed">
                  <div class="HARP-step-dot"></div>
                  <span>Closed</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
