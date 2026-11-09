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

Any prepaid license can become a yearly subscription on the same site, in one step. You do not release anything and there is no new code to activate: the license the site already uses becomes the subscription.

1. Sign in at [dashboard.super-forms.com](https://dashboard.super-forms.com).
2. Open the site's card under **Your Websites** and click **Switch to subscription** on the prepaid license row. On a code you have not activated yet, the same button sits next to **Activate License** under **Unused License Codes**.
3. The window shows the yearly price and when your first payment is. Confirm, then pay by card through Stripe.
4. The license row moves to the subscription part of the site's card, where it shows the renewal date. Nothing else changes.

**The time you already paid for is added to the subscription.** If your prepaid license is paid until 12 May, your first subscription payment is on 12 May and the plan renews yearly from then. If the license has already expired, the subscription starts today and the site is licensed again once the plugin picks it up, which takes up to 15 minutes.

The site stays licensed the whole time, and you can cancel the subscription from the dashboard whenever you want ([Cancel subscription](cancel-subscription.md)).

### Where the renewal reminders go

Super Forms sends a renewal reminder 30 days and 2 days before a license expires. They go to your account's e-mail address by default. You can add more recipients, each with their own settings, under **Notification Settings** in the dashboard ([E-mail Notification](e-mail-notification.md)).
