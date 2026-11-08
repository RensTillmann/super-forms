---
description: What happens when a license expires, how to renew a prepaid license and how to move a site to a yearly subscription
---

# License expiry and renewal

A **Yearly Subscription** renews by itself. A **Prepaid License** does not: you renew it yourself from the dashboard, before it expires or after. The [Purchasing a license](../../quick-start/purchasing-a-license.md) article explains both when you buy.

### When a prepaid license expires

A prepaid license keeps working for 7 days after its expiry date. After those 7 days:

* Visitors who open a page with one of your forms see **This form is temporarily unavailable.** where the form would be.
* While you are signed in as an administrator, the form shows a **Super Forms — Expired** card: *"Your Super Forms license for this site has expired. Renew it to make this form live again."* The button on that card opens the renewal for the site's domain.

A lapsed license doesn't show up instantly. Super Forms checks the license and keeps that answer for 15 minutes, and when the check can't complete it keeps the last answer it received for up to 7 days. So a site can keep showing its forms for a short while after a license runs out, and for longer while the license can't be checked.

### Renew a prepaid license

1. Sign in at [dashboard.super-forms.com](https://dashboard.super-forms.com).
2. Open the site's card under **Your Websites** and click **Renew** on the license row.
3. In the renewal window, choose how long to renew for: **1** to **5** years. The window shows the price before you pay.
4. Pay by card through Stripe.
5. The renewal extends the license the site already uses, so there is no new code to activate. Its new expiry counts from the current expiry, or from today when the license has already expired, so the time you have left is kept and an expired license becomes active again.

The form goes live again once the plugin picks up the renewal, which takes up to 15 minutes.

{% hint style="info" %}
A **Super Forms — Expired** card over a form has a **Renew license** button that starts the same renewal for the site's domain. You can also open `dashboard.super-forms.com/?renew=example.com` yourself, with `example.com` replaced by the site's domain.
{% endhint %}

### Switch from a prepaid license to a yearly subscription

A license can't be converted in place. A domain holds one license per product, so while the old license — even an expired one — is still on the domain, activating another code is refused with:

> This domain already has a license for Super Forms (it may have expired). Renew or release that license first, or use the pencil icon to move it to another domain.

To move the site to a yearly subscription:

1. Buy the subscription under **Purchase New License** → **Yearly Subscription** ([Purchasing a license](../../quick-start/purchasing-a-license.md)).
2. Release the prepaid license: open the site's card under **Your Websites** and click **Release** on the prepaid license row. The old code returns to **Unused License Codes**. See [Free up a one-time licence for later](your-licenses.md#free-up-a-one-time-licence-for-later).
3. Activate the subscription code on the site: open **Unused License Codes** → **Activate License** → enter the site's domain.

From then on the subscription renews automatically, and you can cancel it from the dashboard whenever you want ([Cancel subscription](cancel-subscription.md)).

### Where the renewal reminders go

Super Forms sends a renewal reminder 30 days and 2 days before a license expires. They go to your account's e-mail address by default. You can add more recipients, each with their own settings, under **Notification Settings** in the dashboard ([E-mail Notification](e-mail-notification.md)).
