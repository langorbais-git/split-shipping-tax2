<?php
/*
	Plugin Name: Split shipping tax (By Gajelabs)
	Plugin URI: http://wordpress.org/plugins/split-shipping-tax
	Description: Split shipping tax.
	Author: GajeLabs
	Developer: BOUBA LABOBE Janvier (Langorbais)
	Version: 0.0.1
	Author URI: http://www.gajelabs.com
*/
			
namespace Example;

	// Define BLJ_SPLIT_SHIPPING_TAX
	if ( !defined( 'BLJ_SPLIT_SHIPPING_TAX' ) )
	{
		define( 'BLJ_SPLIT_SHIPPING_TAX', '0.0.1' );
	}

	if (!function_exists('dump')) {
		function dump($product, $label=''){
			echo '<pre>';
			print_r($label);
			print_r($product);
			echo"</pre>";
		}
	}

	// main class
	if(!class_exists('TaxRate')){
		class TaxRate {
			
			private $get_shipping_rate = 0;
			private $get_landedcost_rate = 0;

			private $eu_countries_code = array('BE', 'BG', 'CZ', 'DE', 'EE', 'IE', 'EL', 'ES', 'FR', 'HR', 'IT', 'CY', 'LV',
											   'LT', 'LU', 'HU', 'MT', 'NL', 'AT', 'PL', 'PT', 'RO', 'SI', 'SK', 'FI', 'SE');

			private $eu_countries = array('Austria', 'Belgium', 'Bulgaria', 'Croatia', 'Cyprus', 'Czech Republic',
										  'Estonia', 'Finland', 'France', 'Germany', 'Greece', 'Hungary', 'Ireland', 'Italy',
										  'Latvia', 'Lithuania', 'Luxembourg', 'Malta', 'Netherlands', 'Poland', 'Portugal',
										  'Romania', 'Slovakia', 'Slovenia', 'Spain', 'Sweden');


			public function __construct() {
				$this->addFilters();

				$this->addActions();

				
			}
			
			// Add actions
			private function addActions() {
				
				add_action( 'woocommerce_after_get_rates_for_package', [$this, 'get_rates_for_package'], 99, 2 );
				add_action( 'woocommerce_cart_calculate_fees', [$this, 'woo_add_cart_fee'],  99, 1);

			}

			public function get_rates_for_package( $package, $shipping_method ){
				$chosen_methods = WC()->session->get( "chosen_shipping_methods" );
				//var_dump($chosen_methods[0]);
				$chosen_shipping = $chosen_methods[0];
				
				  

				foreach( $package["rates"] as $id => $value ){
					//dump($value->get_cost());
					$this->get_shipping_rate = $value->get_cost();
					WC()->session->set( "shipping_cost",$value->get_cost() );
					
				}
			}		

			public function woo_add_cart_fee( $bookable_total = 1 ) {

				if ( is_admin() && ! defined( 'DOING_AJAX' ) )
					return;
					$fee = 0;
			//	var_dump($res);
			//var_dump(WC()->session->get( "Landedcost"));
					

					if (in_array(WC()->customer->get_shipping_country(), $this->eu_countries_code) ||
						in_array(WC()->customer->get_shipping_country(), $this->eu_countries)) {
						$fee = ( ( (WC()->session->get( "shipping_cost") + WC()->session->get( "Landedcost")) * 0 ) / 100 );
						//WC()->cart->add_fee( 'Shipping Fees: ', $this->get_shipping_rate + 100, false );
					} elseif ((WC()->customer->get_shipping_country() === 'DK')) {
						$fee = ( ( (WC()->session->get( "shipping_cost") + WC()->session->get( "Landedcost")) * 25 ) / 100 );
						//WC()->cart->add_fee( 'Shipping Fees: ', ( $this->get_shipping_rate * 25 ) / 100 , false );
					} else { 
						$fee = ( ( (WC()->session->get( "shipping_cost") + WC()->session->get( "Landedcost")) * 0 ) / 100 );
						//WC()->cart->add_fee( 'Shipping Fees: ', $this->get_shipping_rate + 300, false );
					}
					
					WC()->cart->add_fee( 'Shipping Fees ', $fee, false );
			}
			
	
  //....................................................................................................................................\\
//\\````````````````````````````````````````````````````````````````````````````````````````````````````````````````````````````````````//	
                  
				  

			private function addFilters() {
				add_filter( 'woocommerce_package_rates', [$this, 'selected_shipping_methods'], 1, 2 ); 
				add_filter( 'woocommerce_cart_get_taxes', [ $this, 'reorder_taxes' ], 999, 2 );
			}

			public function selected_shipping_methods($rates, $package) {

				$chosen_methods = WC()->session->get( "chosen_shipping_methods" );
				$chosen_shipping = $chosen_methods[0];

				return $rates;
			}	

			public function reorder_taxes( $taxes, $cart ) {
				

				
				$this->woo_add_cart_fee();

				foreach( $taxes as $id => $value ){
					if (strpos($id, 'LandedCost') !== false) {
						$this->get_landedcost_rate = $taxes[$id];
						WC()->session->set( "Landedcost", $taxes[$id]);
						

					return $taxes;
					}
					
				}

				return $taxes;
			}
	
		}
		new TaxRate();
	}