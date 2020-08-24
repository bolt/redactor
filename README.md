# 📝 Bolt Redactor Extension

This extension provides a "Redactor" field type, which is a sophisticated,
lightweight and elegant WYSIWYG editor component for
[Bolt](https://boltcms.io). The editor itself is developed by
[Imperavi][redactor], and is licensed for usage in Bolt.

This extension allows you to add fields of `type: redactor` in your
ContentTypes, as defined in `contenttypes.yaml`, like any other Field type.

## Installation

Note: Installation is not required if you've installed the default Bolt
project. In that case it's already present. If you've installed Bolt through
different means, you'll need to run the command below:

```bash
composer require bolt/redactor
```

After installation, you can add it to any ContentType in your
`contenttypes.yaml`, like any other field. For example:

```yaml
blogposts:
    name: Blogposts
    singular_name: Blogpost
    fields:
        title:
            type: text
        slug:
            type: slug
            uses: title
        content:
            type: redactor
```

The result will be like this:

![](https://user-images.githubusercontent.com/1833361/90637112-dbf59f80-e22b-11ea-8bfd-574b72a79fdc.png)

You can configure the editor in `config/extensions/bolt-redactor.yaml`. This
configuration affects all the instances of the Redactor field that you've
configured in your ContentTypes. The default configuration looks like this:

```yaml
default:
  buttons: [ bold, italic, format, lists, link, html, image ]
  plugins: [ fullscreen, table, inlinestyle, video, widget ]
  source: true

plugins:
  ~
```

## Configuring the buttons

Bolt's version of Redactor ships with all the official plugins and options. you
can add or remove buttons by configuring them in the `buttons:` and `plugins:`
parameters. Check the official Redactor documentation for [all available
buttons][buttons]. Note that some buttons might require you to enable the
corresponding plugin as well. See here for a list of
[the available plugins][plugins].

## Settings

Where applicable, you can add extra settings under the `default:` key in the
`bolt-redactor.yaml` configuration. See the documentation for available
settings.

Note that this documentation uses Javascript, whilst Bolt's configuration uses
Yaml. For example, the documentation for '[Paste][paste]' has this example:

```javascript
$R('#content', {
        pastePlainText: true
});
```

In `bolt-redactor.yaml` you can add this as:

```yaml
default:
  buttons: [ …]
  plugins: [ … ]
  pastePlainText: true
```

## Adding custom plugins

If you've written your own plugin for Redactor according to the documentation
[for Creating Plugins][create-plugin], you can add it to the editor in Bolt, by
placing it in `/public/assets/redactor/_plugins`. Then, add it to the
`bolt-redactor.yaml` configuration:

```yaml
default:
  buttons: [ … ]
  plugins: [ … ]

plugins:
  myplugin: [ 'myplugin/myplugin.js', 'myplugin/myplugin.css' ]
```

-------

The part below is only for _developing_ the extension. Not required for general
usage of the extension in your Bolt Project

## Running PHPStan and Easy Codings Standard

First, make sure dependencies are installed:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer update
```

And then run ECS:

```bash
vendor/bin/ecs check src --fix
```

[redactor]: https://imperavi.com/redactor/
[create-plugin]: https://imperavi.com/redactor/docs/how-to/create-a-plugin/
[buttons]: https://imperavi.com/redactor/examples/buttons/change-buttons-in-the-toolbar/
[plugins]: https://imperavi.com/redactor/plugins/
[paste]: https://imperavi.com/redactor/docs/settings/paste/
