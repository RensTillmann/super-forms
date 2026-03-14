---
description: >-
  Enhance your WordPress form with custom API phone number validation. Learn how
  to implement JavaScript code to validate phone numbers via a custom API for
  accurate form submissions.
---

# Custom API Phone Number Validation for Your WordPress Form

### Introduction

Phone number validation is crucial for ensuring that the data collected through your WordPress forms is accurate and reliable. Although Super Forms comes with an **International Phone number element** with build-in phone number validation along with a **Custom Regex (regular expression) field validation** option, you might require to validate the entered phone number via a custom API. With our WordPress form plugin, you can easily integrate a custom API to validate phone numbers in real time. This guide provides step-by-step instructions and sample JavaScript code to help you implement custom phone number validation on your WordPress forms.

### Why Phone Number Validation is Important

Phone number validation helps prevent the entry of invalid or incorrectly formatted phone numbers, ensuring that your contact information database remains clean and accurate. By using a custom API for validation, you can also implement additional checks, such as verifying the phone number against a specific country's numbering plan or ensuring that the number is active and reachable.

### How to Implement Custom API Phone Number Validation

To add phone number validation to your WordPress form using a custom API, follow the steps below:

1. **Access the JavaScript Code Area**: Navigate to the JavaScript code area of your WordPress form plugin where you can insert custom scripts. You can use a plugin for this or edit your child theme files.
2. **Insert the Provided JavaScript Code**: Copy and paste the following JavaScript code into the appropriate section. This code will trigger a validation process whenever a user interacts with the phone number field in your form.

```javascript
(function() {
    var timer = setInterval(function() {
        debugger;
        if (super_common_i18n && SUPER) {
            console.log(super_common_i18n.dynamic_functions.after_field_change_blur_hook);
            clearInterval(timer);
            super_common_i18n.dynamic_functions.after_field_change_blur_hook.push({
                name: 'f4d_custom_api_phone_validation'
            });
            SUPER.f4d_custom_api_phone_validation = function(args) {
                debugger;
                // args.form; // The current form
                // args.el; // The current element
                // Before we continue check if this is the phonenumber field by comparing the field name 
                if (args.el.name !== 'my_phone_number_field_name_here') return;
                // Make API call to verify phone number 
                // ... function here
                // e.g:
                // var responseBody = API.makeRequest({phonenumber:el.value});
                // Example response from API server:
                var responseBody = '{"valid":false}';
                // If phone is invalid display error
                var result = JSON.parse(responseBody);
                if (result.valid === true) {
                    // Phonenumber is valid
                    if (args.el.closest('.super-field')) args.el.closest('.super-field').classList.remove('super-error-active');
                    SUPER.remove_error_status_parent_layout_element($, args.el);
                } else {
                    // Phonenumber is not valid
                    SUPER.handle_errors(args.el);
                    SUPER.add_error_status_parent_layout_element($, args.el);
                }
            }
        }
    }, 1000);
})();
```

3. **Customize the Field Name**: Replace `'my_phone_number_field_name_here'` with the actual name of your phone number field. This ensures that the validation is applied only to the correct input field.
4. **API Integration**: Modify the placeholder API call in the script to connect with your custom API server. The code provided includes a simulated API response (`var responseBody = '{"valid":false}';`) which you should replace with the actual API call and response handling logic.
5. **Handle the Validation Result**: The code parses the API response and either removes the error state if the phone number is valid or adds an error state if the number is invalid. Customize the response handling as needed based on your API's response structure.

### Testing Your Validation

After implementing the script, test the form to ensure that the phone number validation works correctly. Enter various phone numbers to verify that the API responds as expected and that the form displays errors or success messages appropriately.

### Phone number validation services (API providers)

There are several API providers that offer phone number validation services. These services can verify whether a phone number is valid, correctly formatted, and sometimes even active. Here are some popular API providers for phone number validation:

#### 1. **Twilio**

