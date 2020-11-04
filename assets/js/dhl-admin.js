// Run Actions
import PickupDatePicker from "./freight-service-point-admin/PickupDatePicker";

const actions = () => {
  const run = () => {
    PickupDatePicker().init()
  }

  return {run}
}

// On jQuery done run all actions
jQuery(document).ready(() => actions().run());
