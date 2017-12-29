# Installing Super Forms

Below we will guide you through the steps to install the plugin on your WordPress website.

### Download the plugin

If you haven't already, purchase and download the [latest version](https://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866) of Super Forms and save it somewhere so you know where to find it later. You should now have the installable plugin named **super-forms.zip**.

### Upload the plugin

Within your WordPress dashboard go to `Plugins` > `Add New` > `Upload Plugins`.<br />
Now click on `Browse file` and search on your local computer for the **super-forms.zip** file you just downloaded.<br />
After you have selected the **.zip** file you can click on `Install Now`.<br />
WordPress will now start uploading the plugin files and install it on your site.

### Activating the plugin

After Wordpress finishes uploading the files, you will have to activate the plugin.<br />
You can simply do this by clicking on the `Activate plugin` link after the upload completed.<br />
You should now see a new menu item called `Super Forms`.<br />
If you do not see a new menu item confirm you have followed above steps correctly.<br />
After that you can [contact support](support.md) and we will personally help you out :)

```bash
docsify init ./docs
```

## Writing content

After the `init` is complete, you can see the file list in the `./docs` subdirectory.

* `index.html` as the entry file
* `README.md` as the home page
* `.nojekyll` prevents GitHub Pages from ignoring files that begin with an underscore

You can easily update the documentation in `./docs/README.md`, of course you can add [more pages](more-pages.md).

## Preview your site

Run the local server with `docsify serve`. You can preview your site in your browser on `http://localhost:3000`.

```bash
docsify serve docs
```

?> For more use cases of `docsify-cli`, head over to the [docsify-cli documentation](https://github.com/QingWei-Li/docsify-cli).

## Manual initialization

If you don't like `npm` or have trouble installing the tool, you can manually create `index.html`:

```html
<!-- index.html -->

<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta charset="UTF-8">
  <link rel="stylesheet" href="//unpkg.com/docsify/themes/vue.css">
</head>
<body>
  <div id="app"></div>
</body>
<script src="//unpkg.com/docsify/lib/docsify.min.js"></script>
</html>
```

If you installed python on your system, you can easily use it to run a static server to preview your site.

```bash
cd docs && python -m SimpleHTTPServer 3000
```

## Loading dialog

If you want, you can show a loading dialog before docsify starts to render your documentation:

```html
  <!-- index.html -->

  <div id="app">Please wait...</div>
```

You should set the `data-app` attribute if you changed `el`:

```html
  <!-- index.html -->

  <div data-app id="main">Please wait...</div>

  <script>
    window.$docsify = {
      el: '#main'
    }
  </script>
```

Compare [el configuration](configuration.md#el).