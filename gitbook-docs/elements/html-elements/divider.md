---
description: >-
  A visual separator used to divide sections of your WordPress form.
---

# Divider

{% hint style="info" %}
If you are looking for a way to create a WordPress form with steps (with next / previous buttons) check out the [Multi-part element](../layout-elements/multi-part-step.md) instead.
{% endhint %}

## Conditional logic

Since **6.3.323 LTS**, edit the Divider and choose **Conditional Logic** from its settings dropdown to show or hide the separator based on field values. For example, show a Divider together with a conditional section so that the separator is not left behind when that section is hidden. See [Conditional Logic](../../features/advanced/conditional-logic.md#how-to-set-conditions) for configuring conditions.

{% hint style="info" %}
Direct Divider conditional-logic settings are available in LTS since 6.3.323 only, not in 6.4. On older LTS versions and in 6.4, place the Divider inside a Column and apply conditional logic to the Column instead.
{% endhint %}

The Divider is a visual separator, not a navigation control or an input field.
