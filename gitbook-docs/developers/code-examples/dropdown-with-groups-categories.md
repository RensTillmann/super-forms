---
description: >-
  How to add groups for WordPress dropdown element so the items belong to
  categories/subcategories.
---

# Dropdown with groups (categories)

In the below JavaScript code simply change `field_name` to the dropdown field name, and add or edit `red1`, `blue1`, `green1` with the first item that should belong to the group. If you have multiple dropdowns you can extend the `groups` object.

You can copy paste this code under **Super Forms > Settings > Custom JS**.

```javascript
var groups = {
    field_name: {
        red1: 'Red colors:',
        blue1: 'Blue colors:',
        green1: 'Green colors:'
    },
    //another_dropdown_field: {
    //    value1: 'First group:',
    //    value2: 'Second group:',
    //    value3: 'Third group:'
    //}
};
Object.keys(groups).forEach(function(fieldName){
    var field = document.querySelector('.super-field input[name="'+fieldName+'"]'); if(!field) return;
    var list = field.parentNode.querySelector('.super-dropdown-list'); if(!list) return;
    Object.keys(groups[fieldName]).forEach(function(dropdownValue){
        var node = document.createElement('strong'),
            item = document.querySelector('li[data-value="'+dropdownValue+'"]');
        node.style.width = '100%';node.style.display = 'inline-block'; node.style.padding = '5px 0px 5px 3px';
        node.innerText = groups[fieldName][dropdownValue];
        item.parentNode.insertBefore(node, item);
    });
});
```
