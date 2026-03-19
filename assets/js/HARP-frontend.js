/* HARP — HIT Anonymous Reporting Portal — Frontend JS */
(function ($) {
  'use strict';

  $(function () {
    HARP_Form.init();
    HARP_Track.init();
  });

  /* ═══════════════════════════════════════════════════════════════════════
     REPORT FORM
     ═══════════════════════════════════════════════════════════════════════ */
  var HARP_Form = {

    init: function () {
      if (!$('#HARP-report-wrapper').length) return;
      this.bindToggle();
      this.bindFileUpload();
      this.bindSubmit();
      this.bindCopyCode();
      this.bindLiveValidation();
    },

    // ── Live validation feedback on blur ──────────────────────────────────
    bindLiveValidation: function () {
      // Category
      $('#HARP-category').on('change', function () {
        HARP_Form.validateField($(this), $(this).val() !== '', 'Please select a report category.');
      });

      // Urgency (always has a value, but still hook change)
      $('#HARP-urgency').on('change', function () {
        HARP_Form.validateField($(this), $(this).val() !== '', 'Please select an urgency level.');
      });

      // Location
      $('#HARP-location').on('change', function () {
        HARP_Form.validateField($(this), $(this).val() !== '', 'Please select a location.');
      });

      // Description
      $('#HARP-description').on('blur input', function () {
        var val = $(this).val().trim();
        var ok  = val.length >= 10;
        HARP_Form.validateField($(this), ok, 'Please provide a description of at least 10 characters.');
      });

      // Reporter name (only when not anonymous)
      $('#HARP-reporter-name').on('blur', function () {
        if ($('#HARP-anonymous').is(':checked')) return;
        HARP_Form.validateField($(this), $(this).val().trim().length > 0, 'Your name is required.');
      });

      // Reporter email (only when not anonymous)
      $('#HARP-reporter-email').on('blur', function () {
        if ($('#HARP-anonymous').is(':checked')) return;
        var email = $(this).val().trim();
        var valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        HARP_Form.validateField($(this), valid, 'Please enter a valid email address.');
      });
    },

    // Mark field valid/invalid and show inline message
    validateField: function ($el, isValid, msg) {
      $el.removeClass('HARP-input-error HARP-input-ok');
      $el.closest('.HARP-field-group, .HARP-field-half').find('.HARP-field-error').remove();

      if (isValid) {
        $el.addClass('HARP-input-ok');
      } else {
        $el.addClass('HARP-input-error');
        $el.after('<span class="HARP-field-error">' + msg + '</span>');
      }
      return isValid;
    },

    // Full form validation before submit
    validateAll: function () {
      var ok = true;

      if (!HARP_Form.validateField($('#HARP-category'), $('#HARP-category').val() !== '', 'Please select a report category.'))  ok = false;
      if (!HARP_Form.validateField($('#HARP-location'), $('#HARP-location').val() !== '', 'Please select a location.'))          ok = false;

      var desc = $('#HARP-description').val().trim();
      if (!HARP_Form.validateField($('#HARP-description'), desc.length >= 10, 'Please provide a description of at least 10 characters.')) ok = false;

      if (!$('#HARP-anonymous').is(':checked')) {
        if (!HARP_Form.validateField($('#HARP-reporter-name'),  $('#HARP-reporter-name').val().trim().length > 0, 'Your name is required.'))  ok = false;
        var email = $('#HARP-reporter-email').val().trim();
        var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        if (!HARP_Form.validateField($('#HARP-reporter-email'), emailOk, 'Please enter a valid email address.'))  ok = false;
      }

      return ok;
    },

    // ── Toggle anonymous / identified ────────────────────────────────────
    bindToggle: function () {
      $('#HARP-anonymous').on('change', function () {
        var $fields = $('#HARP-reporter-fields');
        if ($(this).is(':checked')) {
          $fields.slideUp(200);
          $fields.find('input').prop('required', false);
          // Clear errors when switching back to anonymous
          $fields.find('.HARP-input-error').removeClass('HARP-input-error');
          $fields.find('.HARP-field-error').remove();
        } else {
          $fields.slideDown(200);
          $fields.find('input').prop('required', true);
        }
      });
    },

    // ── Drag-and-drop file upload ─────────────────────────────────────────
    bindFileUpload: function () {
      var $drop    = $('#HARP-file-drop');
      var $input   = $('#HARP-evidence');
      var $content = $('#HARP-file-drop-content');
      var $preview = $('#HARP-file-preview');
      var maxSize  = 10 * 1024 * 1024;
      var allowed  = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];

      $drop.on('dragover dragleave drop', function (e) { e.preventDefault(); e.stopPropagation(); });
      $drop.on('dragover',       function () { $(this).addClass('HARP-dragover'); });
      $drop.on('dragleave drop', function () { $(this).removeClass('HARP-dragover'); });
      $drop.on('drop', function (e) {
        var file = e.originalEvent.dataTransfer.files[0];
        if (file) HARP_Form.handleFile(file, $input, $content, $preview, maxSize, allowed);
      });
      $input.on('change', function () {
        var file = this.files[0];
        if (file) HARP_Form.handleFile(file, $input, $content, $preview, maxSize, allowed);
      });
    },

    handleFile: function (file, $input, $content, $preview, maxSize, allowed) {
      if (allowed.indexOf(file.type) === -1) {
        HARP_Form.showError(HARP.strings.file_type_error);
        return;
      }
      if (file.size > maxSize) {
        HARP_Form.showError(HARP.strings.file_size_error);
        return;
      }

      var reader = new FileReader();
      reader.onload = function (e) {
        $content.hide();
        $preview.empty().show();

        var isVideo = file.type.indexOf('video') === 0;
        var $media  = isVideo
          ? $('<video controls style="max-width:100%;max-height:200px;border-radius:8px;"></video>').attr('src', e.target.result)
          : $('<img style="max-width:100%;max-height:200px;border-radius:8px;object-fit:cover;" alt="Preview">').attr('src', e.target.result);

        var $remove = $('<button type="button" class="HARP-file-remove" title="Remove file">'
          + '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">'
          + '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
          + '</button>');
        $remove.on('click', function (ev) {
          ev.stopPropagation();
          $preview.hide().empty();
          $content.show();
          $input.val('');
        });

        var $fileName = $('<p style="margin:8px 0 0;font-size:13px;color:#6b7280;">').text(
          file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)'
        );
        $preview.append($media).append($remove).append($fileName);
      };
      reader.readAsDataURL(file);
    },

    // ── Submit form via AJAX ──────────────────────────────────────────────
    bindSubmit: function () {
      $('#HARP-submit-btn').on('click', function () { HARP_Form.submit(); });
    },

    submit: function () {
      this.clearError();

      // Run full validation first
      if (!this.validateAll()) {
        this.showError('Please fix the errors above before submitting.');
        // Scroll to first error
        var $firstErr = $('.HARP-input-error').first();
        if ($firstErr.length) {
          $firstErr[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
      }

      var $btn     = $('#HARP-submit-btn');
      var formData = new FormData();
      formData.append('action',          'HARP_submit_report');
      formData.append('nonce',           HARP.nonce);
      formData.append('category',        $('#HARP-category').val());
      formData.append('urgency',         $('#HARP-urgency').val());
      formData.append('location',        $('#HARP-location').val());
      formData.append('location_detail', $('#HARP-location-detail').val());
      formData.append('description',     $('#HARP-description').val());

      var isAnon = $('#HARP-anonymous').is(':checked') ? 1 : 0;
      formData.append('is_anonymous', isAnon);
      if (!isAnon) {
        formData.append('reporter_name',  $('#HARP-reporter-name').val());
        formData.append('reporter_email', $('#HARP-reporter-email').val());
      }

      var fileInput = document.getElementById('HARP-evidence');
      if (fileInput && fileInput.files.length > 0) {
        formData.append('evidence', fileInput.files[0]);
      }

      this.setLoading($btn, true);

      $.ajax({
        url:         HARP.ajax_url,
        type:        'POST',
        data:        formData,
        processData: false,
        contentType: false,
        success: function (res) {
          HARP_Form.setLoading($btn, false);
          if (res.success) {
            HARP_Form.showSuccess(res.data.tracking_code);
          } else {
            HARP_Form.showError(res.data.message || 'Something went wrong. Please try again.');
          }
        },
        error: function () {
          HARP_Form.setLoading($btn, false);
          HARP_Form.showError('A network error occurred. Please check your connection and try again.');
        }
      });
    },

    showSuccess: function (code) {
      $('#HARP-new-tracking-code').text(code);
      $('#HARP-form-container').fadeOut(200, function () {
        $('#HARP-success-state').fadeIn(300);
      });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    showError: function (msg) {
      var $err = $('#HARP-form-error');
      $err.html(
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;margin-right:6px;">'
        + '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
        + msg
      ).slideDown(200);
      $err[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    },

    clearError: function () {
      $('#HARP-form-error').slideUp(150).text('');
    },

    setLoading: function ($btn, loading) {
      if (loading) {
        $btn.prop('disabled', true).find('.HARP-btn-text').hide();
        $btn.find('.HARP-btn-spinner').show();
      } else {
        $btn.prop('disabled', false).find('.HARP-btn-text').show();
        $btn.find('.HARP-btn-spinner').hide();
      }
    },

    bindCopyCode: function () {
      $('#HARP-copy-code').on('click', function () {
        var code = $('#HARP-new-tracking-code').text();
        if (!code) return;
        if (navigator.clipboard) {
          navigator.clipboard.writeText(code).then(function () {
            HARP_Form.flashCopied();
          });
        } else {
          // Fallback for older browsers
          var $temp = $('<input>').val(code).appendTo('body').select();
          document.execCommand('copy');
          $temp.remove();
          HARP_Form.flashCopied();
        }
      });
    },

    flashCopied: function () {
      var $btn = $('#HARP-copy-code');
      $btn.addClass('copied').attr('title', 'Copied!');
      setTimeout(function () { $btn.removeClass('copied').attr('title', 'Copy to clipboard'); }, 2000);
    }
  };

  /* ═══════════════════════════════════════════════════════════════════════
     TRACKING PAGE
     ═══════════════════════════════════════════════════════════════════════ */
  var HARP_Track = {

    init: function () {
      if (!$('#HARP-track-wrapper').length) return;

      // Live format validation on input
      $('#HARP-track-code').on('input', function () {
        $(this).val( $(this).val().toUpperCase().replace(/[^A-Z0-9\-]/g, '') );
        HARP_Track.clearError();
        HARP_Track.clearFieldError($(this));
      });

      $('#HARP-track-code').on('keypress', function (e) {
        if (e.which === 13) HARP_Track.track();
      });

      $('#HARP-track-btn').on('click', function () { HARP_Track.track(); });

      // Pre-fill code from URL param and auto-track
      var urlParams = new URLSearchParams(window.location.search);
      var code = urlParams.get('code');
      if (code) {
        $('#HARP-track-code').val(code.toUpperCase());
        HARP_Track.track();
      }
    },

    validateCode: function (code) {
      if (!code) {
        return 'Please enter a tracking code.';
      }
      if (!/^SR-\d{4}-[A-Z0-9]{6}$/.test(code)) {
        return 'Invalid format. Tracking codes look like: SR-2026-ABC123';
      }
      return '';
    },

    track: function () {
      var $input = $('#HARP-track-code');
      var code   = $input.val().trim().toUpperCase();
      var errMsg = HARP_Track.validateCode(code);

      this.clearError();
      HARP_Track.clearFieldError($input);

      if (errMsg) {
        HARP_Track.showFieldError($input, errMsg);
        return;
      }

      var $btn = $('#HARP-track-btn');
      this.setLoading($btn, true);
      $('#HARP-track-results').hide();

      $.ajax({
        url:  HARP.ajax_url,
        type: 'POST',
        data: { action: 'HARP_track_report', nonce: HARP.nonce, tracking_code: code },
        success: function (res) {
          HARP_Track.setLoading($btn, false);
          if (res.success) {
            HARP_Track.renderResult(res.data);
          } else {
            HARP_Track.showError(res.data.message || 'Report not found.');
          }
        },
        error: function () {
          HARP_Track.setLoading($btn, false);
          HARP_Track.showError('A network error occurred. Please try again.');
        }
      });
    },

    showFieldError: function ($el, msg) {
      $el.addClass('HARP-input-error');
      $el.after('<span class="HARP-field-error HARP-track-field-error">' + msg + '</span>');
    },

    clearFieldError: function ($el) {
      $el.removeClass('HARP-input-error HARP-input-ok');
      $('.HARP-track-field-error').remove();
    },

    renderResult: function (d) {
      $('#HARP-result-code').text(d.tracking_code);
      $('#HARP-result-category').text(d.category);
      $('#HARP-result-location').text(d.location + (d.location_detail ? ' — ' + d.location_detail : ''));
      $('#HARP-result-submitted').text(d.submitted_at);
      $('#HARP-result-updated').text(d.updated_at);

      var urgencyColors = { Emergency: '#dc2626', High: '#ea580c', Medium: '#d97706', Low: '#16a34a' };
      var uc = urgencyColors[d.urgency] || '#6b7280';
      $('#HARP-result-urgency').html('<span style="color:' + uc + ';font-weight:700;">' + d.urgency + '</span>');

      var statusSlug = d.status.replace(/ /g, '-');
      $('#HARP-result-status')
        .attr('class', 'HARP-status-badge status-' + statusSlug)
        .text(d.status);

      if (d.admin_note) {
        $('#HARP-admin-note-text').text(d.admin_note);
        $('#HARP-admin-note-block').show();
      } else {
        $('#HARP-admin-note-block').hide();
      }

      if (d.comments_html) {
        $('#HARP-comments-list').html(d.comments_html);
      } else {
        $('#HARP-comments-list').html('<p class="HARP-no-comments">No updates yet. Check back later.</p>');
      }
      $('#HARP-comment-count').text(d.comment_count > 0 ? '(' + d.comment_count + ')' : '');

      var steps      = ['Pending', 'In Progress', 'Resolved', 'Closed'];
      var currentIdx = steps.indexOf(d.status);
      $('.HARP-step').each(function (i) {
        $(this).removeClass('active completed');
        if (i < currentIdx) $(this).addClass('completed');
        if (i === currentIdx) $(this).addClass('active');
      });

      $('#HARP-track-results').slideDown(300);
      $('#HARP-track-results')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    showError: function (msg) {
      $('#HARP-track-error').html(
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;margin-right:6px;">'
        + '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
        + msg
      ).slideDown(200);
    },

    clearError: function () {
      $('#HARP-track-error').slideUp(150).text('');
    },

    setLoading: function ($btn, loading) {
      if (loading) {
        $btn.prop('disabled', true).find('.HARP-btn-text').hide();
        $btn.find('.HARP-btn-spinner').show();
      } else {
        $btn.prop('disabled', false).find('.HARP-btn-text').show();
        $btn.find('.HARP-btn-spinner').hide();
      }
    }
  };

})(jQuery);
