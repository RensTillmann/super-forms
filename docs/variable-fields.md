# Variable Fields

Creating the most complex forms is possible with **variable fields** ([Hidden field](hidden-field)).
A variable field it's value can be updated dynamically on the fly based on other fields values.
This allows you to have more flexibility within your final value or for doing complex calculations and speed things up when building your form.

* [What is a variable field?](#what-is-a-variable-field)
* [When to use a variable field?](#when-to-use-a-variable-field)
* [How to create a variable field?](#how-to-create-a-variable-field)
* [Using {tags} with variable fields](#using-tags-with-variable-fields)
* [Example form code](#example-form-code)


### What is a variable field?

A variable field is a [Hidden field](hidden-field) that contains a value that dynamically changes based on other field(s) values. In programming languages you also have a so called $variable. In general this will act the same way.


### When to use a variable field?

You should use a variable field whenever you require to have a specific final value that can vary based on user selected options in an other field or in other fields. A simple example would be whenever you want to apply 3 different discounts based on a selected quantity.

**Example:**<br />
When a user orders 10 products 0% discount should be applied, when more than 10 products are ordered the user receives 15% discount and when 30 or more products are ordered the user receives 35% discount.

Because the discount amount is dynamic you should use a variable field to be able to retrieve the correct discount.


### How to create a variable field?

From the `Form Elements` TAB drag and drop the `Hidden field` element in place.
Edit the element and choose `Conditional Variable (dynamic value)` from the dropdown.
Now set the **Make field variable** option to: Enable (make variable).
Now apply the conditions and enter the value that you require when the conditions are met.

These conditions work the exact same way as [Conditional Logic](conditional-logic) do except that it will update the value instead of showing/hiding elements.


### Using {tags} with variable fields

Variable fields can deal with {tags}, please read the [{tags} system](tags-system) section for more information about tags.


### Example form code

_The below form is an example to apply discounts based on the quantity ordered by the user_

	[{"tag":"quantity","group":"form_elements","data":{"name":"quantity","email":"Quantity:","value":"0"}},{"tag":"hidden","group":"form_elements","data":{"name":"discount","email":"Discount:","conditional_variable_action":"enabled","conditional_items":[{"field":"quantity","logic":"less_than_or_equal","value":"10","and_method":"","field_and":"quantity","logic_and":"","value_and":"","new_value":"0"},{"field":"quantity","logic":"greater_than_or_equal","value":"11","and_method":"","field_and":"quantity","logic_and":"","value_and":"","new_value":"15"},{"field":"quantity","logic":"greater_than_or_equal","value":"30","and_method":"","field_and":"quantity","logic_and":"","value_and":"","new_value":"35"}]}},{"tag":"html","group":"html_elements","data":{"html":"Your discount: {discount}%"}},{"tag":"spacer","group":"html_elements","data":{"height":50,"conditional_action":"disabled","conditional_trigger":"all"}}]


_The below form is an example to calculate total adults and children with use of dynamic columns and calculator element (requires the [Calculator Add-on](calculator-add-on))_

	[{"tag":"column","group":"layout_elements","inner":[{"tag":"radio","group":"form_elements","data":{"name":"person","email":"Option:","radio_items":[{"checked":false,"image":"","label":"Adult","value":"adult"},{"checked":false,"image":"","label":"Child","value":"child"}],"display":"horizontal","icon":"dot-circle-o"}},{"tag":"hidden","group":"form_elements","data":{"name":"var_adult","exclude":"2","conditional_variable_action":"enabled","conditional_items":[{"field":"person","logic":"equal","value":"adult","and_method":"","field_and":"person","logic_and":"","value_and":"","new_value":"1"}]}},{"tag":"hidden","group":"form_elements","data":{"name":"var_child","exclude":"2","conditional_variable_action":"enabled","conditional_items":[{"field":"person","logic":"equal","value":"child","and_method":"","field_and":"person","logic_and":"","value_and":"","new_value":"1"}]}}],"data":{"duplicate":"enabled","duplicate_dynamically":"true"}},{"tag":"calculator","group":"form_elements","data":{"name":"total_adults","email":"Total adults:","math":"{var_adult}+{var_adult_*}","description":"Adults:","decimals":"0","thousand_separator":",","icon":"calculator"}},{"tag":"calculator","group":"form_elements","data":{"name":"total_children","email":"Total children:","math":"{var_child}+{var_child_*}","description":"Children:","decimals":"0","thousand_separator":",","icon":"calculator"}}]