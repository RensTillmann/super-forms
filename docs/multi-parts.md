# Multi-parts / Multi-steps

This guide will explain what a multi-part is, what features it has, when it's smart to use a multi-part and how to add it.

* [What is a multi-part?](#what-is-a-multi-part)
* [What features does a multi-part have?](#what-features-does-a-multi-part-have)
* [When to use a multi-part?](#when-to-use-a-multi-part)
* [How to add a multi-part?](#how-to-add-a-multi-part)
* [Multi-part customization](#multi-part-steps-and-progress-bar-customization)

## What is a multi-part

A multi-part is an element that will become a so called "step" or section of the form on the front-end.
This comes in handy when wish to seperate specific sections of your form into one step each.
Each multi-part represents it's own step. If you need a 3 step form, you will add 3 multi-part elements.
Inside each of them you put the elements and fields that belong to this particulair step.

The end user will be able to navigate through the steps with the `Next` and `Prev` buttons.
The end user will also be able to click on the step number, this way the user can navigate through the available steps of the form.

A progress bar will will show the progression that the user has made so far based on the current step the user is on.

## What features does a multi-part have

Each multi-part has the following features:

* [Automatically go to next step](#automatically-go-to-next-step) - when user filled out last field of current step proceed to the next
* [Disable autofocus on first field](#disable-autofocus-on-first-field) - when user goes to next step do not focus the first field
* [Check for errors before going to next step](#check-for-errors-before-going-to-next-step) - only allow the user to proceed to the next step if current has no errors

### Automatically go to next step

This option comes in handy whenever you only have 1 field in each step.
For instance when you have radio buttons in all of your steps it would be more user friendly to proceed to the next step automatically after the user selected their option.

### Disable autofocus on first field

By default whenever a user proceeds to the next step, the first field will be automatically focussed.
If a dropdown is the first element in this next step, it will automatically be opened so the user can choose an option instantly.
By default this option is enabled, so if you do not want this you can disable this setting on each of your multi-parts.

### Check for errors before going to next step

This option allows you to lock remaining steps in case the user did not completely fill out the current step.
This comes in handy whenever you do not want a user to walk through all the steps.
This setting can prevent "lazy" users to not wanting to fill out the form because they might think it's to large or to much work.

## When to use a multi-part

You should use a multi-part for large forms.
You can also use it when a form must be placed in a small area but still needs to have many fields (think of a survey or poll).

## How to add a multi-part

In order to add a multi-part you can open up the **Layout Elements** section on the builder page.
You will see 4 elements, the last one is the so called Multi-part.
You can simply drag & drop the Multi-part into the form.
Not that a multi-part cannot be nested in a column nor inside another multi-part.
Once you have added the multi-part you are allowed to add any element inside it that belongs to this step of the form.

## Multi-part Steps and Progress Bar Customization

To customize the colors of the Steps and Progress bar, you can find the settings under `Form Settings` > `Theme & Colors` on the builder page.
From here you can scroll down to the part that says **Progress Bar Colors** and **Progress Step Colors**.
Optionally you can also choose to **hide/show** the Steps and or Progress Bar.
