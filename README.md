# University Fault Reporter — WordPress Plugin

**Version:** 1.0.0  
**Requires WordPress:** 5.8+  
**Requires PHP:** 8.0+  
**Tested up to:** WordPress 6.5

---

## Overview

A complete anonymous fault and crime reporting system for universities. Users can submit reports with optional evidence uploads, receive a unique tracking code, and check report status at any time. Administrators manage reports from a full WordPress dashboard.

---

## Features

| Feature | Details |
|---|---|
| Anonymous submission | Toggle to submit with or without identity |
| Report categories | Sexual Harassment, Bullying, Theft, Vandalism, Drug/Substance Abuse, Other |
| Urgency levels | Emergency, High, Medium, Low |
| Campus locations | 10 predefined + Other |
| Evidence upload | Images (JPG, PNG, GIF) and videos (MP4, MOV), max 10 MB |
| Unique tracking code | Auto-generated `SR-YYYY-XXXXXX` format |
| Success notification | On-screen confirmation with copyable code |
| Email to admin | Styled HTML email on every new submission |
| Status tracking | Pending → In Progress → Resolved → Closed |
| Admin comments | Add public updates visible on tracking page |
| Reporter email notification | Non-anonymous reporters emailed on status change |
| Admin dashboard | Stats, table, filters, search, single report view |
| Settings page | Configure admin email, from name/email, max file size |

---

## Installation

### Step 1 — Upload the plugin

**Option A — via WordPress Admin (recommended)**
1. Log in to your WordPress admin panel
2. Go to **Plugins → Add New**
3. Click **Upload Plugin**
4. Choose `university-fault-reporter.zip` and click **Install Now**
5. Click **Activate Plugin**

**Option B — via FTP**
1. Extract `university-fault-reporter.zip`
2. Upload the `university-fault-reporter/` folder to `/wp-content/plugins/`
3. Go to **Plugins** in wp-admin and click **Activate**

### Step 2 — What happens on activation

The plugin automatically:
- Creates two database tables: `wp_HARP_reports` and `wp_HARP_comments`
- Creates two WordPress pages:
  - **Submit Anonymous Report** → slug: `HARP-submit-report`
  - **Track Your Report** → slug: `HARP-track-report`

### Step 3 — Add pages to your navigation menu (optional)

1. Go to **Appearance → Menus**
2. Find **Submit Anonymous Report** and **Track Your Report** under Pages
3. Add them to your desired menu

### Step 4 — Configure settings

1. Go to **Fault Reports → Settings** in your WordPress admin sidebar
2. Set the **Admin Notification Email** (where new reports are sent)
3. Optionally customise the **From Name** and **From Email** for outgoing emails
4. Click **Save Settings**

---

## 🔧 Using Shortcodes Manually

If you want to embed the forms on existing pages, use these shortcodes:

```
[HARP_report_form]     — Displays the full report submission form
[HARP_track_report]    — Displays the report tracking page
```

Simply paste either shortcode into any page or post content area.

---

## Testing in Development

### Prerequisites
- Local WordPress environment (e.g. LocalWP, DevKinsta, XAMPP, MAMP)
- PHP 8.0+, WordPress 5.8+

### Test 1 — Submit a Report

1. Navigate to `/HARP-submit-report/` on your site
2. Fill in:
   - Category: **Theft**
   - Urgency: **High**
   - Location: **Library**
   - Description: "Test submission — a laptop was reported missing."
3. Upload a test image as evidence (optional)
4. Leave the **Submit Anonymously** toggle ON
5. Click **Submit Report**
6. You should see a green success screen with a tracking code like `SR-2026-ABCD12`
7. Copy the tracking code

### Test 2 — Track a Report

1. Navigate to `/HARP-track-report/`
2. Paste your tracking code into the input field
3. Click **Track Status**
4. You should see your report details, current status (Pending), and the progress stepper

### Test 3 — Admin Dashboard

