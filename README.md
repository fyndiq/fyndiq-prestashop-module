# Fyndiq Prestashop Module

Fyndiq official Prestashop module

## Installation

Ensure that `_PS_MODE_DEV_` is set to `false`.
This flag is normally found in `prestashop/config/defines.inc.php`, but your milage may vary.

Copy `fyndiq-prestashop-module/fyndiqmerchant/` to `prestashop/modules/fyndiqmerchant/`.

Go to PrestaShop admin -> Modules -> Fyndiq, and click the Install button.

This module requires Apache to have `AcceptPathInfo` set to `On`.
Specifically, it's required for the automated notification system to work.

## Development

### SASS / CSS

This project uses [SASS](http://sass-lang.com/) to compile SCSS files into CSS files.
This means that you have to have a [SASS compiler](http://sass-lang.com/install) program installed on your computer.

Here is an example of how to install SASS on Debian 7:

```
sudo gem install sass
```

And this is how to run the SASS compiler during development:

```
cd fyndiqmerchant/backoffice/frontend/css/
sass --watch style.scss:style.css
```

### JavaScript

Most features in the main panel of the module make use of AJAX to get and send data.
Since PrestaShop provides access to jQuery by default, this module uses jQuery as well, wherever possible.

This module also makes use of [Handlebars](http://handlebarsjs.com/) which is a JavaScript template engine.
