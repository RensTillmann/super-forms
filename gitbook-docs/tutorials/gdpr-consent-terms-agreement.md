---
description: >-
  How to request the user to accept the terms of service or GDPR consent or
  privacy policy to be able to submit the WordPress form.
---

# GDPR Consent / Terms agreement

A basic "Accept terms of service" demo form is available via the menu **Super Forms > Demos**. The demo form is named "Accept Terms of Service".&#x20;

<div align="left"><figure><img src="../.gitbook/assets/image (51).png" alt="Accept terms of service demo form for WordPress"><figcaption><p>Accept terms of service demo form for WordPress</p></figcaption></figure></div>

This demo form consists of a simple [Checkbox element](../developers/code-examples/change-checkbox-radio-layout-to-vertical-on-mobile-devices.md) with only one option. The field is set to be required so that the user must agree to the terms in order to submit the form. This will work for any consent that you require from your user such as GDPR consent or privacy policy consent.

Another option that you might require is to still allow the form to be submitted even without the users consent. But in this case you do not wish to store the form data on the server. In these cases you will require to configure the form so that it only stores the contact entry whenever the user checked the box.

To do this edit your form and open the "Form Settings" panel. Now choose "Form settings" from the dropdown. From here enable the "Conditionally save Contact Entry based on user data". If you have a checkbox named `gdpr` and the value of the checkbox is `true` you can define the condition as shown in the image below. Now whenever the user gave their consent the contact entry will be created and stored on the server. Otherwise no contact entry will be created, but the form will still be submitted.

{% hint style="warning" %}
**Note:** if you are sending emails for the form, that they will still be send, they will not be disabled unless you disabled them in the settings. They currently do not have the option to be conditionally send. If you require such a future please [submit a ticket](../support.md).
{% endhint %}

<div align="left"><figure><img src="../.gitbook/assets/image (26).png" alt="Only save form submission data as entry when GDPR consent was given by the user on WordPress form"><figcaption><p>Only save form submission data as entry when GDPR consent was given by the user on WordPress form</p></figcaption></figure></div>
