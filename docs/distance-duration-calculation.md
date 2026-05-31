# Distance & Duration Calculation

?> **NOTE:** To use this feature you must first obtain a Google API key via your [API manager](https://console.developers.google.com/). Also make sure the **Directions API** library is enabled in your [API manager](https://console.developers.google.com/). After you obtained your API key you require to enter it under "Super Forms > Settings > Form Settings".

* [About](#about)
* [When to use this feature](#when-to-use-this-feature)
* [How to enable it?](#how-to-enable-it)
* [Example form](#example-form)

## About

With this feature for [Text fields](text) you can calculate either the distance or duration between 2 different locations.
When calculating the distance you can return the total kilometers (metric) or miles (imperial).
When calculating the duration (travel time) you can return the total seconds or minutes.

The distance and duration between the two locations are calculated with the **google directions API**.

?> **NOTE:** In order for this feature to work you must enable the **Directions API** within

## When to use this feature?

You will want to use this feature whenever you need to calculate **travel time** or **travel distance** between 2 different locations. The value returned can be populated to another [Text field](text) that you can optionally set to be disabled to not allow the user to edit the field. You can also put this field in a hidden column to make it invisible to the end user.

## How to enable it?

This feature can be enabled for any [Text field](text) in your form.

In order to do so, go ahead and edit the element. Now choose `Distance & Duration Calculation (google directions)` from the dropdown menu. Now check the **Enable distance calculator** option to enable the feature for this field.

?> **NOTE:** It is strongly suggested to also enable the [Address Auto Complete](address-auto-complete) feature.

When enabled, you will have to choose if the field acts as the Start or Destination address.
You can select this via the **Select if this field must act as Start or Destination** option.

Depending on whether the field is the Start or Destination you will now see some extra options.

When the field acts as **Start address**, you will have to define what the **Destination address** is. When the field acts as **Destiantion address**, you will have to define what the **Start address** is.

?> **NOTE:** For **Destination address** or **Starting address** you can either enter a fixed address/zipcode or enter the unique field name to retrieve dynamic location from user entered.

When you have choosen to use the field as the Start address, you can now choose what value you wish to return from the API.
You can choose one of the following options:

* **Distance in meters** (Tip: use this option in combination with [Calculator element](calculator) to do calculations)
* **Duration in seconds** (Tip: use this option in combination with [Calculator element](calculator) to do calculations)
* **Distance text in km or miles**
* **Duration text in minutes**

Now we have to **Select a unit system** for the value returned by the API.
You can choose between **Metric** (kilometer/meters) or **Imperial** (miles/feet) unit system.

The final step is to enter the [Unique field name](unique-field-name) which the distance/duration value should be populated to. This can either be a [Text field](text) or [Hidden field](hidden) (only enter the unique field name without any brackets).

## Example form

You can find an example form that uses conditional logic under: `Super Forms` > `Demos` > `Distance calculator`
