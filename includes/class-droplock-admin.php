<?php
/**
 * Admin: product fields + WooCommerce > DropLock dashboard (Lite).
 *
 * Lite differences vs Pro:
 *   - No "counted order statuses" checkbox group (statuses are hard-coded).
 *   - No "Clear log" button (Pro feature).
 *   - Adds an "Upgrade to Pro" promo card at bottom of the dashboard page.
 *
 * @package DropLock_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DropLock_Admin {

	const NONCE_FIELD  = 'droplock_product_nonce';
	const NONCE_ACTION = 'droplock_save_product';
	const MENU_SLUG    = 'droplock';

	protected $logger;

	public function __construct( DropLock_Logger $logger ) {
		$this->logger = $logger;
	}

	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ), 10, 1 );
	}

	public function render_product_fields() {
		global $post;
		if ( ! $post ) {
			return;
		}

		echo '<div class="options_group droplock-product-options">';
		echo '<h4 style="padding-left:12px;margin:12px 0 4px;">' . esc_html__( 'DropLock', 'droplock' ) . '</h4>';

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		woocommerce_wp_checkbox( array(
			'id'          => DropLock_Helper::META_ENABLED,
			'label'       => __( 'Enable DropLock for this product', 'droplock' ),
			'description' => __( 'Limit how many of this product a single customer can buy across all of their orders.', 'droplock' ),
			'desc_tip'    => true,
		) );

		woocommerce_wp_text_input( array(
			'id'                => DropLock_Helper::META_MAX_QTY,
			'label'             => __( 'Maximum quantity per customer', 'droplock' ),
			'description'       => __( 'Total quantity allowed across all previous orders and the current cart.', 'droplock' ),
			'desc_tip'          => true,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => '1', 'step' => '1' ),
			'value'             => get_post_meta( $post->ID, DropLock_Helper::META_MAX_QTY, true ) ?: '1',
		) );

		woocommerce_wp_textarea_input( array(
			'id'          => DropLock_Helper::META_LIMIT_MESSAGE,
			'label'       => __( 'Custom limit message', 'droplock' ),
			'placeholder' => DropLock_Helper::default_limit_message(),
			'description' => __( 'Variables: {product_name}, {limit}, {purchased_qty}, {cart_qty}, {remaining_qty}', 'droplock' ),
			'rows'        => 3,
		) );

		woocommerce_wp_text_input( array(
			'id'          => DropLock_Helper::META_BADGE_TEXT,
			'label'       => __( 'Product badge text', 'droplock' ),
			'placeholder' => DropLock_Helper::default_badge_text(),
			'description' => __( 'Variables: {product_name}, {limit}', 'droplock' ),
		) );

		$show_badge = get_post_meta( $post->ID, DropLock_Helper::META_SHOW_BADGE, true );
		if ( '' === $show_badge ) {
			$show_badge = 'yes';
		}
		woocommerce_wp_checkbox( array(
			'id'    => DropLock_Helper::META_SHOW_BADGE,
			'label' => __( 'Show badge on product page', 'droplock' ),
			'value' => $show_badge,
		) );

		echo '<p class="form-field" style="padding-left:12px;color:#666;font-size:12px;">'
			. esc_html__( 'Counted order statuses: Completed, Processing, On-hold.', 'droplock' )
			. ' <a href="https://droplockwp.com/?utm_source=lite&utm_medium=product_edit&utm_campaign=pro" target="_blank" rel="noopener">'
			. esc_html__( 'Customize in Pro', 'droplock' ) . '</a>'
			. '</p>';

		echo '</div>';
	}

	public function save_product_fields( $post_id ) {
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}
		if ( empty( $_POST[ self::NONCE_FIELD ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		update_post_meta( $post_id, DropLock_Helper::META_ENABLED, isset( $_POST[ DropLock_Helper::META_ENABLED ] ) ? 'yes' : 'no' );

		$max_qty = isset( $_POST[ DropLock_Helper::META_MAX_QTY ] )
			? absint( wp_unslash( $_POST[ DropLock_Helper::META_MAX_QTY ] ) )
			: 1;
		if ( $max_qty < 1 ) {
			$max_qty = 1;
		}
		update_post_meta( $post_id, DropLock_Helper::META_MAX_QTY, $max_qty );

		$msg = isset( $_POST[ DropLock_Helper::META_LIMIT_MESSAGE ] )
			? wp_kses_post( wp_unslash( $_POST[ DropLock_Helper::META_LIMIT_MESSAGE ] ) )
			: '';
		update_post_meta( $post_id, DropLock_Helper::META_LIMIT_MESSAGE, $msg );

		$badge = isset( $_POST[ DropLock_Helper::META_BADGE_TEXT ] )
			? sanitize_text_field( wp_unslash( $_POST[ DropLock_Helper::META_BADGE_TEXT ] ) )
			: '';
		update_post_meta( $post_id, DropLock_Helper::META_BADGE_TEXT, $badge );

		update_post_meta( $post_id, DropLock_Helper::META_SHOW_BADGE, isset( $_POST[ DropLock_Helper::META_SHOW_BADGE ] ) ? 'yes' : 'no' );
	}

	public function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'DropLock', 'droplock' ),
			__( 'DropLock', 'droplock' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array( $this, 'render_admin_page' )
		);
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'droplock' ) );
		}

		$rows  = $this->logger->get_recent( DROPLOCK_LITE_LOG_CAP );
		$total = $this->logger->count_total();
		?>
		<div class="wrap droplock-wrap">
			<h1><?php esc_html_e( 'DropLock', 'droplock' ); ?></h1>

			<div class="droplock-card">
				<h2><?php esc_html_e( 'Overview', 'droplock' ); ?></h2>
				<p><?php esc_html_e( 'DropLock enforces lifetime purchase limits per customer on the products you protect.', 'droplock' ); ?></p>
				<ol>
					<li><?php esc_html_e( 'Edit a product.', 'droplock' ); ?></li>
					<li><?php esc_html_e( 'Enable DropLock and set the maximum quantity per customer.', 'droplock' ); ?></li>
					<li><?php esc_html_e( 'Customize the message and badge if you want.', 'droplock' ); ?></li>
					<li><?php esc_html_e( 'Save the product.', 'droplock' ); ?></li>
				</ol>
			</div>

			<div class="droplock-card">
				<h2><?php esc_html_e( 'Recent blocked attempts', 'droplock' ); ?></h2>
				<p><?php printf( esc_html__( 'Showing the last %1$d entries (free version cap). Total blocked: %2$d.', 'droplock' ), (int) DROPLOCK_LITE_LOG_CAP, (int) $total ); ?></p>

				<?php if ( empty( $rows ) ) : ?>
					<p><em><?php esc_html_e( 'No blocked attempts yet.', 'droplock' ); ?></em></p>
				<?php else : ?>
					<table class="widefat striped droplock-log-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'droplock' ); ?></th>
								<th><?php esc_html_e( 'Product', 'droplock' ); ?></th>
								<th><?php esc_html_e( 'User', 'droplock' ); ?></th>
								<th><?php esc_html_e( 'Email', 'droplock' ); ?></th>
								<th><?php esc_html_e( 'Reason', 'droplock' ); ?></th>
								<th><?php esc_html_e( 'Purchased', 'droplock' ); ?></th>
								<th><?php esc_html_e( 'Cart', 'droplock' ); ?></th>
								<th><?php esc_html_e( 'Limit', 'droplock' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rows as $row ) : ?>
								<tr>
									<td><?php echo esc_html( $row['created_at'] ); ?></td>
									<td>
										<?php
										$pid   = (int) $row['product_id'];
										$pname = $row['product_name'] ? $row['product_name'] : ( '#' . $pid );
										$edit  = $pid ? get_edit_post_link( $pid ) : false;
										if ( $edit ) {
											echo '<a href="' . esc_url( $edit ) . '">' . esc_html( $pname ) . '</a>';
										} else {
											echo esc_html( $pname );
										}
										?>
									</td>
									<td>
										<?php
										$uid = (int) $row['user_id'];
										if ( $uid ) {
											$u = get_userdata( $uid );
											echo esc_html( $u ? $u->user_login : ( '#' . $uid ) );
										} else {
											echo '&mdash;';
										}
										?>
									</td>
									<td><?php echo esc_html( $row['billing_email'] ?: '—' ); ?></td>
									<td><?php echo esc_html( $row['reason'] ); ?></td>
									<td><?php echo (int) $row['purchased_qty']; ?></td>
									<td><?php echo (int) $row['cart_qty']; ?></td>
									<td><?php echo (int) $row['max_limit']; ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<div class="droplock-card droplock-pro-card">
				<h2><?php esc_html_e( 'Want more?', 'droplock' ); ?></h2>
				<p><?php esc_html_e( 'DropLock Pro adds:', 'droplock' ); ?></p>
				<ul style="list-style:disc;padding-left:20px;">
					<li><?php esc_html_e( 'Per-variation limits', 'droplock' ); ?></li>
					<li><?php esc_html_e( 'Category and tag level rules', 'droplock' ); ?></li>
					<li><?php esc_html_e( 'Custom counted order statuses per product', 'droplock' ); ?></li>
					<li><?php esc_html_e( 'Full blocked attempts log with filters, search, and CSV export', 'droplock' ); ?></li>
					<li><?php esc_html_e( 'Launch date/time window and countdown badge', 'droplock' ); ?></li>
					<li><?php esc_html_e( 'Waitlist email capture', 'droplock' ); ?></li>
					<li><?php esc_html_e( 'Priority email support', 'droplock' ); ?></li>
				</ul>
				<p>
					<a class="button button-primary" href="https://droplockwp.com/?utm_source=lite&utm_medium=dashboard&utm_campaign=pro" target="_blank" rel="noopener">
						<?php esc_html_e( 'See DropLock Pro', 'droplock' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
}
