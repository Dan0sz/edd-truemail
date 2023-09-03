# Truemail for Easy Digital Downloads

This plugin for WordPress adds Truemail integration to Easy Digital Downloads' checkout.

## Requirements

* PHP 7.2 or higher
* WordPress 5.x or higher
* Easy Digital Downloads 3.x
* A working/reachable instance of [TrueMail](https://truemail-rb.org/#/about)

## Installation

Releases are listed [here](https://github.com/Dan0sz/edd-truemail/releases). Download the `edd-truemail.zip` file listed with every release in the **Assets** section to download a properly "WordPress-style" packed .zip-file.

## Configuration

This plugin's options are available under Downloads > Settings > Extensions > TrueMail:

1. Enter your Access Token, and
2. The public URL of your TrueMail instance, and
3. Hit Save.

That's it!

## How it works

This plugin adds two things to Easy Digital Downloads' checkout:

1. Instant verification/validation of the entered email address, with feedback:
   - It shows a green check in the email address field, if validation was successful.
   - It shows a red cross, along with an error message, if validation failed -- also in case of a timeout.
2. If a user tries to submit the form, even though validation failed, an EDD error message will be shown, saying: "The email address you entered is incorrect."
