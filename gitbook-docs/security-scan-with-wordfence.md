---
description: >-
  Install Wordfence, complete a security scan, review findings safely, and know
  when to involve your hosting provider or a security professional.
---

# Run a WordPress security scan with Wordfence

Wordfence can help you find known malware, suspicious changes, vulnerable software, and other security issues on your WordPress site. This guide walks you through a scan and the decisions that follow it. You can run a scan with **Wordfence Free**; purchasing Premium is not required. [Wordfence Free documentation](https://www.wordfence.com/help/wordfence-free/)

{% hint style="warning" %}
**A completed scan with no findings is not proof that a site has never been compromised or is completely clean.** Scanning, patching, and cleaning are different tasks. Updating a vulnerable plugin does not remove an attacker's existing files, accounts, or other changes. A plugin being inactive or removed now does not rule out an earlier compromise. If you already see unexpected administrators, redirects, spam, or other suspicious activity, follow [If you suspect a compromise](#if-you-suspect-a-compromise), even if a scan reports no new problems. [Wordfence: what the plugin will and won't do](https://www.wordfence.com/help/if-your-site-is-hacked/)
{% endhint %}

## 1. Back up before making changes

Before installing security software, updating your site, repairing files, or deleting anything, make a backup of **both the WordPress files and the database**. Downloading the site's folders alone does not normally back up its database. Include plugins, themes, uploads, configuration files, and any form attachments stored outside the usual WordPress folders. Store the backup securely away from the public website, and confirm with your host or backup provider how you would restore it. [WordPress backup guidance](https://developer.wordpress.org/advanced-administration/security/backup/)

If you suspect an infection, preserve a copy of the current site and relevant logs for investigation. Label that snapshot as potentially compromised: it is evidence and a recovery reference, not a known-clean restore point. Older backups can also contain an infection, so do not restore one without checking it. Ask your host for help if necessary. [WordPress hacked-site guidance](https://wordpress.org/documentation/article/faq-my-site-was-hacked/) · [Wordfence incident guidance](https://www.wordfence.com/help/if-your-site-is-hacked/)

For routine maintenance, keep WordPress, Wordfence, your themes, and your other plugins updated using trusted sources. For Super Forms, use our [Downloads](downloads.md) page and choose the release for your existing channel. **Do not install a 6.3 release on a site running 6.4.** If the site may already be compromised, coordinate updates and cleanup with your host rather than treating an update as the entire response. [WordPress security guidance](https://developer.wordpress.org/advanced-administration/security/hardening/)

## 2. Install the official Wordfence plugin and complete licensing

1. Sign in to your own WordPress administration area as an authorized administrator.
2. Open **Plugins > Add New** and search for **Wordfence Security**. Check that it matches the official [Wordfence Security – Firewall, Malware Scan, and Login Security listing](https://wordpress.org/plugins/wordfence/), whose plugin address ends in `/plugins/wordfence/`.
3. Select **Install Now**, then **Activate**. If you need a ZIP instead, obtain it from that official listing, not a third-party download site. On multisite, have the network administrator follow Wordfence's network-activation instructions.
4. When prompted, select **Get Your Wordfence License**. On Wordfence's website, select **Get a Free License**, or choose a paid plan if you prefer one.
5. For Free, enter a valid email address you monitor, choose whether to subscribe to the optional mailing list, read and accept the terms, and select **Register**. Open the license email and follow its **Install License** link using the browser in which you requested the license. Confirm that it returns you to the correct website. Registration alone does not complete installation.
6. If you already have a license, or the email's automatic installation link has expired or fails, select **Install an existing license** on your WordPress **Plugins** page. Copy the key from your Wordfence account or license email, accept the applicable terms, and select **Install License**. Return to Wordfence and confirm that it recognizes the installed license.

The precise onboarding screens can change. Use the official [license-key instructions](https://www.wordfence.com/help/api-key/) if a prompt or registration email differs from what you expect. Never share your WordPress password or license key in a public support post. [WordPress plugin installation](https://wordpress.org/documentation/article/manage-plugins/) · [Wordfence installation instructions](https://wordpress.org/plugins/wordfence/#installation)

{% hint style="info" %}
**Free versus paid:** Wordfence Free includes scanning, a firewall, login security, and alerts. New firewall rules and malware signatures reach Free users **30 days after their release to Premium customers**. Premium provides real-time rule/signature updates and additional features. That delay is important when choosing protection, but it does not mean Free cannot scan your site, and neither plan guarantees detection of every compromise. [Free features and delay](https://www.wordfence.com/help/wordfence-free/) · [License choices](https://www.wordfence.com/help/api-key/)
{% endhint %}

## 3. Set up alerts and check visitor IP detection

Open **Wordfence > Dashboard > Global Options** and find **Where to email alerts**. Set an address that you or your site maintainer actually monitor. Review **Email Alert Preferences** so important scan findings and security events reach the person responsible for responding. Use the test-email tool under **Wordfence > Tools > Diagnostics** to check delivery; investigate missing mail with your host rather than assuming a quiet inbox means there are no issues. [Alert configuration and delivery](https://www.wordfence.com/help/dashboard/alerts/) · [Global Options](https://www.wordfence.com/help/dashboard/options/)

Most sites should keep the default **How does Wordfence get IPs** setting. If your site uses a CDN, reverse proxy, or load balancer and Wordfence reports the wrong visitor IP, ask your host or proxy provider which setting and trusted-proxy ranges apply. Compare **Your IP with this setting** with your actual public IP before saving a change. Incorrect detection can make all visitors appear to share one address and cause legitimate visitors to be blocked. Do not select a forwarded-IP header at random: an untrusted header can be spoofed. Do not allowlist an entire proxy/CDN range to bypass all firewall rules. [Visitor-IP detection and trusted proxies](https://www.wordfence.com/help/dashboard/options/)

## 4. Review the firewall without breaking legitimate forms

The firewall and scanner have separate jobs. Wordfence initially uses **Basic WordPress Protection**, loading as a normal WordPress plugin. Firewall optimization enables **Extended Protection**, loading the firewall earlier, before WordPress and directly accessed PHP files. [Basic versus Extended Protection](https://www.wordfence.com/help/firewall/optimizing-the-firewall/)

To configure it:

1. Open **Wordfence > Firewall > All Firewall Options**.
2. Under **Protection Level**, select **Optimize the Wordfence Firewall**, or follow the optimization notice in your dashboard.
3. Follow the detected server setup unless your host advises otherwise. **Download the configuration-file backups offered by the wizard before continuing.** Depending on the server, optimization changes `.htaccess`, `.user.ini`, or `php.ini`.
4. If the wizard needs manual configuration, or the site stops working, involve your host. Do not paste generic server directives into production configuration. Ask the host to ensure configuration files such as `.user.ini` are not publicly readable where applicable.
5. Confirm that the protection level changes to **Extended Protection**. Some hosts take a few minutes to apply the change. If it remains Basic, consult the [optimization instructions and host-specific guidance](https://www.wordfence.com/help/firewall/optimizing-the-firewall/).

### Learning Mode is not the same as active protection

In **Learning Mode**, Wordfence learns exceptions for requests that would otherwise match firewall rules. Certain protections remain active, but the site is not protected from some complex attacks. **Do not use Learning Mode on a recently hacked site or while it is under attack.** Seek help instead. A new installation normally starts with a seven-day learning period; a manually enabled learning period needs an end date or a deliberate switch back. [Learning Mode documentation](https://www.wordfence.com/help/firewall/learning-mode/)

For a routine installation on a site without signs of compromise, exercise normal site features during the learning period. For Super Forms, test representative real workflows: submit a form as a logged-out visitor, upload a permitted test attachment, and check that the expected entry, notification, and any configured redirect or integration are received. Also test applicable login, registration, checkout, and administrator workflows. Use non-sensitive test data.

After testing, set **Web Application Firewall Status** to **Enabled and Protecting**, and test those workflows again. Extended Protection describes *when* the firewall loads; Enabled and Protecting describes its operating state. Check both.

If a legitimate request is blocked, review **Wordfence > Tools > Live Traffic**, filtering for **Blocked by Firewall**. Reproduce and identify the specific safe request before considering a narrow allowlist entry. Do not disable the firewall permanently, allow every request to a form endpoint, or approve a request just because someone sent you a link. Ask your host, developer, or Wordfence support when unsure. [Learning and safe handling of blocked requests](https://www.wordfence.com/help/firewall/learning-mode/)

## 5. Run a scan and wait for completion

1. Open **Wordfence > Scan**, then **Scan Options and Scheduling**.
2. Start with **Standard Scan**, which Wordfence recommends for most sites, and save your settings.
3. Under **General Options**, review **Scan plugin files against repository versions for changes** and **Scan theme files against repository versions for changes**. Wordfence documents these comparisons as additional checks not included in Standard Scan; enable them when appropriate for your site. Changing individual checks may change the scan type to **Custom**.
4. Keep relevant malware, core-integrity, vulnerability, and content checks enabled. Review existing exclusions. Do not exclude uploads or an entire plugin folder just to hide a warning. Avoid turning on every intensive option: wider scanning and treating images/binary files as executable can consume substantially more resources.
5. Return to the Scan page and select **Start New Scan** (wording can vary by version).
6. Watch the scan stages and activity log. **Wait until Wordfence explicitly reports that the scan has completed.** Starting it, seeing some results, or seeing a stage pass is not the same as completing the scan.
7. Review all results, including existing and ignored findings. A message saying no **new** problems were found does not clear previously reported or ignored issues.

[Scan types, stages, and results](https://www.wordfence.com/help/scan/) · [Individual scan settings and exclusions](https://www.wordfence.com/help/scan/options/)

{% hint style="warning" %}
**Completion is different from coverage.** A completed scan covers the configured checks and accessible paths, not necessarily every file, database table, other website, or server component on the hosting account. Review skipped paths and exclusions. The status circles also reflect enabled features and license capabilities, not a percentage guarantee that your site is clean. Ask your host about account-wide investigation when needed. [Scan scope and options](https://www.wordfence.com/help/scan/options/) · [Detection and cleanup limitations](https://www.wordfence.com/help/if-your-site-is-hacked/)
{% endhint %}

### If the scan stops, fails, or never completes

Treat it as an **incomplete scan**, not a clean result. Save the error message and the relevant activity-log details for your host or Wordfence support. Do not publish logs containing private paths, customer information, or credentials.

Wordfence's [scan troubleshooting guide](https://www.wordfence.com/help/scan/troubleshooting/) covers hosting process limits, memory limits, loopback connections, and connectivity to its scanning services. Ask your host to investigate those causes. **Low-resource scanning** can spread the work over a longer period; a **Limited Scan** may help diagnose resource restrictions, but runs fewer checks and is not equivalent to completing your intended scan. Work with your host before changing memory or execution limits. Do not disable security controls or broadly exclude files merely to force a successful-looking result. After addressing the cause, run the intended scan again and confirm completion. [Performance options](https://www.wordfence.com/help/scan/options/)

## 6. Review findings individually before repair or deletion

Start with **Critical**, then **High** severity findings. Open each issue and read its explanation. A changed or unknown file is a reason to investigate, **not automatic proof of malware**: legitimate customizations, hosting changes, and leftover files can trigger findings. Conversely, tidy-looking code is not proof that a file is safe. [Understanding scan results](https://www.wordfence.com/help/scan/scan-results/)

| Finding | Safe next step |
| --- | --- |
| Known-vulnerable or outdated software | Back up, obtain a trusted update, and test the site. If exploitation is suspected, investigate and clean up as well as patching. |
| Modified WordPress.org core, plugin, or theme file | Use **View Differences** where available. Ask your developer or host whether the change is intentional before replacing it. |
| Unknown file or possible malware | Use **View File** to inspect the source through Wordfence, rather than visiting or executing the suspicious file. Preserve a copy and obtain expert help if its purpose is unclear. |
| Unexpected administrator account | Check with the site owner, host, and authorized maintainers. If it was not authorized, treat it as a possible compromise, not merely a housekeeping issue. |
| Exposed configuration, backup, or log file | Ask your host to remove public access safely. Do not delete active configuration files simply because they are listed. |

**Repair File overwrites the file with the reference version and can destroy intentional changes.** Back up the affected file and confirm that the reference is appropriate before repairing it. **Ignore** and **Mark as Fixed** do not clean anything; use them only after investigation and actual resolution where required. [Result actions and limitations](https://www.wordfence.com/help/scan/) · [Specific result explanations](https://www.wordfence.com/help/scan/scan-results/)

{% hint style="warning" %}
**Do not bulk-delete or blindly repair scan findings.** Do not use **Delete all deletable files** as a general cleanup step. Never delete uploads, Super Forms attachments, custom code, or unfamiliar files solely because you do not recognize them. You could permanently remove legitimate documents or break your site. Preserve a backup and establish what each affected file is before taking action. [Wordfence's deletion and repair warnings](https://www.wordfence.com/help/scan/)
{% endhint %}

### Commercial plugins such as Super Forms

Wordfence's repository comparison and automatic repair rely on reference files for WordPress core and plugins/themes distributed through WordPress.org. Commercial or custom files, including Super Forms, may not have WordPress.org checksums, **View Differences**, or an automatic repair copy. Wordfence can still scan non-repository code for suspicious patterns, but that is not the same as verifying it against the vendor's original package. [Repository comparison limits](https://www.wordfence.com/help/scan/options/) · [Repair availability](https://www.wordfence.com/help/scan/scan-results/)

If these files need investigation or replacement, obtain a genuine ZIP from the vendor, using [Super Forms Downloads](downloads.md) for Super Forms. Have your developer compare against the appropriate original release while preserving legitimate customizations. If infection is confirmed, use a trusted supported release in the correct channel as part of the cleanup. Simply overwriting existing files may leave additional attacker-created files behind, so have the host or security professional check for those too. Never use a pirated or “nulled” package. [WordPress trusted-source guidance](https://developer.wordpress.org/advanced-administration/security/hardening/) · [WordPress cleanup guidance](https://wordpress.org/documentation/article/faq-my-site-was-hacked/)

## If you suspect a compromise

Do not wait for another scan to tell you whether to act if there are credible signs of unauthorized activity.

1. **Contact your hosting provider or a qualified WordPress security professional.** Share the symptoms and findings privately. Ask them to contain further access and harmful activity, preserve evidence, and investigate the hosting account as well as WordPress. Other installations on the same account may be involved.
2. **Review access and administrator accounts.** Confirm which users and services are genuinely authorized. Remove or disable unauthorized access with your responder's help; do not assume an unfamiliar name is malicious without checking who maintains the site.
3. **Clean up the cause and the persistence.** Investigation may need to cover malicious files, database changes, backdoors, exposed configuration, other sites, and compromised backups. Patching the original entry point is necessary but is not a substitute for removing the effects of the intrusion.
4. **Reset access from a trusted device after cleanup.** Your responder may also reset credentials immediately for containment; repeat the reset after cleanup so an attacker cannot retain credentials captured during the infection. Review WordPress, hosting/control panel, SFTP/SSH, database, and connected-service access. Revoke unauthorized sessions, application passwords, and keys, and rotate affected secrets with expert help so working integrations and database configuration stay consistent.
5. **Re-scan to completion, test real site workflows, and monitor.** Review alerts, accounts, logs, and any recurrence. Do not restore potentially infected backups or call the site clean solely because one scan has no findings.

These steps follow [WordPress's hacked-site response guidance](https://wordpress.org/documentation/article/faq-my-site-was-hacked/) and [Wordfence's incident and after-cleanup guidance](https://www.wordfence.com/help/if-your-site-is-hacked/). For trusted-device precautions, see [WordPress guidance on local computer security](https://developer.wordpress.org/advanced-administration/security/hardening/#vulnerabilities-on-your-computer).

## Keep protection and monitoring working

- **Keep scheduled scans enabled.** Wordfence Free provides automatic scanning, including a full scan every 72 hours and a daily quick scan. Premium normally schedules a daily full scan and supports custom scheduling. Manual scans are available on both plans. Review **Scan Options and Scheduling** for the options supported by your license, and periodically confirm that scheduled scans actually complete. [Scan scheduling](https://www.wordfence.com/help/scan/scheduling/)
- **Enable two-factor authentication for administrator accounts.** Review the current **Login Security** settings rather than assuming 2FA is already enabled. Register an authenticator, save recovery codes securely, and test login in a separate browser before signing out of your existing session. Test custom login forms too; compatibility is not universal. Wordfence Free includes 2FA. [2FA setup and recovery codes](https://www.wordfence.com/help/tools/two-factor-authentication/) · [Current Login Security settings](https://www.wordfence.com/help/login-security/)
- **Maintain updates, recoverable backups, and monitored alerts.** Recheck forms after firewall or login-security changes, and keep responsibility for responding to findings explicit. These are ongoing risk-reduction measures, not a guarantee against compromise. [WordPress security guidance](https://developer.wordpress.org/advanced-administration/security/hardening/)
