<?php
/**
 * Plugin Name: WC Ajuste Pagamento - desconto por metodo de pagamento
 * Description: Permite configurar acrescimo ou desconto por metodo de pagamento em cada produto no WooCommerce.
 * Version:     1.0.1
 * Author:      Giovani Tureck
 * Text Domain: wc-metodo-pagamento-acrescimo
 * Requires at least: 6.0
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 *
 * @package WC_Metodo_Pagamento_Acrescimo
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WC_Metodo_Pagamento_Acrescimo')) {
	final class WC_Metodo_Pagamento_Acrescimo
	{
		const VERSION = '1.0.1';
		const META_KEY = '_wc_payment_method_surcharges';
		const NONCE_ACTION = 'wc_payment_method_surcharges_save';
		const NONCE_NAME = 'wc_payment_method_surcharges_nonce';

		/**
		 * Bootstraps plugin hooks.
		 *
		 * @return void
		 */
		public function __construct()
		{
			add_action('before_woocommerce_init', array($this, 'declare_compatibility'));
			add_action('plugins_loaded', array($this, 'init'));
		}

		/**
		 * Initializes plugin hooks after WooCommerce is loaded.
		 *
		 * @return void
		 */
		public function init()
		{
			if (!class_exists('WooCommerce')) {
				add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));
				return;
			}

			add_filter('woocommerce_product_data_tabs', array($this, 'register_product_data_tab'));
			add_action('woocommerce_product_data_panels', array($this, 'render_product_data_panel'));
			add_action('woocommerce_admin_process_product_object', array($this, 'save_product_data'));
			add_action('woocommerce_checkout_update_order_review', array($this, 'capture_checkout_payment_method'));
			add_action('woocommerce_cart_calculate_fees', array($this, 'apply_payment_method_surcharge'));
			add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_script'));
		}

		/**
		 * Declares compatibility with WooCommerce custom order tables.
		 *
		 * @return void
		 */
		public function declare_compatibility()
		{
			if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
			}
		}

		/**
		 * Renders an admin notice when WooCommerce is not active.
		 *
		 * @return void
		 */
		public function render_missing_woocommerce_notice()
		{
			?>
			<div class="notice notice-error">
				<p><?php echo esc_html__('WooCommerce Acrescimo por Metodo de Pagamento requer o WooCommerce ativo.', 'wc-metodo-pagamento-acrescimo'); ?>
				</p>
			</div>
			<?php
		}

		/**
		 * Adds the custom tab to product data.
		 *
		 * @param array<string, array<string, mixed>> $tabs Existing tabs.
		 * @return array<string, array<string, mixed>>
		 */
		public function register_product_data_tab($tabs)
		{
			$tabs['wc_payment_method_surcharges'] = array(
				'label' => esc_html__('Ajuste por pagamento', 'wc-metodo-pagamento-acrescimo'),
				'target' => 'wc_payment_method_surcharges_panel',
				'priority' => 75,
			);

			return $tabs;
		}

		/**
		 * Renders the product settings panel.
		 *
		 * @return void
		 */
		public function render_product_data_panel()
		{
			global $post;

			$product_id = $post instanceof WP_Post ? (int) $post->ID : 0;
			$saved_data = $this->get_saved_surcharges_for_product_id($product_id);
			$gateways = $this->get_payment_gateways();
			?>
			<div id="wc_payment_method_surcharges_panel" class="panel woocommerce_options_panel hidden">
				<?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
				<div class="options_group">
					<p class="form-field">
						<label><?php echo esc_html__('Configuracao', 'wc-metodo-pagamento-acrescimo'); ?></label>
						<span class="description" style="display:inline-block;max-width:70%;">
							<?php echo esc_html__('Use valor positivo para acrescimo e negativo para desconto. Deixe em branco ou 0 para nao aplicar ajuste.', 'wc-metodo-pagamento-acrescimo'); ?>
						</span>
					</p>
					<?php if (empty($gateways)): ?>
						<p class="form-field">
							<label><?php echo esc_html__('Metodos de pagamento', 'wc-metodo-pagamento-acrescimo'); ?></label>
							<span class="description" style="display:inline-block;max-width:70%;">
								<?php echo esc_html__('Nenhum gateway de pagamento foi encontrado no WooCommerce.', 'wc-metodo-pagamento-acrescimo'); ?>
							</span>
						</p>
					<?php else: ?>
						<table class="widefat striped" style="margin: 12px; width: calc(100% - 24px);">
							<thead>
								<tr>
									<th><?php echo esc_html__('Metodo', 'wc-metodo-pagamento-acrescimo'); ?></th>
									<th><?php echo esc_html__('Status', 'wc-metodo-pagamento-acrescimo'); ?></th>
									<th><?php echo esc_html__('Ajuste (%)', 'wc-metodo-pagamento-acrescimo'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($gateways as $gateway_id => $gateway): ?>
									<?php
									$value = isset($saved_data[$gateway_id]) ? wc_format_localized_decimal($saved_data[$gateway_id]) : '';
									$status = 'yes' === $gateway->enabled ? esc_html__('Ativo', 'wc-metodo-pagamento-acrescimo') : esc_html__('Inativo', 'wc-metodo-pagamento-acrescimo');
									?>
									<tr>
										<td>
											<strong><?php echo esc_html($gateway->get_title()); ?></strong><br />
											<code><?php echo esc_html($gateway_id); ?></code>
										</td>
										<td><?php echo esc_html($status); ?></td>
										<td>
											<input type="number" step="0.01" style="max-width: 120px;"
												name="wc_payment_method_surcharges[<?php echo esc_attr($gateway_id); ?>]"
												value="<?php echo esc_attr($value); ?>" />
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Saves surcharges configured on the product page.
		 *
		 * @param WC_Product $product Product object.
		 * @return void
		 */
		public function save_product_data($product)
		{
			if (!isset($_POST[self::NONCE_NAME])) {
				return;
			}

			$nonce = sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]));

			if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
				return;
			}

			$raw_surcharges = isset($_POST['wc_payment_method_surcharges']) && is_array($_POST['wc_payment_method_surcharges'])
				? wp_unslash($_POST['wc_payment_method_surcharges'])
				: array();

			$clean_surcharges = array();

			foreach ($raw_surcharges as $gateway_id => $raw_percentage) {
				$gateway_id = sanitize_key($gateway_id);
				$percentage = (float) wc_format_decimal($raw_percentage);

				if (0.0 === $percentage) {
					continue;
				}

				$clean_surcharges[$gateway_id] = $percentage;
			}

			if (empty($clean_surcharges)) {
				$product->delete_meta_data(self::META_KEY);
				return;
			}

			$product->update_meta_data(self::META_KEY, $clean_surcharges);
		}

		/**
		 * Stores the chosen payment method in session while checkout updates.
		 *
		 * @param string $posted_data Serialized checkout fields.
		 * @return void
		 */
		public function capture_checkout_payment_method($posted_data)
		{
			if (!WC()->session) {
				return;
			}

			$data = array();
			parse_str($posted_data, $data);

			if (empty($data['payment_method'])) {
				return;
			}

			WC()->session->set('chosen_payment_method', wc_clean($data['payment_method']));
		}

		/**
		 * Applies the surcharge as a fee in cart and checkout.
		 *
		 * @param WC_Cart $cart Cart object.
		 * @return void
		 */
		public function apply_payment_method_surcharge($cart)
		{
			if (!$cart instanceof WC_Cart) {
				return;
			}

			if (is_admin() && !wp_doing_ajax()) {
				return;
			}

			if ($cart->is_empty()) {
				return;
			}

			$payment_method = $this->get_current_payment_method();

			if ('' === $payment_method) {
				return;
			}

			$total_surcharge = 0.0;

			foreach ($cart->get_cart() as $cart_item) {
				if (empty($cart_item['data']) || !$cart_item['data'] instanceof WC_Product) {
					continue;
				}

				$product = $cart_item['data'];
				$percentage = $this->get_surcharge_percentage_for_product($product, $payment_method);

				if (0.0 === $percentage) {
					continue;
				}

				$line_total = isset($cart_item['line_total']) ? (float) $cart_item['line_total'] : 0.0;

				if ($line_total <= 0) {
					$line_total = (float) $product->get_price() * (int) $cart_item['quantity'];
				}

				if ($line_total <= 0) {
					continue;
				}

				$total_surcharge += $line_total * ($percentage / 100);
			}

			if (0.0 === $total_surcharge) {
				return;
			}

			$gateway_title = $this->get_gateway_title($payment_method);
			$is_discount = $total_surcharge < 0;
			$fee_label = $gateway_title
				? sprintf(
					/* translators: %s: payment gateway title. */
					$is_discount
					? esc_html__('Desconto (%s)', 'wc-metodo-pagamento-acrescimo')
					: esc_html__('Acrescimo (%s)', 'wc-metodo-pagamento-acrescimo'),
					$gateway_title
				)
				: (
					$is_discount
					? esc_html__('Desconto por forma de pagamento', 'wc-metodo-pagamento-acrescimo')
					: esc_html__('Acrescimo por forma de pagamento', 'wc-metodo-pagamento-acrescimo')
				);

			$fee_taxable = (bool) apply_filters('wc_metodo_pagamento_acrescimo_fee_taxable', false, $payment_method, $cart);

			$cart->add_fee($fee_label, $total_surcharge, $fee_taxable);
		}

		/**
		 * Enqueues the checkout updater script.
		 *
		 * @return void
		 */
		public function enqueue_checkout_script()
		{
			if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
				return;
			}

			wp_enqueue_script(
				'wc-metodo-pagamento-acrescimo',
				plugins_url('assets/js/checkout-update.js', __FILE__),
				array('jquery'),
				self::VERSION,
				true
			);
		}

		/**
		 * Retrieves the currently selected payment method.
		 *
		 * @return string
		 */
		private function get_current_payment_method()
		{
			if (isset($_POST['payment_method'])) {
				return wc_clean(wp_unslash($_POST['payment_method']));
			}

			if (isset($_POST['post_data'])) {
				$data = array();
				parse_str(wp_unslash($_POST['post_data']), $data);

				if (!empty($data['payment_method'])) {
					return wc_clean($data['payment_method']);
				}
			}

			if (WC()->session) {
				$session_value = WC()->session->get('chosen_payment_method');
				return is_string($session_value) ? $session_value : '';
			}

			return '';
		}

		/**
		 * Gets saved surcharge data for a product or its parent.
		 *
		 * @param WC_Product $product Product object.
		 * @return array<string, float>
		 */
		private function get_saved_surcharges_for_product($product)
		{
			$data = $product->get_meta(self::META_KEY, true);

			if (empty($data) && $product->get_parent_id()) {
				$parent_product = wc_get_product($product->get_parent_id());
				$data = $parent_product instanceof WC_Product ? $parent_product->get_meta(self::META_KEY, true) : array();
			}

			return $this->normalize_surcharges($data);
		}

		/**
		 * Gets saved surcharge data for a product id.
		 *
		 * @param int $product_id Product id.
		 * @return array<string, float>
		 */
		private function get_saved_surcharges_for_product_id($product_id)
		{
			if ($product_id <= 0) {
				return array();
			}

			$data = get_post_meta($product_id, self::META_KEY, true);

			return $this->normalize_surcharges($data);
		}

		/**
		 * Returns the surcharge percentage for a product and gateway.
		 *
		 * @param WC_Product $product Product object.
		 * @param string     $gateway_id Payment gateway id.
		 * @return float
		 */
		private function get_surcharge_percentage_for_product($product, $gateway_id)
		{
			$surcharges = $this->get_saved_surcharges_for_product($product);

			return isset($surcharges[$gateway_id]) ? (float) $surcharges[$gateway_id] : 0.0;
		}

		/**
		 * Sanitizes stored surcharge data.
		 *
		 * @param mixed $data Raw stored data.
		 * @return array<string, float>
		 */
		private function normalize_surcharges($data)
		{
			if (!is_array($data)) {
				return array();
			}

			$clean = array();

			foreach ($data as $gateway_id => $percentage) {
				$gateway_id = sanitize_key($gateway_id);
				$percentage = (float) $percentage;

				if ('' === $gateway_id || 0.0 === $percentage) {
					continue;
				}

				$clean[$gateway_id] = $percentage;
			}

			return $clean;
		}

		/**
		 * Returns all registered WooCommerce gateways.
		 *
		 * @return array<string, WC_Payment_Gateway>
		 */
		private function get_payment_gateways()
		{
			if (!function_exists('WC') || !WC()->payment_gateways()) {
				return array();
			}

			$gateways = WC()->payment_gateways()->payment_gateways();

			return is_array($gateways) ? $gateways : array();
		}

		/**
		 * Resolves a gateway title from its id.
		 *
		 * @param string $gateway_id Gateway id.
		 * @return string
		 */
		private function get_gateway_title($gateway_id)
		{
			$gateways = $this->get_payment_gateways();

			if (empty($gateways[$gateway_id])) {
				return $gateway_id;
			}

			return (string) $gateways[$gateway_id]->get_title();
		}
	}
}

new WC_Metodo_Pagamento_Acrescimo();
