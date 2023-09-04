document.addEventListener('DOMContentLoaded', () => {
    var emailField = document.getElementById('edd-email');
    var message = document.createElement('div');

    message.classList.add('edd-truemail-message');
    emailField.insertAdjacentElement('afterend', message);

    emailField.addEventListener('change', () => {
        emailField.classList.remove('edd-truemail-success');
        emailField.classList.remove('edd-truemail-warning');
    });

    emailField.addEventListener('blur', () => {
        if (emailField.classList.contains('edd-truemail-success')) {
            return;
        }

        var add_message = false;

        message.innerHTML = '';

        set_loader();

        wp.ajax.post(
            'edd_truemail_verify_email',
            {
                email: emailField.value
            }
        ).done(function (response) {
            if (response.status === 200 && response.success === true) {
                // Valid email address.
                emailField.classList.remove('edd-truemail-warning');
                emailField.classList.add('edd-truemail-success');
            }

            remove_loader();
        }).fail(function (response) {
            if (response.status === 200 && response.success === false) {
                // Email address is invalid.
                emailField.classList.remove('edd-truemail-success');
                emailField.classList.add('edd-truemail-warning');

                add_message = true;
            } else {
                // Fail silently on a timeout.
                emailField.classList.remove('edd-truemail-success');
                emailField.classList.remove('edd-truemail-warning');
            }

            if (add_message === true) {
                message.innerHTML = '<sup><em>' + response.message + '</em></sup>';
            } else {
                message.innerHTML = '';
            }

            remove_loader();

            console.log(response);
        });
    });

    function set_loader() {
        var emailField = document.getElementById('edd-email');
        emailField.classList.remove('edd-truemail-success');
        emailField.classList.remove('edd-truemail-warning');
        emailField.classList.add('edd-truemail-loading');
    }

    function remove_loader() {
        var emailField = document.getElementById('edd-email');
        emailField.classList.remove('edd-truemail-loading');
    }
});