import flatpickr from "flatpickr";

const PickupDatePicker = () => {
    const field = document.getElementById('pr_dhl_pickup_date');

    const init = () => {
        if (! field) {
            return;
        }

        flatpickr(field, {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: new Date()
        })
    }

    return {init}
}

export default PickupDatePicker
