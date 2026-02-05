/**
 * Correct Contact Admin Scripts
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 * @copyright © 2023-2026 Daan van den Bergh
 */

(function () {
    'use strict';

    const CCAdmin = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('cc-wizard-restart')) {
                    this.restartWizard(e);
                }

                if (e.target.classList.contains('cc-run-wizard-again')) {
                    this.showConfirmDialog(e);
                }

                if (e.target.classList.contains('cc-dialog-cancel')) {
                    this.hideConfirmDialog(e);
                }

                if (e.target.classList.contains('cc-dialog-confirm')) {
                    this.confirmRunWizard(e);
                }

                // Close dialog when clicking overlay
                if (e.target.classList.contains('cc-dialog-overlay')) {
                    this.hideConfirmDialog(e);
                }
            });
        },

        showConfirmDialog: function (e) {
            e.preventDefault();
            const dialog = document.getElementById('cc-wizard-confirm-dialog');
            if (dialog) {
                dialog.style.display = 'flex';
            }
        },

        hideConfirmDialog: function (e) {
            e.preventDefault();
            const dialog = document.getElementById('cc-wizard-confirm-dialog');
            if (dialog) {
                dialog.style.display = 'none';
            }
        },

        confirmRunWizard: function (e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'cc_wizard_run_again');
            formData.append('nonce', ccAdmin.nonce);

            fetch(ccAdmin.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        window.location.reload();
                    }
                })
                .catch(() => {
                    // Silently fail - user can try again
                });
        },

        restartWizard: function (e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'cc_wizard_restart');
            formData.append('nonce', ccAdmin.nonce);

            fetch(ccAdmin.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        window.location.reload();
                    }
                })
                .catch(() => {
                    // Silently fail - user can try again
                });
        }
    };

    // Initialize on document ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            CCAdmin.init();
        });
    } else {
        CCAdmin.init();
    }

})();
