# Custom form POST URL

* [When should I use this feature?](#when-should-i-use-this-feature)
* [How to enable this feature?](#how-to-enable-this-feature)

## When should I use this feature?

Whenever you require to submit the form data to an external API, or to your custom PHP script.

In that case you can let the form submit all it's data to this custom URL via a POST request after the form was submitted.

?> **Please note:** that it's up to the URL how to deal with the POST request and that this is not Super Forms responsibility.

## How to enable this feature?

To use this feature you can go to `Form Settings > Form Settings` and enable the option **Enable form POST method**.

Once enabled you can enter the URL under the option **Enter a custom form post URL**.

You can also define custom parameters if required by enabling **Enable custom parameter string for POST method**.

When enabled you can enter them under the option **Enter custom parameter string** on each line like so:

    first_name|{first_name}
    last_name|{last_name}

The `first_name` in the above example is the **key** and the `{first_name}` is the [field tag](tags-system) that would retrieve the value entered by the user in the field that was called **first_name** in your form.

Optionally you can also adjust the **Post timeout in seconds**, the **HTTP version** and wether or not to use debug mode **Enable debug mode** which will output the POST response (for developers).
