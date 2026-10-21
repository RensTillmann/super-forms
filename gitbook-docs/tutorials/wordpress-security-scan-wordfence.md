---
description: >-
  Check PHP security with your host, install Wordfence, run a WordPress security
  scan, and review findings safely with help when needed.
---

# Scan your WordPress site with Wordfence

This guide is for WordPress site owners, including sites that currently use Super Forms or had it installed in the past. It walks you through checking PHP security with your host, a first scan, safe handling of results, and ongoing protection. You can start with Wordfence Free; purchasing a paid license is not required to run a scan.

{% hint style="warning" %}
**Updating is not the same as cleaning an infected site.** Security updates address vulnerabilities, but do not necessarily remove files, accounts, or database changes left by an earlier compromise. Removing a previously vulnerable plugin does not undo those changes either. If your site previously ran an affected version of WordPress, Super Forms, or another component, investigation may still be appropriate even if that component is now updated or removed. A scan with no findings is useful information, not proof that a site is secure.
{% endhint %}

## 1. Prepare without losing evidence

* Make sure you can sign in as a WordPress administrator and contact your hosting provider if needed. For a managed site or WordPress Multisite, ask its administrator to handle installation and server changes.
* Preserve a backup of **both files and the database** before changing or removing anything. If compromise is suspected, ask your host to preserve access/error logs and an isolated copy of the current site. Store these securely outside the public website.
* A backup made from a compromised site is **evidence, not a known-clean restore point**. Do not overwrite your older backups or restore one without checking it.
* Keep WordPress, plugins, themes, and Wordfence updated using trusted sources. For Super Forms, see [How to update the plugin](how-to-update-the-plugin.md). If there are already signs of an intrusion, involve your host promptly so evidence preservation and urgent containment can happen together.
* If you already use a security plugin or your host manages Wordfence, coordinate with them rather than installing several security plugins at once.

