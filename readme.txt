=== Correct Contact - Privacy-First Email Validation for WordPress ===
Contributors: Dan0sz
Tags: email validation, email verification, privacy, woocommerce, easy digital downloads
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.1.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Privacy-first email validation for WordPress. Validate emails in real-time using your own infrastructure. No third-party services, no API limits, full control.

== Description ==

**Stop losing customers to typos. Validate email addresses in real-time—without compromising privacy.**

Correct Contact is the **only WordPress email validation plugin** that runs entirely on **your own infrastructure**. No third-party services. No data sharing. No API limits. Just fast, reliable email validation that respects your users' privacy.

### 🔒 Privacy-First by Design

Unlike other email validation services that send your customers' data to external APIs, Correct Contact validates emails using a lightweight server **in your own DigitalOcean account**. You maintain complete control over your data and infrastructure.

**What this means for you:**
✅ **GDPR & Privacy Compliant** - Customer data never leaves your infrastructure
✅ **No Third-Party Dependencies** - You're not at the mercy of external services
✅ **Full Data Ownership** - Complete control over validation logs and data
✅ **No Vendor Lock-In** - Your infrastructure, your rules

### ⚡ Best-in-Class User Experience

Correct Contact provides **instant, real-time feedback** as users type their email address—catching typos before they hit submit.

**Visual Feedback:**
- ✅ **Green checkmark** - Email validated successfully
- ⚠️ **Orange warning** - Invalid email or typo detected
- 🔄 **Loading indicator** - Validation in progress
- 💬 **Helpful messages** - Clear guidance for users

**The result?** Fewer failed transactions, fewer support tickets, and happier customers.

### 🚀 Seamless Integration

Works out-of-the-box with:
- **WooCommerce** - Prevent checkout with invalid emails
- **Easy Digital Downloads** - Block purchases on validation failure
- **Any WordPress Form** - Validate any email field using CSS selectors
- **Contact Forms** - Gravity Forms, Contact Form 7, WPForms, and more

### 💰 Predictable, Affordable Pricing

**No per-validation fees. No usage limits. No surprises.**

Pay only for your DigitalOcean infrastructure (~$10/month for most sites). That's it. Validate 10 emails or 10,000 emails—the cost stays the same.

**Compare that to traditional services:**
- ZeroBounce: $16/month for 2,000 validations
- NeverBounce: $10 for 1,000 validations
- Hunter.io: $49/month for 5,000 validations

With Correct Contact, you get **unlimited validations** for a fraction of the cost.

### 🎯 Key Features

**Real-Time Validation**
- Instant feedback as users type
- Catches typos before form submission
- Validates syntax, domain, and mailbox

**Flexible Configuration**
- Target any email field using CSS selectors
- Enable/disable checkout blocking per platform
- Customize validation behavior

**Developer-Friendly**
- Clean, modern codebase
- PSR-4 autoloading
- Extensible architecture
- Well-documented code

**Automated Setup**
- One-click DigitalOcean integration
- Automatic server provisioning
- Guided setup wizard
- No technical expertise required

### 🛠️ How It Works

1. **Install the plugin** - Download and activate from WordPress
2. **Run the setup wizard** - Automatically creates your validation server
3. **Configure fields** - Add CSS selectors for email fields to validate
4. **Done!** - Real-time validation is now active on your site

The setup wizard handles everything: creating your DigitalOcean project, provisioning the server, installing Truemail (the validation engine), and configuring secure API access.

### 🌍 Powered by Truemail

