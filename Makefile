SRC = ./fyndiqmerchant
BUILD = ./build

build: clean
	mkdir $(BUILD)
	rsync -a --exclude='.*' $(SRC) $(BUILD)
	cd $(BUILD); zip -r fyndiq-prestashop-module.zip fyndiqmerchant/
	rm -rf mkdir $(BUILD)/fyndiqmerchant

clean:
	rm -rf $(BUILD)