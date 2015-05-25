SRC = ./src
BUILD = ./build
DOCS = ./docs
COMMIT = $(shell git rev-parse --short HEAD)
MODULE_VERSION = $(shell grep -Po "version = '\K[^']*" fyndiqmerchant/fyndiqmerchant.php)

build: clean
	mkdir $(BUILD)
	rsync -a --exclude='.*' $(SRC) $(BUILD)
	mv $(BUILD)/src $(BUILD)/fyndiqmerchant
	cp $(DOCS)/* $(BUILD)/fyndiqmerchant
	cd $(BUILD); zip -r -X fyndiq-prestashop-module-v$(MODULE_VERSION)-$(COMMIT).zip fyndiqmerchant/
	rm -r $(BUILD)/fyndiqmerchant

clean:
	rm -r $(BUILD)
