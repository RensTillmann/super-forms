# Button

## Submitting a form

Use a Button configured to submit the form to let visitors send their completed form. Clicking the submit button runs the form's normal validation before submission.

## Conditional logic

Since **6.3.323 LTS**, edit the Button and choose **Conditional Logic** from its settings dropdown. You can show, hide or make the button read-only based on field values. A conditionally hidden button is not displayed, and a read-only button cannot be clicked.

For example, show a submit button only when a visitor selects an appropriate option, and show a Heading or HTML message otherwise. See [Conditional Logic](../../features/advanced/conditional-logic.md#how-to-set-conditions) for setting up conditions.

{% hint style="info" %}
Direct Button conditional-logic settings are available in LTS since 6.3.323 only, not in 6.4. On older LTS versions and in 6.4, place the Button inside a Column and apply conditional logic to the Column instead.
{% endhint %}

In [multi-part forms](../layout-elements/multi-part-step.md), automatic Prev/Next copies do not inherit the submit button's conditional settings. Hiding or making the submit button read-only therefore does not hide or disable those navigation copies.

{% hint style="warning" %}
Hiding a submit button is a user-interface choice, not a server-side access-control rule. Do not use button visibility to protect restricted actions or data; enforce those permissions on the server.
{% endhint %}

## Pressing Enter

In **6.3.323 LTS**, when Enter-to-submit is enabled, pressing Enter in a field uses the first submit button that is not hidden by conditional logic and is not read-only. If no such button is available, Enter does nothing. Textareas keep their normal line breaks.

In a multi-part form, Enter on a step before the last uses that step's visible, non-read-only Next button with the usual step validation; if Next is absent, hidden or read-only, Enter does nothing. The final step uses the first visible, non-read-only submit button. See [Enter key navigation](../layout-elements/multi-part-step.md#enter-key-navigation).
