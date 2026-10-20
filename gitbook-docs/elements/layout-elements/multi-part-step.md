---
description: >-
  In short the multi-part allows to create WordPress forms that consist of
  multiple steps. This guide explains what a multi-part element is, what
  features it has, when to use the multi-part.
---

# Multi-part / step

## What is a multi-part

A multi-part is an element that will become a so called "step" or section of the form on the front-end. This comes in handy when wish to separate specific sections of your form into one step each. Each multi-part represents it's own step. If you need a 3 step form, you will add 3 multi-part elements. Inside each of them you put the elements and fields that belong to this particulair step.

The end user will be able to navigate through the steps with the `Next` and `Prev` buttons. The end user will also be able to click on the step number, this way the user can navigate through the available steps of the form.

A progress bar will will show the progression that the user has made so far based on the current step the user is on.

## What features does a multi-part have

Each multi-part has the following features:

* [Automatically go to next step](multi-part-step.md#automatically-go-to-next-step)\
  When user filled out last field of current step proceed to the next
* [Disable autofocus on first field](multi-part-step.md#disable-autofocus-on-first-field)\
  When user goes to next step do not focus the first field
* [Check for errors before going to next step](multi-part-step.md#check-for-errors-before-going-to-next-step)\
  Only allow the user to proceed to the next step if current has no errors

#### Automatically go to next step

This option comes in handy whenever you only have 1 field in each step. For instance when you have radio buttons in all of your steps it would be more user friendly to proceed to the next step automatically after the user selected their option.

#### Disable autofocus on first field

By default whenever a user proceeds to the next step, the first field will be automatically focussed. If a dropdown is the first element in this next step, it will automatically be opened so the user can choose an option instantly. By default this option is enabled, so if you do not want this you can disable this setting on each of your multi-parts.

#### Check for errors before going to next step

This option allows you to lock remaining steps in case the user did not completely fill out the current step. This comes in handy whenever you do not want a user to walk through all the steps. This setting can prevent "lazy" users to not wanting to fill out the form because they might think it's to large or to much work.

## When to use a multi-part

You should use a multi-part for large forms. You can also use it when a form must be placed in a small area but still needs to have many fields (think of a survey or poll).

## How to add a multi-part

In order to add a multi-part you can open up the **Layout Elements** section on the builder page. You will see 4 elements, the last one is the so called Multi-part. You can simply drag & drop the Multi-part into the form. Not that a multi-part cannot be nested in a column nor inside another multi-part. Once you have added the multi-part you are allowed to add any element inside it that belongs to this step of the form.

## Current step in the URL (#step-FORMID-STEP)

Each time the user changes step, Super Forms writes the current step into the URL. It adds an anchor like `#step-12345-3`. Here `12345` is the form ID and `3` is the step number. Step numbers start at 1.

You can use this anchor in two ways:

* **Link to a step.** Add the anchor to the page URL, for example `https://example.com/contact/#step-12345-2`. The form opens on step 2.
* **Reopen the step after a reload.** When the user reloads the page, the form opens on the step in the URL.

To turn this off, go to `Form Settings` > `Form settings` on the builder page. Enable **Disable Multi-part current step parameter in the URL**. The step is then no longer added to the URL.

## Browser Back button

The browser Back button works on the browser history, not on the form steps. Every step change adds a new `#step-...` anchor, and so a new history entry. What Back does with that entry depends on your Super Forms version.

{% hint style="info" %}
To move between steps, use the `Prev` button or click a step number. These always work as expected.
{% endhint %}

#### Super Forms 6.3 (LTS)

* Back only changes the `#step-...` anchor in the address bar. The form stays on the current step.
* When there are no more step anchors in the history, Back leaves the page.
* The anchor is only read when the page loads. So if the user reloads after pressing Back, the form opens on the step in the URL.

#### Super Forms 6.4 (beta)

Back moves the form to the step from the previous anchor. There are some limitations:

* The first history entry has no anchor. Going back to it does nothing, so the form stays on step 2. The next Back leaves the page.
* Back undoes the last step change. It does not always go to the previous step number. For example, after the user clicks `Prev`, Back goes forward one step.
* No validation runs when Back changes the step.

#### When the step is not in the URL

If **Disable Multi-part current step parameter in the URL** is enabled, no step anchors are added. Back then leaves the page right away, on both versions.

Full browser history navigation (History API / pushState) between steps is not available.

{% hint style="info" %}
If users may leave the page by accident, enable [Save form progression](../../features/basic/save-form-progression-continue-later.md). Their entered data is then kept when they come back.
{% endhint %}

## Multi-part Steps and Progress Bar Customization

To customize the colors of the Steps and Progress bar, you can find the settings under `Form Settings` > `Theme & Colors` on the builder page. From here you can scroll down to the part that says **Progress Bar Colors** and **Progress Step Colors**. Optionally you can also choose to **hide/show** the Steps and or Progress Bar.
