/* University Fault Reporter — Admin JS */
(function ($) {
  'use strict';

  $(function () {

    /* ── Filter / Search ────────────────────────────────────────────────── */
    $('#HARP-apply-filter').on('click', function () {
      var status = $('#HARP-filter-status').val();
      var search = $('#HARP-search').val();
      var url    = new URL(window.location.href);
      if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
      if (search) url.searchParams.set('search', search); else url.searchParams.delete('search');
      window.location.href = url.toString();
    });

    $('#HARP-search').on('keypress', function (e) {
      if (e.which === 13) $('#HARP-apply-filter').click();
    });

    /* ── Update Status ──────────────────────────────────────────────────── */
    $('#HARP-update-status').on('click', function () {
      var $btn       = $(this);
      var reportId   = $btn.data('id');
      var status     = $('#HARP-admin-status').val();
      var adminNote  = $('#HARP-admin-note').val();
      var $msg       = $('#HARP-status-msg');

      $btn.prop('disabled', true).text('Updating…');
      $msg.attr('class', 'HARP-admin-msg').text('');

      $.ajax({
        url:  HARP_Admin.ajax_url,
        type: 'POST',
        data: {
          action:     'HARP_update_status',
          nonce:      HARP_Admin.nonce,
          report_id:  reportId,
          status:     status,
          admin_note: adminNote,
        },
        success: function (res) {
          $btn.prop('disabled', false).text('Update Status');
          if (res.success) {
            $msg.addClass('success').text( res.data.message);
            // Update status badge in header
            var badgeClass = {
              'Pending':     'HARP-badge-yellow',
              'In Progress': 'HARP-badge-blue',
              'Resolved':    'HARP-badge-green',
              'Closed':      'HARP-badge-gray',
            }[status] || 'HARP-badge-gray';
            $('.HARP-admin-header .HARP-badge').attr('class', 'HARP-badge ' + badgeClass).text(status);
          } else {
            $msg.addClass('error').text((res.data.message || 'Update failed.'));
          }
        },
        error: function () {
          $btn.prop('disabled', false).text('Update Status');
          $msg.addClass('error').text('Network error. Please try again.');
        }
      });
    });

    /* ── Post Comment ───────────────────────────────────────────────────── */
    $('#HARP-post-comment').on('click', function () {
      var $btn      = $(this);
      var reportId  = $btn.data('id');
      var comment   = $('#HARP-comment-text').val().trim();
      var $msg      = $('#HARP-comment-msg');
      var $list     = $('#HARP-admin-comments-list');

      if (!comment) {
        $msg.attr('class', 'HARP-admin-msg error').text('Comment cannot be empty.');
        return;
      }

      $btn.prop('disabled', true).text('Posting…');
      $msg.attr('class', 'HARP-admin-msg').text('');

      $.ajax({
        url:  HARP_Admin.ajax_url,
        type: 'POST',
        data: {
          action:    'HARP_add_comment',
          nonce:     HARP_Admin.nonce,
          report_id: reportId,
          comment:   comment,
        },
        success: function (res) {
          $btn.prop('disabled', false).text('Post Comment');
          if (res.success) {
            var d = res.data;
            // Remove "no comments" placeholder
            $list.find('.HARP-no-comments-admin').remove();
            // Append new comment
            var html = '<div class="HARP-admin-comment">'
              + '<div class="HARP-admin-comment-meta">'
              + '<span class="HARP-admin-comment-author">' + $('<div>').text(d.author).html() + '</span>'
              + '<span class="HARP-admin-comment-date">' + $('<div>').text(d.date).html() + '</span>'
              + '</div>'
              + '<p>' + d.comment + '</p>'
              + '</div>';
            $list.append(html);
            $('#HARP-comment-text').val('');
            $msg.addClass('success').text(' Comment posted.');
          } else {
            $msg.addClass('error').text((res.data.message || 'Failed to post comment.'));
          }
        },
        error: function () {
          $btn.prop('disabled', false).text('Post Comment');
          $msg.addClass('error').text('Network error.');
        }
      });
    });

    /* ── Delete Report ──────────────────────────────────────────────────── */
    $(document).on('click', '.HARP-delete-report', function () {
      if (!confirm('Are you sure you want to permanently delete this report? This cannot be undone.')) return;

      var $btn     = $(this);
      var reportId = $btn.data('id');
      var $row     = $btn.closest('tr');

      $btn.prop('disabled', true).text('Deleting…');

      $.ajax({
        url:  HARP_Admin.ajax_url,
        type: 'POST',
        data: {
          action:    'HARP_delete_report',
          nonce:     HARP_Admin.nonce,
          report_id: reportId,
        },
        success: function (res) {
          if (res.success) {
            $row.fadeOut(300, function () { $(this).remove(); });
          } else {
            alert(res.data.message || 'Failed to delete report.');
            $btn.prop('disabled', false).text('Delete');
          }
        },
        error: function () {
          alert('Network error. Please try again.');
          $btn.prop('disabled', false).text('Delete');
        }
      });
    });

  });

})(jQuery);
 