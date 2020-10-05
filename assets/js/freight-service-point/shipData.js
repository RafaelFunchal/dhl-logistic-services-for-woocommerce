const shipData = () => {
  const shipping_country = document.getElementById('shipping_country')
  const shipping_address_1 = document.getElementById('shipping_address_1')
  const shipping_postcode = document.getElementById('shipping_postcode')
  const shipping_city = document.getElementById('shipping_city')

  const setData = (point) => {
    shipping_country.value = point.countryCode
    shipping_address_1.value = point.street
    shipping_postcode.value = point.postalCode
    shipping_city.value = point.cityName
  }

  return {setData}
}

export default shipData