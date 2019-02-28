# E-mail foreach loops

* [What is an email foreach loop?](#what-is-an-email-foreach-loop)
* [How to create my own foreach loops?](#how-to-create-my-own-foreach-loops)


?> **NOTE:** This feature also works with HTML elements since v4.6.0+


### What is an email foreach loop?

With the Super Forms build in `foreach loops` for your emails you will have more flexibility the way your emails will be generated based on user input.

The foreach loop will be used in combination with elements inside a so called [Dynamic column](columns).

If you are familiar with any programming language you can construct your own loops with ease for your emails.



### How to create my own if statements?

An example foreach loop would look like this: 

	foreach(first_name): 
		Person #<%counter%>: <%first_name%> <%last_name%><br />
	endforeach;

Depending on how many dynamic columns have been generated a new extra line is printend inside the email.

The `foreach(first_name)` will have to contain any of the field names that was inside the dynamic column. If your dynamic column has a field named `last_name` it could also use this as a parameter. Both will work and is only used to point out to the correct dynamic column the loop belongs to.

The `<%counter%>` will always output the current loop number, for instance when having 3 dynamic columns it would count up from 1 to 3.

The `<%first_name%>` in this example will be replaced with the entered value in the field that was named **first_name**.

The example output would look something like this:

	Person #1: Mike Tyson
	Person #2: Bill Gates
	Person #3: Mark Zuckerberg

