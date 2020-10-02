import availability from "./availability";
import findPlace from "./findPlace";
import locationSelector from "./locationSelector";

const fsp = () => {
  /**
   * Initialize functionality
   */
  const init = () => {
    window.locationSelector = locationSelector()

    availability().init();

    document.querySelector('#dhl-fr-find') && findPlace().init();
  }

  return {init}
}

export default fsp;