# Fyndiq Prestashop Module

Fyndiq official Prestashop module

## Build

To build the module go to the root directory and run:
```shell
make
```

The newly built module package will be created in `./build`

## Installation

Ensure that `_PS_MODE_DEV_` is set to `false`.
This flag is normally found in `prestashop/config/defines.inc.php`, but your mileage may vary.

Go to Prestashop admin -> Modules and click the `Add new module` button on the top. Select the module archive and install it. Then click the install button besides the module in the list.

This module requires Apache to have `AcceptPathInfo` set to `On`.
Specifically, it's required for the automated notification system to work.

## Development

### SASS / CSS

This project uses [SASS](http://sass-lang.com/) to compile SCSS files into CSS files.
This means that you have to have a [SASS compiler](http://sass-lang.com/install) program installed on your computer.

Here is an example of how to install SASS on Debian 7:

```shell
sudo gem install sass
```

And this is how to run the SASS compiler during development:

```shell
make css
```
