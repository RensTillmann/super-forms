# PDF Generator Add-on:

* [About](#about)
* [Quick start](#quick-start)
* [Include/Exclude elements from PDF](#include-exclude-elements-from-pdf)
* [Setting up header and footer](#setting-up-header-and-footer)
* [Using dynamic PDF filename](#using-dynamic-pdf-filename)
* [Attach PDF to Admin E-mail](#attach-pdf-to-admin-e-mail)
* [Attach PDF to Confirmation E-mail](#attach-pdf-to-confirmation-e-mail)
* [Exclude PDF from Contact Entries)](#exclude-pdf-from-contact-entries)
* [Show download PDF button](#show-download-pdf-button)
* [Portrait vs Landscape](#portrait-vs-landscape)
* [Advanced settings](#advanced-settings)
* [Pricing](#pricing)

## About

This Add-on allows you to convert any form submission into a PDF file which would look identical to how the form was displayed on the front-end in the browser.

When a user submits the form, the PDF will be generated and optionally (if enabled) attached to the Admin and/or Confirmation E-mail.

The PDF file will also be attached to the Contact Entry (if enabled).

You also have the option to specifically include or exclude elements from the PDF, which should give you a ton of flexibility to choose from.

You can also define a Header and Footer element which would then be visible on all pages of the generated PDF file.

## Quick start

The Add-on comes with a 15 day free trial, so you can try it out for free (no strings attached). Play around with it and decide if it's for you or not.

To enable the trial, login to your WordPress site and navigate to `Super Forms > Add-ons`. On this page you can start the 15 day trial for the PDF Generator.

Once the trial is activated, you can navigate to any of your existing forms `Super Forms > Your forms`, or create a new form `Super Forms > Create form`.

Now click on the `PDF` TAB at the top of the builder page. Here you will find all the settings and options for the Add-on.

To enable PDF generation you can simple check the option `Enable Form to PDF generation`.

## Include/Exclude elements from PDF

When creating your form, you will add some elements which by default are visible to the user unless defined otherwise. Any element that is visible to the user will also be visible in the generated PDF. However you can override this behaviour be editing any element in your form and navigating to the `PDF Settings` section where you can choose between one of the following options:

* Show on Form and in PDF file (default)
* Only show on Form
* Only show in PDF file
* Use as PDF header
* Use as PDF footer

## Setting up header and footer

In order to enable a header or footer you must define which element in your form should act as such. Please note that there can only be 1 element selected for either one.

When you require more elements to be placed in either one, you can simply use a `Column` element and define it as your header/footer. Just put any elements that you require in your header/footer inside this column.

You can enable a header by editing your element and navigating to the `PDF Settings` section. There you can choose between `Use as PDF header` or `Use as PDF footer`.

### Displaying pagination

Inside your header and footer you can use the tags `{pdf_page}` and `{pdf_total_pages}` inside a HTML element to display the current page.

## Using dynamic PDF filename

When the PDF file is saved or downloaded it will have a default name `form.pdf`. You can change this under the `PDF filename` setting.

This setting is compatible with the [Tags system](#tags-system) so you can generate dynamic filenames based on user input data.

For instance, when you have a form with fields named `first_name` and `last_name`, you can define your filename as: `{first_name}-{last_name}.pdf` which would translate to `John-Doe.pdf`.

You can do the same for the `E-mail label` setting.

## Attach PDF to Admin E-mail

By default the PDF will be attached to the Admin E-mail, but you can disable this by unchecking the option `Attach generated PDF to admin e-mail`

## Attach PDF to Confirmation E-mail

By default the PDF will be attached to the Confirmation E-mail, but you can disable this by unchecking the option `Attach generated PDF to confirmation e-mail`

## Exclude PDF from Contact Entries

By default the PDF will be saved in the Contact Entry (if you enabled to save Contact Entries that is). You can disable this by checking `Do not save PDF in Contact Entry`

## Show download PDF button

In some cases you might not send any E-mails and perhaps not even save a Contact Entry, but you might still want to download the PDF that was generated. In that case you can display a `Download Summary` button to the user after the form was submitted. You can enable this by checking the `Show download button to the user after PDF was generated` setting.

You can optionally define the download button text e.g: `Download Summary` or `Download PDF file` (or anything that suits your usecase).

You can also define what text should be displayed during the PDF generation itself e.g `Generating PDF file...`

## Portrait vs Landscape

By default the PDF generated has it's orientation set to `Portrait`, but for some usecases you might prefer the `Landscape` orientation. You can switch between them at any time via the `Page orientation` setting.

## Advanced settings

There are several more settings which you can define, which are liste below:

* Unit `mm (default)`, `pt`, `cm`, `in`, `px`
* Page format `a3`, `a4 (default)`, `a5`, `letter`, `legal`, `Custom page format` etc.
* Body margins
* Header margins
* Footer margins

## Render scale

The render scale should normally be left unchanged unless your PDF file size is becoming to large (when working with very large forms).

Keep in mind that you will lose "pixel" quality when lowering the render scale.

When working with huge forms it is really important to check the PDF file size during development and to adjust the render scale accordingly.

## Pricing

This Add-on comes with a single site licensing system with the following volume pricing:

Volume | Price per license | Monthly cost
--- | --- | ---
15 day trial | n/a | n/a
1+ | $5.00 | 1 license would cost $5 p/m
5+ | $3.00 | 5 license would cost $15 p/m
10+ | $2.50 | 10 license would cost $25 p/m
20+ | $2.00 | 20 license would cost $40 p/m
40+ | $1.50 | 40 license would cost $60 p/m
