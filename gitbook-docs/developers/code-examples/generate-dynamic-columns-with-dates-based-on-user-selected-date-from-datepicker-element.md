---
description: >-
  This is an example form that allows a user to select a date from a Datepicker
  element. After which a dynamic column with fields will be added for all
  following days.
---

# Generate dynamic columns with dates based on user selected date from Datepicker element

Below is an image of an example where the users selects a date, after which the script automatically adds 3 [Dynamic columns](../../elements/layout-elements/column-grid.md#dynamic-add-more) with the 2 dates that follow the selected date from the [Datepicker element](../../elements/form-elements/datepicker.md)

***

* Get the[ Form demo code](generate-dynamic-columns-with-dates-based-on-user-selected-date-from-datepicker-element.md#form-code)
* Get the [JavaScript code](generate-dynamic-columns-with-dates-based-on-user-selected-date-from-datepicker-element.md#javascript-code)

***

### Demo:

<figure><img src="../../.gitbook/assets/Recording 2025-04-30 at 15.54.46.gif" alt=""><figcaption><p>Form with dynamic columns added based on selected date</p></figcaption></figure>

### Form code:

{% hint style="warning" %}
Copy paste the below form code on your \[CODE] tab as your Form Elements
{% endhint %}

```
[
    {
        "tag": "date",
        "group": "form_elements",
        "data": {
            "name": "dynamic_column_datepicker",
            "email": "Date:",
            "placeholder": "Select a date",
            "icon": "calendar",
            "work_days": "true",
            "weekends": "true"
        }
    },
    {
        "tag": "column",
        "group": "layout_elements",
        "inner": [
            {
                "tag": "heading",
                "group": "html_elements",
                "data": {
                    "title": "Monday 5th May",
                    "size": "h3",
                    "heading_size": "14"
                }
            },
            {
                "tag": "dropdown",
                "group": "form_elements",
                "data": {
                    "name": "option",
                    "email": "Option (Monday 5th May):",
                    "dropdown_items": [
                        {
                            "checked": "false",
                            "label": "First choice",
                            "value": "first_choice"
                        },
                        {
                            "checked": "false",
                            "label": "Second choice",
                            "value": "second_choice"
                        },
                        {
                            "checked": "false",
                            "label": "Third choice",
                            "value": "third_choice"
                        }
                    ],
                    "placeholder": "- select a option -",
                    "icon": "caret-square-down;far"
                }
            },
            {
                "tag": "dropdown",
                "group": "form_elements",
                "data": {
                    "name": "reason",
                    "email": "Reason (Monday 5th May):",
                    "dropdown_items": [
                        {
                            "checked": "false",
                            "label": "First choice",
                            "value": "first_choice"
                        },
                        {
                            "checked": "false",
                            "label": "Second choice",
                            "value": "second_choice"
                        },
                        {
                            "checked": "false",
                            "label": "Third choice",
                            "value": "third_choice"
                        }
                    ],
                    "placeholder": "- select a option -",
                    "icon": "caret-square-down;far"
                }
            }
        ],
        "data": {
            "duplicate": "enabled",
            "duplicate_limit": "7"
        }
    }
]
```

***

### JavaScript code:

JavaScript code, which you can place on the page where your form is located (or you could add it under `Super Forms > Settings > Custom JS` )

{% hint style="warning" %}
Place the below JavaScript code on the page where your form is located

(or add it under `Super Forms > Settings > Custom JS` ), make sure to adjust it according to your form field names. For example, the code below had hardcoded field name references to Datepicker field named`dynamic_column_datepicker` and Dropdown fields named `option` and `reason` .
{% endhint %}

```javascript
(function(){
	var timer2,timer = setInterval(function() {
	    if (super_common_i18n && SUPER) {
	        console.log(super_common_i18n.dynamic_functions.after_field_change_blur_hook);
	        clearInterval(timer);
	        super_common_i18n.dynamic_functions.after_field_change_blur_hook.push({
	            name: 'f4d_generate_week_columns'
	        });
	        var currentDateValue = '';
	        if(document.querySelector('.super-form input[name="dynamic_column_datepicker"]')){
	        	currentDateValue = document.querySelector('.super-form input[name="dynamic_column_datepicker"]').value;
	    	}
	        SUPER.f4d_generate_week_columns = function(args) {
	            if (!args || !args.el || args.el.name !== 'dynamic_column_datepicker') return;
				if(!timer2){
					timer2 = setInterval(function() {
			            clearInterval(timer2);
			            timer2 = null;
			            if(currentDateValue!==args.el.value){
			            	currentDateValue = args.el.value;
			                const form = SUPER.get_frontend_or_backend_form({ el: args.el });
			                if (!form) return; 
			                clearDynamicColumnsExceptFirst(form);
							const selected = args.el.value;
			                const [day, month, year] = selected.split('-').map(Number);
			                const baseDate = new Date(year, month - 1, day);
			                if (isNaN(baseDate)) return;
			                addColumnsWithDates(baseDate, form);
			            }
					}, 150);
				}
	        }
	    }
	}, 150); 
    function formatHeading(date) {
        const options = { weekday: 'long', month: 'long' };
        const weekdayMonth = date.toLocaleDateString('en-GB', options);
        const day = date.getDate();
        const suffix = (d => {
            if (d > 3 && d < 21) return 'th';
            switch (d % 10) {
                case 1: return 'st';
                case 2: return 'nd';
                case 3: return 'rd';
                default: return 'th';
            }
        })(day);
        return `${weekdayMonth.split(' ')[0]} ${day}${suffix} ${weekdayMonth.split(' ')[1]}`;
    }
    function clearDynamicColumnsExceptFirst(form) {
        const columns = form.querySelectorAll('.super-duplicate-column-fields');
        const firstColumn = columns[0];
        SUPER.init_clear_form({ form: form, clone: firstColumn });
		for (let i = columns.length-1; i >= 1; i--){
            const deleteBtn = columns[i].querySelector('.super-delete-duplicate');
            deleteBtn.click();
        }
    }
    function updateHeading(container, dateObj) {
        var formattedDate = formatHeading(dateObj);
        var el = container.querySelector('.super-heading-title h3');
		if (el) el.innerHTML = formattedDate;
		el = container.querySelector('.super-shortcode-field[data-oname="option"]');
		label = 'Option ('+formattedDate+')';
		if (el) el.dataset.email = label;
		el = container.querySelector('.super-shortcode-field[data-oname="reason"]');
		label = 'Reason ('+formattedDate+')';	
		if (el) el.dataset.email = label;
	}
    function addColumnsWithDates(baseDate, form) {
        const allContainers = () => form.querySelectorAll('.super-duplicate-column-fields');
        // First column
        const firstColumn = allContainers()[0];
        updateHeading(firstColumn, baseDate);
        const addBtn = firstColumn.querySelector('.super-add-duplicate');
        if (!addBtn) return;
        for (let i = 1; i < 7; i++) {
            const prevCount = allContainers().length;
            addBtn.click();
            // Wait until new column is actually added
            let retries = 0;
            while (allContainers().length <= prevCount && retries < 20) {
                retries++;
            }
            const newColumns = allContainers();
            const newColumn = newColumns[newColumns.length - 1];
            const newDate = new Date(baseDate);
            newDate.setDate(baseDate.getDate() + i);
            updateHeading(newColumn, newDate);
        }
    }
})();
```
