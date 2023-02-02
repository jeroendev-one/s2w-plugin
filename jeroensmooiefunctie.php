<?php

// Function to add the stock of warehouses to the product stock
function addWarehouses($id) {
	
    // Initialize total stock
    $total = 0;
	
    // Get the stock of Jopa warehouse
    $wcmlim_stock_at_12676 = get_post_meta($id, 'wcmlim_stock_at_12676', true);
    if (!empty($wcmlim_stock_at_12676)) {
        $total = $total + $wcmlim_stock_at_12676;
    }
    // Get the stock of Winkel warehouse
    $wcmlim_stock_at_11450 = get_post_meta($id, 'wcmlim_stock_at_11450', true);
    if (!empty($wcmlim_stock_at_11450)) {
        $total = $total + $wcmlim_stock_at_11450;
    }
	
    // Get the stock of JHsports warehouse
    $wcmlim_stock_at_11449 = get_post_meta($id, 'wcmlim_stock_at_11449', true);
    if (!empty($wcmlim_stock_at_11449)) {
        $total = $total + $wcmlim_stock_at_11449;
    }
	
    // Update the total stock of the product
    update_post_meta($id, '_stock', $total);
	
    // Update the stock status of the product
    if ($total > 0) {
        update_post_meta($id, '_stock_status', 'instock');
    } else {
        update_post_meta($id, '_stock_status', 'outofstock');
    }
}

add_action('pmxi_saved_post', 'addWarehouses', 10, 1);

?>

