<?php declare(strict_types=1);

namespace MOS\Affiliate;

use \Exception;
use \WP_CLI;
use \WP_Post;
use \MOS\Affiliate\User;
use \MOS\Affiliate\Product;

use function \get_post;
use function \wp_insert_post;
use function \update_user_meta;
use function \delete_user_meta;
use function \MOS\Affiliate\ranstr;
use function \add_filter;
use function \remove_filter;
use function \WP_CLI\Utils\format_items;

class Test {

  const CURRENT_USER_HOOK = 'mos_current_user';
  const CURRENT_SPONSOR_HOOK = 'mos_sponsor';
  const TEST_META_KEY = 'mos_inserted_via_test';
  const TEST_META_VALUE = 'true';
  const TEST_COMMISSION_DESCRIPTION = 'mos_affiliate_test';
  const TEST_REFERRAL_DESCRIPTION = 'mos_affiliate_test';
  const TEST_REFERRAL_SOURCE = 'mos_affiliate_test';
  const TEST_REFERRAL_REFERENCE = 'mos_affiliate_test';

  protected $_user_ids_to_delete = [];
  protected $_post_ids_to_delete = [];
  protected $_commission_ids_to_delete = [];
  protected $_injected_user;
  protected $_injected_sponsor;
  protected $_user_is_set = false;
  protected $_sponsor_is_set = false;


  public function run(): void {
    $this->_set_up();
    $this->_before();
    foreach( $this->get_test_methods() as $method ) {
      $this->run_method( $method );
    }
    $this->_after();
    $this->_clean_up();
  }


  protected function _before(): void {
    // To be overridden for each individual test
  }
  
  
  protected function _after(): void {
    // To be overridden for each individual test
  }


  // Common setup for all tests
  protected final function _set_up(): void {
    
  }
  
  
  // Common cleanup for all tests
  protected final function _clean_up(): void {
    $this->delete_test_users();
    $this->delete_test_posts();
    $this->delete_test_commissions();
    $this->unset_user();
    $this->unset_sponsor();
  }


  private function get_test_methods(): array {
    $methods = get_class_methods( get_class( $this ) );
    $test_methods = [];

    foreach( $methods as $method ) {
      if ( strpos( $method, 'test_'  ) === 0 ) {
        $test_methods[] = $method;
      }
    }

    return $test_methods;
  }


  private function run_method( string $method ): void {
    $this->notice( "$method starting..." );
    $this->{$method}();
    $this->notice_success( "$method finished" );
  }


  private function get_class_name( bool $verbose=false ): string {
    $class_name = get_class( $this );

    if ( $verbose === false ) {
      // Get class name stub
      foreach ( explode( '\\', $class_name ) as $part ) {
        $class_name_short = $part;
      }
      $class_name = $class_name_short;
    }

    return $class_name;
  }


