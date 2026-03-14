---
description: >-
  Trigger or execute custom JavaScript when a column becomes conditionally
  visible on your WordPress form.
---

# Execute custom JS when a column becomes conditionally visible

In some cases you might want to trigger or execute some custom JavaScript upon a column becoming conditionally visible. This is possible with just a little code. In this example we have added a custom class to the column named `f4d-column-script`. This way we can identify the column and do a check on it wether it became visible at a specific point. As soon as it becomes visible to the user it executes the custom javascript as seen below. Just replace `YOUR CUSTOM JAVASCRIPT GOES HERE` with your JS.

```javascript
// Check every 100ms and figure out if the column became visible or not
setInterval(function(){
    var column = document.querySelector('.f4d-column-script');
    if(column){
        // First check if it has any of the classes
        if(column.style.display=='block'){
            if(!column.classList.contains('super-custom-js-executed')){
                column.classList.add('super-custom-js-executed');
                // YOUR CUSTOM JAVASCRIPT GOES HERE
            }
        }else{
            // do nothing
            column.classList.remove('super-custom-js-executed');
        }
    }
},100);
```
