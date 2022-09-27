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

#### match password that is at least 8 characters long, contains a lower case and upper case letter, contains at least one number and at least a special character/symbol.

`^(?=.*[A-Z])(?=.*[0-9])(?=.*[a-z])(?=.*[$&+,:;=?@#|\/\\[\]{}'"<>.^*()%!-]).{8,}$`

#### match username

`^[a-z0-9_-]{3,16}$`

#### match any ip address

`^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$`

#### match credit card numbers

`^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35d{3})d{11})$`

#### match email address

`^[A-Z0-9._%+-]+@[A-Z0-9.-]+.[A-Z]{2,4}$`

#### select integers only

`^[0-9 -()+]+$`

#### match number in range 0-255

`^([01][0-9][0-9]|2[0-4][0-9]|25[0-5])$`

#### match number in range 0-999

`^([0-9]|[1-9][0-9]|[1-9][0-9][0-9])$`

#### match ints and floats/decimals

`^[-+]?([0-9]*.[0-9]+|[0-9]+)$`

#### Match Any number from 1 to 50 inclusive

`^(^[1-9]{1}$|^[1-4]{1}[0-9]{1}$|^50$)$`

#### match elements that could contain a phone number

`^[0-9-()+]{3,20}$`

#### MatchDate (e.g. 21/3/2006)

`^(d{1,2}/d{1,2}/d{4})$`

#### match date in format MM/DD/YYYY

`^(0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])[- /.](19|20)dd$`

#### match date in format DD/MM/YYYY

`^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)dd$`

#### match a url string (Fixes spaces and querystrings)

`^(http(s)?:\/\/)?(www\.)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$`

#### match domain name (with HTTP)

`(.*?)[^w{3}.]([a-zA-Z0-9]([a-zA-Z0-9-]{0,65}[a-zA-Z0-9])?.)+[a-zA-Z]{2,6}$`

#### match domain name (www. only)

`[^w{3}.]([a-zA-Z0-9]([a-zA-Z0-9-]{0,65}[a-zA-Z0-9])?.)+[a-zA-Z]{2,6}$`

#### match domain name (alternative)

`(.*?).(com|net|org|info|coop|int|com.au|co.uk|org.uk|ac.uk|)$`

#### match sub domains: www, dev, int, stage, int.travel, stage.travel

`(http://|https://)?(www.|dev.)?(int.|stage.)?(travel.)?(.*)+?$`

#### Match jpg, gif or png image

`([^s]+(?=.(jpg|gif|png)).2)$`

#### match all images

`<img .+?src="(.*?)".+?/>$`

#### match just .png images

`<img .+?src="(.*?.png)".+?/>$`

#### match RGB (color) string

`^rgb((d+),s*(d+),s*(d+))$`

#### match hex (color) string

`^#?([a-f0-9]{6}|[a-f0-9]{3})$`

#### Match Valid hexadecimal colour code

`(#?([A-Fa-f0-9]){3}(([A-Fa-f0-9]){3})?)$`

#### match a HTML tag (v1)

`^< ([a-z]+)([^<]+)*(?:>(.*)< /1>|s+/>)$`

#### match HTML Tags (v2)

`(< (/?[^>]+)>)$`

#### match /product/123456789

`(/product/)?+[0-9]+$`

#### Match Letters, numbers and hyphens

`([A-Za-z0-9-]+)$`
