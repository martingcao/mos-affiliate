<?php declare(strict_types=1);

namespace MOS\Affiliate\Test;

use MOS\Affiliate\Test;

use function \MOS\Affiliate\mis_default_value;
use function \do_shortcode;
use function \update_user_meta;

class SponsorShortcodesTest extends Test {

  protected $_injected_sponsor;

  private $mis = [
    'gr' => 'my_gr_id',
    'cm' => '',
    'non_existent' => 'my_nonexistent_id',
  ];


  protected function _before(): void {
    $this->_injected_sponsor = $this->create_test_user();
    $this->set_sponsor();
  }

  public function test_sponsor_affid_shortcode(): void {
    $expected = $this->_injected_sponsor->get_affid();
    $shortcode = '[mos_sponsor_affid]';
    $this->assert_shortcode_equal( $shortcode, $expected );
  }


  public function test_sponsor_email_shortcode(): void {
    $email = 'teEGRaghlR83SBEOfMCfYjNO4NIrHZvN@gmail.com';
    $this->_injected_sponsor->user_email = $email;
    $shortcode = '[mos_sponsor_email]';
    $this->assert_shortcode_equal( $shortcode, $email );
  }


  public function test_sponsor_first_name(): void {
    $first_name = 'Hayasaka';
    $this->_injected_sponsor->first_name = $first_name;
    $shortcode = '[mos_sponsor_first_name]';
    $this->assert_shortcode_equal( $shortcode, $first_name );
  }


  public function test_sponsor_last_name(): void {
    $last_name = 'Ai';
    $this->_injected_sponsor->last_name = $last_name;
    $shortcode = '[mos_sponsor_last_name]';
    $this->assert_shortcode_equal( $shortcode, $last_name );
  }
    

  public function test_sponsor_level_shortcode(): void {
    $level_slug = 'monthly_partner';
    $level_name = 'Monthly Partner';
    $this->user_give_access( $this->_injected_sponsor->ID, $level_slug );
    $shortcode = '[mos_sponsor_level]';
    $this->assert_shortcode_equal( $shortcode, $level_name );
  }
    

  public function test_sponsor_mis_shortcode(): void {
    // User not logged in --> show default
    $this->unset_sponsor();
    $this->assert_mis( 'gr', mis_default_value( 'gr' ) );
    $this->assert_mis( 'cm', mis_default_value( 'cm' ) );
    $this->assert_mis( 'cb', mis_default_value( 'cb' ) );
    $this->set_sponsor();
    
    // User has no caps --> show default
    $this->assert_mis( 'gr', mis_default_value( 'gr' ) );
    $this->assert_mis( 'cm', mis_default_value( 'cm' ) );
    $this->assert_mis( 'cb', mis_default_value( 'cb' ) );

    // Give access
    $this->user_give_access( $this->_injected_sponsor->ID, 'monthly_partner' );

    // Give MIS to Sponsor
    foreach( $this->mis as $slug => $value ) {
      $meta_key = 'mos_mis_' . $slug;
      update_user_meta( $this->_injected_sponsor->ID, $meta_key, $value );
    }

    // Has cap --> show value
    $this->assert_mis( 'gr', $this->mis['gr'], 'Sponsor MIS should be displayed if it is set and sponsor is qualified' );

    // Has cap but value is empty --> show default value
    $this->assert_mis( 'cm', mis_default_value( 'cm' ), 'Sponsor MIS should show default value if mis is set to empty string, even if sponsor is qualified' );

    // mis slug not in config --> show nothing
    $this->assert_mis( 'non_existent', '', 'Sponsor MIS should return blank if network slug is non existent' );

    // did not fill in mis --> show default
    $this->assert_mis( 'cb', mis_default_value( 'cb' ), 'Sponsor MIS should show default value if mis is not set at all, even if sponsor is qualified' );
  }


  public function test_sponsor_name_shortcode(): void {
    $first_name = 'Hayasaka';
    $last_name = 'Ai';
    $this->_injected_sponsor->first_name = $first_name;
    $this->_injected_sponsor->last_name = $last_name;

    $expected = "$first_name $last_name";
    $shortcode = '[mos_sponsor_name]';
    $this->assert_shortcode_equal( $shortcode, $expected );
  }


  public function test_sponsor_username_shortcode(): void {
    $expected = $this->_injected_sponsor->get_username();
    $shortcode = '[mos_sponsor_username]';
    $this->assert_shortcode_equal( $shortcode, $expected );
  }


  public function test_sponsor_wpid_shortcode(): void {
    $expected = $this->_injected_sponsor->ID;
    $shortcode = '[mos_sponsor_wpid]';
    $this->assert_shortcode_equal( $shortcode, $expected );
  }


  private function assert_shortcode_equal( string $shortcode, $expected, ...$data ): void {
    $output = do_shortcode( $shortcode );
    $data[] = [
      'expected' => $expected,
      'shortcode' => $shortcode,
      'output' => $output,
    ];
    $this->assert_equal( $expected, $output, $data );
  }


  private function assert_mis( string $mis_slug, $expected, ...$data ): void {
    $shortcode = "[mos_sponsor_mis network=$mis_slug]";
    $this->assert_shortcode_equal( $shortcode, $expected );
  }

  
}