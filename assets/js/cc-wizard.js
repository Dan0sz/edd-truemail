/**
 * Correct Contact Setup Wizard
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 * @copyright © 2023-2026 Daan van den Bergh
 */

(function () {
    'use strict';

    const CCWizard = {
        currentSlide: 0,
        doToken: '',
        appUrl: '',
        accessToken: '',
        projectId: '',
        appId: '',

        init: function () {
            this.bindEvents();

            const tokenInput = document.getElementById('cc-do-token');
            if (tokenInput) {
                // Load saved DO token from input
                this.doToken = tokenInput.value.trim();

                // If token is already present, validate it to enable the Continue button.
                if (this.doToken) {
                    this.validateToken();
                }
            }

            // Check for hash in URL and load that slide, otherwise start at slide 0
            const hash = window.location.hash;
            let initialSlide = 0;

            if (hash && hash.startsWith('#slide-')) {
                const slideNumber = parseInt(hash.replace('#slide-', ''));
                if (!isNaN(slideNumber) && slideNumber >= 0 && slideNumber <= 4) {
                    initialSlide = slideNumber;
                }
            }

            this.showSlide(initialSlide);
        },

        bindEvents: function () {
            // Navigation item clicks
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('cc-wizard-nav-item')) {
                    this.handleNavClick(e);
                }

                if (e.target.classList.contains('cc-wizard-next')) {
                    this.nextSlide(e);
                }

                if (e.target.classList.contains('cc-wizard-provision')) {
                    this.createApp(e);
                }

                if (e.target.classList.contains('cc-wizard-provision-complete')) {
                    this.nextSlide(e);
                }

                if (e.target.classList.contains('cc-wizard-remove-token')) {
                    this.removeToken(e);
                }

                if (e.target.classList.contains('cc-wizard-complete')) {
                    this.completeWizard(e);
                }

                if (e.target.classList.contains('cc-wizard-exit')) {
                    this.skipWizard(e);
                }
            });

            // Token input
            const tokenInput = document.getElementById('cc-do-token');
            if (tokenInput) {
                tokenInput.addEventListener('input', this.validateToken.bind(this));
            }

            // Handle browser back/forward navigation
            window.addEventListener('hashchange', this.handleHashChange.bind(this));
        },

        handleNavClick: function (e) {
            e.preventDefault();
            const navItem = e.target;
            const targetSlide = parseInt(navItem.dataset.slide);

            // Don't allow clicking on disabled items
            if (navItem.classList.contains('disabled')) {
                return;
            }

            // Only allow clicking on completed items or current item
            if (navItem.classList.contains('completed') || navItem.classList.contains('nav-tab-active')) {
                this.showSlide(targetSlide);
            }
        },

        showSlide: function (slideNumber) {
            const slides = document.querySelectorAll('.cc-wizard-slide');
            slides.forEach(slide => {
                slide.style.display = 'none';
                slide.style.opacity = '0';
            });

            const targetSlide = document.querySelector(`.cc-wizard-slide[data-slide="${slideNumber}"]`);
            if (targetSlide) {
                targetSlide.style.display = 'block';
                // Simple fade in effect
                setTimeout(() => {
                    targetSlide.style.transition = 'opacity 0.3s ease-in-out';
                    targetSlide.style.opacity = '1';
                }, 10);
            }

            this.currentSlide = slideNumber;

            // Update URL hash
            window.location.hash = 'slide-' + slideNumber;

            // Update navigation states
            this.updateNavigationStates(slideNumber);

            // Fetch regions if on Slide 3
            if (slideNumber === 3) {
                this.fetchRegions();
            }
        },

        fetchRegions: function () {
            const regionSelect = document.getElementById('cc-region');
            const provisionButton = document.querySelector('.cc-wizard-provision');

            if (!regionSelect || regionSelect.options.length > 1) {
                return; // Already loaded or doesn't exist
            }

            const formData = new FormData();
            formData.append('action', 'cc_wizard_fetch_regions');
            formData.append('nonce', ccWizard.nonce);
            formData.append('token', this.doToken);

            fetch(ccWizard.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success && response.data.regions) {
                        regionSelect.innerHTML = '';
                        response.data.regions.forEach(region => {
                            const option = document.createElement('option');
                            option.value = region.slug;
                            option.textContent = region.name;
                            regionSelect.appendChild(option);
                        });
                        if (provisionButton) {
                            provisionButton.disabled = false;
                        }
                    } else {
                        regionSelect.innerHTML = '<option value="">' + (response.data.message || 'Error loading regions') + '</option>';
                    }
                })
                .catch(() => {
                    if (regionSelect) {
                        regionSelect.innerHTML = '<option value="">Error loading regions</option>';
                    }
                });
        },

        handleHashChange: function () {
            const hash = window.location.hash;

            if (hash && hash.startsWith('#slide-')) {
                const slideNumber = parseInt(hash.replace('#slide-', ''));
                if (!isNaN(slideNumber) && slideNumber >= 0 && slideNumber <= 4) {
                    // Only update if it's a different slide
                    if (slideNumber !== this.currentSlide) {
                        this.showSlide(slideNumber);
                    }
                }
            }
        },

        updateNavigationStates: function (currentSlide) {
            const navItems = document.querySelectorAll('.cc-wizard-nav-item');

            navItems.forEach(item => {
                const itemSlide = parseInt(item.dataset.slide);

                // Remove all state classes
                item.classList.remove('nav-tab-active', 'completed', 'disabled');

                if (itemSlide === currentSlide) {
                    // Current slide - active
                    item.classList.add('nav-tab-active');
                } else if (itemSlide < currentSlide) {
                    // Previous slides - completed and clickable
                    item.classList.add('completed');
                } else {
                    // Future slides - disabled
                    item.classList.add('disabled');
                }
            });
        },

        nextSlide: function (e) {
            e.preventDefault();

            // Save token if on slide 2
            if (this.currentSlide === 2) {
                this.saveDOToken();
            }

            this.showSlide(this.currentSlide + 1);
        },

        validateToken: function () {
            const tokenInput = document.getElementById('cc-do-token');
            const token = tokenInput ? tokenInput.value.trim() : '';
            const button = document.querySelector('.cc-wizard-slide[data-slide="2"] .cc-wizard-next');

            if (button) {
                button.disabled = token.length <= 20;
            }
        },

        saveDOToken: function () {
            const tokenInput = document.getElementById('cc-do-token');
            const token = tokenInput ? tokenInput.value.trim() : '';
            this.doToken = token;

            if (token.length <= 20) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'cc_wizard_save_do_token');
            formData.append('nonce', ccWizard.nonce);
            formData.append('token', token);

            fetch(ccWizard.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        console.log('DO token saved.');
                    }
                });
        },

        createApp: function (e) {
            e.preventDefault();

            const button = e.target;
            const slide = document.querySelector('.cc-wizard-slide[data-slide="3"]');
            const content = slide.querySelector('.cc-wizard-provision-content');
            const progress = slide.querySelector('.cc-wizard-provision-progress');

            // Disable button and keep it visible
            button.disabled = true;

            // Fade content, show progress
            if (content) {
                content.style.transition = 'opacity 0.2s';
                content.style.opacity = '0';
                setTimeout(() => {
                    content.style.display = 'none';
                    if (progress) {
                        progress.style.display = 'block';
                        progress.style.opacity = '0';
                        setTimeout(() => {
                            progress.style.transition = 'opacity 0.3s';
                            progress.style.opacity = '1';
                        }, 10);
                    }
                }, 200);
            }

            // Start provisioning
            this.provisionServer();
        },

        provisionServer: function () {
            const self = this;
            const steps = ['project', 'app', 'deploy', 'finalize', 'done'];

            const updateProgress = (stepIndex) => {
                const stepElements = document.querySelectorAll('.cc-wizard-progress-steps li');
                const progressFill = document.querySelector('.cc-wizard-progress-fill');
                const progressPercentage = ((stepIndex + 1) / steps.length) * 100;

                stepElements.forEach((el, index) => {
                    el.classList.remove('active', 'complete', 'error');
                    if (index === stepIndex) {
                        el.classList.add('active');
                    } else if (index < stepIndex) {
                        el.classList.add('complete');
                    }
                });

                if (progressFill) {
                    progressFill.style.width = progressPercentage + '%';
                }
            };

            const processStep = (stepIndex) => {
                updateProgress(stepIndex);

                const regionSelect = document.getElementById('cc-region');
                const formData = new FormData();
                formData.append('action', 'cc_wizard_provision');
                formData.append('nonce', ccWizard.nonce);
                formData.append('step', steps[stepIndex]);
                formData.append('token', this.doToken);
                formData.append('region', regionSelect ? regionSelect.value : 'ams3');
                formData.append('project_id', this.projectId);
                formData.append('app_id', this.appId);

                fetch(ccWizard.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(response => {
                        if (response.success) {
                            // Store credentials if returned
                            if (response.data.app_url) {
                                self.appUrl = response.data.app_url;
                            }
                            if (response.data.access_token) {
                                self.accessToken = response.data.access_token;
                            }
                            if (response.data.project_id) {
                                self.projectId = response.data.project_id;
                            }
                            if (response.data.app_id) {
                                self.appId = response.data.app_id;
                            }

                            // Move to next step or complete
                            if (response.data.retry) {
                                setTimeout(() => {
                                    processStep(stepIndex);
                                }, 2000);
                            } else if (stepIndex < steps.length - 1) {
                                setTimeout(() => {
                                    processStep(stepIndex + 1);
                                }, 500);
                            } else {
                                // Provisioning complete - turn bar green
                                const progressFill = document.querySelector('.cc-wizard-progress-fill');
                                if (progressFill) {
                                    progressFill.classList.add('success');
                                }

                                // Re-enable button and change label to "Continue"
                                const button = document.querySelector('.cc-wizard-provision');
                                if (button) {
                                    button.disabled = false;
                                    button.textContent = 'Continue';
                                    button.classList.remove('cc-wizard-provision');
                                    button.classList.add('cc-wizard-provision-complete');
                                }
                            }
                        } else {
                            self.handleProvisioningError(response.data, stepIndex);
                        }
                    })
                    .catch(error => {
                        self.handleProvisioningError({
                            message: 'Network error: ' + error.message,
                            code: 'network_error'
                        }, stepIndex);
                    });
            };

            // Start with first step
            processStep(0);
        },

        handleProvisioningError: function (error, stepIndex) {
            const slide = document.querySelector('.cc-wizard-slide[data-slide="3"]');
            const errorMessage = slide.querySelector('.cc-error-message');
            const errorActions = slide.querySelector('.cc-wizard-provision-error-actions');
            const progressFill = slide.querySelector('.cc-wizard-progress-fill');
            const stepsList = slide.querySelector('.cc-wizard-progress-steps');

            // Mark current step as error
            const stepElements = document.querySelectorAll('.cc-wizard-progress-steps li');
            if (stepElements[stepIndex]) {
                stepElements[stepIndex].classList.add('error');
            }

            // Turn progress bar red and full
            if (progressFill) {
                progressFill.style.width = '100%';
                progressFill.classList.add('error');
            }

            // Show error message and actions
            if (stepsList) {
                stepsList.style.display = 'none';
            }

            if (errorMessage) {
                errorMessage.textContent = error.message || 'An error occurred during setup.';
                errorMessage.style.display = 'block';
            }

            if (errorActions) {
                errorActions.style.display = 'block';
            }
        },

        /**
         * Generic handler for wizard AJAX actions.
         *
         * @param {Event} e - The event object
         * @param {Object} config - Configuration object
         * @param {string} config.action - The AJAX action name
         * @param {string} config.loadingText - Text to display while loading
         * @param {Function} config.onSuccess - Callback function on success
         */
        handleWizardAction: function (e, config) {
            e.preventDefault();

            const button = e.target;
            button.disabled = true;
            const originalText = button.textContent;
            button.textContent = config.loadingText;

            const formData = new FormData();
            formData.append('action', config.action);
            formData.append('nonce', ccWizard.nonce);

            fetch(ccWizard.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        config.onSuccess(button);
                    } else {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                })
                .catch(() => {
                    button.disabled = false;
                    button.textContent = originalText;
                });
        },

        removeToken: function (e) {
            this.handleWizardAction(e, {
                action: 'cc_wizard_remove_token',
                loadingText: ccWizard.removingText,
                onSuccess: (button) => {
                    button.textContent = ccWizard.tokenRemovedText;
                    button.classList.add('button-disabled');
                }
            });
        },

        completeWizard: function (e) {
            this.handleWizardAction(e, {
                action: 'cc_wizard_complete',
                loadingText: ccWizard.completingText,
                onSuccess: () => {
                    window.location.reload();
                }
            });
        },

        skipWizard: function (e) {
            this.handleWizardAction(e, {
                action: 'cc_wizard_skip',
                loadingText: ccWizard.redirectingText,
                onSuccess: () => {
                    window.location.href = window.location.pathname + '?page=correct-contact&tab=cc-advanced-settings';
                }
            });
        }
    };

    // Initialize on document ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (document.querySelector('.cc-admin')) {
                CCWizard.init();
            }
        });
    } else {
        if (document.querySelector('.cc-admin')) {
            CCWizard.init();
        }
    }

})();
