/**
 * WooCommerce Affiliate System — Frontend JS
 *
 * Handles: copy-to-clipboard, affiliate link generation.
 *
 * @package WooAffiliateByJ
 */
(function () {
    'use strict';

    /* ----------------------------------------------------------
     * Copy to Clipboard
     * --------------------------------------------------------*/
    function initCopyButtons() {
        document.querySelectorAll('.wabj-copy-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-copy-target');
                var input = document.getElementById(targetId);
                if (!input) return;

                var text = input.value;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        showCopyFeedback(btn, true);
                    }).catch(function () {
                        fallbackCopy(input);
                        showCopyFeedback(btn, true);
                    });
                } else {
                    fallbackCopy(input);
                    showCopyFeedback(btn, true);
                }
            });
        });
    }

    function fallbackCopy(input) {
        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
    }

    function showCopyFeedback(btn, success) {
        var textEl = btn.querySelector('.wabj-copy-text');
        if (!textEl) return;

        var i18n = (typeof wabjFront !== 'undefined') ? wabjFront.i18n : {};
        var originalText = i18n.copy || 'Copy Link';
        var feedbackText = success ? (i18n.copied || 'Copied!') : (i18n.error || 'Failed');

        textEl.textContent = feedbackText;
        btn.classList.add('wabj-copied');

        setTimeout(function () {
            textEl.textContent = originalText;
            btn.classList.remove('wabj-copied');
        }, 2000);
    }

    /* ----------------------------------------------------------
     * Link Generator (Dashboard)
     * --------------------------------------------------------*/
    function initLinkGenerator() {
        var generateBtn = document.getElementById('wabj-generate-btn');
        if (!generateBtn) return;

        var urlInput   = document.getElementById('wabj-store-url');
        var outputWrap = document.getElementById('wabj-generated-output');
        var outputLink = document.getElementById('wabj-generated-link');
        var errorEl    = document.getElementById('wabj-generator-error');

        if (!urlInput || !outputWrap || !outputLink) return;

        var siteUrl  = urlInput.getAttribute('data-site-url') || (typeof wabjFront !== 'undefined' ? wabjFront.siteUrl : '');
        var urlParam = (typeof wabjFront !== 'undefined') ? wabjFront.urlParam : 'ref';
        var userId   = (typeof wabjFront !== 'undefined') ? wabjFront.userId : 0;

        generateBtn.addEventListener('click', function () {
            var inputUrl = urlInput.value.trim();
            errorEl.style.display = 'none';
            outputWrap.style.display = 'none';

            // Validate not empty.
            if (!inputUrl) {
                showError(errorEl, 'Please enter a URL.');
                return;
            }

            // Ensure it starts with the store URL.
            if (siteUrl && inputUrl.indexOf(siteUrl) !== 0) {
                showError(errorEl, 'Please enter a URL from this store (' + siteUrl + ').');
                return;
            }

            // Build affiliate link.
            var separator = inputUrl.indexOf('?') !== -1 ? '&' : '?';
            var affLink = inputUrl + separator + encodeURIComponent(urlParam) + '=' + encodeURIComponent(userId);

            outputLink.value = affLink;
            outputWrap.style.display = 'block';

            // Re-initialize copy buttons for the new output.
            initCopyButtons();
        });

        // Allow Enter key.
        urlInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                generateBtn.click();
            }
        });
    }

    function showError(el, message) {
        el.textContent = message;
        el.style.display = 'block';
    }

    /* ----------------------------------------------------------
     * Init
     * --------------------------------------------------------*/
    document.addEventListener('DOMContentLoaded', function () {
        initCopyButtons();
        initLinkGenerator();
    });

})();
