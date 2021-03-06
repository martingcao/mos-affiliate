<?php declare(strict_types=1);

namespace MOS\Affiliate\Test;

use \MOS\Affiliate\Test;
use \MOS\Affiliate\Product;

class ProductClassTest extends Test {

  public function test_construct_from_slug(): void {
    $product = Product::from_slug( 'monthly_partner' );
    $this->assert_equal( $product->cb_id, 1000 );
    $this->assert_equal( $product->name, "Monthly Partner" );
    $this->assert_equal( $product->slug, "monthly_partner" );
    $this->assert_equal( $product->access_meta_key, "mos_access_monthly_partner" );
    $this->assert_equal( $product->no_access_url_path, "/no-access-monthly-partner" );
    $this->assert_equal( $product->price, 7 );
    $this->assert_equal( $product->trial_period, 7 );
    $this->assert_equal( $product->rebill_price, 47 );
    $this->assert_equal( $product->rebill_period, 31 );
    
    $product = Product::from_slug( 'yearly_partner' );
    $this->assert_equal( $product->cb_id, 54 );
    $this->assert_equal( $product->name, "Yearly Partner" );
    $this->assert_equal( $product->slug, "yearly_partner" );
    $this->assert_equal( $product->access_meta_key, "mos_access_yearly_partner" );
    $this->assert_equal( $product->no_access_url_path, "/no-access-yearly-partner" );
    $this->assert_equal( $product->price, 497 );
    
    $product = Product::from_slug( 'lifetime_partner' );
    $this->assert_equal( $product->name, "Lifetime Partner" );
    $this->assert_equal( $product->slug, "lifetime_partner" );
    $this->assert_equal( $product->access_meta_key, "mos_access_lifetime_partner" );
    $this->assert_equal( $product->no_access_url_path, "/no-access-lifetime-partner" );
    $this->assert_equal( $product->price, 1997 );
    
    $product = Product::from_slug( 'coaching' );
    $this->assert_equal( $product->name, "Personal Coaching with Chuck" );
    $this->assert_equal( $product->slug, "coaching" );
    $this->assert_equal( $product->access_meta_key, "mos_access_coaching" );
    $this->assert_equal( $product->no_access_url_path, "/no-access-coaching" );
    $this->assert_equal( $product->price, 8997 );
  }

  public function test_construct_from_cb_id(): void {
    $product = Product::from_cb_id( 1000 );
    $this->assert_equal( $product->cb_id, 1000 );
    $this->assert_equal( $product->name, "Monthly Partner" );
    $this->assert_equal( $product->slug, "monthly_partner" );
    $this->assert_equal( $product->access_meta_key, "mos_access_monthly_partner" );
    $this->assert_equal( $product->no_access_url_path, "/no-access-monthly-partner" );
    $this->assert_equal( $product->price, 7 );
    $this->assert_equal( $product->trial_period, 7 );
    $this->assert_equal( $product->rebill_price, 47 );
    $this->assert_equal( $product->rebill_period, 31 );
    
    $product = Product::from_cb_id( 54 );
    $this->assert_equal( $product->cb_id, 54 );
    $this->assert_equal( $product->name, "Yearly Partner" );
    $this->assert_equal( $product->slug, "yearly_partner" );
    $this->assert_equal( $product->access_meta_key, "mos_access_yearly_partner" );
    $this->assert_equal( $product->no_access_url_path, "/no-access-yearly-partner" );
    $this->assert_equal( $product->price, 497 );
  }

}