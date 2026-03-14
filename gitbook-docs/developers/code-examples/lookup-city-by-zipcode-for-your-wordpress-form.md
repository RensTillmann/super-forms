---
description: >-
  Example JavaScript code and form elements code to lookup a city based on
  entered zipcode on your WordPress forms. You can change the region by setting
  the country as parameter if needed.
---

# Lookup City by Zipcode for your WordPress form

Even though natively there is no build-in option in Super Forms to lookup a city based on an entered zipcode, you could achieve this relatively easily with some custom JavaScript code and using the Google Geocoding API.

For instance if you enter **7064BW** as the zipcode, it will populate the field below that with the corresponding city name e.g. **Silvolde**.

**What you will need are:** \
A Text field named "enter\_zipcode" a Text field named "city" and the API key from google.\
You can change the country name in the API request URL to lookup zipcodes from inside a specific region or country only.

In the example form code below, the city field is set to read only/disabled so that the user cannot alter it themselves. And a validation "Not empty" is applied to display an error in case the entered zipcode didn't match (populate) any city.

If you also wish to only allow your service for specific cities, you could hook into the form submission (PHP side), and return an error whenever the city field doesn't match the list of your cities. Please refer to an example on how to do this (you will need to adjust the code to your liking, but the use-case is the same): [https://docs.super-forms.com/developers/code-examples/compare-input-field-value-with-database-value](https://docs.super-forms.com/developers/code-examples/compare-input-field-value-with-database-value)

Make sure to set the API key and country in the below code, and make sure this javascript code is loaded on the page where your form is displayed.&#x20;

```
<script> 
(function(){ 
    var lookupCityByZipcode = function(zipCode){ 
        var apiKey = 'XXXXX-XXXXX-XXXXX'; // Replace with your actual API key 
        var country = 'Netherlands'; // You can use the full country name or its ISO code (e.g., 'NL') 
        var field = document.querySelector('.super-form input[name="city"]'); 
        fetch('https://maps.googleapis.com/maps/api/geocode/json?address='+zipCode+','+country+'&key='+apiKey).then(response => response.json()).then(data => { 
            if (data.status === 'OK') { 
                var city = data.results[0].address_components.find(component => 
                    component.types.includes('locality') 
                ); 
                if (city) { 
                    console.log('City:', city.long_name); 
                    field.value = city.long_name; 
                } else { 
                    console.log('City not found for this ZIP code.'); 
                    field.value = ''; 
                } 
                SUPER.after_field_change_blur_hook({el: field}); 

            } else { 
                console.error('Error:', data.status); 
            } 
        }).catch(error => console.error('Error fetching data:', error)); 
    }; 
    var debounceTimer, inputField = document.querySelector('.super-form input[name="enter_zipcode"]'); 
    inputField.addEventListener('input', function(event) { 
        // Clear the existing timer if it's still running 
        clearTimeout(debounceTimer); 
        // Set a new timer for 1 second (1000 milliseconds) 
        debounceTimer = setTimeout(function() { 
            // Call the API or function after 1 second of inactivity 
            console.log('API called with value: ' + event.target.value); 
            // Add your API call function here 
            lookupCityByZipcode(event.target.value); 
        }, 1000); 
    }); 

})(); 
</script>
```

Example form elements code with only two Text fields (for entering a zipcode, and to populate a field with the corresponding city). You can copy paste this code under the \[CODE] tab on the form builder page when creating a new form to test this out):

```
[
    {
        "tag": "text",
        "group": "form_elements",
        "data": {
            "name": "enter_zipcode",
            "email": "Enter zipcode:",
            "placeholder": "Enter zipcode",
            "placeholderFilled": "Enter zipcode",
            "type": "text",
            "validation": "empty",
            "error": "Please enter your zipcode",
            "address_normalize": "",
            "exclude": "2",
            "exclude_entry": "true",
            "icon": "user"
        }
    },
    {
        "tag": "text",
        "group": "form_elements",
        "data": {
            "name": "city",
            "email": "City:",
            "placeholder": "Your Full Name",
            "placeholderFilled": "Name",
            "type": "text",
            "validation": "empty",
            "address_normalize": "",
            "disabled": "1",
            "readonly": "true",
            "autocomplete": "true",
            "icon": "user"
        }
    }
]
```
