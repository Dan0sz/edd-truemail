# Correct Contacts for WordPress

This plugin adds email address validation to any field in any WordPress form.

## Requirements

* PHP 7.2 or higher
* WordPress 5.x or higher
* A working/reachable instance of [TrueMail](https://truemail-rb.org/#/about)

## Installation

Download the plugin and install it via the WordPress admin area.

## Configuration

This plugin's options are available under Settings > Correct Contacts:

1. Enter your Access Token,
2. The public URL of your TrueMail instance,
3. Add the CSS selectors for the email fields you want to validate, and
4. Hit Save.

That's it!

## How it works

This plugin adds the following to the configured fields:

1. Instant verification/validation of the entered email address, with feedback:
    - It shows a green check in the email address field if validation was successful.
    - It shows an orange exclamation mark, along with an error message, if validation failed.
    - In case of a timeout, it will fail silently.
2. If the **Prevent Purchase on Failure** option is enabled, and the field is within an Easy Digital Downloads checkout form, users will not be able to finalize their purchase if the email address is incorrect. In case of a timeout, it will fail silently.
