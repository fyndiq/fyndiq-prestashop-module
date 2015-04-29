SRC = ./fyndiqmerchant
BUILD = ./build
COMMIT = $(shell git rev-parse --short HEAD)
MODULE_VERSION = $(shell grep -Po "version = '\K[^']*" fyndiqmerchant/fyndiqmerchant.php)

build: clean
	mkdir $(BUILD)
	rsync -a --exclude='.*' $(SRC) $(BUILD)
	cd $(BUILD); zip -r -X fyndiq-prestashop-module-v$(MODULE_VERSION)-$(COMMIT).zip fyndiqmerchant/
	rm -rf mkdir $(BUILD)/fyndiqmerchant

clean:
	rm -rf $(BUILD)