document.addEventListener('DOMContentLoaded', () => {
    var emailField = document.getElementById('edd-email');
    var message = document.createElement('div');

    message.classList.add('edd-truemail-message');
    emailField.insertAdjacentElement('afterend', message);

    emailField.addEventListener('blur', (e) => {
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
        ).then(data => {
            if (data.success) {
                emailField.classList.remove('edd-truemail-error');
                emailField.classList.add('edd-truemail-success');

                message.innerHTML = '<sup>The email address you entered is <span style="color: green;">valid</span></sup>';
            } else {
                emailField.classList.remove('edd-truemail-success');
                emailField.classList.add('edd-truemail-error');

                message.innerHTML = '<sup>The email address you entered is <span style="color: red;">invalid</span></sup>';
            }

            remove_loader();
        });
    });

    function set_loader() {
        var emailField = document.getElementById('edd-email');
        emailField.classList.remove('edd-truemail-success');
        emailField.classList.remove('edd-truemail-error');
        emailField.classList.add('edd-truemail-loading');
    }

    function remove_loader() {
        var emailField = document.getElementById('edd-email');
        emailField.classList.remove('edd-truemail-loading');
    }
});