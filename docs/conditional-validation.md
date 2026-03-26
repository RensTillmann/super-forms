# Conditional Validation

The **Conditional Validation** works in the same fasion as the [Conditional Logic](conditional-logic) does except that this is used for throwing an error message for a specific field whenever the selected condition(s) did not match.

> ⚠️ **Browser-only feature — no server-side equivalent**
>
> Conditional Validation (including the conditional-required `may_be_empty = conditions` feature introduced in v4.9.0) is implemented entirely in front-end JavaScript. It requires a modern browser with JavaScript enabled.
>
> There is **no server-side equivalent**: a user who submits via a crafted HTTP POST request bypasses all conditional-validation rules unconditionally. Do not rely on this feature alone to enforce data-integrity or security requirements on the server.

**Example use case 1:**

Let's say you let the user enter their age and you only allow users to submit the form who are 16 years or older.
In this case you can use the `< Less than` condition and enter the number 16 for this condition.
Now whenever a user enters the number 15 or below, it will display the given error message to the user.

**Example use case 2:**

Let's say you only want to allow users who are between 16 and 18 years old to register the form.
In this case we can use the `>= && <= Greater than or equal to AND Less than or equal to` condition and enter the number 16 for the first condition and the number 18 for the second.
Now whenever a user enters the number 15 or below or either 19 or above it will display the error message to the user.

## Conditional Required Fields (may_be_empty = conditions)

Since **v4.9.0**, a field can be made conditionally required: it is only required when one or more conditions are true. This is configured by setting the field's "May be empty" option to `conditions` and defining the conditional logic rules.

**How it works (browser-side):**
The JavaScript engine evaluates the conditions at submit time. If the condition(s) match, the field is treated as required (`allowEmpty = false`); otherwise the field may be left blank.

**Limitations:**
- Requires JavaScript to be enabled in the browser.
- Has no server-side equivalent — a crafted HTTP POST bypasses condition evaluation entirely.
- The hidden-parent check (`has_hidden_parent`) inspects `data-conditional-action` attributes and the `super-conditional-hidden` class, but not the `super-hidden` class on parent shortcodes. A secondary guard inside `handle_validations` handles the latter case.

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
