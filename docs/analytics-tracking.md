# Analytics Tracking

!> **Please note:** You will require a Tracking ID from your Google Analytics account to make this work. If you have't one yet you can get yours from [Google Analytics](https://developers.google.com/analytics/devguides/collection/analyticsjs/)

## About

If you are using Google Analtyics to track web traffic, you might also wish to track form submissions. This guid will provide you with the steps required to set it up correctly.

?> **Please note:** the JavaScript code snippet used is based on the latest version of the Google Analytics library. If you use the [Legacy library (ga.js)](https://developers.google.com/analytics/devguides/collection/gajs/) you will need to change the code accordingly to the offical Google Documentation.

## Configuration

Tracking form submissions with your **Google Analytics** account is very easy with Super Forms.

The only thing you will have to do is add the code snippet to your site and setting up the events for the forms you wish to track.

Go to `Super Forms` > `Settings` > `Form Settings`.

Enable the option **Track form submissions with Google Analytics**.

Add the following code snippet and replace **UA-XXXXXX-X** with your Tracking ID.

```js
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName[o](0);a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-XXXXXX-X', 'auto');
ga('send', 'pageview');
```

## Event Tracking

The last step is to add an **Tracking Event** that will be send to Analytics.

**Tracking a specific form only:**

The only thing you have to do is prepend the form ID before `send` with a collon `:` like so:

`3519:send|event|Contact Form|submit`

This event will only be triggered for the form with ID `3519`.

**Tracking all forms:**

In order to setup a global tracking event you can simply add the following line:

`send|event|Contact Form|submit`

Replace **Contact Form** with a more suitable name if needed. This will be visible in your Analytics dashboard.

**Tracking event with a Label and Value:**

In some cases you might need or want to give some additional information, for instance if you are running multiple campagns in a specific time period. You can append the Label and Value like this:

`send|event|Campaign Form|submit|Fall Campaign|43`

## Testing & Debugging

If you have setup everything correctly you should be able to see some activity in your Analytics Dashboard when a form has been submitted.

Current form submissions will now be listed under `Realtime` > `Events`.

Earlier form submissions will now be listed under `Behavior` > `Events` > `Overview`.

**For developers:**

If you are not sure if everything is correctly setup, you can use the [Google Tag Assistant](https://chrome.google.com/webstore/detail/tag-assistant-by-google/kejbdjndbnbjgmefkgdddjlbokphdefk?hl=en) extension for Google Chrome to debug any issues.
