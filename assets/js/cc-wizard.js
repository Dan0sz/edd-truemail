/**
 * Correct Contact Setup Wizard
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 * @copyright © 2023-2026 Daan van den Bergh
 */

(function ($) {
    'use strict';

    const CCWizard = {
        currentSlide: 0,
        doToken: '',
        appUrl: '',
        accessToken: '',

        init: function () {
            this.bindEvents();
            this.showSlide(0);
        },

        bindEvents: function () {
            // Navigation item clicks
            $(document).on('click', '.cc-wizard-nav-item', this.handleNavClick.bind(this));

            // Next button
            $(document).on('click', '.cc-wizard-next', this.nextSlide.bind(this));

            // Token input
            $(document).on('input', '#cc-do-token', this.validateToken.bind(this));

            // Create app button
            $(document).on('click', '.cc-wizard-create-app', this.createApp.bind(this));

            // Remove token button
            $(document).on('click', '.cc-wizard-remove-token', this.removeToken.bind(this));

            // Complete wizard button
            $(document).on('click', '.cc-wizard-complete', this.completeWizard.bind(this));
        },

        handleNavClick: function (e) {
            e.preventDefault();
            const $navItem = $(e.currentTarget);
            const targetSlide = parseInt($navItem.data('slide'));

            // Don't allow clicking on disabled items
            if ($navItem.hasClass('disabled')) {
                return;
            }

            // Only allow clicking on completed items or current item
            if ($navItem.hasClass('completed') || $navItem.hasClass('nav-tab-active')) {
                this.showSlide(targetSlide);
            }
        },

        showSlide: function (slideNumber) {
            $('.cc-wizard-slide').hide();
            $('.cc-wizard-slide[data-slide="' + slideNumber + '"]').fadeIn(300);
            this.currentSlide = slideNumber;

            // Update navigation states
            this.updateNavigationStates(slideNumber);
        },

        updateNavigationStates: function (currentSlide) {
            const $navItems = $('.cc-wizard-nav-item');

            $navItems.each(function () {
                const $item = $(this);
                const itemSlide = parseInt($item.data('slide'));

                // Remove all state classes
                $item.removeClass('nav-tab-active completed disabled');

                if (itemSlide === currentSlide) {
                    // Current slide - active
                    $item.addClass('nav-tab-active');
                } else if (itemSlide < currentSlide) {
                    // Previous slides - completed and clickable
                    $item.addClass('completed');
                } else {
                    // Future slides - disabled
                    $item.addClass('disabled');
                }
            });
        },

        nextSlide: function (e) {
            e.preventDefault();

            // Save token if on slide 2
            if (this.currentSlide === 2) {
                this.doToken = $('#cc-do-token').val().trim();
            }

            this.showSlide(this.currentSlide + 1);
        },

        validateToken: function () {
            const token = $('#cc-do-token').val().trim();
            const $button = $('.cc-wizard-slide[data-slide="2"] .cc-wizard-next');

            if (token.length > 20) {
                $button.prop('disabled', false);
            } else {
                $button.prop('disabled', true);
            }
        },

        createApp: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $slide = $('.cc-wizard-slide[data-slide="3"]');
            const $content = $slide.find('.cc-wizard-content-main');
            const $progress = $slide.find('.cc-wizard-progress');
            const $error = $slide.find('.cc-wizard-error');

            // Hide button and content, show progress
            $button.hide();
            $content.fadeOut(200, function () {
                $progress.fadeIn(300);
            });

            // Start provisioning
            this.provisionServer();
        },

        provisionServer: function () {
            const self = this;
            let currentStep = 0;
            const steps = ['project', 'server', 'install', 'secure', 'done'];

            function updateProgress(step) {
                const $steps = $('.cc-wizard-progress-steps li');
                const $progressFill = $('.cc-wizard-progress-fill');
                const progress = ((step + 1) / steps.length) * 100;

                $steps.removeClass('active complete error');
                $steps.eq(step).addClass('active');
                $steps.slice(0, step).addClass('complete');
                $progressFill.css('width', progress + '%');
            }

            function processStep(step) {
                updateProgress(step);

                $.ajax({
                    url: ccWizard.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'cc_wizard_provision',
                        nonce: ccWizard.nonce,
                        step: steps[step],
                        token: self.doToken
                    },
                    success: function (response) {
                        if (response.success) {
                            // Store credentials if returned
                            if (response.data.app_url) {
                                self.appUrl = response.data.app_url;
                            }
                            if (response.data.access_token) {
                                self.accessToken = response.data.access_token;
                            }

                            // Move to next step or complete
                            if (step < steps.length - 1) {
                                setTimeout(function () {
                                    processStep(step + 1);
                                }, 500);
                            } else {
                                // Provisioning complete
                                setTimeout(function () {
                                    self.saveCredentials();
                                }, 1000);
                            }
                        } else {
                            self.handleProvisioningError(response.data, step);
                        }
                    },
                    error: function (xhr, status, error) {
                        self.handleProvisioningError({
                            message: 'Network error: ' + error,
                            code: 'network_error'
                        }, step);
                    }
                });
            }

            // Start with first step
            processStep(0);
        },

        handleProvisioningError: function (error, step) {
            const $slide = $('.cc-wizard-slide[data-slide="3"]');
            const $progress = $slide.find('.cc-wizard-progress');
            const $error = $slide.find('.cc-wizard-error');
            const $errorMessage = $error.find('.cc-wizard-error-message');
            const $errorActions = $error.find('.cc-wizard-error-actions');

            // Mark current step as error
            $('.cc-wizard-progress-steps li').eq(step).addClass('error');

            // Hide progress, show error
            $progress.fadeOut(200, function () {
                $errorMessage.text(error.message || 'An error occurred during setup.');

                // Handle specific error types
                if (error.code === 'payment_method_required') {
                    $errorActions.html(
                        '<a href="https://cloud.digitalocean.com/account/billing" target="_blank" class="button">Add payment method</a>' +
                        '<button type="button" class="button button-primary cc-wizard-retry">Retry setup</button>'
                    );
                } else {
                    $errorActions.html(
                        '<button type="button" class="button button-primary cc-wizard-retry">Retry setup</button>'
                    );
                }

                $error.fadeIn(300);
            });

            // Bind retry button
            $(document).on('click', '.cc-wizard-retry', this.retryProvisioning.bind(this));
        },

        retryProvisioning: function (e) {
            e.preventDefault();

            const $slide = $('.cc-wizard-slide[data-slide="3"]');
            const $error = $slide.find('.cc-wizard-error');
            const $progress = $slide.find('.cc-wizard-progress');

            // Reset progress
            $('.cc-wizard-progress-steps li').removeClass('active complete error');
            $('.cc-wizard-progress-fill').css('width', '0%');

            // Hide error, show progress
            $error.fadeOut(200, function () {
                $progress.fadeIn(300);
            });

            // Retry provisioning
            this.provisionServer();
        },

        saveCredentials: function () {
            const self = this;

            $.ajax({
                url: ccWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cc_wizard_save_credentials',
                    nonce: ccWizard.nonce,
                    app_url: this.appUrl,
                    access_token: this.accessToken,
                    do_token: this.doToken
                },
                success: function (response) {
                    if (response.success) {
                        // Move to success slide
                        self.showSlide(4);
                    } else {
                        alert('Failed to save credentials. Please try again.');
                    }
                },
                error: function () {
                    alert('Failed to save credentials. Please try again.');
                }
            });
        },

        removeToken: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            $button.prop('disabled', true).text('Removing...');

            $.ajax({
                url: ccWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cc_wizard_remove_token',
                    nonce: ccWizard.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $button.text('Token removed').addClass('button-disabled');
                        $('.cc-wizard-token-cleanup').fadeOut(300);
                    } else {
                        $button.prop('disabled', false).text('Remove API token');
                        alert('Failed to remove token. Please try again.');
                    }
                },
                error: function () {
                    $button.prop('disabled', false).text('Remove API token');
                    alert('Failed to remove token. Please try again.');
                }
            });
        },

        completeWizard: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            $button.prop('disabled', true).text('Completing...');

            $.ajax({
                url: ccWizard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cc_wizard_complete',
                    nonce: ccWizard.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Reload page to show settings
                        window.location.reload();
                    } else {
                        $button.prop('disabled', false).text('Continue to settings');
                        alert('Failed to complete setup. Please try again.');
                    }
                },
                error: function () {
                    $button.prop('disabled', false).text('Continue to settings');
                    alert('Failed to complete setup. Please try again.');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        if ($('.cc-admin').length) {
            CCWizard.init();
        }
    });

})(jQuery);
