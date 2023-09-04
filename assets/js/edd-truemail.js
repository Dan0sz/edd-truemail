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

        set_loader();

        const form = new FormData();

        form.append('action', 'edd_truemail_verify_email');
        form.append('email', emailField.value);


        fetch(
            edd_global_vars.ajaxurl,
            {
                method: 'POST',
                body: form
            }
        ).then(
            response => response.json()
        ).then(response => {
            if (response === 0) {
                remove_loader();

                return;
            }

            if (response.data.status === 200 && response.data.success === true) {
                // Valid email address.
                emailField.classList.remove('edd-truemail-warning');
                emailField.classList.add('edd-truemail-success');
            } else if (response.data.status === 200 && response.data.success === false) {
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
                message.innerHTML = '<sup><em>' + response.data.message + '</em></sup>';
            } else {
                message.innerHTML = '';
            }

            remove_loader();
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