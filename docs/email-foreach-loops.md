# E-mail foreach loops

* [What is an email foreach loop?](#what-is-an-email-foreach-loop)
* [How to create my own foreach loops?](#how-to-create-my-own-foreach-loops)
* [How to loop over files?](#how-to-loop-over-files)

?> **NOTE:** This feature also works with HTML elements since v4.6.0+

## What is an email foreach loop?

With the Super Forms build in `foreach loops` for your emails you will have more flexibility the way your emails will be generated based on user input.

The foreach loop will be used in combination with elements inside a so called [Dynamic column](columns).

If you are familiar with any programming language you can construct your own loops with ease for your emails.

## How to create my own foreach loops?

An example foreach loop would look like this:

```php
foreach(first_name):
    Person #<%counter%>: <%first_name%> <%last_name%><br />
endforeach;
```

Depending on how many dynamic columns have been generated a new extra line is printend inside the email.

The `foreach(first_name)` will have to contain any of the field names that was inside the dynamic column. If your dynamic column has a field named `last_name` it could also use this as a parameter. Both will work and is only used to point out to the correct dynamic column the loop belongs to.

The `<%counter%>` will always output the current loop number, for instance when having 3 dynamic columns it would count up from 1 to 3.

The `<%first_name%>` in this example will be replaced with the entered value in the field that was named **first_name**.

The example output would look something like this:

```html
Person #1: Mike Tyson
Person #2: Bill Gates
Person #3: Mark Zuckerberg
```

## How to loop over files?
  
You can simply loop over all uploaded files from a file upload element by replacing `fileupload_field_name_here` in the below example.

You can use this inside a HTML element as well as inside your E-mail body.

The below example loops over the files, and prints it's data in a list form:

```php
foreach(fileupload_field_name_here;loop):
    <strong>Name (<%counter%>):</strong> <%name%>
    <strong>URL (<%counter%>):</strong> <%url%>
    <strong>Extension (<%counter%>):</strong> <%ext%>
    <strong>Type (<%counter%>):</strong> <%type%>
    <strong>ID (<%counter%>):</strong> <%attachment_id%>
endforeach;
```

The below example loops over the files and links to the file itself:

```php
foreach(fileupload_field_name_here;loop):
    <strong>File <%counter%>:</strong> <a href="<%url%>"><%name%></a>
endforeach;
```
