# Foreach loops

* [What is a foreach loop?](#what-is-an-email-foreach-loop)
* [How to create my own foreach loops?](#how-to-create-my-own-foreach-loops)
* [How to loop over selected checkboxes and dropdown items?](#how-to-loop-over-selected-checkboxes-and-dropdown-items)
* [How to loop over files?](#how-to-loop-over-files)

?> **NOTE:** This feature also works with HTML elements since v4.6.0+

## What is a foreach loop?

A foreach loop is a method used inside your E-mails, HTML element and generated PDF file.

It can be used in combination with [Dynamic column](columns).

This will allow you to loop over all the dynamically added fields by a user and display this information either in your E-mails, HTML elements on the front-end or inside your generated PDF file.

## How to create my own foreach loops?

In order for the foreach loop to work you must have a `Dynamic` column added to your form. 

?> **NOTE:** Make sure that the HTML element is placed **outside** your dynamic column!

For example, let's say you have a form that users fill out to register for a team based game/match. They are required to enter 3 to 5 persons per team.

In this case you will add a Dynamic column to your form and set it's limits to a minimum of 3 and a maximum of 5.

Now inside the dynamic column add two Text fields `First name` and `Last name`.

The final step is to place the HTML element with the below content outside the dynamic column (do not put it inside the dynamic colum):

```html
foreach(first_name):
    Team member #<%counter%>: <%first_name%> <%last_name%><br />
endforeach;
```

As you can see we used `first_name` to loop over the dynamic column that contains the field named `first_name`.

The example above also implements the `<%counter%>` tag to retrieve the current index of this team member.

The result of above foreach loop with a total of 4 team members filled out on the form could be as follows:

```html
    Team member #1: Bill Gates
    Team member #2: Steve Jobs
    Team member #3: Elon Musk
    Team member #4: Mark Zuckerberg
```

## How to loop over selected checkboxes and dropdown items?

The below example loops over all selected items of a `Checkbox` field and prints both the item Label and Value. This can be used inside the Dynamic column itself:

```html
foreach(option;loop):
    #<%counter%>: Label: <%label%> / Value: <%value%><br />
endforeach;
```

If you wish to combine multiple checkboxes that are inside a dynamic column you could merge them with the use of a HTML element. First create a dynamic column with your checkbox element inside. Now add a HTML element and set it's content to below (rename `option` to your checkbox field name). You can name the HTML field `options_list`.

```html
foreach(option;loop):
    #<%counter%>: <%value%><br />
endforeach;
```

Now outside of the dynamic column add a final HTML element and set it's content as follows. This will loop over all the `option` fields in the form, and then it prints out the `options_list` that belongs to this dynamic column. If there are multiple columns then the below foreach loop will simply append them after eachother. As you can see the foreach loop above contains a horizontal rule so that each checkbox items are seperated nicely. Of course you are totally free to design this in the way you wish, because this is plain HTML and CSS.

```html
foreach(option):
    {options_list}
endforeach;
```

## How to loop over files?
  
When you are using a `File upload` element you can display the file info (including the image itself) before they are being uploaded.

Super Forms does this by default, but if you need the picture to be displayed somewhere else in your form you can do so with the use of the below example code inside your HTML element:

```html
foreach(file;loop):
    <strong>Name (<%counter%>):</strong> <%name%>
    <strong>URL (<%counter%>):</strong> <%url%>
    <strong>Extension (<%counter%>):</strong> <%ext%>
    <strong>Type (<%counter%>):</strong> <%type%>
    <strong>ID (<%counter%>):</strong> <%attachment_id%>
endforeach;
```

The below example loops over the files and links to the file itself:

```html
foreach(file;loop):
    <strong>File <%counter%>:</strong> <a href="<%url%>"><%name%></a>
endforeach;
```

To display the image directly to the user you can use the below HTML.

Keep in mind that if you allow users to upload files other than images, you might want to make sure you add a custom File upload element purely for image file types.

```html
foreach(file;loop):
    <img src="<%url%>" style="max-width:200px;max-height:200px;" /><br />
endforeach;
```