  protected function assert_is_int( $var, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = is_int( $var );
    $data['var'] = $var;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_arrays_equal( $array1, $array2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = (
      is_array( $array1 )
      && is_array( $array2 )
      && count( $array1 ) == count( $array2 )
      && array_diff( $array1, $array2 ) === array_diff( $array2, $array1 )
    );
    $data['array1'] = $array1;
    $data['array2'] = $array2;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_arrays_not_equal( $array1, $array2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = !(
      is_array( $array1 )
      && is_array( $array2 )
      && count( $array1 ) == count( $array2 )
      && array_diff( $array1, $array2 ) === array_diff( $array2, $array1 )
    );
    $data['array1'] = $array1;
    $data['array2'] = $array2;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_is_number( $var, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = is_int( $var ) || is_float( $var );
    $data['var'] = $var;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_greater_than( $var1, $var2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $var1 > $var2;
    $data['comparison'] = "$var1 > $var2";
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_greater_than_or_equal( $var1, $var2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $var1 >= $var2;
    $data['comparison'] = "$var1 >= $var2";
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_less_than( $var1, $var2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $var1 < $var2;
    $data['comparison'] = "$var1 < $var2";
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_less_than_or_equal( $var1, $var2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $var1 <= $var2;
    $data['comparison'] = "$var1 <= $var2";
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_is_array( $var, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = is_array( $var );
    $data['var'] = $var;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_is_string( $var, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = is_string( $var );
    $data['var'] = $var;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_is_url( $var, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = filter_var( $var, FILTER_VALIDATE_URL );
    $data['var'] = $var;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_equal( $value1, $value2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $value1 == $value2;
    $data['value1'] = $value1;
    $data['value2'] = $value2;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_equal_strict( $value1, $value2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $value1 === $value2;
    $data['value1'] = $value1;
    $data['value2'] = $value2;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_not_equal( $value1, $value2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $value1 != $value2;
    $data['value1'] = $value1;
    $data['value2'] = $value2;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_not_equal_strict( $value1, $value2, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $value1 !== $value2;
    $data['value1'] = $value1;
    $data['value2'] = $value2;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_true( $expression, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $expression == true;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_true_strict( $expression, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $expression === true;
    $data['expression'] = $expression;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_false( $expression, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $expression == false;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_empty( $var, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = empty( $var );
    $data['var'] = $var;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_not_empty( $var, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = ! empty( $var );
    $data['var'] = $var;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_false_strict( $expression, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $expression === false;
    $data['expression'] = $expression;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_string_contains( string $haystack, string $needle, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = ( strpos( $haystack, $needle ) !== false );
    $data['haystack'] = $haystack;
    $data['needle'] = $needle;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_string_not_contains( string $haystack, string $needle, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = ( strpos( $haystack, $needle ) === false );
    $data['haystack'] = $haystack;
    $data['needle'] = $needle;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_has_key( array $array, $key, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = array_key_exists( $key, $array );
    $data['array'] = $array;
    $data['key'] = $key;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_contains( array $array, $item, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = in_array( $item, $array );
    $data['array'] = $array;
    $data['item'] = $item;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_instanceof( $instance, string $class, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = $instance instanceof $class;
    $data['instance'] = $instance;
    $data['class'] = $class;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_class_exists( string $class_name, ...$data ): void {
    $assertion = __FUNCTION__;
    $condition = class_exists( $class_name );
    $data['class_name'] = $class_name;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_db_table_exists( string $table_without_prefix, ...$data ): void {
    global $wpdb;
    $assertion = __FUNCTION__;
    $table = $wpdb->prefix . $table_without_prefix;
    $query = "SHOW TABLES LIKE '$table'";
    $result = $wpdb->get_var( $query );
    $condition = $result == $table;
    $data['query'] = $query;
    $data['result'] = $result;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_user_id_exists( int $id, ...$data ): void {
    $assertion = __FUNCTION__;
    $user = \get_user_by( 'id', $id );
    $condition = $user !== false;
    $data['user'] = $user;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_user_id_not_exists( int $id, ...$data ): void {
    $assertion = __FUNCTION__;
    $user = \get_user_by( 'id', $id );
    $condition = $user === false;
    $data['user'] = $user;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_user_is_affiliate( int $wpid ): void {
    $assertion = __FUNCTION__;
    $condition = $this->get_affid( $wpid ) != 0;
    $data['wpid'] = $wpid;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_user_is_not_affiliate( int $wpid ): void {
    $assertion = __FUNCTION__;
    $condition = $this->get_affid( $wpid ) == 0;
    $data['wpid'] = $wpid;
    $this->assert( $condition, $data, $assertion );
  }


  private function get_affid( int $wpid ): int {
    global $wpdb;
    $table = $wpdb->prefix . 'uap_affiliates';
    $query = "SELECT id FROM $table WHERE uid = $wpid";
    $affid = (int) $wpdb->get_var( $query );
    $affid = $affid ? $affid : 0;
    return $affid;
  }


  protected function assert_commission_exists( int $id, ...$data ): void {
    $condition = $this->commission_exists( $id );
    $assertion = __FUNCTION__;
    $data['id'] = $id;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_commission_not_exists( int $id, ...$data ): void {
    $condition = !$this->commission_exists( $id );
    $assertion = __FUNCTION__;
    $data['id'] = $id;
    $this->assert( $condition, $data, $assertion );
  }


  private function commission_exists( int $id ): bool {
    global $wpdb;
    $table = $wpdb->prefix . \MOS\Affiliate\Migration\CommissionsMigration::TABLE_NAME;
    $query = "SELECT id FROM $table WHERE id = $id";
    $result = $wpdb->get_var( $query );
    $exists = $result == $id;
    return $exists;
  }


  protected function assert_user_has_sponsor( $id, ...$data ): void {
    $assertion = __FUNCTION__;
    $sponsor_id = $this->get_sponsor_id( $id );
    $condition = $sponsor_id != 0;
    $data['user_id'] = $id;
    $data['sponsor_id'] = $sponsor_id;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_user_not_has_sponsor( $id, ...$data ): void {
    $assertion = __FUNCTION__;
    $sponsor_id = $this->get_sponsor_id( $id );
    $condition = $sponsor_id == 0;
    $data['user_id'] = $id;
    $data['sponsor_id'] = $sponsor_id;
    $this->assert( $condition, $data, $assertion );
  }


  private function get_sponsor_id( int $id ): int {
    global $wpdb;
    $table = $wpdb->prefix . 'uap_referrals';
    $query = "SELECT affiliate_id FROM $table WHERE refferal_wp_uid = $id";
    $sponsor_id = (int) $wpdb->get_var( $query );
    return $sponsor_id;
  }


  protected function assert_user_has_referral( int $user_id, int $referral_id, ...$data ): void {
    $assertion = __FUNCTION__;
    $referral_ids = $this->get_referral_ids( $user_id );
    $condition = in_array( $referral_id, $referral_ids );
    $data['referral_id'] = $referral_id;
    $data['user_id'] = $user_id;
    $data['user_referral_ids'] = $referral_ids;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_user_not_has_referrals( int $id, ...$data ): void {
    $assertion = __FUNCTION__;
    $referral_ids = $this->get_referral_ids( $id );
    $condition = empty( $referral_ids );
    $data['user_id'] = $id;
    $data['user_referral_ids'] = $referral_ids;
    $this->assert( $condition, $data, $assertion );
  }


  private function get_referral_ids( int $id ): array {
    global $wpdb;
    $affid = $this->get_affid( $id );
    $table = $wpdb->prefix . 'uap_referrals';
    $query = "SELECT refferal_wp_uid FROM $table WHERE affiliate_id = $affid";
    $results = $wpdb->get_results( $query, \ARRAY_A );

    $referrals = [];
    foreach ( $results as $row ) {
      if ( isset( $row['refferal_wp_uid'] ) ) {
        $referrals[] = (int) $row['refferal_wp_uid'];
      }
    }
    return $referrals;
  }


  protected function assert_user_has_test_meta( int $id, ...$data ): void {
    $assertion = __FUNCTION__;
    $meta_value = \get_user_meta( $id, self::TEST_META_KEY, true );
    $condition = $meta_value == self::TEST_META_VALUE;
    $data['user_id'] = $id;
    $data['expected_meta_value'] = self::TEST_META_VALUE;
    $data['actual_meta_value'] = $meta_value;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert_user_campaign_is( int $id, string $campaign, ...$data ): void {
    $assertion = __FUNCTION__;
    $campaign_actual = $this->user_get_campaign( $id );
    $condition = $campaign == $campaign_actual;
    $data['user_id'] = $id;
    $data['expected_campaign'] = $campaign;
    $data['actual_campaign'] = $campaign_actual;
    $this->assert( $condition, $data, $assertion );
  }


  private function user_get_campaign( int $id ): string {
    global $wpdb;
    $table = $wpdb->prefix . 'uap_referrals';
    $query = "SELECT campaign FROM $table WHERE refferal_wp_uid = $id";
    $campaign = (string) $wpdb->get_var( $query );
    return $campaign;
  }


  protected function assert_user_clicks_equal( int $id, int $expected_clicks, $campaign=null ): void {
    $assertion = __FUNCTION__;
    $actual_clicks = $this->user_get_clicks( $id, $campaign );
    $condition = $expected_clicks == $actual_clicks;
    $data['user_id'] = $id;
    $data['expected_clicks'] = $expected_clicks;
    $data['actual_clicks'] = $actual_clicks;
    $this->assert( $condition, $data, $assertion );
  }


  private function user_get_clicks( int $id, $campaign=null ): int {
    global $wpdb;
    $affid = $this->get_affid( $id );
    $table = $wpdb->prefix . 'uap_visits';
    $query = "SELECT COUNT(affiliate_id) FROM $table WHERE affiliate_id = $affid";
    $query .= isset( $campaign ) ? " AND campaign_name = '$campaign'" : "";
    $clicks = (int) $wpdb->get_var( $query );
    return $clicks;
  }


  protected function assert_redirect( string $entry_path, string $redirected_path, ...$data ): void {
    $assertion = __FUNCTION__;
    $this->db_notice( 'HTTP GET: ' . $entry_path );
    $res = wp_remote_get( home_url( $entry_path ) )['http_response']->get_response_object() ?? null;
    $this->assert_not_empty( $res, ['msg' => 'error during wp_remote_get() in Test class', 'url' => $entry_path] );
    $actual_url = \trim( $res->url, '/' );
    $expected_url = \trim( \home_url( $redirected_path ), '/' );
    $condition = $actual_url == $expected_url;
    $data['entry_path'] = $entry_path;
    $data['expected_path'] = $redirected_path;
    $data['expected_url'] = $expected_url;
    $data['actual_url'] = $actual_url;
    $this->assert( $condition, $data, $assertion );
  }


  protected function assert( $condition, $data=[], string $assertion ): void {
    if ( $condition ) {
      return;
    }

    $e = new Exception();
    $trace_string = $e->getTraceAsString();
    $trace_formatted = $this->format_trace( $trace_string );
    
    WP_CLI::line( $trace_formatted );

    $this->print_yaml( $data );
    $this->_after();
    $this->_clean_up();
    $this->print_error( $assertion );
  }

  
  protected function print_error( string $message ): void {
    $colorized_message = WP_CLI::colorize( '%1%W' . $message . '%n%N');
    WP_CLI::error( $colorized_message );
  }
  
  
  protected function db_notice( string $message ): void {
    $class_name = $this->get_class_name();
    $colorized_message = WP_CLI::colorize( "%c♦%n $class_name: $message" );
    WP_CLI::debug( $colorized_message, 'mosa test' );
  }


  protected function notice( string $message ): void {
    $class_name = $this->get_class_name();
    $colorized_message = WP_CLI::colorize( "%k●%n $class_name: $message" );
    WP_CLI::debug( $colorized_message, 'mosa test' );
  }


  protected function notice_success( string $message ): void {
    $class_name = $this->get_class_name();
    $colorized_message = WP_CLI::colorize( "%g✔ $class_name: $message%n" );
    WP_CLI::debug( $colorized_message, 'mosa test' );
  }


  protected function print_yaml( $original ): void {
    if ( empty( $original ) ) {
      WP_CLI::warning( "No debug data passed" );
      return;
    }
    $debug_dump = [
      [
        'debug_dump' => $original,
      ],
    ];

    format_items( 'yaml', $debug_dump, 'debug_dump' );
  }


  protected function format_trace( string $original ): string {
    $exploded = explode("\n", print_r( $original, true));

    // Cut and colorize
    foreach ( $exploded as &$line ) {
      $line = preg_replace( '/(.*)(\/[a-zA-Z0-9]+\.php)(\(\d+\))(.*)/', '%5%W$2%6%W$3%n%N$4', $line );
      $line = WP_CLI::colorize( $line );
    }

    $formatted = 'Stack trace:' . PHP_EOL;

    foreach ( $exploded as $line ) {
      if ( strpos( $line, "Test.php" ) !== false ) {
        $formatted .= $line . PHP_EOL;
      } else {
        break;
      }
    }

    return $formatted;
  }


  protected function create_test_user( array $user_data=[] ): User {
    if ( empty( $user_data['user_login'] ) ) {
      $user_data['user_login'] = ranstr();
    }
    
    if ( empty( $user_data['user_pass'] ) ) {
      $user_data['user_pass'] = ranstr();
    }

    if ( empty( $user_data['user_email'] ) ) {
      $user_data['user_email'] = ranstr() . "@" . ranstr(5) . ".com";
    }

    $user = new User();

    foreach ( $user_data as $key => $value ) {
      $user->$key = $value;
    }

    $user->db_insert();
    $user = User::from_username( $user->get_username() );
    update_user_meta( $user->ID, self::TEST_META_KEY, self::TEST_META_VALUE );
    $this->db_notice( "user created: $user->ID" );
    $this->_user_ids_to_delete[] = $user->ID;
    
    return $user;
  }


  protected function delete_test_users(): void {
    if ( empty( $this->_user_ids_to_delete ) ) {
      return;
    }

    foreach ( $this->_user_ids_to_delete as $id ) {
      $user = User::from_id( $id );
      $user->db_delete();
      $this->db_notice( "user deleted: $id" );
    }

    $this->_user_ids_to_delete = [];
  }


  /**
   * Used as a callback for add_filter only
   * Do not call directly!
   */
  public final function _get_injected_user( $user ): ?User {
    if ( empty( $this->_injected_user ) ) {
      return $user;
    } else {
      return $this->_injected_user;
    }
  }


  protected final function set_user(): void {
    if ( $this->_user_is_set ) {
      return;
    }
    add_filter( self::CURRENT_USER_HOOK, [$this, '_get_injected_user'] );
    $this->db_notice( "current user set" );
    $this->_user_is_set = true;
  }


  protected final function unset_user(): void {
    if ( ! $this->_user_is_set ) {
      return;
    }
    remove_filter( self::CURRENT_USER_HOOK, [$this, '_get_injected_user'] );
    $this->db_notice("current user unset");
    $this->_user_is_set = false;
  }


  /**
   * Used as a callback for add_filter only
   * Do not call directly!
   */
  public final function _get_injected_sponsor( $sponsor ): ?User {
    if ( empty( $this->_injected_sponsor ) ) {
      return $sponsor;
    } else {
      return $this->_injected_sponsor;
    }
  }


  protected final function set_sponsor(): void {
    if ( $this->_sponsor_is_set ) {
      return;
    }
    add_filter( self::CURRENT_SPONSOR_HOOK, [$this, '_get_injected_sponsor'] );
    $this->db_notice( "current sponsor set" );
    $this->_sponsor_is_set = true;
  }


  protected final function unset_sponsor(): void {
    if ( ! $this->_sponsor_is_set ) {
      return;
    }
    remove_filter( self::CURRENT_SPONSOR_HOOK, [$this, '_get_injected_sponsor'] );
    $this->db_notice("current sponsor unset");
    $this->_sponsor_is_set = false;
  }


  protected final function create_test_post( array $data=[] ): WP_Post {
    $default_data = [
      'post_author' => 1,
      'post_title' => ranstr(),
      'post_name' => ranstr(),
      'post_status' => 'publish',
      'post_content' => '#content: test',
      'meta_input' => [
        self::TEST_META_KEY => self::TEST_META_VALUE,
      ],
    ];
    $post_data = array_replace_recursive( $default_data, $data );
    $post_id = wp_insert_post( $post_data, false );
    $post = get_post( $post_id, 'OBJECT' );
    $this->_post_ids_to_delete[] = $post_id;
    $this->db_notice( "post created: $post_id" );
    return $post;
  }


  protected final function delete_test_posts(): void {
    if ( empty( $this->_post_ids_to_delete ) ) {
      return;
    }
    
    foreach ( $this->_post_ids_to_delete as $post_id ) {
      wp_delete_post( $post_id, true );
      $this->db_notice( "post deleted: $post_id" );
    }

    $this->_post_ids_to_delete = [];
  }


  protected final function create_test_commission( array $passed_data=[] ): Commission {
    $default_data = [
      'date' => '1991-01-01',
      'amount' => 1.00,
      'description' => self::TEST_COMMISSION_DESCRIPTION,
      'earner_id' => 1,
    ];
    $data = array_replace_recursive( $default_data, $passed_data );
    $commission = Commission::create_from_array( $data );
    $this->assert_true( $commission->is_valid(), "Commission should be valid before we try to insert it..." );
    $commission->db_insert();
    return $commission;
  }


  protected final function delete_test_commissions(): void {
    global $wpdb;
    $table = $wpdb->prefix . \MOS\Affiliate\Migration\CommissionsMigration::TABLE_NAME;
    $wpdb->delete( $table, ['description' => self::TEST_COMMISSION_DESCRIPTION], ['description' => '%s'] );
  }


  protected final function create_test_referral( int $user_id, int $sponsor_id, string $campaign=null ): void {
    $this->assert_user_has_test_meta( $user_id );
    $this->assert_user_has_test_meta( $sponsor_id );
    
    global $wpdb;
    $sponsor_affid = $this->get_affid( $sponsor_id );
    $table = $wpdb->prefix . "uap_referrals";
    $columns = [
      'refferal_wp_uid' => $user_id,
      'affiliate_id' => $sponsor_affid,
      'campaign' => $campaign,
      'description' => self::TEST_REFERRAL_DESCRIPTION,
      'source' => self::TEST_REFERRAL_SOURCE,
      'reference' => self::TEST_REFERRAL_REFERENCE,
    ];
    $formats = [
      'refferal_wp_uid' => '%d',
      'affiliate_id' => '%d',
      'campaign' => '%s',
      'description' => '%s',
      'source' => '%s',
      'reference' => '%s',
    ];
    $wpdb->insert( $table, $columns, $formats );
  }


  protected final function create_test_click( int $user_id, string $campaign='' ): void {
    $this->assert_user_has_test_meta( $user_id );

    global $wpdb;
    $affid = $this->get_affid( $user_id );
    $table = $wpdb->prefix . 'uap_visits';
    $columns = [
      'affiliate_id' => $affid,
      'campaign_name' => $campaign,
      'ip' => ranstr(16),
    ];
    $formats = [
      'affiliate_id' => '%d',
      'campaign_name' => '%s',
      'ip' => '%s',
    ];
    $wpdb->insert( $table, $columns, $formats );
  }


  protected final function user_give_access( int $user_id, string $access_level ): void {
    $product = Product::from_slug( $access_level );
    if ( $product->exists ) {
      $tomorrow = \date( 'Y-m-d', \time() + \DAY_IN_SECONDS );
      $meta_key = $product->access_meta_key;
      update_user_meta( $user_id, $meta_key, $tomorrow );
    }
  }


  protected final function user_remove_access( int $user_id, string $access_level ): void {
    $product = Product::from_slug( $access_level );
    if ( $product->exists ) {
      $meta_key = $product->access_meta_key;
      delete_user_meta( $user_id, $meta_key );
    }
  }


}