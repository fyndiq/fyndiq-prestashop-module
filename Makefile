BASE = $(realpath ./)
SRC_DIR = $(BASE)/src
TESTS_DIR = $(BASE)/tests
BUILD_DIR = $(BASE)/build
DOCS_DIR = $(BASE)/docs
COVERAGE_DIR = $(BASE)/coverage
BIN_DIR = $(BASE)/vendor/bin

COMMIT = $(shell git rev-parse --short HEAD)
MODULE_VERSION = $(shell grep -Po "version = '\K[^']*" src/fyndiqmerchant.php)

build: clean
	mkdir $(BUILD_DIR)
	rsync -a --exclude='.*' $(SRC_DIR) $(BUILD_DIR)
	mv $(BUILD_DIR)/src $(BUILD_DIR)/fyndiqmerchant
	cp $(DOCS_DIR)/* $(BUILD_DIR)/fyndiqmerchant
	cd $(BUILD_DIR); zip -r -X fyndiq-prestashop-module-v$(MODULE_VERSION)-$(COMMIT).zip fyndiqmerchant/
	rm -r $(BUILD_DIR)/fyndiqmerchant

clean:
	rm -r $(BUILD_DIR)

css:
	cd $(SRC_DIR)/frontend/css; scss -C --sourcemap=none main.scss:main.css

test:
	$(BIN_DIR)/phpunit
