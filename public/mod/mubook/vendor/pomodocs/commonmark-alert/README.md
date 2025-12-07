# CommonMark Alert Extension

![Tests](https://github.com/pomodocs/commonmark-alert/actions/workflows/test.yml/badge.svg)
[![Maintainability](https://qlty.sh/gh/pomodocs/projects/commonmark-alert/maintainability.svg)](https://qlty.sh/gh/pomodocs/projects/commonmark-alert)
[![Code Coverage](https://qlty.sh/gh/pomodocs/projects/commonmark-alert/coverage.svg)](https://qlty.sh/gh/pomodocs/projects/commonmark-alert)
![GitHub](https://img.shields.io/github/license/pomodocs/commonmark-alert)

CommonMark Alert Extension is an extension for [League CommonmMark](https://commonmark.thephpleague.com/) to support alerts in [Github blockquote style](https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax#alerts).

## Installation

Install the extension via [Composer](https://getcomposer.org):

```bash
composer require pomodocs/commonmark-alert
```

## Basic Usage

After installing it, register the extension in your [Commonmark Environment](https://commonmark.thephpleague.com/2.7/basic-usage/):

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;
use PomoDocs\CommonMark\Alert\AlertExtension;

$environment = new Environment();

$environment->addExtension(new AlertExtension());

$converter = new MarkdownConverter($environment);
```

Now you can write a blockquote alert everywhere in your documentation:

```markdown

Lorem ipsum dolor sit amet, consectetur adipiscing elit.
Nullam eros tortor, scelerisque sit amet metus a, aliquet varius nisl. Cras elementum egestas tortor, vel finibus urna suscipit ac.

> [!TIP]
> Nulla lobortis ultricies massa id laoreet.
> Quisque ut varius est. Aliquam fringilla finibus eleifend.

Sed nec odio nec magna bibendum vulputate. Suspendisse at ipsum nisi.
```

It's converted into the following html:

```html
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.
Nullam eros tortor, scelerisque sit amet metus a, aliquet varius nisl. Cras elementum egestas tortor, vel finibus urna suscipit ac.</p>

<div class="alert alert-tip">
    <p class="alert-title">Tip</p>
    <p>Nulla lobortis ultricies massa id laoreet. Quisque ut varius est. Aliquam fringilla finibus eleifend.</p>
</div>

<p>Sed nec odio nec magna bibendum vulputate. Suspendisse at ipsum nisi.</p>
```

Of course, the look&feel of your html snippet depends on your CSS. 

## Configuration

You can configure this extension by passing an array of parameters to your environment:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\MarkdownConverter;
use PomoDocs\CommonMark\Alert\AlertExtension;

$config = [
    'alert' => [
        'strong_title' => false,
        'icons' => true
    ]
];

$environment = new Environment($config);

$environment->addExtension(new AlertExtension());

$converter = new MarkdownConverter($environment);
```

Here is the complete options array, with default values:

```php
<?php

$options = [
    'alert' = [
        'class_name' => 'alert',
        'colors' => [
            'note' => 'note',
            'tip' => 'tip',
            'important' => 'important',
            'warning' => 'warning',
            'caution' => 'caution',
        ],
        'labels' => [
            'note' => 'Note',
            'tip' => 'Tip',
            'important' => 'Important',
            'warning' => 'Warning',
            'caution' => 'Caution',
        ],
        'icons' => [
            'active' => false,
            'use_svg' => false,
            'names' => [
                'note' => 'octicons octicons-info',
                'tip' => 'octicons octicons-light-bulb',
                'important' => 'octicons octicons-report',
                'warning' => 'octicons octicons-alert',
                'caution' => 'octicons octicons-stop'
            ]
            'svg' => [
                'note' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8Zm8-6.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13ZM6.5 7.75A.75.75 0 0 1 7.25 7h1a.75.75 0 0 1 .75.75v2.75h.25a.75.75 0 0 1 0 1.5h-2a.75.75 0 0 1 0-1.5h.25v-2h-.25a.75.75 0 0 1-.75-.75ZM8 6a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path></svg>',
                'tip' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M8 1.5c-2.363 0-4 1.69-4 3.75 0 .984.424 1.625.984 2.304l.214.253c.223.264.47.556.673.848.284.411.537.896.621 1.49a.75.75 0 0 1-1.484.211c-.04-.282-.163-.547-.37-.847a8.456 8.456 0 0 0-.542-.68c-.084-.1-.173-.205-.268-.32C3.201 7.75 2.5 6.766 2.5 5.25 2.5 2.31 4.863 0 8 0s5.5 2.31 5.5 5.25c0 1.516-.701 2.5-1.328 3.259-.095.115-.184.22-.268.319-.207.245-.383.453-.541.681-.208.3-.33.565-.37.847a.751.751 0 0 1-1.485-.212c.084-.593.337-1.078.621-1.489.203-.292.45-.584.673-.848.075-.088.147-.173.213-.253.561-.679.985-1.32.985-2.304 0-2.06-1.637-3.75-4-3.75ZM5.75 12h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5ZM6 15.25a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Z"></path></svg>',
                'important' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M0 1.75C0 .784.784 0 1.75 0h12.5C15.216 0 16 .784 16 1.75v9.5A1.75 1.75 0 0 1 14.25 13H8.06l-2.573 2.573A1.458 1.458 0 0 1 3 14.543V13H1.75A1.75 1.75 0 0 1 0 11.25Zm1.75-.25a.25.25 0 0 0-.25.25v9.5c0 .138.112.25.25.25h2a.75.75 0 0 1 .75.75v2.19l2.72-2.72a.749.749 0 0 1 .53-.22h6.5a.25.25 0 0 0 .25-.25v-9.5a.25.25 0 0 0-.25-.25Zm7 2.25v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"></path></svg>',
                'warning' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M6.457 1.047c.659-1.234 2.427-1.234 3.086 0l6.082 11.378A1.75 1.75 0 0 1 14.082 15H1.918a1.75 1.75 0 0 1-1.543-2.575Zm1.763.707a.25.25 0 0 0-.44 0L1.698 13.132a.25.25 0 0 0 .22.368h12.164a.25.25 0 0 0 .22-.368Zm.53 3.996v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"></path></svg>',
                'caution' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M4.47.22A.749.749 0 0 1 5 0h6c.199 0 .389.079.53.22l4.25 4.25c.141.14.22.331.22.53v6a.749.749 0 0 1-.22.53l-4.25 4.25A.749.749 0 0 1 11 16H5a.749.749 0 0 1-.53-.22L.22 11.53A.749.749 0 0 1 0 11V5c0-.199.079-.389.22-.53Zm.84 1.28L1.5 5.31v5.38l3.81 3.81h5.38l3.81-3.81V5.31L10.69 1.5ZM8 4a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path></svg>'
            ]
        ]
    ]
];
```

Let's explain each configuration parameter.

### class_name

The name of the CSS class to apply to the alert `<div>` tag.

### colors

The name of the color classes to apply to the alert `<div>` tag. 
In example, if you use [Bootstrap](https://getbootstrap.com/) you could set the colors as the following:

```php
$config = [
    'alert' => [
        'colors' => [
            'note' => 'primary',
            'tip' => 'info',
            'important' => 'success',
            'warning' => 'warning',
            'caution' => 'danger',
        ]
    ],
];
```
and the resulted `<div>` tag, for a NOTE alert will be:

```html
<div class="alert alert-primary">
```

### labels

An array of text labels that can be used to localize the alerts in different languages.

### icons

An array of parameters to configure an optional icon, near the title.
The icons array has the following keys:

-  __active__: if display the icon or not. Default is `false`.
-  __use_svg__: if use the svg icons defined in `svg` options array or use the css classes defined in `names` options array. Default is `false`.
-  __names__: the class names to  use to apply the icons. Default: Octicons icon set.
-  __svg__: the svg icons to use. Default: Octicons icon set.

Let's suppose, in example, we want to use [Fontawesome](https://fontawesome.com/) icons, using the web font. The configuration array could be the following:

```php
$config = [
    'alert' => [
        'icons' => [
            'active' => true,
            'names' => [
                'note' => 'fa-solid fa-circle-info',
                'tip' => 'fa-regular fa-lightbulb',
                'important' => 'fa-solid fa-exclamation',
                'warning' => 'fa-solid fa-triangle-exclamation',
                'caution' => 'fa-solid fa-circle-exclamation'
            ]
        ]
    ]
];
```

Now, the following markdown:

```markdown
> [!TIP]
> Nulla lobortis ultricies massa id laoreet.
> Quisque ut varius est. Aliquam fringilla finibus eleifend.
```
it results into:

```html
<div class="alert alert-tip">
    <p class="alert-title"><i class="fa-regular fa-lightbulb"></i>Tip</p>
    <p>Nulla lobortis ultricies massa id laoreet. Quisque ut varius est. Aliquam fringilla finibus eleifend.</p>
</div>
```    

If we want to use the default svg icons, the configuration array is the following:

```php
$config = [
    'alert' => [
        'icons' => [
            'active' => true,
            'use_svg' => true
        ]
    ]
];
```
and the following markdown:

```markdown
> [!WARNING]
> Warning lorem ipsum dolor sit amet, _consectetur adipiscing elit_
> sed do eiusmod tempor incidunt ut labore et dolore magna aliqua.
```

it results into the following html:

```html
<div class="alert alert-warning">
<p class="alert-title"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path d="M6.457 1.047c.659-1.234 2.427-1.234 3.086 0l6.082 11.378A1.75 1.75 0 0 1 14.082 15H1.918a1.75 1.75 0 0 1-1.543-2.575Zm1.763.707a.25.25 0 0 0-.44 0L1.698 13.132a.25.25 0 0 0 .22.368h12.164a.25.25 0 0 0 .22-.368Zm.53 3.996v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"></path></svg>Warning</p>
<p>Warning lorem ipsum dolor sit amet, <em>consectetur adipiscing elit</em>
sed do eiusmod tempor incidunt ut labore et dolore magna aliqua.</p>
</div>
```

## Issues

Please, open an issue on [Github repository](https://github.com/pomodocs/commonmark-alert/issues).

## Contributing

Please, see our [Contributing guide](CONTRIBUTING.md).

## Licensing

This library is released under [MIT license](LICENSE).