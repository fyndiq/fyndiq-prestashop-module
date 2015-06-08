# Fyndiq Prestashop Module

Fyndiq official Prestashop module. Supports versions `1.5.*` - `1.6.*`;

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

## Vagrant
To use the vagrant box for development, go to `vagrant/` and run:

```shell
vagrant up
```

to bootstrap the machine.

## Local development

To develop the module, you can make s symbolic link to the `src/` directory into the modules directory in your Prestashop installation:

```shell
ln -s src/ /path/to/prestashop/modules/fyndiqmerchant
```

### Dependencies
To install the PHP development dependencies, you'll need [Composer](https://getcomposer.org/). Once installed run `composer update` to get all dependencies;

This project uses [SASS](http://sass-lang.com/) to compile SCSS files into CSS files.
This means that you have to have a [SASS compiler](http://sass-lang.com/install) program installed on your computer.

Here is an example of how to install SASS on Debian 7:

```shell
sudo gem install sass
```

### Make commands:

* `build` - builds the module package from source;
* `compatinfo` - checks the code for the lowest compatible PHP version;
* `coverage` - generates test coverage report in `coverage/`;
* `css` - builds the CSS file from SCSS using SASS;
* `php-lint` - checks the files with the PHP internal linter;
* `phpmd` - checks the code with [PHP Mess Detector](http://phpmd.org/);
* `scss-lint` - lint checks the SCSS files using `scss-lint`;
* `sniff` - checks the code for styling issues;
* `sniff-fix` - tries to fix the styling issues
* `test` - runs the PHPUnit tests;
