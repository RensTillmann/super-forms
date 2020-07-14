# Conditional Validation

The **Conditional Validation** works in the same fasion as the [Conditional Logic](conditional-logic) does except that this is used for throwing an error message for a specific field whenever the selected condition(s) did not match.

**Example use case 1:**

Let's say you let the user enter their age and you only allow users to submit the form who are 16 years or older.
In this case you can use the `< Less than` condition and enter the number 16 for this condition.
Now whenever a user enters the number 15 or below, it will display the given error message to the user.

**Example use case 2:**

Let's say you only want to allow users who are between 16 and 18 years old to register the form.
In this case we can use the `>= && <= Greater than or equal to AND Less than or equal to` condition and enter the number 16 for the first condition and the number 18 for the second.
Now whenever a user enters the number 15 or below or either 19 or above it will display the error message to the user.

_Below you can find all the possible conditional validation methods:_

* ?? Contains
* !! Not contains
* == Equal
* != Not equal
* &gt; Greater than
* &lt;  Less than
* &gt;= Greater than or equal to
* &lt;= Less than or equal
* &gt; &amp;&amp; &lt; Greater than AND Less than
* &gt; || &lt; Greater than OR Less than
* &gt;= &amp;&amp; &lt; Greater than or equal to AND Less than
* &gt;= || &lt; Greater than or equal to OR Less than
* &gt; &amp;&amp; &lt;= Greater than AND Less than or equal to
* &gt; || &lt;= Greater than OR Less than or equal to
* &gt;= &amp;&amp; &lt;= Greater than or equal to AND Less than or equal to
* &gt;= || &lt;= Greater than or equal to OR Less than or equal to