Unexpected administrator accounts, redirects, spam, unfamiliar files, or a hosting malware warning deserve attention immediately. **Do not wait for a scan to finish before asking for help with an active compromise.** See [If an infection is suspected](#if-an-infection-is-suspected) below and Wordfence's [compromised-site guidance](https://www.wordfence.com/help/if-your-site-is-hacked/).

### Check PHP with your hosting provider

**PHP is the server software that runs WordPress.** Updating WordPress, Super Forms, other plugins, or themes does not normally update PHP or apply its security fixes. Your hosting provider or server maintainer must handle that separately. **Wordfence scanning neither patches PHP nor certifies the server's patch status**, even when a scan finishes with no findings.

PHP's [security advisory](https://www.php.net/index.php#2026-10-21-5) covers two critical vulnerabilities that can allow remote code execution without logging in: [CVE-2026-109618](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w) and [CVE-2026-109619](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp). The advisory includes all PHP installations serving web requests in its risk scope. This does not establish that your site has been compromised, but it does warrant prompt confirmation of patches with your host.

These are the **first fixed upstream releases for both CVEs** on the listed branches, not a permanent list of the latest releases or proof that every other security issue is fixed:

| PHP branch | First upstream release fixing both CVEs |
| --- | --- |
| 8.2 | 8.2.35 |
| 8.3 | 8.3.36 |
| 8.4 | 8.4.27 |
| 8.5 | 8.5.12 |

Source: [PHP's advisory and fixed releases](https://www.php.net/index.php#2026-10-21-5). A distribution or hosting vendor may **backport** the fixes into its own package without changing the upstream version to one in this table. A version string alone cannot confirm whether those fixes are present; ask for the vendor's advisory and the installed package/build that includes them.

1. In your WordPress dashboard, open **Tools > Site Health > Info**, expand **Server**, and find **PHP version**. Note it for your host; this screen reports information rather than changing PHP. If the screen is unavailable, ask the administrator or host to check. See WordPress's [Site Health documentation](https://wordpress.org/documentation/article/site-health-screen/) and [PHP version-check instructions](https://wordpress.org/support/update-php/#how-to-check-your-php-version).
2. Ask the host to confirm the PHP runtime **actually serving this website's web requests**, including all serving instances or worker pools. A command-line `php -v` result or the hosting control panel's selected default may refer to a different PHP installation or setting; neither alone proves which patched runtime visitors reach. Do not create a publicly accessible `phpinfo()` page or publish a full diagnostic report.
3. Ask whether that runtime has fixes for **both CVEs above and other applicable PHP security issues**. Have the host confirm the installed release or vendor package against its security advisory. A general “PHP 8.3 or higher” recommendation, a green Site Health status, or a clean Wordfence scan is not that confirmation.
4. If fixes are missing, have the host promptly apply a patched release on a supported PHP branch, or a maintained vendor package with confirmed backported fixes. Ask them to ensure **all running web workers use the patched runtime**, not merely that a new package has been installed or a control-panel setting changed. The host should manage any required worker replacement or restart.
5. If changing PHP branches is necessary, coordinate WordPress, theme, plugin, and custom-code compatibility with the maintainer first. Preserve backups and test the intended branch on staging where possible; agree a recovery plan without leaving production on an unpatched runtime. **Do not blindly switch to the newest PHP branch.** After applying the update, have the host reconfirm the serving runtime and test the website and forms, including submissions, file uploads, confirmations, and email delivery, using non-sensitive test data.

PHP 8.1 and older are **end-of-life upstream**. PHP's exceptional source backports for 7.4, 8.0, and 8.1 address these CVEs but do not restore ongoing upstream support. If your host relies on a vendor-maintained older branch, ask it to confirm both security coverage and its support lifetime; otherwise plan a compatible move to a supported branch. Check PHP's [current support schedule](https://www.php.net/supported-versions.php) when choosing a branch, and WordPress's [PHP update preparation guidance](https://wordpress.org/support/update-php/#before-you-update-your-php-version) for backup and compatibility planning.

## 2. Install Wordfence and finish license setup

1. In your WordPress dashboard, open **Plugins > Add New** (shown as **Add New Plugin** in some versions).
2. Search for **Wordfence** and choose the official **Wordfence Security** plugin, not the separate Login Security-only plugin. Click **Install Now**, then **Activate**.
3. Follow **Get Your Wordfence License**. For the free edition, choose **Get a Free License**. Enter a working email address, review the terms, and complete registration. Subscribing to the mailing list is optional.
4. Open the license email and follow **Install License**. Wordfence says the automatic installation link must be used within 24 hours and in the same browser used for registration. If it expires or fails, use **Install an existing license** on the WordPress Plugins page and paste the key from the email.
5. Confirm that the license is installed and you can open **Wordfence > Scan**. Merely receiving a license email does not finish setup. If Wordfence was already installed, check its existing license and settings instead of reinstalling it.

If the license email does not arrive, check spam and the address you entered. Use a separate license for each separate WordPress installation; see the official license documentation for Multisite and staging exceptions.

Sources: [installation](https://www.wordfence.com/products/wordfence-free/install/) and [license setup and troubleshooting](https://www.wordfence.com/help/api-key/).

{% hint style="info" %}
**Free versus paid:** Wordfence Free receives newly released firewall rules and malware signatures **30 days after paid customers**. Paid editions receive these in real time and include additional features. Do not postpone updating or scanning while deciding whether to upgrade, and do not assume either edition can detect every infection or block every attack. See [Wordfence Free](https://www.wordfence.com/help/wordfence-free/).
{% endhint %}

### Check the firewall's initial status

Wordfence may initially put its firewall in **Learning Mode**. This is not full firewall protection: suspicious-looking requests can be added to an allowlist instead of blocked. Wordfence specifically advises **not using Learning Mode on a recently hacked site or a site currently under attack**.

Open **Wordfence > Firewall > All Firewall Options > Basic Firewall Options** and check **Web Application Firewall Status**. For a recently hacked or actively attacked site, use **Enabled and Protecting**, with your host or security specialist's help if necessary. Do not blindly leave an exposed site learning traffic. Detailed firewall optimization is optional and can wait until after the scan; see [the firewall section](#7-optional-optimize-the-firewall-and-test-your-forms).

Source: [Firewall Learning Mode](https://www.wordfence.com/help/firewall/learning-mode/).

## 3. Set a monitored alert address

1. Open **Wordfence > All Options** and expand **General Wordfence Options**.
2. Set **Where to email alerts** to an address you regularly check. You can enter multiple addresses separated by commas.
3. Review **Email Alert Preferences**. Keep notifications for security scan findings enabled, particularly critical and high-severity findings, and save your changes.
4. Check that site email delivery works and check spam folders. Also review the Wordfence dashboard directly: email limits, spam filtering, or delivery failures can prevent an alert from reaching you.

Source: [Global Options and Email Alert Preferences](https://www.wordfence.com/help/dashboard/options/).

## 4. Choose the scan settings and understand coverage

Open **Wordfence > Scan > Scan Options and Scheduling**. Choose **Standard Scan**, then **Save Changes**. This is Wordfence's recommended starting point for most sites.

Before starting, review these settings:

* **General Options:** consider enabling **Scan theme files against repository versions for changes** and **Scan plugin files against repository versions for changes**. Wordfence's documentation notes that these comparisons are not included in Standard Scan. Turning them on may change the scan type label to **Custom Scan**; that is expected.
* **Advanced Options:** inspect **Exclude files from scan that match these wildcard patterns**. Excluded files are not checked by that scan. Ask the person who configured any existing exclusions why they are there; do not add exclusions for uploads, plugins, or other large areas simply to get a successful result.
* Ask your host about any reported unscanned paths, unreadable files, other sites, or applications in your hosting account. A normal scan covers the WordPress root files and standard WordPress directories, but is **not a complete audit of the entire hosting account or server**. The option **Scan files outside your WordPress installation** broadens scanning to additional subdirectories; it does not guarantee server-wide coverage and can increase resource usage.

The scanner looks for known malware patterns, suspicious code and URLs, changed files, vulnerable/outdated components, and some suspicious database content. It does **not inspect every database table for every possible infection**. Its findings depend on enabled checks, accessible files, exclusions, and available signatures. It does **not patch PHP or certify server patch status**; follow [Check PHP with your hosting provider](#check-php-with-your-hosting-provider) separately even if the scan has no findings.

**Premium and custom plugins:** malware scanning can inspect commercial plugin files, including Super Forms, but comparison and automatic repair against WordPress.org originals are different features. Do not expect Wordfence to have an authoritative clean copy of premium Super Forms files. A file that cannot be compared or repaired is not automatically malicious, nor is it automatically safe.

If compromise is strongly suspected, ask a specialist whether to use **High Sensitivity** and broader scanning. High Sensitivity examines additional file types as executable code, takes more resources, and can produce false positives. It is not a substitute for an investigation.

Sources: [scan types](https://www.wordfence.com/help/scan/), [scan options and exclusions](https://www.wordfence.com/help/scan/options/), and [coverage limitations after a compromise](https://www.wordfence.com/help/if-your-site-is-hacked/).

## 5. Run the scan and wait for completion

1. Return to **Wordfence > Scan** and click **Start New Scan**.
2. Watch the scan stages and **Scan Detailed Activity**. Large sites and resource-limited hosting can take longer; a quiet screen alone does not establish success or failure.
3. Wait for the scan to report completion. Review warnings, skipped checks, and errors as well as the findings. A failed or interrupted scan is incomplete, even if it has already found some issues.
4. Record the completion status and findings for your own follow-up. The percentage/status circles describe enabled capabilities, not a certificate that the website is safe. Some capabilities require a paid license.

If you see **Live Updates Paused** after switching windows, this may only mean that the screen stopped refreshing, not that the scan stopped. Return to the page and check its current status.

Sources: [scan progress and results](https://www.wordfence.com/help/scan/) and [live update behavior](https://www.wordfence.com/help/dashboard/options/).

### If a scan fails, stalls, or cannot start

* Save the exact error and the relevant **Scan Detailed Activity** or **View activity log** messages. Confirm that Wordfence is up to date.
* For resource problems, try **Use low-resource scanning** under **Scan Options and Scheduling > Performance Options**. This spreads the work out and can take longer without deliberately removing checks.
* Ask your host to check memory/process limits, disk space, loopback connections (the site's ability to contact itself), scheduled tasks, and connectivity to Wordfence. **Wordfence > Tools > Diagnostics** can help them investigate. Adjust execution or memory settings only within the host's supported limits.
* Do not disable the firewall, TLS certificate verification, or other security controls, or broadly exclude files just to make the scan finish. Have your host resolve the specific failure and rerun the intended scan.
* If it still fails, contact Wordfence support with the error and relevant details. Do not publish full diagnostic reports, configuration files, database backups, license keys, passwords, or customer submissions in a public forum. Use a secure private support channel for sensitive material.

See Wordfence's [Scan Troubleshooting](https://www.wordfence.com/help/scan/troubleshooting/) and [scan performance options](https://www.wordfence.com/help/scan/options/#performance-options). Unresolved scan failures require follow-up; they are not a clean result.

## 6. Review findings before changing files

Start with **Critical**, then **High** severity findings. Read each item's explanation: a vulnerable plugin warning, a modified file, and confirmed malware need different responses.

| Finding or action | Safe next step |
| --- | --- |
| Outdated or vulnerable software | Update from the official source, confirm the installed version, and rescan. An update does not establish whether the vulnerability was exploited earlier. |
| Modified or suspicious file | Use **View file** or **View differences**, where available, and ask your developer or host to distinguish legitimate customizations from unauthorized changes. Do not judge safety by a filename or how readable the code looks. |
| **Repair file** | Only repair after preserving a copy and understanding the change. Repair replaces the file with an available original and can erase legitimate customizations. Wordfence supports this for WordPress core and recognized WordPress.org plugins/themes, not arbitrary premium or custom code. |
| Premium Super Forms or another commercial/custom component | Ask the vendor or developer to compare with a trusted package and plan a clean replacement if needed, retaining legitimate customizations and site data. Do not delete individual files simply because Wordfence cannot repair them. |
| **Delete file** | Use only when the file has been confirmed unwanted and its removal is understood. Preserve evidence first. Deleting a required plugin/theme file can break the site; it does not clean every location an attacker may have changed. |
| **Ignore** | Use only after confirming a false positive, and document why. Review ignored results periodically. Ignoring hides the finding from normal results; it does not remove a threat. |
| **Mark as fixed** | This only changes the current results view. It does not repair anything, and an unresolved issue can return in the next scan. |

{% hint style="warning" %}
Do **not** use **Delete all deletable files** as a routine cleanup step, and do not bulk-repair files you have not reviewed. Back up legitimate customizations before any replacement. If you cannot tell whether a finding is malicious, stop before altering it and ask for qualified help.
{% endhint %}

After addressing findings, run another scan and test the website. A completed scan with no remaining findings does not rule out hidden persistence, compromised credentials, or data access that already occurred.

Sources: [working with scan results](https://www.wordfence.com/help/scan/#working-with-scan-results) and [detailed scan result explanations](https://www.wordfence.com/help/scan/scan-results/).

## 7. Optional: optimize the firewall and test your forms

**Do not delay scanning to complete this step.** Firewall optimization is separate from scanning. It enables **Extended Protection**, loading the firewall before WordPress rather than as an ordinary plugin. It can require server configuration changes, so involve your host if you are unsure.

1. Open **Wordfence > Firewall > All Firewall Options**. Under **Protection Level**, choose **Optimize the Wordfence Firewall**.
2. Download the configuration-file backups requested by the wizard and store them safely outside the website. Follow the detected server configuration only if it is appropriate; ask your host about manual or unsupported setups rather than guessing.
3. Complete the wizard and check that **Protection Level** becomes **Extended Protection**. Some hosts take up to five minutes to apply the change. If the site breaks or optimization does not take effect, ask your host to use the saved configuration and the official troubleshooting guidance.
4. Separately verify **Web Application Firewall Status** is **Enabled and Protecting**. Extended Protection and Learning Mode describe different settings; optimizing alone does not mean blocking is active.
5. Test normal visitor activity, especially Super Forms submissions, required-field validation, file uploads, confirmations, email delivery, and any login/payment flows you use. Test as a logged-out visitor as well as an administrator, using non-sensitive test data and payment test facilities where applicable.

For a legitimate action that is blocked, check **Wordfence > Tools > Live Traffic**, using the **Blocked by Firewall** filter. Ask support to review the precise request before allowing it. Do not disable rules site-wide, broadly allowlist addresses/URLs, or turn on Learning Mode simply to make a form work. If controlled Learning Mode is appropriate on a site that is not compromised or under attack, set an end time, exercise trusted actions, review what was allowed, and return to **Enabled and Protecting**. Never leave it learning indefinitely.

Sources: [firewall optimization](https://www.wordfence.com/help/firewall/optimizing-the-firewall/), [firewall settings](https://www.wordfence.com/help/firewall/options/), and [Learning Mode and false-positive blocks](https://www.wordfence.com/help/firewall/learning-mode/).

## 8. Enable two-factor authentication without locking yourself out

1. Open **Wordfence > Login Security > My 2FA** for your own account.
2. Add the displayed QR code to a compatible authenticator app.
3. **Download recovery codes before activating**, and store them securely somewhere other than the website. Keep the QR code and setup secret private too.
4. Enter the current six-digit authenticator code and click **Activate**.
5. Keep your existing session open while testing a login in a separate browser or private window. Arrange the same setup for other administrators.

A recovery code can replace an authenticator code if you lose the device; each recovery code works once. Generating new recovery codes invalidates the old ones. If you lose both the device and the codes, contact a verified administrator or your host for controlled recovery rather than disabling protection without a recovery plan.

Wordfence notes that its 2FA support is intended for the default WordPress login and supported WooCommerce integration; **custom login forms may not be compatible**. If you use a Super Forms login form or another custom login page, test that flow and do not assume it is protected merely because 2FA is enabled.

Source: [Two-Factor Authentication and recovery](https://www.wordfence.com/help/tools/two-factor-authentication/).

## 9. Keep automatic scanning and follow-up active

Under **Wordfence > Scan > Scan Options and Scheduling**, keep scheduled scanning enabled and review that scans actually complete.

Wordfence documents a **daily quick scan and a full scan every 72 hours for Free**. Paid licenses have a daily quick scan and, by default, a full scan every 24 hours, with customizable scheduling. A quick scan is a limited set of checks, not a full malware scan. Scheduled starts may be delayed; hosting and WordPress scheduled-task problems can also prevent scans from running. You can start a manual scan when needed rather than waiting for the next scheduled one.

Keep alert addresses current, install updates promptly, review new findings, and maintain tested backups. Recheck forms after relevant plugin or firewall changes.

Source: [Scan Scheduling](https://www.wordfence.com/help/scan/scheduling/).

## If an infection is suspected

Contact your hosting provider or a qualified WordPress incident-response specialist. Share the findings privately and ask for help with:

* **Containment and evidence:** preserve files, database, logs, and scan findings before cleanup where practical. If visitors or data are at risk, have the host restrict access safely while preserving evidence. Do not leave infected backups or suspicious files publicly downloadable.
* **Scope and cleanup:** investigate the entry point, unauthorized accounts, altered files, database content, scheduled tasks, other sites in the same account, and any means the attacker could use to regain access. Replacing one plugin is not a complete investigation.
* **Credentials and sessions:** from a trusted device, coordinate resetting affected WordPress, hosting, SFTP/SSH, and database credentials, plus any exposed email, payment, or other integration secrets. Ask the responder to invalidate compromised sessions and coordinate configuration changes so the site does not break. New secrets entered into a still-compromised site may be stolen again; credential changes must be part of containment and cleanup, not the only response.
* **Recovery:** use verified clean software and assessed backups, preserve legitimate customizations and required business data, patch the entry point, then rescan and test. Ask for review of possible data exposure and any notification obligations that apply to your business.

Wordfence's [If Your Site Is Hacked](https://www.wordfence.com/help/if-your-site-is-hacked/) explains what its plugin can and cannot restore and the available escalation options. A scanner is one tool in this process, not a guarantee that a compromise has been fully resolved.
