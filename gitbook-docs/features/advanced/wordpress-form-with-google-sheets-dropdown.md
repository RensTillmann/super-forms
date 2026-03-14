---
description: >-
  How to setup a WordPress form that has a dropdown with items retrieved from a
  Google (drive) sheets file.
---

# WordPress form with Google sheets dropdown

{% hint style="danger" %}
This feature is currently only available in the [BETA version](../../developers/beta-version.md).
{% endhint %}

{% hint style="info" %}
This guide will walk you through the steps to setup a [Dropdown element](../../elements/form-elements/dropdown.md) for your form that implements Google Sheets service to retrieve the rows from your sheet as the dropdown items. Note that this can also be used for [Keyword](../../elements/form-elements/keywords.md) element, [Autosuggest ](../../elements/form-elements/autosuggest.md)element and any other elements that implement the "Retrieve method" setting.
{% endhint %}

First open the Google Cloud Console and [Create a New Project](https://console.cloud.google.com/projectcreate) if you haven't already.

Enter the project name, billing account and company (optional) and click **CREATE**.

Enable the [Google Sheets API](https://console.cloud.google.com/apis/library/sheets.googleapis.com) for your project. Direct link to Google Sheets API: [https://console.cloud.google.com/apis/library/sheets.googleapis.com](https://console.cloud.google.com/apis/library/sheets.googleapis.com)

Confirm you are still on the correct project and enable the API by clicking **ENABLE** as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (61).png" alt="Enable Google Sheets API."><figcaption><p>Enable Google Sheets API.</p></figcaption></figure></div>

Next we will want to create our credentials so that we can communicate with Google Sheets API. Click on the **CREDENTIALS** tab, then click **+ CREATE CREDENTIALS** and choose **Service account** as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (68).png" alt="Creating a new Service account credential."><figcaption><p>Creating a new Service account credential.</p></figcaption></figure></div>

Enter the Service account name, ID and description. For our demo we will name it `superforms`. Click **DONE**.

<div align="left"><figure><img src="../../.gitbook/assets/image (67).png" alt="Entering service account details."><figcaption><p>Entering service account details.</p></figcaption></figure></div>

{% hint style="danger" %}
**Important:** Make sure to copy the service account email address as shown in the picture below. You will need it later on to share the Google Sheet document.
{% endhint %}

<div align="left"><figure><img src="../../.gitbook/assets/image (14).png" alt="Copy the service account email address"><figcaption><p>Copy the service account email address</p></figcaption></figure></div>

Now go ahead and click on the account you just created, in our case `superforms@xxxxxx`:

<div align="left"><figure><img src="../../.gitbook/assets/image (46).png" alt="Select the Service Account to create a key."><figcaption><p>Select the Service Account to create a key.</p></figcaption></figure></div>

Create a new key for this account. Click on the **KEYS** tab and click **ADD KEY**. Choose **Create new key** from the dropdown to create a new one as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (22).png" alt="Creating a new key for your service account."><figcaption><p>Creating a new key for your service account.</p></figcaption></figure></div>

Choose **JSON** as the key type and click **CREATE** as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (28).png" alt="Create private key for service account as type JSON."><figcaption><p>Create private key for service account as type JSON.</p></figcaption></figure></div>

A `.json` file should now be downloaded. Open the file and copy the contents to your clipboard. Navigate to your form and add or edit your [Dropdown element](../../elements/form-elements/dropdown.md). Set the **Retrieve method** to **Google sheets** and paste the contents of the json file under **Google API credentials.json**.

Change the **Range** if needed, but by default this will be `Sheet1` which will read all the rows from Sheet1.

The last step is to [create a Google Sheet](https://docs.google.com/spreadsheets) (if you haven't already). A sheet can be set to public or private. If you choose for a private sheet, you will require to add (share) the sheet with the service account created so that it has permissions to view the contents. To do this click the "Share" button or go to File > Share. Here you can paste the service account address:

<div align="left"><figure><img src="../../.gitbook/assets/image (17).png" alt="Share google sheet document with service account."><figcaption><p>Share google sheet document with service account.</p></figcaption></figure></div>

<div align="left"><figure><img src="../../.gitbook/assets/image (15).png" alt="Sharing the google sheet and giving &#x22;Viewer&#x22; permissions only."><figcaption><p>Sharing the google sheet and giving "Viewer" permissions only.</p></figcaption></figure></div>

Now copy the sheet ID. You can find your sheet ID from the URL in your browser as shown below.

<div align="left"><figure><img src="../../.gitbook/assets/image (58).png" alt="Find the google sheet ID from the URL."><figcaption><p>Find the google sheet ID from the URL.</p></figcaption></figure></div>

Paste this ID under **Google sheet ID** for your Dropdown element on your WordPress form, and click **Update Element** to save the settings for the Dropdown element.

<div align="left"><figure><img src="../../.gitbook/assets/image (69).png" alt="Define the google sheet ID for the Dropdown element."><figcaption><p>Define the google sheet ID for the Dropdown element.</p></figcaption></figure></div>

If setup correctly your Dropdown settings should look something like this:

<div align="left"><figure><img src="../../.gitbook/assets/image (59).png" alt="Dropdown configured to retrieve Google Sheets rows as items."><figcaption><p>Dropdown configured to retrieve Google Sheets rows as items.</p></figcaption></figure></div>

Now Save the form and test if the changes made to the Google Sheet are reflected on the form Dropdown element.

{% hint style="success" %}
You should now be able to manipulate the dropdown items by editing the Google spreadsheet.
{% endhint %}