* **Overview**: Twilio offers a comprehensive phone number validation API as part of its Lookup API. It provides information such as the phone number’s carrier, type (mobile, landline, VOIP), and its international format.
* **Features**:
  * Carrier and type lookup.
  * Number format validation.
  * Risk assessment and spam detection.
* **Website**: [Twilio](https://www.twilio.com/)

#### 2. **Numverify**

* **Overview**: Numverify provides a reliable and scalable phone number validation API with worldwide coverage. It can validate international and local phone numbers and provides additional data like country, location, and carrier information.
* **Features**:
  * Global phone number validation.
  * Format, country, and carrier detection.
  * Real-time validation.
* **Website**: [Numverify](https://numverify.com/)

#### 3. **Google's libphonenumber**

* **Overview**: Although not a direct API service, Google's libphonenumber library is widely used for phone number parsing, validation, and formatting. Many API providers use this library as part of their service offerings.
* **Features**:
  * Parsing, formatting, and validating international phone numbers.
  * Support for all countries' phone number formats.
* **Website**: [libphonenumber](https://github.com/google/libphonenumber)

#### 4. **Vonage (formerly Nexmo)**

* **Overview**: Vonage offers a phone number insight API that allows you to validate phone numbers, identify their type, and gain carrier information.
* **Features**:
  * Number validation and formatting.
  * Carrier lookup.
  * Mobile number portability (MNP) detection.
* **Website**: [Vonage Phone number validation](https://www.vonage.com/communications-apis/programmable-solutions/phone-number-validation/)

#### 5. **Telesign**

* **Overview**: Telesign provides phone number intelligence services, including validation, through its PhoneID API. It offers details like carrier, phone type, and risk level associated with the number.
* **Features**:
  * Real-time number validation.
  * Risk assessment.
  * Detailed phone number information.
* **Website**: [Telesign PhoneID API](https://www.telesign.com/products/phone-id)

#### 6. **Loqate**

* **Overview**: Loqate, a GBG solution, offers phone validation as part of its suite of global data verification services. It ensures the phone numbers collected are valid and deliverable.
* **Features**:
  * Global phone number validation.
  * Carrier identification.
  * Formatting and cleansing of phone number data.
* **Website**: [Loqate Phone Validation](https://www.loqate.com/en-gb/phone-validation/)

#### 7. **Experian Phone Validation**

* **Overview**: Experian provides a phone validation service that can be integrated via API, offering real-time validation and enrichment of phone number data.
* **Features**:
  * Validation of mobile, landline, and VOIP numbers.
  * Number format correction.
  * Carrier and region identification.
* **Website**: [Experian Phone Validation](https://www.experian.com/data-quality/phone-verification)

#### 8. **Byteplant Phone Validator**

* **Overview**: Byteplant offers a phone validation API that supports over 200 countries, providing real-time validation and additional information like carrier and line type.
* **Features**:
  * International phone number validation.
  * Carrier lookup and line type identification.
* **Website**: [Byteplant Phone Validator](https://www.phone-validator.net/)

#### 9. **Abstract API**

* **Overview**: Abstract API offers a lightweight phone validation service that provides information such as the phone number's validity, format, country, and carrier.
* **Features**:
  * Phone number validation.
  * Carrier and country information.
  * Number type detection (mobile, landline, etc.).
* **Website**: [Abstract Phone Number Validation API](https://www.abstractapi.com/a/phone-validation-api)

These API providers offer various levels of phone number validation services, from basic format checks to more advanced features like carrier lookup and fraud detection. Depending on your specific needs, you can choose the one that best fits your requirements.

### Conclusion

By adding custom API phone number validation to your WordPress form, you ensure that only valid phone numbers are accepted, leading to better data quality and enhanced user experience. This feature is particularly beneficial for businesses that rely on accurate phone contact information, such as customer service operations, marketing campaigns, and more.

By following this guide, you can easily integrate phone number validation into your WordPress forms, leveraging the power of custom APIs to maintain data accuracy and enhance form functionality.
