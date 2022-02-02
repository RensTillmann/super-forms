# Popups

## Documentation

### Creating a form and enabling popup

Create your form and enable and define the popup settings under `Form Settings` > `Popup Settings`.

### Adding the popup to a single page or multiple pages

Grab the form shortcode and add it on the page(s) where you want to display the popup (form).

### Adding the popup to all pages

If you want to display the popup on all of your pages, it is recommended to use a `Text Widget` with the form shortcode and to put it into the footer of your website. This way the popup and form will be loaded on all pages and can be triggered on all pages.

### Opening the popup via a link or button

There are 2 methods to open the popup manually.
In the below examples simply replace `XXX` with your form ID.

#### Open popup with URL

```html
<a href="#super-popup-XXX">Open the Popup</a>
```

#### Open popup with shortcode

```html
[super-popup id=XXX]Click Here for Extra Bonus[/super-popup]
```
