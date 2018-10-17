# Distance & Duration Calculation

?> **NOTE:** To use this feature you must first obtain a Google API key via your [API manager](https://console.developers.google.com/). Also make sure the **Directions API** library is enabled in your [API manager](https://console.developers.google.com/).

* [About](#about)
* [When to use this feature](#when-to-use-this-feature)
* [How to enable it?](#how-to-enable-it)
* [Example form code](#example-form-code)


### About

With this feature for [Text fields](text) you can calculate either the distance or duration between 2 different locations.
When calculating the distance you can return the total kilometers (metric) or miles (imperial).
When calculating the duration (travel time) you can return the total seconds or minutes. 

The distance and duration between the two locations are calculated with the **google directions API**.

?> **NOTE:** In order for this feature to work you must enable the **Directions API** within 


### When to use this feature?

You will want to use this feature whenever you need to calculate **travel time** or **travel distance** between 2 different locations. The value returned can be populated to another [Text field](text) that you can optionally set to be disabled to not allow the user to edit the field. You can also put this field in a hidden column to make it invisible to the end user.


### How to enable it?

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
- **Distance in meters** (Tip: use this option in combination with [Calculator Add-on](calculator-add-on) to do calculations)
- **Duration in seconds** (Tip: use this option in combination with [Calculator Add-on](calculator-add-on) to do calculations)
- **Distance text in km or miles**
- **Duration text in minutes**

Now we have to **Select a unit system** for the value returned by the API.
You can choose between **Metric** (kilometer/meters) or **Imperial** (miles/feet) unit system.

The final step is to enter the [Unique field name](unique-field-name) which the distance/duration value should be populated to. This can either be a [Text field](text) or [Hidden field](hidden) (only enter the unique field name without any brackets).


### Example form code

_In the below example we have used the Calculator Add-on to demonstrate how flexible Super Forms in a whole is. If you do not own this add-on you can still use the below code, super forms will automatically leave out the calculator element. The below example also uses Google places API to auto populate the addresses and calculates the difference between the 2 addresses entered by the user. Based on the result a price is being calculated. Of course you can customize the math for this calculation._

	[{"tag":"column","group":"layout_elements","inner":[{"tag":"html","group":"html_elements","data":{"html":"In the below example we have used the Calculator Add-on to demonstrate how flexible Super Forms in a whole is. The form uses Google places API to auto populate the addresses and calculates the difference between the 2 addresses entered by the user. Based on the result a price is being calculated. Of course you can customize the math for this calculation."}},{"tag":"spacer","group":"html_elements","data":{"height":50,"conditional_action":"disabled","conditional_trigger":"all"}}],"data":{"duplicate_limit":0,"label":"Column","bg_opacity":1,"conditional_action":"disabled","conditional_trigger":"all"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","data":{"name":"from","email":"From:","label":"From:","placeholder":"From","validation":"empty","error":"Enter from address","enable_distance_calculator":"true","distance_destination":"destination","distance_value":"dis_text","distance_field":"distance","enable_address_auto_complete":"true"}}],"data":{"size":"1/3"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","data":{"name":"destination","email":"Destination:","label":"Destination:","placeholder":"Destination","validation":"empty","error":"Enter your destination","enable_distance_calculator":"true","distance_method":"destination","distance_start":"from","enable_address_auto_complete":"true"}}],"data":{"size":"1/3"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","data":{"name":"distance","email":"Distance:","label":"Distance:","placeholder":"0 km","disabled":"1"}}],"data":{"size":"1/3"}}]

