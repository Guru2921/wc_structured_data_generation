<?php
/**
 * Electro Child
 *
 * @package electro-child
 */

/**
 * Include all your custom code here
 */

function update_structured_data_product( $markup, $product ) {
	
	$shop_name = get_bloginfo( 'name' );
	$shop_url  = home_url();
	$currency  = get_woocommerce_currency();
	$permalink = get_permalink( $product->get_id() );
	$image     = wp_get_attachment_url( $product->get_image_id() );

	if ( '' !== $product->get_price() ) {
		// Assume prices will be valid until the end of next year, unless on sale and there is an end date.
		$price_valid_until = date( 'Y-12-31', current_time( 'timestamp', true ) + YEAR_IN_SECONDS );

		if ( $product->is_type( 'variable' ) ) {
			$lowest  = $product->get_variation_price( 'min', false );
			$highest = $product->get_variation_price( 'max', false );

			foreach($product->get_children() as $id => $product_child) {

				$variation_id = $product_child;
				$single_variation = new WC_Product_Variation($variation_id);
				$variation_name = implode(" / ", $single_variation->get_variation_attributes());
				$markup_offer[] = array(
					'@type'              => 'Offer',
					'price'              => wc_format_decimal( $lowest, wc_get_price_decimals() ),
					'priceValidUntil'    => $price_valid_until,
					'name' => $product->get_title(),
					'priceCurrency' => $currency,
					'availability'  => 'http://schema.org/' . ( $product->is_in_stock() ? 'InStock' : 'OutOfStock' ),
					'url'           => $single_variation->get_permalink(),
					'seller'        => array(
						'@type' => 'Organization',
						'name'  => $shop_name,
						'url'   => $shop_url,
					),
				);
			}

		} else {
			if ( $product->is_on_sale() && $product->get_date_on_sale_to() ) {
				$price_valid_until = date( 'Y-m-d', $product->get_date_on_sale_to()->getTimestamp() );
			}
			$markup_offer = array(
				'@type'              => 'Offer',
				'price'              => wc_format_decimal( $product->get_price(), wc_get_price_decimals() ),
				'priceValidUntil'    => $price_valid_until,
				'priceSpecification' => array(
					'price'                 => wc_format_decimal( $product->get_price(), wc_get_price_decimals() ),
					'priceCurrency'         => $currency,
					'valueAddedTaxIncluded' => wc_prices_include_tax() ? 'true' : 'false',
				),
			);
			$markup_offer += array(
				'priceCurrency' => $currency,
				'availability'  => 'http://schema.org/' . ( $product->is_in_stock() ? 'InStock' : 'OutOfStock' ),
				'url'           => $permalink,
				'seller'        => array(
					'@type' => 'Organization',
					'name'  => $shop_name,
					'url'   => $shop_url,
				),
			);
		}

		$markup['offers'] = array( apply_filters( 'woocommerce_structured_data_product_offer', $markup_offer, $product ) );
	}
	return $markup;
}

add_filter( 'woocommerce_structured_data_product', 'update_structured_data_product', 10, 2 );