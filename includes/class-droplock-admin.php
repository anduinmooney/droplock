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

	const MILESTONE_THRESHOLD = 25;             // blocks before the milestone notice appears
	const MILESTONE_OPTION    = 'droplock_milestone_dismissed';

	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'maybe_render_milestone_notice' ) );
		add_action( 'admin_init', array( $this, 'maybe_dismiss_milestone_notice' ) );
	}

	/**
	 * Build a consistent, attributed link to the Pro page.
	 *
	 * @param string $medium Where the click came from (for UTM).
	 * @param string $anchor Optional URL fragment, e.g. 'editions'.
	 */
	public static function pro_url( $medium, $anchor = '' ) {
		$url = 'https://droplockwp.com/' . ( $anchor ? '#' . $anchor : '' );
		$url = add_query_arg(
			array(
				'utm_source'   => 'droplock-free',
				'utm_medium'   => $medium,
				'utm_campaign' => 'upgrade',
			),
			$url
		);
		return $url;
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

		echo '<p class="form-field" style="padding-left:12px;color:#666;font-size:12px;margin-bottom:4px;">'
			. esc_html__( 'Counted order statuses: Completed, Processing, On-hold.', 'droplock' )
			. '</p>';

		// Tasteful, single-line "locked in Pro" hint. Not a nag — one line, contextual.
		echo '<p class="form-field droplock-pro-hint" style="padding-left:12px;color:#777;font-size:12px;">'
			. '<span class="dashicons dashicons-lock" style="font-size:14px;width:14px;height:14px;vertical-align:text-bottom;color:#999;"></span> '
			. esc_html__( 'Pro unlocks per-variation limits, category &amp; tag rules, configurable statuses, and a launch-window countdown.', 'droplock' )
			. ' <a href="' . esc_url( self::pro_url( 'product_edit', 'editions' ) ) . '" target="_blank" rel="noopener">'
			. esc_html__( 'Compare Free vs Pro', 'droplock' ) . ' &rarr;</a>'
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
				<h2><?php esc_html_e( 'Free vs Pro', 'droplock' ); ?></h2>
				<p><?php esc_html_e( 'You are running the free version. Here is exactly what it does, and what Pro adds.', 'droplock' ); ?></p>

				<table class="widefat striped droplock-compare">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Capability', 'droplock' ); ?></th>
							<th style="text-align:center;width:90px;"><?php esc_html_e( 'Free', 'droplock' ); ?></th>
							<th style="text-align:center;width:90px;"><?php esc_html_e( 'Pro', 'droplock' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$same = array(
							__( 'Lifetime per-customer limit', 'droplock' ),
							__( 'Add-to-cart, cart &amp; checkout validation', 'droplock' ),
							__( 'Guest matching by billing email', 'droplock' ),
							__( 'Variations roll up to the parent product', 'droplock' ),
							__( 'HPOS &amp; Block Checkout support', 'droplock' ),
							__( 'Admin / shop-manager bypass', 'droplock' ),
							__( 'Custom limit message &amp; product badge', 'droplock' ),
						);
						foreach ( $same as $row_label ) {
							echo '<tr><td>' . wp_kses_post( $row_label ) . '</td>'
								. '<td style="text-align:center;color:#46b450;font-weight:600;">&#10003;</td>'
								. '<td style="text-align:center;color:#46b450;font-weight:600;">&#10003;</td></tr>';
						}
						$pro = array(
							array( __( 'Blocked-attempt log history', 'droplock' ), __( 'Last 50', 'droplock' ), __( 'Unlimited', 'droplock' ) ),
							array( __( 'Choose which order statuses count', 'droplock' ), __( 'Fixed', 'droplock' ), __( 'Per product', 'droplock' ) ),
							array( __( 'Clear log &amp; CSV export', 'droplock' ), '&mdash;', '&#10003;' ),
							array( __( 'Per-variation limits', 'droplock' ), '&mdash;', __( 'Planned', 'droplock' ) ),
							array( __( 'Category &amp; tag rules', 'droplock' ), '&mdash;', __( 'Planned', 'droplock' ) ),
							array( __( 'Launch window + countdown', 'droplock' ), '&mdash;', __( 'Planned', 'droplock' ) ),
							array( __( 'Priority email support', 'droplock' ), '&mdash;', '&#10003;' ),
						);
						foreach ( $pro as $r ) {
							echo '<tr><td>' . wp_kses_post( $r[0] ) . '</td>'
								. '<td style="text-align:center;color:#888;">' . wp_kses_post( $r[1] ) . '</td>'
								. '<td style="text-align:center;color:#1d2327;font-weight:600;">' . wp_kses_post( $r[2] ) . '</td></tr>';
						}
						?>
					</tbody>
				</table>

				<p style="margin-top:16px;">
					<a class="button button-primary button-hero" href="<?php echo esc_url( self::pro_url( 'dashboard', 'editions' ) ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Upgrade to DropLock Pro', 'droplock' ); ?> &rarr;
					</a>
					&nbsp;
					<a class="button" href="<?php echo esc_url( self::pro_url( 'dashboard', 'ch-iv' ) ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'View pricing', 'droplock' ); ?>
					</a>
					&nbsp;
					<a href="https://droplockwp.com/docs/" target="_blank" rel="noopener" style="line-height:2.4;">
						<?php esc_html_e( 'Documentation', 'droplock' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/* -------------------------------------------------------------------
	 * Milestone notice: shown once after DropLock has blocked enough
	 * purchases to prove its value. Dismissible, WooCommerce screens only.
	 * ------------------------------------------------------------------- */

	public function maybe_render_milestone_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( get_option( self::MILESTONE_OPTION ) ) {
			return;
		}
		// Only on WooCommerce / DropLock / Plugins screens — never globally.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}
		$allowed = ( false !== strpos( (string) $screen->id, 'woocommerce' ) )
			|| ( false !== strpos( (string) $screen->id, 'droplock' ) )
			|| ( 'plugins' === $screen->id )
			|| ( 'dashboard' === $screen->id );
		if ( ! $allowed ) {
			return;
		}

		$total = (int) $this->logger->count_total();
		if ( $total < self::MILESTONE_THRESHOLD ) {
			return;
		}

		$dismiss_url = wp_nonce_url(
			add_query_arg( 'droplock_dismiss_milestone', '1' ),
			'droplock_dismiss_milestone'
		);
		?>
		<div class="notice notice-success is-dismissible droplock-milestone">
			<p style="font-size:13px;">
				<strong><?php esc_html_e( 'Nice work — DropLock is paying off.', 'droplock' ); ?></strong>
				<?php
				printf(
					/* translators: %d: number of blocked purchases */
					esc_html__( 'It has already blocked %d duplicate purchases on your store. Pro adds per-variation limits, launch windows, and an unlimited log.', 'droplock' ),
					$total
				);
				?>
				<a href="<?php echo esc_url( self::pro_url( 'milestone_notice', 'editions' ) ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'See what Pro adds', 'droplock' ); ?> &rarr;
				</a>
				&nbsp;<a href="<?php echo esc_url( $dismiss_url ); ?>" style="color:#787c82;text-decoration:none;"><?php esc_html_e( 'Dismiss', 'droplock' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function maybe_dismiss_milestone_notice() {
		if ( empty( $_GET['droplock_dismiss_milestone'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		check_admin_referer( 'droplock_dismiss_milestone' );
		update_option( self::MILESTONE_OPTION, time(), false );
		wp_safe_redirect( remove_query_arg( array( 'droplock_dismiss_milestone', '_wpnonce' ) ) );
		exit;
	}
}
