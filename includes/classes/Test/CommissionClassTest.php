<?php declare(strict_types=1);

namespace MOS\Affiliate\Test;

use \MOS\Affiliate\Test;
use \MOS\Affiliate\Commission;

class CommissionClassTest extends Test {

  private $valid_data = [
    'date' => '2020-12-06',
    'amount' => 50,
    'description' => 'Some description',
    'transaction_id' => '1vG92ClVTga2GIROZ5fEK8JQ1GL7gkCk',
    'campaign' => 'facebook',
    'actor_id' => 64,
    'earner_id' => 144,
    'payout_date' => null,
    'payout_method' => null,
    'payout_address' => null,
    'payout_transaction_id' => null,
    'refund_date' => null,
  ];

  public function test_construct(): void {
    $class_name = '\MOS\Affiliate\Commission';
    $this->assert_class_exists( $class_name );
    $commission = new Commission();
    $this->assert_instanceof( $commission, $class_name );
  }


  public function test_construct_from_array(): void {
    $commission_data = [
      'date' => '2020-12-06',
      'amount' => 50,
      'description' => 'Some description',
      'transaction_id' => '1vG92ClVTga2GIROZ5fEK8JQ1GL7gkCk',
      'campaign' => 'facebook',
      'actor_id' => 64,
      'earner_id' => 144,
      'payout_date' => null,
      'payout_method' => null,
      'payout_address' => null,
      'payout_transaction_id' => null,
      'refund_date' => null,
    ];
    $commission = Commission::create_from_array( $commission_data );
    $this->assert_equal( $commission->get_date(), $commission_data['date']);
    $this->assert_equal( $commission->get_amount(), $commission_data['amount']);
    $this->assert_equal( $commission->get_description(), $commission_data['description']);
    $this->assert_equal( $commission->get_transaction_id(), $commission_data['transaction_id']);
    $this->assert_equal( $commission->get_campaign(), $commission_data['campaign']);
    $this->assert_equal( $commission->get_actor_id(), $commission_data['actor_id']);
    $this->assert_equal( $commission->get_earner_id(), $commission_data['earner_id']);
    $this->assert_equal( $commission->get_payout_date(), $commission_data['payout_date']);
    $this->assert_equal( $commission->get_payout_method(), $commission_data['payout_method']);
    $this->assert_equal( $commission->get_payout_address(), $commission_data['payout_address']);
    $this->assert_equal( $commission->get_payout_transaction_id(), $commission_data['payout_transaction_id']);
    $this->assert_equal( $commission->get_refund_date(), $commission_data['refund_date']);
  }


  public function test_is_valid(): void {
    $valid_data = [
      'date' => '2020-12-06',
      'amount' => 50,
      'description' => 'Some description',
      'transaction_id' => '1vG92ClVTga2GIROZ5fEK8JQ1GL7gkCk',
      'campaign' => 'facebook',
      'actor_id' => 64,
      'earner_id' => 144,
      'payout_date' => null,
      'payout_method' => null,
      'payout_address' => null,
      'payout_transaction_id' => null,
      'refund_date' => null,
    ];
    
    $edit = [];
    $this->assert_true( $this->commission_is_valid( $valid_data, $edit ));

    $edit = ['date' => '2020-13-13'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['date' => '2020-06-32'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['date' => '2020-02-31'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['amount' => 'string instead of number'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['amount' => -1];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['description' => ''];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['campaign' => null];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['earner_id' => 'string instead of int'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['earner_id' => -1];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['earner_id' => 0.05];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['payout_date' => '2020-13-13'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['payout_date' => '2020-06-32'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['payout_date' => '2020-02-31'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );
    
    $edit = ['payout_method' => 42];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );
    
    $edit = ['payout_address' => 42];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );
    
    $edit = ['payout_transaction_id' => 42];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['refund_date' => '2020-13-13'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['refund_date' => '2020-06-32'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );

    $edit = ['refund_date' => '2020-02-31'];
    $this->assert_false( $this->commission_is_valid( $valid_data, $edit ) );
    
  }


  private function commission_is_valid( array $commission_data, array $edit=[], ...$data ): bool {
    // Apply edits
    if ( !empty( $edit ) ) {
      foreach( $edit as $index => $value ) {
        $commission_data[$index] = $value;
      }
    }

    $commission = Commission::create_from_array( $commission_data );
    return $commission->is_valid();
  }

}