1. Log in to `/wp-admin/`
2. Click **Fault Reports** in the left sidebar
3. You should see the report you submitted in the table
4. Click **View** next to it
5. Change the status to **In Progress** and add an admin note
6. Click **Update Status**
7. Refresh the tracking page — status should now show **In Progress**

### Test 4 — Admin Comments

1. On the single report admin page
2. Scroll to **Admin Comments / Updates**
3. Type a comment: "We are investigating this incident."
4. Click **Post Comment**
5. Refresh the tracking page — comment should appear under "Updates from Response Team"

### Test 5 — Non-Anonymous Submission + Email

1. Submit a new report with the **Submit Anonymously** toggle OFF
2. Enter your name and a valid email address
3. Submit the report
4. In admin, update the status of this report
5. The reporter's email address should receive a status update notification

### Test 6 — Evidence Upload

1. Submit a report and attach a JPG or MP4 file
2. In the admin single report view, scroll to **Evidence**
3. The image/video should render inline with a download button

### Test 7 — Delete Report

1. In the admin reports table, click **Delete** next to any report
2. Confirm the prompt
3. The row should disappear and evidence file should be removed from uploads

---

##  File Structure

```
university-fault-reporter/
├── university-fault-reporter.php   ← Main plugin file (entry point)
├── uninstall.php                   ← Cleanup on plugin deletion
├── includes/
│   ├── class-HARP-db.php            ← Database operations (CRUD)
│   ├── class-HARP-mailer.php        ← HTML email notifications
│   ├── class-HARP-ajax.php          ← AJAX endpoints (submit, track, admin)
│   ├── class-HARP-shortcodes.php    ← [HARP_report_form] and [HARP_track_report]
│   ├── class-HARP-admin.php         ← WordPress admin dashboard UI
│   └── class-HARP-settings.php      ← Admin settings page
├── assets/
│   ├── css/
│   │   ├── HARP-frontend.css        ← Front-end form and tracking page styles
│   │   └── HARP-admin.css           ← Admin dashboard styles
│   └── js/
│       ├── HARP-frontend.js         ← Form submission, tracking, file upload
│       └── HARP-admin.js            ← Admin AJAX (status update, comments, delete)
└── README.md                       ← This file
```

---

##  Security

- All AJAX actions are protected with WordPress nonces
- All user input is sanitised before database insertion (`sanitize_text_field`, `sanitize_textarea_field`, `sanitize_email`, `intval`)
- All output is escaped before rendering (`esc_html`, `esc_attr`, `esc_url`, `esc_textarea`)
- Evidence uploads are MIME-type validated (not just extension)
- Admin-only actions verify `administrator` capability
- Evidence files stored in a private subfolder (`/wp-content/uploads/HARP-evidence/`)

---

## Troubleshooting

| Issue | Solution |
|---|---|
| Pages not created on activation | Go to Settings → Permalinks and click Save to flush rewrite rules |
| Emails not being received | Install [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) and configure your mail provider |
| File upload fails | Check `upload_max_filesize` and `post_max_size` in your PHP configuration — set both to at least 12M |
| Tracking code not found | Ensure the code is typed exactly as shown (format: `SR-2026-XXXXXX`). It's case-insensitive. |
| Admin menu not visible | Ensure you are logged in as an **Administrator** role |

---

##  Uninstalling

1. Deactivate the plugin from **Plugins**
2. Click **Delete**

This will permanently remove all database tables and plugin options. **All report data will be lost.** Back up your database first if you want to preserve records.

---

##  Customisation

**Adding new categories:** Edit the `$categories` array in `includes/class-HARP-shortcodes.php` → `render_report_form()`.

**Adding new locations:** Edit the `$locations` array in the same function.

**Changing the colour scheme:** Edit the CSS custom properties (`:root` block) in `assets/css/HARP-frontend.css`.

---

*University Fault Reporter v1.0.0 — Built for WordPress.org*
# HARP