Correct Contact uses [Truemail](https://truemail-rb.org/), a robust open-source email validation library that performs:
- **Syntax validation** - RFC-compliant email format checking
- **DNS validation** - Verifies domain exists and accepts email
- **SMTP validation** - Confirms mailbox exists (when possible)

### 📊 Perfect For

- **E-commerce stores** - Reduce failed orders and cart abandonment
- **Membership sites** - Ensure users can receive login credentials
- **Lead generation** - Improve email list quality
- **SaaS platforms** - Validate user registrations
- **Any WordPress site** - Catch typos in contact forms

### 🔐 Security & Reliability

- **Secure API communication** - Token-based authentication
- **Timeout handling** - Fails gracefully on network issues
- **Transient caching** - Reduces validation overhead
- **EU-based infrastructure** - DigitalOcean datacenters in Amsterdam, Frankfurt, London

### 💡 Why Choose Correct Contact?

**Other Email Validation Plugins:**
❌ Send data to third-party APIs
❌ Charge per validation
❌ Have monthly limits
❌ Can be blocked or rate-limited
❌ Require ongoing subscriptions

**Correct Contact:**
✅ Runs on your infrastructure
✅ Unlimited validations
✅ No usage limits
✅ Full control and privacy
✅ One-time setup, predictable costs

### 🎓 Documentation & Support

- **Setup Guide** - Step-by-step wizard walks you through everything
- **Configuration Docs** - Detailed documentation for advanced use cases
- **Developer Resources** - Code examples and integration guides
- **Community Support** - WordPress.org support forums

== Installation ==

### Automatic Installation

1. Log in to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Search for "Correct Contact"
4. Click **Install Now** and then **Activate**
5. Navigate to **Settings > Correct Contact**
6. Follow the setup wizard to create your validation server

### Manual Installation

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New > Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Click **Activate Plugin**
6. Navigate to **Settings > Correct Contact**
7. Follow the setup wizard

### Setup Wizard

The setup wizard will guide you through:

1. **Creating a DigitalOcean account** (if you don't have one)
2. **Generating an API token** (temporary, used only during setup)
3. **Selecting a datacenter region** (choose closest to your users)
4. **Provisioning your validation server** (automated, takes 2-3 minutes)
5. **Configuring email fields** (add CSS selectors for fields to validate)

**Cost:** The validation server runs on a basic DigitalOcean droplet (~$10/month). DigitalOcean bills you directly—Correct Contact adds no markup.

**New to DigitalOcean?** New accounts receive $200 in free credit for 60 days—more than enough to test the plugin risk-free.

== Frequently Asked Questions ==

= Do I need a DigitalOcean account? =

Yes. Correct Contact runs on your own infrastructure to ensure privacy and eliminate per-validation fees. The setup wizard makes it easy—even if you've never used DigitalOcean before.

= How much does it cost? =

The plugin itself is free. You pay only for the DigitalOcean infrastructure (~$10/month for a basic droplet). This covers unlimited email validations—no per-validation fees, no usage limits.

= Is this GDPR compliant? =

Yes! Since validation happens on your own infrastructure, customer data never leaves your control. This makes compliance much simpler compared to third-party validation services.

= Will this slow down my forms? =

No. Validation happens asynchronously as users type. The validation request doesn't block form submission. If validation times out, the form still works — it fails gracefully.

= What happens if the validation server is down? =

The plugin fails silently. If the validation server is unreachable or times out, users can still submit forms. This ensures your site remains functional even if validation is temporarily unavailable.

= Can I use this with any form plugin? =

Yes! Correct Contact works with any form that has an email field. Just add the CSS selector for the email field in the plugin settings. Works with Gravity Forms, Contact Form 7, WPForms, Ninja Forms, and more.

= Does this work with WooCommerce? =

Yes! Correct Contact has built-in WooCommerce integration. Enable "Prevent Purchase on Failure" in settings to block checkout when email validation fails.

= Does this work with Easy Digital Downloads? =

Yes! Correct Contact has built-in EDD integration. Enable "Prevent Purchase on Failure" in settings to block checkout when email validation fails.

= Can I validate multiple email fields? =

Yes! Add multiple CSS selectors (comma-separated) in the plugin settings. The plugin will validate all matching fields.

= What email validation checks are performed? =

Correct Contact uses Truemail to perform:
- **Syntax validation** - Checks email format against RFC standards
- **DNS validation** - Verifies the domain exists and has MX records
- **SMTP validation** - Confirms the mailbox exists (when possible)

= Can I remove the DigitalOcean API token after setup? =

Yes! The API token is only needed during setup. Once your validation server is created, you can safely delete the token. The plugin even prompts you to remove it.

= What if I already have a Truemail instance? =

Perfect! Skip the setup wizard and manually enter your Truemail instance URL and access token in **Settings > Correct Contact > Advanced**.

= Can I use a different hosting provider? =

Currently, the automated setup wizard only supports DigitalOcean. However, you can manually deploy Truemail on any server and configure Correct Contact to use it.

= How do I uninstall the plugin? =

Deactivate and delete the plugin from WordPress. Your DigitalOcean validation server will continue running (and billing) until you manually delete it from your DigitalOcean account.

= Where can I get support? =

- **WordPress.org Forums** - Community support
- **Plugin Documentation** - Setup guides and troubleshooting
- **GitHub Issues** - Bug reports and feature requests

== Screenshots ==

1. **Real-time validation** - Green checkmark shows valid email
2. **Typo detection** - Orange warning catches invalid emails
3. **Setup wizard** - Automated DigitalOcean integration
4. **Settings page** - Configure validation behavior
5. **Field configuration** - Add CSS selectors for email fields
6. **WooCommerce integration** - Block checkout on validation failure

== Changelog ==

= 1.1.8 =
* Improved setup wizard UX
* Added region selection for DigitalOcean datacenters
* Enhanced error handling during provisioning
* Updated documentation

= 1.1.7 =
* Added WooCommerce support
* Improved validation feedback messages
* Fixed transient caching issues
* Performance optimizations

= 1.1.6 =
* Added setup wizard for automated server provisioning
* Improved admin UI with tabbed settings
* Enhanced security with nonce verification
* Code refactoring for better maintainability

= 1.1.5 =
* Initial public release
* Real-time email validation
* Easy Digital Downloads integration
* Configurable field selectors

== Upgrade Notice ==

= 1.1.8 =
This version adds datacenter region selection and improves the setup wizard experience. Recommended for all users.

= 1.1.7 =
WooCommerce support is now available! Update to validate emails in WooCommerce checkout forms.

== Privacy Policy ==

Correct Contact is designed with privacy as a core principle:

- **No third-party services** - Validation happens on your own infrastructure
- **No data sharing** - Customer emails are never sent to external APIs
- **No tracking** - The plugin doesn't collect analytics or usage data
- **Full control** - You own and control all validation data

The plugin communicates only with your own Truemail instance running on your DigitalOcean account.

== Credits ==

Correct Contact is powered by [Truemail](https://truemail-rb.org/), an open-source email validation library.

Developed by [Daan van den Bergh](https://daan.dev/) with ❤️ for the WordPress community.