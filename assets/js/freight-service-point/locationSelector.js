import locationService from "./services/locationService";

const locationSelector = () => {
  const field = document.getElementById('dhl_freight_selected_service_point')
  const noResultsNotice = document.querySelector('.dhl-freight-cf__field-wrap__noresults')

  // Billing fields
  const postalCodeField = document.getElementById('billing_postcode')
  const city = document.getElementById('billing_city')
  const address = document.getElementById('billing_address_1')

  let data = [];

  // @todo Implement cache
  let cached = [];

  const clearOptions = () => {
    // Clear options
    field.innerHTML = ''
  }

  const disableField = () => {
    field.style.display = 'none'
  }

  const enableField = () => {
    field.style.display = 'block'
  }

  const showNoResults = () => {
    noResultsNotice.style.display = 'block'
  }

  const hideNoResults = () => {
    noResultsNotice.style.display = 'none'
  }

  const setFields = (e) => {
    const point = getPoint(e.target.value)

    const shipping_country = document.getElementById('shipping_country')
    const shipping_address_1 = document.getElementById('shipping_address_1')
    const shipping_postcode = document.getElementById('shipping_postcode')
    const shipping_city = document.getElementById('shipping_city')

    shipping_country.value = point.countryCode
    shipping_address_1.value = point.street
    shipping_postcode.value = point.postalCode
    shipping_city.value = point.cityName
  }

  const setOptions = (points) => {
    clearOptions()

    data = []

    // Add new
    points.forEach(function (point) {
      let option = document.createElement("option");

      option.text = point.name;
      option.value = point.id;

      field.appendChild(option);

      data[point.id] = point
    })
  }

  const getPoint = (id) => {
    return data[id]
  }

  const getOptions = () => {
    return data
  }


  const loadValues = () => {
    return new Promise(function (resolve, reject) {
      locationService.request({
        postalCode: postalCodeField.value,
        city: city.value,
        address: address.value
      })
          .then(function (response) {
            if (response.data.error) {
              reject(response.data.error)

              return
            }

            if (response.data.length > 0) {
              hideNoResults()
              enableField()
              setOptions(response.data)
            } else {
              clearOptions()
              disableField()
              showNoResults()
            }

            resolve()
          })
          .catch(function () {
            reject()
          })
    })
  }

  /**
   * Initialize functionality
   */
  const init = () => {
    field.addEventListener('change', setFields)
  }

  init();

  return {loadValues, getOptions}
}

export default locationSelector;