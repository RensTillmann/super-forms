---
description: >-
  If you require multiple forms that consist partially of the same elements, you
  can use the "Include Form" element to inject elements from another form.
  Reduces the editing time while building forms.
---

# Form templates - Include elements into other forms - WordPress

> View the short [explanation video](form-templates-include-elements-into-other-forms-wordpress.md#include-form-element-explanation-video) about the "Include Form" element.

{% hint style="info" %}
In short, this allows you to "inject" elements from an already existing form into another. It acts as a "template" system.
{% endhint %}

### When should you use this?

You should use the `Include Form` layout element whenever you have Fields or Elements that are the same for multiple forms.

This will help reduce editing time since you do not have to re-do (or edit) these across all your forms. It also means you don't have to copy/paste these across all your forms.

They are managed from one single location. Any edits made to the "Template form" will automatically be applied across all of the forms that included this form.

Basically the `Include Form` element injects an already existing form's elements into another. You can find the `Include Form` under the **Layout Elements** section on the builder page as shown below:

<div align="left"><figure><img src="../../.gitbook/assets/image (12).png" alt="Include an existing form into another (injecting) on WordPress website. "><figcaption><p>Include an existing form into another (injecting) on WordPress website.</p></figcaption></figure></div>

### Step 1: Create a form that acts as your template

Create a new form called `Template XYZ` and simply build the form as you would normally. Add all the Layout and Form elements. For instance if you require all other forms to have an E-mail and Name field in a 1/2 layout, simply add the Columns and the fields. Once done, click the `Save` button to save the form.

### Step 2: Create your actual form(s) and Include the template

Now create your actual form(s) that you want to display on your website. Now add the "Include Form" element and set the form ID of your form template. It will now automatically load the elements from that form into your current form.

<div align="left"><figure><img src="../../.gitbook/assets/image (96).png" alt="Form injected with the &#x22;Include form&#x22; element - WordPress"><figcaption><p>Form injected with the "Include form" element - WordPress</p></figcaption></figure></div>

{% hint style="success" %}
When you preview or view the form on your front-end you will now see the elements are added from your template form.
{% endhint %}

### &#x20;"Include Form" element explanation video

{% embed url="https://youtube.com/shorts/o3vjlibgnto" fullWidth="false" %}

