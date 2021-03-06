<?php declare(strict_types=1);

namespace MOS\Affiliate\Test;

use MOS\Affiliate\DataStructs\ClickbankEvent;
use MOS\Affiliate\Test;
use MOS\Affiliate\User;
use MOS\Affiliate\Product;
use MOS\Affiliate\Migration\CommissionsMigration;
use function MOS\Affiliate\ranstr;

class SalesAutomationTest extends Test {

  const HOOK = 'clickbank_event';

  private $user;
  private $sponsor;

  public function _before(): void {
    $this->user = $this->create_test_user();
    $this->sponsor = $this->create_test_user();
  }

  public function test_update_commissions(): void {
    $sale = $this->create_test_cb_event([
      'transaction_type' => 'SALE',
    ]);

    $refund = $this->create_test_cb_event([
      'transaction_type' => 'RFND',
      'amount' => -($sale->amount + 10),
      'transaction_id' => $sale->transaction_id,
    ]);

    $error_refund = $this->create_test_cb_event([
      'transaction_type' => 'RFND',
    ]);

    \do_action( 'clickbank_event', $sale );
    \do_action( 'clickbank_event', $refund );
    \do_action( 'clickbank_event', $error_refund );

    $db_commission = $this->find_commission( $sale );
    $db_refund = $this->find_commission( $refund );
    $db_error_refund = $this->find_commission( $error_refund );

    $this->assert_not_empty( $db_commission );
    $this->assert_equal( $db_commission->date, $sale->date);
    $this->assert_equal( $db_commission->amount, $sale->commission);
    $this->assert_equal( $db_commission->description, $sale->product_name);
    $this->assert_equal( $db_commission->transaction_id, $sale->transaction_id);
    $this->assert_equal( $db_commission->campaign, $sale->campaign);
    $this->assert_equal( $db_commission->actor_id, $sale->customer_wpid);
    $this->assert_equal( $db_commission->earner_id, $sale->sponsor_wpid);
    $this->assert_equal( $db_commission->payout_date, $sale->date);
    $this->assert_equal( $db_commission->payout_method, 'Clickbank');
    $this->assert_equal( $db_commission->payout_address, $sale->cb_affiliate);
    $this->assert_equal( $db_commission->payout_transaction_id, $sale->transaction_id);

    $this->assert_not_empty( $db_refund );
    $this->assert_equal( $db_refund->date, $refund->date);
    $this->assert_equal( $db_refund->amount, -$sale->commission);
    $this->assert_equal( $db_refund->description, $refund->product_name);
    $this->assert_equal( $db_refund->transaction_id, $refund->transaction_id);
    $this->assert_equal( $db_refund->campaign, $refund->campaign);
    $this->assert_equal( $db_refund->actor_id, $refund->customer_wpid);
    $this->assert_equal( $db_refund->earner_id, $refund->sponsor_wpid);
    $this->assert_equal( $db_refund->payout_date, $refund->date);
    $this->assert_equal( $db_refund->payout_method, 'Clickbank');
    $this->assert_equal( $db_refund->payout_address, $refund->cb_affiliate);
    $this->assert_equal( $db_refund->payout_transaction_id, $refund->transaction_id);

    $this->assert_empty( $db_error_refund );
  }

  public function test_give_remove_access_on_sale(): void {
    $products = Product::get_all();

    foreach ( $products as $product_slug => $product ) {
      // Only for clickbank products
      if ( empty( $product->cb_id ) ) {
        continue;
      }

      $this->assert_false( $this->user->has_access( $product_slug ), "User {$this->user->ID} should not have access to $product_slug by default" );
      
      // Emit sale
      $transaction_id = ranstr(10);
      $this->emit_test_cb_event([
        'transaction_type' => 'SALE',
        'transaction_id' => $transaction_id,
        'product_id' => $product->cb_id,
      ]);

      $this->assert_true( $this->user->has_access( $product_slug ), "User {$this->user->get_wpid()} should have access to $product_slug after sale" );
      $this->assert_equal( $this->user->get_access_date( $product_slug ), $this->expected_access_date( $product, 'SALE' ) );

      // Remit refund
      $this->emit_test_cb_event([
        'transaction_type' => 'RFND',
        'transaction_id' => $transaction_id,
        'product_id' => $product->cb_id,
      ]);

      $this->assert_false( $this->user->has_access( $product_slug ), "User {$this->user->ID} should not have access to $product_slug after refund" );
    }
  }

