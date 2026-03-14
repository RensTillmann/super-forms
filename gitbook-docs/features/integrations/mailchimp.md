---
description: >-
  Connect your form with MailChimp and add new subscribers after they fill out
  the form.
---

# MailChimp

## About

This allows you to add and update subscribers to your MailChimp lists.

## Quick start

The quickest way to get started is by installing the demo form: `Super Forms > Demos` > `MailChimp`.

If you are starting from scratch you should do the following:

*
  1. Register an account with MailChimp and create an `Audience`
*
  1. In your MailChimp dashboard navigate to `Audience > All contacts`
*
  1. Select your adience and from the `Settings` tab choose `Audience name and defaults`
*
  1. Copy the `Audience ID` (which looks like `f14b7103f3`)
*
  1. Go back to your wordpress site, edit your form and add the MailChimp element via `Form Elements > MailChimp`
*
  1. Edit the MailChimp element and paste in the `Audience ID` under `Mailchimp Audiance ID`
*
  1. Optionally configure the other settings for the `MailChimp` element
*
  1. Once finished click `Update Element` to save it
*
  1. Save the form and test if it works

?> **Note:** Make sure your form has at least an email field named `email` which is required by MailChimp (obviously).

If needed you can map your fields with MailChimp `MERGE` tags.

You can also display `Groups` (or better said interestes) if you configured any for your Audience.
