<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Order_Meta')) :

class DHLPWC_Model_Service_Order_Meta extends DHLPWC_Model_Core_Singleton_Abstract
{

    const ORDER_LABELS = '_dhlpwc_order_labels';

    public function save_label($order_id, $data)
    {
        $meta_object = new DHLPWC_Model_Meta_Order_Label($data);
        return DHLPWC_Model_Logic_Order_Meta::instance()->add_to_stack(
            self::ORDER_LABELS, $order_id, $data['label_id'], $meta_object
        );
    }

    public function delete_label($order_id, $label_id)
    {
        return DHLPWC_Model_Logic_Order_Meta::instance()->remove_from_stack(
            self::ORDER_LABELS, $order_id, $label_id
        );
    }

    public function get_labels($order_id)
    {
        return DHLPWC_Model_Logic_Order_Meta::instance()->get_stack(
            self::ORDER_LABELS, $order_id
        );
    }

}

endif;
