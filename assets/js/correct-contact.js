document.addEventListener('DOMContentLoaded', () => {
    const selectors = cc_ajax_obj.selectors;
    const elements = document.querySelectorAll(selectors);

    elements.forEach((emailField) => {
        var message = document.createElement('div');
        message.classList.add('cc-message');
        emailField.insertAdjacentElement('afterend', message);

        emailField.addEventListener('change', () => {
            emailField.classList.remove('cc-success');
            emailField.classList.remove('cc-warning');
        });

        emailField.addEventListener('blur', () => {
            if (emailField.classList.contains('cc-success')) {
                return;
            }

            if (!emailField.value) {
                return;
            }

            var add_message = false;
            message.innerHTML = '';

            set_loader(emailField);

            wp.ajax.post(
                'cc_verify_email',
                {
                    email: emailField.value
                }
            ).done(function (response) {
                if (response.code === 200 && response.success === true) {
                    // Valid email address.
                    emailField.classList.remove('cc-warning');
                    emailField.classList.add('cc-success');
                }

                remove_loader(emailField);
            }).fail(function (response) {
                if (response.code === 200 && response.success === false) {
                    // Email address is invalid.
                    emailField.classList.remove('cc-success');
                    emailField.classList.add('cc-warning');

                    add_message = true;
                } else {
                    // Fail silently on a timeout.
                    emailField.classList.remove('cc-success');
                    emailField.classList.remove('cc-warning');
                }

                if (add_message === true) {
                    message.innerHTML = '<sup><em>' + response.message + '</em></sup>';
                } else {
                    message.innerHTML = '';
                }

                remove_loader(emailField);

                console.log(response);
            });
        });
    });

    function set_loader(element) {
        element.classList.remove('cc-success');
        element.classList.remove('cc-warning');
        element.classList.add('cc-loading');
    }

    function remove_loader(element) {
        element.classList.remove('cc-loading');
    }
});