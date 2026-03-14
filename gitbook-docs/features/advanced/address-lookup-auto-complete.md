---
description: >-
  How to lookup/autocomplete an address within your WordPress form and
  populating form input fields with the address data.
---

# Address lookup/auto complete

### About

This feature allows you to convert a regular [Text field](../../elements/form-elements/text-field.md) into an [Address search](address-lookup-auto-complete.md) field to search for an address (place).

### Getting started

{% hint style="info" %}
To use this feature you must first obtain a Google API key via your [API manager](https://console.developers.google.com/).
{% endhint %}

In order to enable the **Address Auto Complete** function you will have to edit your Text field and select the `Address auto complete (google places)` option from the dropdown.

Now make sure you enable the feature by checking **Enable address auto complete**.

Now enter your **Google API key** and make sure you have enabled these libraries in your [API manager](https://console.developers.google.com/):

* Google Maps JavaScript API
* Google Places API (New)

{% hint style="danger" %}
Since March 2025 new Google API users are required to enable the Places API (New) since Google does not allow usage of their Places API (legacy) for new users.
{% endhint %}

### Restrict by country

It's possible to restrict results based on countries (up to a maximum of 5).

* `fr,nl,de` would restrict results for France, Netherlands and Germany
* `us,pr,vi,gu,mp` would restrict your results to places within the United States and its unincorporated organized territories.', 'super-forms' ),

### Return results by type

You can define what type of results you wish to return, you can choose one of the below types (leave blank to return all types):

* `geocode`: return only geocoding results, rather than business results. Generally, you use this request to disambiguate results where the location specified may be indeterminate.
* `address`: return only geocoding results with a precise address. Generally, you use this request when you know the user will be looking for a fully specified address.
* `establishment`: return only business results.
* `(regions)`: return any result matching the following types: locality, sublocality, postal\_code, country, administrative\_area\_level\_1, administrative\_area\_level\_2
* `(cities)`: type collection instructs the Places service to return results that match locality or administrative\_area\_level\_3

### Mapping data with fields

It's also possible to automatically populate other text fields with data based on the selected place. For instance, you could add fields named `street`, `city`, `zipcode`. You can then map the text field where the user searches for a place/address so that it automatically fills out these individual fields.
