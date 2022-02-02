# Datepicker element

The datepicker element is an advanced element with tons of options. On this page we will go through all of them along with their usecases. This will hopefully help you to implement it in your own forms.

## Features

* [Date Format](#date-format)
* [Date range (min/max)](#date-range-minmax)
* [Connecting 2 datepickers](#connecting-2-datepickers)
* [Year range](#year-range)
* [Return the current date as default value](#return-the-current-date-as-default-value)
* [Optionally allow users to select work days and or weekends](#optionally-allow-users-to-select-work-days-and-or-weekends)
* [Allow users to choose more than one date](#allow-users-to-choose-more-than-one-date)
* [Exclude dates or a range of dates](#exclude-dates-or-a-range-of-dates)
* [Exclude specific days](#exclude-specific-days)
* [Override days exclusion](#override-days-exclusion)
* [Allow users to change month/year](#allow-users-to-change-monthyear)
* [Change appearance](#change-appearance)
  * [Show the month after the year in the header](#show-the-month-after-the-year-in-the-header)
  * [Show the week of the year](#show-the-week-of-the-year)
  * [Display dates in other months at the start or end of the current month](#display-dates-in-other-months-at-the-start-or-end-of-the-current-month)
  * [The number of months to show at once](#the-number-of-months-to-show-at-once)
* [Return age in years/months/days (Calculator element)](#return-age-in-yearsmonthsdays-calculator)
* [Localization](#localization)
* [Demo Forms](#demo-forms)
* [Example & Tutorial](#example-amp-tutorial)

### Date Format

Date formats vary largely accross the globe, which is why this setting will be much appreciated in case you need to change to a different date format.

You are also allowed to use your own custom date format if needed. Just keep in mind when doing so that some features within Super Forms are not always compatible with a custom date format.

Possible date formats you can choose from are:

* `European - dd-mm-yy` 31-08-2021
* `Default - mm/dd/yy` 08/31/2021
* `ISO 8601 - yy-mm-dd` 2021-08-31
* `Short - d M, y` 31 Aug, 21
* `Medium - d MM, y` 31 August, 21
* `Full - DD, d MM, yy` Tuesday, 31 August, 2021
* `Custom date format`

### Date range (min/max)

By default ther is no date range defined. Meaning a user would be able to select an date based on the `Year range` you defined.

The date range comes in handy when you want to allow a user to only select a date between a specified range.

Let's say you want a user to choose a date between tomorrow and 2 weeks ahead. In this case you can enter `1` under `General > Date range (minimum)`, and `14` under `General > Date range (maximum)`.

It is also possible to define negative numbers, so if you wish to let the user to select 1 week in the past, and 3 weeks in the future then you can define `-7` (min) and `21` (max).

### Connecting 2 datepickers

Connecting datepickers is useful whenever you need to allow users to book for a specific weekend or perhaps a week or weeks.

**How to setup connected datepickers:**

Let's assume that we want a user to select a dat range where the dates must be at least 1 day apart from eachother and may not be apart from eachother for more than 6 days.

The first step is to add 2 datepickers named `from` and `till` (for example).

Now edit the `from` datepicker. Under `Min. Connect with other datepicker` choose the `till` datepicker.

The next step would be to define the total days they must be apart from eachother at a minimum. In our case we will set this to `2`.

Now when a user chooses `22 Jan 2021` in the `from` datepicker, the `till` datepickers first possible date to select would be `24 Jan 2021`.

Now we have to define tha maximum possible selection for the `till` datepicker.

We are still editing the `from` datepicker here, so we can now choose `till` for the `Max. Connect with other datepicker` option.

Now define the total days they are allowed to be apart from eachother at a maximum, which is `7` in our case, since we want to have the dates apart from eacother for a maximum of 6 days.

As you can see, we haven't touched the `till` date. All the changes where made on the `from` date which is the first date the user selects.

It is possible to also add more datepickers to this loop als long as it's logic to do.

You can also combine this with conditional logic so that a user would first need to choose date1, before date2 is visible and so on.

### Year range

With the year range you can allow your users to only switch to a year within the given range.

You can change the year range of your datepicker under `General > Year range`.

Let's say you want to allow your users to only select a data that is 2 years in the future and not beyond that. But you also don't want them to be able to select any year In that case you could define a range of `-0:+2`.

Another example could be that you want to allow users to select a date that is at maximum 20 years in the past, and 15 years in the future. In which case you could enter `-20:+15` as the range.

A couple of more examples:

* `-100:+5` 100 years in the past, 5 years in the future
* `-0:+5` 0 years in the past, 5 years in the future
* `-1:+3` 1 year in the past, 3 years in the future
* `0:+100` This allows all dates from 0 up to now and 100 years in the future


?> **NOTE:** Please keep in mind that the `General > Date range (minimum)` and `General > Date range (maximum)` will override the `General > Year range` setting.

### Return the current date as default value

There are many usecases that require the need for the current date. May it be the date of the order, or just a starting date for departure.

In this case you can enable the `General > Return the current date as default value`.

This will populate the datepicker with date of today.

?> **NOTE:** By default the user will be able to change/edit this date, if you want to prevent this you can set `Advanced > Disable the input field` to `Yes`. Another option would be to put the datepicker inside a [column](#columns), and set the [column](#columns) to be invisible via `General > Make column invisible` > `Yes`.

### Optionally allow users to select work days and or weekends

When your business or service is only during work days and not the weekends you can enable the `General > Allow users to select work days` and disable the `General > Allow users to select weekends`.

When your business or service is only during the weekends you can reverse them.

When your business or service is during both work days and weekends you can keep both enabled.

?> **NOTE:** Perhaps your business or service is only closed on Sunday, in this case you should use the `General > Exclude specific days` setting and leave above options enabled.


### Allow users to choose more than one date

By default a user can only choose 1 date. However you can change this by changing the `General > Allow user to choose a maximum of X dates` to anything bigger than 1.

Let's say you want to allow the user to choose 3 dates. In that case you can change the number `1` to `3`. That way the user will be able to choose 3 dates individually.

Optionally you can also define the mimimum dates a user is required to select under `General > Require user to choose a minimum of X dates`.

?> **NOTE:** When using this setting the dates selected by the user do not have to be next to eachother, meaning the user could skip a specific date(s) in between their selected dates. For example: `01-01-2021, 01-02-2021, 02-05-2021` would be allowed. If you require a user to choose a daterange without the option to skip dates in between you should instead use 2 datepickers that are connected with eachother.

### Exclude dates or a range of dates

This setting comes in use when excluding holidays and perhaps specific dates that your business or service is closed by default.

You can define your ranges under `General > Exclude dates or a range of dates`.

You are also allowed to use [tags](#tags-system) if needed.

**Examples:**

* `2020-03-25` (excludes a specific date)
* `2020-06-12;2020-07-26` (excludes a date range)
* `01` (excludes first day for all months)
* `10` (excludes 10th day for all months)
* `Jan` (excludes the month January)
* `Mar` (excludes the month March)
* `Dec` (excludes the month December)

### Exclude specific days

This setting is useful whenever your business or service is closed on a specific day of the week.

You can define which days you want to exclude under `General > Exclude specific days`.

Where: 0 = Sunday and 1 = Monday etc.

When you want to exclude both Sundays and Mondays from the datepicker you can enter: `0,1`

### Override days exclusion

This setting works exactly the same as [Exclude dates or a range of dates](#exclude-dates-or-a-range-of-dates) but is solely intended to override any days defined to be excluded under [Exclude specific days](#exclude-specific-days)

This can become useful in the event of the excluded day being in a month where you are not closed on those days and instead opened for business or service.

Let's say you are always closed on Sundays, with the exception of the month December. In that case you can define `Dec`.

?> **NOTE:** Any exclusion defined under `Exclude dates or a range of dates` will be left untouched.

### Allow users to change month/year

Enabled by default, to disallow users to change the month you can disable `General > Allow users to change month`

Enabled by default, to disallow users to change the year you can disable `General > Allow users to change year`

### Change appearance

There are a couple of appearance options that you can change:

#### Show the month after the year in the header

This option is disabled by default. When enabled under `General > Show the month after the year in the header` the user will be able to choose the month via a dropdown.

#### Show the week of the year

This option is disabled by default. When enabled under `General > Show the week of the year` it will display the week numbers for each week.

#### Display dates in other months at the start or end of the current month

Disabled by default, enable via `General > Display dates in other months at the start or end of the current month`

When enabled you can also optionally enable the option `General > Make days shown before or after the current month selectable` which allows the user to actually click on the date to choose it.

#### The number of months to show at once

By default this is set to `1`, which means only one month will be visible at once. When a user is viewing the month Jan, and clicks next, it will display the next month Feb.

When you increase this number to for instance `3`, it will show the months `Jan, Feb, Mar` at once. When the user clicks to view the next months, it will display `Apr, May, Jun`.

### Return age in years/months/days (Calculator element)

By default when you use a datepicker in combination with the calculator element, the datepicker will contain the timestamp of the selected date.

With this you can basically do any tipe of manipulation and calculation required.

To make things easier for you the [Calculator element](#calculator) simply adds an option for the **Datepicker** element called `General > Return age as value instead of the date`.

When enabled the datepicker will not return the date, but instead it will return the age in years.

This is useful in case you need to know the age of a user, or perhaps the age of an object.

Another option is to let the datepicker keep returning it's timestamp, and instead enable `Advanced > Enable birthdate calculations` on the calculator element itself.

This way you can have both the timestamp plus the age in years, months or days. Depending on what you choose under `Advanced > Select which value to return for calculations`.

### Localization

You can change the localization (language and format) of the datepicker via `General > Choose a localization`. The following languages/formats are available:

* English / Western (default)
* Afrikaans
* Algerian Arabic
* Arabic
* Azerbaijani
* Belarusian
* Bulgarian
* Bosnian
* Català
* Czech
* Welsh/UK
* Danish
* German
* Greek
* English/Australia
* English/UK
* English/New Zealand
* Esperanto
* Español
* Estonian
* Karrikas-ek
* Persian
* Finnish
* Faroese
* Canadian-French
* Swiss-French
* French
* Galician
* Hebrew
* Hindi
* Croatian
* Hungarian
* Armenian
* Indonesian
* Icelandic
* Italian
* Japanese
* Georgian
* Kazakh
* Khmer
* Korean
* Kyrgyz
* Luxembourgish
* Lithuanian
* Latvian
* Macedonian
* Malayalam
* Malaysian
* Norwegian Bokmål
* Dutch (Belgium)
* Dutch
* Norwegian Nynorsk
* Norwegian
* Polish
* Brazilian
* Portuguese
* Romansh
* Romanian
* Russian
* Slovak
* Slovenian
* Albanian
* Serbian
* Swedish
* Tamil
* Thai
* Tajiki
* Turkish
* Ukrainian
* Vietnamese
* Chinese zh-CN
* Chinese zh-HK
* Chinese zh-TW

### Demo Forms

The following demo forms are available under `Super Forms > Demos`:

* Calculate Days Between Dates (requires Calculator element)
* Calculate Age (requires Calculator element)

### Example & Tutorial

#### Different time range based on selected day

Let's say you have a reservation form and you have different working hours during the week.

* On Sunday and Monday you are closed
* On Friday and Saturday you are opened from 13:00 till 22:00
* On other days you are opened from 13:00 till 18:00

The full example code can be found below, which you can simply copy/past under the `Code` TAB on your builder page.

In order to accomplish this, there are a couple of things that you need to do. The steps made to create this form are as follows:

##### 1. Adding the elements

* 1. Add a datepicker field `Form Elements > Date` and name it `date`
* 2. Add a total of 2 timepicker fields `Form Elements > Time` and name them `other`, `friday_saturday`
* 3. Add a hidden field `Form Elements > Hidden` and name it `time` (we will make it a [variable field](#variable-fields) which will hold either the value from `other` or `friday_saturday`)
* 4. Edit the `other` timepicker and change `General > The time that should appear first...` to `13:00` and `General > The time that should appear last...` to `18:00`
* 5. Edit the `friday_saturday` timepicker and change `General > The time that should appear first...` to `13:00` and `General > The time that should appear last...` to `22:00`

##### 2. Changing the date format

In our example we need to know which day the user selected (not to be confused with "which date"). We need to know the day of the week as in "Monday", "Tuesday" etc. Based on this we can display the correct timepicker via [conditional logic](#conditional-logic). Edit the `date` field and change the date format `General > Date Format` to `Full`.

Since we are closed on Sunday and Monday we must also exclude these days from the datepicker. To do so edit the `date` field and set `General > Exclude specific days` to `0,1`. This will make sure both Sundays and Mondays can't be selected by the user.

##### 3. Conditionally show the timepickers

Now we have to define the [conditional logic](#conditional-logic) for our timepickers so they are displayed accordingly.

First let's edit the `other` timepicker so that it will be displayed when the `date` does **not contain** the value `Fri` and `Sat`.

Set `Conditional Logic > Action` to `Show` and set `Conditional Logic > When to Trigger?` to `All`.

Define the conditional logic as follows:

[`{date}` | `!! Not contains` | `Fri`] **AND** [`{date}` | `!! Not contains` | `Sat`]

Now edit the `friday_saturday` timepicker so that it will be displayed when the `date` **contains** either the value `Fri` or `Sat`.

Set `Conditional Logic > Action` to `Show` and set `Conditional Logic > When to Trigger?` to `All`.

Define the conditional logic as follows:

[`{date}` | `!! Contains` | `Fri`] **OR** [`{date}` | `!! Contains` | `Sat`]

Test to see if the timepickers are displayed accordingly, if everything is correctly defined you should now only be able to choose a time between 13:00 and 22:00 for the Fridays and Saturdays. And for the other days between 13:00 and 18:00. You should also not be able to select the Sundays and Mondays because you excluded these days on the `date` field.

##### 4. Merging timepickers into one

If you setup the conditional logic correctly you should only see 1 timepicker at once. Which means that only one timepicker will be send via E-mail and stored in the Contact Entry.

However they do not share the same field name right now (`other` and `friday_saturday`).

This might not be a problem in normal situations, because those fields can still share the same E-mail label. However when you are using a third party service, or doing a POST requires. Or any other data handling (exproting entries for instance) that depends on the field name(s), it might be required to always have the same field name no matter what field was conditionally visible.

?> **TIP:** In general it is good practise to use a [variable field](#variable-fields) in these situations.

If you decide to use a variable field to merge multiple fields into one, you will most likely want to exclude the others fields from both emails and from being saved in the contact entry. You can do so by editing both timepickers `other` and `friday_saturday` and setting `Advanced > Exclude from email` to `Exclude from all emails`. And disable `Advanced > Do not save field in Contact Entry`.

Now edit your hidden field `time` and set `Conditional Variable (dynamic value) > Make field variable` to `Enabled (make variable)`.

In this case there are 2 different ways to configure the conditional logic. The first one being the simples which is to do a check on the datepicker to see if it is not empty. When it is not empty we will apply the condition and therefore grab the values from the timepicker and combine them. In this case there will always be one timepicker conditionally hidden which makes this condition possible:

[`{date}` - `!= Not equal` - `""`]

__When above conditions are met set following value:__

`{other}{friday_saturday}`

**Explanation of the above conditional logic:**

The above condition simply checks if the `date` field is filled out (an date was selected by the user) which means the tag `{date}` would not be empty. Which means the condition holds true and therefore is applied. Which means that the value of the hidden field would be updated to whatever the tags `{other}{friday_saturday}` holds. Which would hold the selected time by the user for both the `other` timepicker and the `friday_saturday` timepicker. However only 1 of the timepickers would be visible at a given time, so either `{other}` or `{friday_saturday}` will always be an empty string (undefined) because conditionally hidden. If the above conditional logic doesn't make sense to you right now then please see the below alternative which might seem more logical.

A more logical conditional logic (to merge the 2 timepickers) would be as follows:

[`{other}` - `!= Not equal` - `""`]

__When above conditions are met set following value:__

`{other}`

Now add another conditional rule by clicking on the `[+]` icon

[`{friday_saturday}` - `!= Not equal` - `""`]

__When above conditions are met set following value:__

`{friday_saturday}`

**Explanation of the above conditional logic:**

The above conditional logic consists out of 2 conditions. The first one checks if the `other` timepicker is not empty, and if so it will populate the `time` field to be whatever value `{other}` returns. Which is the time itself in case of the `other` timepicker being filled out by the user and not conditionally hidden.

The same applies for `friday_saturday` timepicker.

##### Example form code

You can copy/paste the below code under `Code` TAB on your form builder to see it in action.

The only difference in the below form code is that we added a HTML element to easily debug / display the selected values. This way you can see what a hidden field's value contains, and thus what our variable field contains when changing the datepicker and timepicker.

```json
[{"tag":"date","group":"form_elements","data":{"name":"date","email":"Date:","placeholder":"Select a date","range":"-100:+5","work_days":"true","weekends":"true","maxPicks":"1","minPicks":"0","excl_days":"0,1","format":"DD, d MM, yy","custom_format":"dd-mm-yy","first_day":"1","changeMonth":"true","changeYear":"true","showMonthAfterYear":"","showWeek":"","showOtherMonths":"","selectOtherMonths":"","numberOfMonths":"1","validation":"none","may_be_empty":"false","grouped":"0","width":"0","wrapper_width":"0","connected_min_days":"1","connected_max_days":"1","exclude":"0","custom_tab_index":"-1","icon_position":"outside","icon_align":"left","icon":"calendar","conditional_action":"disabled","conditional_trigger":"all","pdfOption":"none"}},{"tag":"time","group":"form_elements","data":{"name":"other","email":"Time:","placeholder":"Select a time","validation":"none","may_be_empty":"false","may_be_empty_conditions":[{"field":"","logic":"","value":"","and_method":"","field_and":"","logic_and":"","value_and":""}],"format":"H:i","step":"15","minlength":"13:00","maxlength":"18:00","duration":"false","grouped":"0","width":"0","exclude":"0","custom_tab_index":"-1","icon_position":"outside","icon_align":"left","icon":"clock;far","conditional_action":"show","conditional_trigger":"all","conditional_items":[{"field":"{date}","logic":"not_contains","value":"Fri","and_method":"and","field_and":"{date}","logic_and":"not_contains","value_and":"Sat"}],"pdfOption":"none"}},{"tag":"time","group":"form_elements","data":{"name":"friday_saturday","email":"Time:","placeholder":"Select a time","validation":"none","may_be_empty":"false","may_be_empty_conditions":[{"field":"","logic":"","value":"","and_method":"","field_and":"","logic_and":"","value_and":""}],"format":"H:i","step":"15","minlength":"13:00","maxlength":"22:00","duration":"false","grouped":"0","width":"0","exclude":"0","custom_tab_index":"-1","icon_position":"outside","icon_align":"left","icon":"clock;far","conditional_action":"show","conditional_trigger":"all","conditional_items":[{"field":"{date}","logic":"contains","value":"Fri","and_method":"or","field_and":"{date}","logic_and":"contains","value_and":"Sat"}],"pdfOption":"none"}},{"tag":"hidden","group":"form_elements","data":{"name":"time","email":"Hidden:","exclude":"0","code_length":"7","code_characters":"1","code_uppercase":"true","code_invoice_padding":"4","conditional_variable_action":"enabled","conditional_variable_method":"manual","conditional_variable_row":"date","conditional_variable_col":"date","conditional_variable_delimiter":",","conditional_variable_enclosure":"\"","conditional_variable_items":[{"field":"{date}","logic":"not_equal","value":"","and_method":"","field_and":"","logic_and":"","value_and":"","new_value":"{other}{friday_saturday}"}],"pdfOption":"none"}},{"tag":"html","group":"html_elements","data":{"title":"Debugging:","html":"<strong>date:</strong> {date}\n<strong>other:</strong> {other}\n<strong>friday_saturday:</strong> {friday_saturday}\n-------------------------\n<strong>time:</strong> {time}","nl2br":"true","conditional_action":"disabled","conditional_trigger":"all","pdfOption":"none"}}]
```

