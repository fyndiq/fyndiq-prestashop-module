SRC = ./fyndiqmerchant
BUILD = ./build
COMMIT = $(shell git rev-parse --short HEAD)

build: clean
	mkdir $(BUILD)
	rsync -a --exclude='.*' $(SRC) $(BUILD)
	cd $(BUILD); zip -r fyndiq-prestashop-module-$(COMMIT).zip fyndiqmerchant/
	rm -rf mkdir $(BUILD)/fyndiqmerchant

clean:
	rm -rf $(BUILD)