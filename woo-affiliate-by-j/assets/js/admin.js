/**
 * WooCommerce Affiliate System — Admin JS
 *
 * Handles: AJAX status updates for referral commissions.
 *
 * @package WooAffiliateByJ
 */
(function ($) {
    'use strict';

    $(document).on('click', '.wabj-action-btn', function () {
        var btn        = $(this);
        var referralId = btn.data('id');
        var newStatus  = btn.data('status');

        if (!referralId || !newStatus) return;

        btn.prop('disabled', true).text('…');

        $.ajax({
            url:  wabjAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action:      'wabj_update_referral_status',
                nonce:       wabjAdmin.nonce,
                referral_id: referralId,
                status:      newStatus,
            },
            success: function (response) {
                if (response.success) {
                    // Update the status badge in the same row.
                    var row       = btn.closest('tr');
                    var badgeCell = row.find('.wabj-status-badge');

                    badgeCell
                        .removeClass('wabj-badge-pending wabj-badge-approved wabj-badge-rejected')
                        .addClass('wabj-badge-' + newStatus)
                        .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));

                    // Update action buttons.
                    var actionsCell = row.find('td.column-actions');
                    var approveHtml = '';
                    var rejectHtml  = '';

                    if (newStatus !== 'approved') {
                        approveHtml = '<button type="button" class="button button-small wabj-action-btn wabj-approve-btn" data-id="' + referralId + '" data-status="approved">Approve</button> ';
                    }
                    if (newStatus !== 'rejected') {
                        rejectHtml = '<button type="button" class="button button-small wabj-action-btn wabj-reject-btn" data-id="' + referralId + '" data-status="rejected">Reject</button>';
                    }

                    actionsCell.html(approveHtml + rejectHtml);
                } else {
                    alert(response.data && response.data.message ? response.data.message : 'Error updating status.');
                    btn.prop('disabled', false).text(newStatus === 'approved' ? 'Approve' : 'Reject');
                }
            },
            error: function () {
                alert('Network error. Please try again.');
                btn.prop('disabled', false).text(newStatus === 'approved' ? 'Approve' : 'Reject');
            },
        });
    });

})(jQuery);