  public function test_give_remove_access_on_bill(): void {
    $products = Product::get_all();

    foreach ( $products as $product_slug => $product ) {
      // Only for recurring clickbank products
      if ( empty( $product->cb_id ) || !$product->is_recurring ) {
        continue;
      }

      $this->assert_false( $this->user->has_access( $product->slug ), "User {$this->user->ID} should not have access to $product->slug by default" );
      
      // Emit sale
      $transaction_id = ranstr(10);
      $this->emit_test_cb_event([
        'transaction_type' => 'BILL',
        'transaction_id' => $transaction_id,
        'product_id' => $product->cb_id,
      ]);

      $this->assert_true( $this->user->has_access( $product->slug ), "User {$this->user->get_wpid()} should have access to $product->slug after rebill" );
      $this->assert_equal( $this->user->get_access_date( $product->slug ), $this->expected_access_date( $product, 'BILL' ) );

      // Remit refund
      $this->emit_test_cb_event([
        'transaction_type' => 'CGBK',
        'transaction_id' => $transaction_id,
        'product_id' => $product->cb_id,
      ]);

      $this->assert_false( $this->user->has_access( $product->slug ), "User {$this->user->ID} should not have access to $product->slug after refund" );
    }
  }

  private function find_commission( ClickbankEvent $event_data ): ?object {
    global $wpdb;
    $table = $wpdb->prefix . CommissionsMigration::TABLE_NAME;
    $conditions = [
      "actor_id = $event_data->customer_wpid",
      "earner_id = $event_data->sponsor_wpid",
      "transaction_id = '$event_data->transaction_id'",
      "campaign = '$event_data->campaign'",
    ];
    $query = "SELECT * FROM $table WHERE " . implode( ' AND ', $conditions );
    $commission = $wpdb->get_row( $query, 'OBJECT' );
    $commission = $commission ? $commission : null;
    return $commission;
  }

  private function emit_test_cb_event( array $args = [] ): void {
    $event = $this->create_test_cb_event( $args );
    \do_action( self::HOOK, $event );
  }

  private function create_test_cb_event( array $args = [] ): ClickbankEvent {
    $default_args = [
      'commission' => rand(10, 997),
      'product_id' => rand(1, 100),
      'transaction_id' => ranstr(32),
      'cb_affiliate' => ranstr(6),
      'campaign' => ranstr(6),
      'customer_wpid' => $this->user->get_wpid(),
      'customer_username' => $this->user->get_username(),
      'customer_name' => $this->user->get_name(),
      'customer_email' => $this->user->get_email(),
      'sponsor_wpid' => $this->sponsor->get_wpid(),
      'sponsor_username' => $this->sponsor->get_username(),
      'sponsor_name' => $this->sponsor->get_name(),
      'sponsor_email' => $this->sponsor->get_email(),
    ];

    $mandatory_args = [
      'product_name' => self::TEST_COMMISSION_DESCRIPTION,
    ];

    $merged = array_replace( $default_args, $args, $mandatory_args );
    $cb_event = new ClickbankEvent();

    foreach ( $merged as $key => $value ) {
      if ( property_exists( $cb_event, $key ) ) {
        $cb_event->$key = $value;
      }
    }

    return $cb_event;
  }

  private function expected_access_date( Product $product, string $transaction_type ): string {
    if ( in_array( $transaction_type, ['SALE', 'TEST_SALE'] ) && !$product->is_recurring ) {
      $access_date = '9999-01-01';
    } elseif ( in_array( $transaction_type, ['SALE', 'TEST_SALE'] ) ) {
      $access_period = $product->has_trial_period ? $product->trial_access_duration : $product->rebill_access_duration;
      $access_date = date( 'Y-m-d', time() + \DAY_IN_SECONDS * $access_period );
    } elseif ( in_array( $transaction_type, ['BILL', 'TEST_BILL'] ) ) {
      $access_period = $product->rebill_access_duration;
      $access_date = date( 'Y-m-d', time() + \DAY_IN_SECONDS * $access_period );
    } else {
      $access_date = '0000-01-01';
    }

    return $access_date;
  }

}