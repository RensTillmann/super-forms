# Validation

The Validation option gives you the ability to quickly add a specific validation to any of your fields.
This will decrease the risk of a user making mistakes or typos while filling out the form.

Below you can find the available validation methods:

## Letters only

Only allow input field to contain letters, and nothing else

```js
^[a-zA-Z]+$
```

## Required Field (not empty)

This is the most used validation method, it will simply check if the field was entered or not.<br />
This allows you to make a field a so called **Required field***.

## E-mail address

This validation method checks if the entered email address was a possible valid

_The regex used for this validation is:_<br />

```js
^([\w-\.]+@([\w-]+\.)+[\w-]{2,63})?$
```

## Phone number

Validations phone numbers with a minimum of 10 characters in length and only allows **numbers, spaces, -, +**

_The regex used for this validation is:_<br />

```js
^((\+)?[1-9]{1,2})?([-\s\.])?((\(\d{1,4}\))|\d{1,4})(([-\s\.])?[0-9]{1,12}){1,2}$
```

## Numeric

This validation checks if the entered value contains numbers only and no other characters.

_The regex used for this validation is:_<br />

```js
^\d+$
```

## Float

This validation method can be used whenever you require to validate the user input to be a float value.
A float value never contains a comma, and only contains numbers and a dot (.).

_The regex used for this validation is:_<br />

```js
^[+-]?\d+(\.\d+)?$
```

## Website URL

This validation method is used whenever you require the user to enter a valid URL address.

_The regex used for this validation is:_<br />

```js
^(http(s)?:\/\/)?(www\.)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$
```

## IBAN

Whenever you are asking for an IBAN number you can use this validation method to make sure the entered IBAN is a valid number.

## Custom Regex

If you require a specific validation this option allows you to use a custom regular expression on the value entered by the user.
If no match was found based on the entered value the [Error Message](error-message) will be displayed to the user.

_Some example regular expressions that you might like to use are:_

```js
//select integers only
var intRegex = '/[0-9 -()+]+$/';

//match any ip address
var ipRegex = 'bd{1,3}.d{1,3}.d{1,3}.d{1,3}b';

//match number in range 0-255
var num0to255Regex = '^([01][0-9][0-9]|2[0-4][0-9]|25[0-5])$';

//match number in range 0-999
var num0to999Regex = '^([0-9]|[1-9][0-9]|[1-9][0-9][0-9])$';

//match ints and floats/decimals
var floatRegex = '[-+]?([0-9]*.[0-9]+|[0-9]+)';

//Match Any number from 1 to 50 inclusive
var number1to50Regex = '/(^[1-9]{1}$|^[1-4]{1}[0-9]{1}$|^50$)/gm';

//match email address
var emailRegex = '^[A-Z0-9._%+-]+@[A-Z0-9.-]+.[A-Z]{2,4}$';

//match credit card numbers
var creditCardRegex = '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35d{3})d{11})$';

//match username
var usernameRegex = '/^[a-z0-9_-]{3,16}$/';

//match password
var passwordRegex = '/^[a-z0-9_-]{6,18}$/';

//Match 8 to 15 character string with at least one upper case letter, one lower case letter, and one digit (useful for passwords).
var passwordStrengthRegex = '/((?=.*d)(?=.*[a-z])(?=.*[A-Z]).{8,15})/gm';

//match elements that could contain a phone number
var phoneNumber = '/[0-9-()+]{3,20}/';

//MatchDate (e.g. 21/3/2006)
var dateRegex = '/(d{1,2}/d{1,2}/d{4})/gm';

//match date in format MM/DD/YYYY
var dateMMDDYYYRegex = '^(0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])[- /.](19|20)dd$';

//match date in format DD/MM/YYYY
var dateDDMMYYYRegex = '^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)dd$';

//match a url string (Fixes spaces and querystrings)
var urlRegex = /^(http(s)?:\/\/)?(www\.)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/;

//match domain name (with HTTP)
var domainRegex = '/(.*?)[^w{3}.]([a-zA-Z0-9]([a-zA-Z0-9-]{0,65}[a-zA-Z0-9])?.)+[a-zA-Z]{2,6}/igm';

//match domain name (www. only)
var domainRegex = '/[^w{3}.]([a-zA-Z0-9]([a-zA-Z0-9-]{0,65}[a-zA-Z0-9])?.)+[a-zA-Z]{2,6}/igm';

//match domain name (alternative)
var domainRegex = '/(.*?).(com|net|org|info|coop|int|com.au|co.uk|org.uk|ac.uk|)/igm';

//match sub domains: www, dev, int, stage, int.travel, stage.travel
var subDomainRegex = '/(http://|https://)?(www.|dev.)?(int.|stage.)?(travel.)?(.*)+?/igm';

//Match jpg, gif or png image
var imageRegex = '/([^s]+(?=.(jpg|gif|png)).2)/gm';

//match all images
var imgTagsRegex = '/<img .+?src="(.*?)".+?/>/ig';

//match just .png images
var imgPngRegex = '/<img .+?src="(.*?.png)".+?/>/ig';

//match RGB (color) string
var rgbRegex = '/^rgb((d+),s*(d+),s*(d+))$/';

//match hex (color) string
var hexRegex = '/^#?([a-f0-9]{6}|[a-f0-9]{3})$/';

//Match Valid hexadecimal colour code
var hexRegex = '/(#?([A-Fa-f0-9]){3}(([A-Fa-f0-9]){3})?)/gm';

//match a HTML tag (v1)
var htmlTagRegex = '/^< ([a-z]+)([^<]+)*(?:>(.*)< /1>|s+/>)$/';

//match HTML Tags (v2)
var htmlTagRegex = '/(< (/?[^>]+)>)/gm';

//match /product/123456789
var productUrlRegex = '(/product/)?+[0-9]+';

//Match Letters, numbers and hyphens
var lnhRegex = '/([A-Za-z0-9-]+)/gm';
```
