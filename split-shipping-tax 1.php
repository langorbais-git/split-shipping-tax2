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


			public function __construct()
			{
				//$this->setCookie();

				$this->addActions();

				$this->addFilters();
			}

			private function addActions()
			{
				
				add_action("init", [$this, 'setCookie']);
				add_action( 'woocommerce_before_calculate_totals', [$this, 'change_tax_class_based_on_payment_method'], 10, 1 );
				add_action('wp_footer', [$this, 'payment_methods_trigger_update_checkout']);
				add_action( 'woocommerce_after_get_rates_for_package', [$this, 'get_rates_for_package'], 10, 2 );
				add_action( 'woocommerce_cart_calculate_fees', [$this, 'woo_add_cart_fee'],  99, 1);


			}

			public function setCookie()
			{
				if(isset($_POST['tax-rate']) && $_POST['tax-rate'] == "wholesale")
				{
					setcookie("tax-rate", "wholesale", time() + (86400 * 365), "/");

					$_COOKIE['tax-rate'] = "wholesale";
				}
				else if(isset($_POST['tax-rate']) && $_POST['tax-rate'] == "standard")
				{
					setcookie("tax-rate", "standard", time() + (86400 * 365), "/");

					$_COOKIE['tax-rate'] = "standard";
				}
				else if(!isset($_COOKIE['tax-rate']))
				{
					setcookie("tax-rate", "standard", time() + (86400 * 365), "/");

					$_COOKIE['tax-rate'] = "standard";
				}
			}
			
			public function change_tax_class_based_on_payment_method( $cart ) 
			{

/*
				// Only for a specific defined payment meyhod
				if ( WC()->session->get('chosen_payment_method') !== 'wdc_woo_credits' )
					return;
*/
					

				if ( is_admin() && ! defined( 'DOING_AJAX' ) )
					return;

				if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
					return;

				// Loop through cart items
				foreach( $cart->get_cart() as $cart_item ){
					// We set "Zero rate" tax class
					$cart_item['data']->set_tax_class("Zero rate");
				}
				//dump($cart->get_cart());
			}

			public function payment_methods_trigger_update_checkout() {
				if( is_checkout() && ! is_wc_endpoint_url() ) :
				?>
				<script type="text/javascript">
					jQuery(function($){
						$( 'form.checkout' ).on('change', 'input[name="payment_method"]', function() {
							$(document.body).trigger('update_checkout');
						});
					});
				</script>
				<?php
				endif;
			}

			public function woo_add_cart_fee( $bookable_total = 1 ) {

				if ( is_admin() && ! defined( 'DOING_AJAX' ) )
					return;
					$fee = 0;
dump($this);

					if (in_array(WC()->customer->get_shipping_country(), $this->eu_countries_code) ||
						in_array(WC()->customer->get_shipping_country(), $this->eu_countries)) {
						$fee = ( ( ($this->get_shipping_rate + $this->get_landedcost_rate) * 0 ) / 100 );
						//WC()->cart->add_fee( 'Shipping Fees: ', $this->get_shipping_rate + 100, false );
					} elseif ((WC()->customer->get_shipping_country() === 'DK')) {
						$fee = ( ( ($this->get_shipping_rate + $this->get_landedcost_rate) * 25 ) / 100 );
						//WC()->cart->add_fee( 'Shipping Fees: ', ( $this->get_shipping_rate * 25 ) / 100 , false );
					} else {
						$fee = ( ( ($this->get_shipping_rate + $this->get_landedcost_rate) * 0 ) / 100 );
						//WC()->cart->add_fee( 'Shipping Fees: ', $this->get_shipping_rate + 300, false );
					}

					WC()->cart->add_fee( 'Shipping Fees ', $fee, false );
			}
		
		
			public function get_rates_for_package( $package, $shipping_method ){
				$chosen_methods = WC()->session->get( "chosen_shipping_methods" );
				$chosen_shipping = $chosen_methods[0];

				foreach( $package["rates"] as $id => $value ){
					//dump($value->get_cost());
					$this->get_shipping_rate = $value->get_cost();
				}
			}		
			
			private function addFilters()
			{
				add_filter("woocommerce_product_get_tax_class", [$this, 'setTaxRate'], 1, 2);
				add_filter("woocommerce_product_variation_get_tax_class", [$this, 'setTaxRate'], 1, 2);
				add_filter( 'woocommerce_package_rates', [$this, 'selected_shipping_methods'], 1, 2 ); 
				add_filter( 'woocommerce_cart_get_taxes', [ $this, 'reorder_taxes' ], 10, 2 );
			}

			public function setTaxRate($tax_class, $product)
			{
				
				if($_COOKIE['tax-rate'] == "wholesale")
				{
					return "Wholesale";
				}

				return "";
			}

			public function selected_shipping_methods($rates, $package) {

				$chosen_methods = WC()->session->get( "chosen_shipping_methods" );
				$chosen_shipping = $chosen_methods[0];

				return $rates;
			}	

			public function reorder_taxes( $taxes, $cart ) {

				foreach( $taxes as $id => $value ){
					if (strpos($id, 'LandedCost') !== false) {
						$this->get_landedcost_rate = $taxes[$id];

					return $taxes;
					}
					
				}dump($this->get_landedcost_rate);


				return $taxes;
			}
	
		}
		new TaxRate();
	}