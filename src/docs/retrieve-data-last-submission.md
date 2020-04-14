# Retrieve form data from users last submission

?> **Please note:** this feature will only work for logged in users, or when a GET or POST request contains a key `contact_entry_id` with the entry ID.

* [How does it work?](#how-does-it-work)
* [How to enable this feature?](#how-to-enable-this-feature)

## How does it work?

In case a logged in user had previously submitted the same form or perhaps a different form you can retrieve the last contact entry of the user by enabling this feature.

You can make this work across all forms by specifying from which form ID (multiple ID's can be seperated by comma's) the last contact entry should be retrieved from.

If you did not specify any form ID, it will automatically grab the last created contact entry of the user no matter what form it belongs to.

Another way would be to specifically specify the contact entry ID via a GET or POST request. In this case a key called `contact_entry_id` must be specified with the corresponding Contact Entry ID.

When enabled and a contact entry was found, it will [autopopulate the form](autopopulate-fields) with the data of contact entry.

_This method works cross forms by specifying the form ID of which to retrieve the last contact entry from. But please keep in mind that when using a GET or POST request the form ID you entered will become obsolete, and that the entry ID parsed in the GET or POST request will be leading. Even if the entry ID doesn't exists it will not fall back to the form ID method. In this case the form will simply not be populated with any data._

## How to enable this feature?

To populate the form with either the submitted contact entry of a logged in user, or a contact entry retrieved by a ID via a GET or POST request as explained above, you can go to `Form Settings > Form Settings` and enable the option **Autopopulate form with last contact entry data**.
