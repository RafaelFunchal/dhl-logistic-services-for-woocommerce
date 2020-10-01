/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/pr-dhl-freight.js":
/*!*************************************!*\
  !*** ./assets/js/pr-dhl-freight.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports) {

// Run Actions
var actions = function actions() {
  var run = function run() {
    document.querySelector('#dhl-fr-find') && findPlace().init();
  };

  return {
    run: run
  };
}; // Find Places action


var findPlace = function findPlace() {
  var googleMapElem = document.getElementById('dhl-freight-map');
  var googleMapFindButton = document.getElementById('dhl-fr-find');
  var popUpElem = document.getElementById('dhl-freight-finder');
  var popUpCloseButton = popUpElem.querySelector('.dff__close');
  var shippingAddressOneField = document.getElementById('shipping_address_1');

  var popUpVisual = function popUpVisual() {
    var open = function open() {
      popUpElem.style.display = 'block';
    };

    var close = function close() {
      popUpElem.style.display = 'none';
    };

    return {
      open: open,
      close: close
    };
  };
  /**
   * Open finder popup
   * @param e
   */


  var openFinder = function openFinder(e) {
    e.preventDefault();
    popUpVisual().open();
    loadMap();
  };
  /**
   * Close finder popup
   * @param e
   */


  var closeFinder = function closeFinder(e) {
    e.preventDefault();
    popUpVisual().close();
  };
  /**
   * Load Google Map
   */


  var loadMap = function loadMap() {
    var myLatLng = {
      lat: -25.363,
      lng: 131.044
    };
    var map = new google.maps.Map(googleMapElem, {
      zoom: 4,
      center: myLatLng,
      disableDefaultUI: true
    });
    var marker = new google.maps.Marker({
      position: myLatLng,
      map: map
    });
    marker.set('address', 'Test address 123');
    marker.addListener('click', function () {
      shippingAddressOneField.value = marker.get('address');
      popUpVisual().close();
    });
  };
  /**
   * Initialization
   */


  var init = function init() {
    // Trigger map click
    googleMapFindButton.addEventListener('click', openFinder);
    popUpCloseButton.addEventListener('click', closeFinder);
  };

  return {
    init: init
  };
}; // On jQuery done run all actions


jQuery(document).ready(function () {
  return actions().run();
});

/***/ }),

/***/ "./assets/scss/pr-dhl-freight.scss":
/*!*****************************************!*\
  !*** ./assets/scss/pr-dhl-freight.scss ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 0:
/*!*****************************************************************************!*\
  !*** multi ./assets/js/pr-dhl-freight.js ./assets/scss/pr-dhl-freight.scss ***!
  \*****************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(/*! /Users/sidassnieska/Sites/wptest/wp-content/plugins/dhl-for-woocommerce/assets/js/pr-dhl-freight.js */"./assets/js/pr-dhl-freight.js");
module.exports = __webpack_require__(/*! /Users/sidassnieska/Sites/wptest/wp-content/plugins/dhl-for-woocommerce/assets/scss/pr-dhl-freight.scss */"./assets/scss/pr-dhl-freight.scss");


/***/ })

/******/ });