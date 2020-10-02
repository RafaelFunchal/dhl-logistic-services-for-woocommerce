const findPlace = () => {
  const googleMapElem = document.getElementById('dhl-freight-map')
  const googleMapFindButton = document.getElementById('dhl-fr-find')
  const popUpElem = document.getElementById('dhl-freight-finder')
  const popUpCloseButton = popUpElem.querySelector('.dhl-freight-popup__close')
  const shippingAddressOneField = document.getElementById('shipping_address_1')

  const popUpVisual = () => {
    const open = () => {
      popUpElem.style.display = 'block'
    }

    const close = () => {
      popUpElem.style.display = 'none'
    }

    return {open, close}
  }

  /**
   * Open finder popup
   * @param e
   */
  const openFinder = (e) => {
    e.preventDefault();

    popUpVisual().open()

    loadMap()
  }

  /**
   * Close finder popup
   * @param e
   */
  const closeFinder = (e) => {
    e.preventDefault()

    popUpVisual().close()
  }

  /**
   * Load Google Map
   */
  const loadMap = () => {
    // Sweeden
    const myLatLng = {lat: 62.8440857, lng: 11.58721};

    const map = new google.maps.Map(googleMapElem, {
      zoom: 4,
      center: myLatLng,
      disableDefaultUI: true,
    });

    const marker = new google.maps.Marker({
      position: myLatLng,
      map: map
    });

    marker.set('address', 'Test address 123')

    marker.addListener('click', function() {
      shippingAddressOneField.value = marker.get('address')
      popUpVisual().close()
    });
  }

  /**
   * Initialization
   */
  const init = () => {
    // Trigger map click
    googleMapFindButton.addEventListener('click', openFinder)
    popUpCloseButton.addEventListener('click', closeFinder)
  }

  return {init}
}

export default findPlace;