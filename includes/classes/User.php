<?php declare(strict_types=1);

namespace MOS\Affiliate;

class User extends \WP_User {

  const LEVELS = [
    [
      'product_slug' => 'coaching',
      'level_name' => 'Coaching',
    ],
    [
      'product_slug' => 'lifetime_partner',
      'level_name' => 'Lifetime Partner',
    ],
    [
      'product_slug' => 'yearly_partner',
      'level_name' => 'Yearly Partner',
    ],
    [
      'product_slug' => 'monthly_partner',
      'level_name' => 'Monthly Partner',
    ],
  ];
  
  public static function current(): self {
    $wpid = \get_current_user_id();
    $current_user = self::from_id( $wpid );
    $current_user = apply_filters( 'mos_current_user', $current_user );
    return $current_user;
  }


  public static function from_id( int $wpid ): self {
    $new_user = new self( $wpid );
    return $new_user;
  }


  public static function from_affid( int $affid ): self {
    global $wpdb;

    $table = $wpdb->prefix . 'uap_affiliates';
    $query = "SELECT uid FROM $table WHERE id = $affid";
    $wpid = $wpdb->get_var( $query );
    $wpid = $wpid ? (int) $wpid : 0;

    return self::from_id( $wpid );
  }


  public static function from_username( string $username ): self {
    global $wpdb;

    $table = $wpdb->prefix . 'users';
    $query = "SELECT id FROM $table WHERE user_login = '$username'";
    $wpid = $wpdb->get_var( $query );
    $wpid = $wpid ? (int) $wpid : 0;

    return self::from_id( $wpid );
  }


  public function exists(): bool {
    $exists = false;
    
    if ( ! empty( $this->get_email() ) && self::email_exists( $this->get_email() ) ) {
      $exists = true;
    }
    
    if ( ! empty( $this->get_username() ) && self::username_exists( $this->get_username() ) ) {
      $exists = true;
    }

    if ( ! empty( $this->get_wpid() ) && self::id_exists( $this->get_wpid() ) ) {
      $exists = true;
    }

    return $exists;
  }


  public static function id_exists( int $id ): bool {
    $user = \get_user_by( 'id', $id );
    return ! empty( $user );
  }


  public static function username_exists( string $username ): bool {
    $user = \get_user_by( 'login', $username );
    return ! empty( $user );
  }


  public static function emaiL_exists( string $email ): bool {
    $user = \get_user_by( 'email', $email );
    return ! empty( $user );
  }


  public static function affid_exists( int $affid ): bool {
    global $wpdb;
    $table = $wpdb->prefix . 'uap_affiliates';
    $query = "SELECT id FROM $table WHERE id = $affid LIMIT 1";
    $result = (int) $wpdb->get_var( $query );
    $affid_exists = $result == $affid;
    return $affid_exists;
  }


  public function sponsor(): self {
    global $wpdb;

    if ( $this->ID ) {
      $table = $wpdb->prefix . 'uap_referrals';
      $query = "SELECT affiliate_id FROM $table WHERE refferal_wp_uid = $this->ID";
      $sponsor_affid = $wpdb->get_var( $query );
      $sponsor_affid = $sponsor_affid ? (int) $sponsor_affid : 0;
      $sponsor = self::from_affid( $sponsor_affid );
    } else {
      // prevent logged out users from having a random sponsor
      // because uap_referrals table
      // uses 0 as the "no user" wpid
      $sponsor = new self();
    }
    
    $sponsor = \apply_filters( 'mos_sponsor', $sponsor, $this->ID );
    return $sponsor;
  }


  public function get_wpid(): int {
    $wpid = $this->ID ? $this->ID : 0;
    return $wpid;
  }


  public function get_affid(): int {
    global $wpdb;

    $table = $wpdb->prefix . 'uap_affiliates';
    $query = "SELECT id FROM $table WHERE uid = {$this->ID}";
    $affid = $wpdb->get_var( $query );

    $affid = $affid ? (int) $affid : 0;

    return $affid;
  }


  public function get_username(): string {
    $username = $this->user_login ? $this->user_login : '';
    return $username;
  }


  public function get_name(): string {
    if ($this->first_name && $this->last_name) {
      $name = implode( ' ', [$this->first_name, $this->last_name] );
    } elseif ( $this->first_name ) {
      $name = $this->first_name;
    } elseif ( $this->display_name ) {
      $name = $this->display_name;
    } else {
      $name = '';
    }
    
    $name = ucwords( $name );
    
    return $name;
  }


  public function get_last_name(): string {
    $last_name = $this->last_name ? $this->last_name : '';
    $last_name = ucwords( $last_name );
    return $last_name;
  }


  public function get_first_name(): string {
    $first_name = $this->first_name ? $this->first_name : '';
    $first_name = ucwords( $first_name );
    return $first_name;
  }


  public function get_email(): string {
    $email = $this->user_email ? $this->user_email : '';
    return $email;
  }


  public function get_mis( $slug ): string {
    $mis = mis_object( $slug );
    $value = $mis->meta_key ? (string) $this->get( $mis->meta_key ) : '';
    return $value;
  }


  public function get_mis_link( string $slug ): string {
    $value = mis_value( $slug, $this->ID );
    $link = mis_generate_link( $slug, $value );
    return $link;
  }


  public function get_level(): string {
    if ( !$this->exists() ) {
      return 'None';
    }

    $level_name = 'Free Member';

    foreach ( self::LEVELS as $level ) {
      if ( $this->has_access( $level['product_slug'] ) ) {
        $level_name = $level['level_name'];
        break;
      }
    }

    return $level_name;
  }


  public function get_level_slug(): string {
    if ( !$this->exists() ) {
      return '';
    }

    $level_slug = 'free';

    foreach ( self::LEVELS as $level ) {
      if ( $this->has_access( $level['product_slug'] ) ) {
        $level_slug = $level['product_slug'];
        break;
      }
    }

    return $level_slug;
  }


  public function get_next_level(): string {
    if ( !$this->exists() ) {
      return 'Free Member';
    }

    $level_name = '';

    foreach ( self::LEVELS as $level ) {
      if ( $this->has_access( $level['product_slug'] ) ) {
        break;
      } else {
        $level_name = $level['level_name'];
      }
    }

    return $level_name;
  }


  public function get_next_level_slug(): string {
    if ( !$this->exists() ) {
      return 'free';
    }

    $level_slug = '';

    foreach ( self::LEVELS as $level ) {
      if ( $this->has_access( $level['product_slug'] ) ) {
        break;
      } else {
        $level_slug = $level['product_slug'];
      }
    }

    return $level_slug;
  }


  public function get_campaign(): string {
    global $wpdb;
    $table = $wpdb->prefix . 'uap_referrals';
    $query = "SELECT campaign FROM $table WHERE refferal_wp_uid = $this->ID LIMIT 1";
    $campaign = $wpdb->get_var( $query );
    $campaign = empty( $campaign ) ? '' : (string) $campaign;
    return $campaign;
  }


  public function qualifies_for_mis( string $slug ): bool {
    $mis = mis_object( $slug );
    $qualifies = $mis->access_level ? $this->has_access( $mis->access_level ) : false;
    return $qualifies;
  }


  public function has_access( string $product_slug ): bool {
    $level = new Level( $product_slug );
    if ( $level->exists ) {
      foreach ( $level->granted_by as $granting_level ) {
        if ( $this->has_access_single( $granting_level ) ) {
          return true;
        }
      }
      return false;
    } else {
      return $this->has_access_single( $product_slug );
    }
  }


  private function has_access_single( string $product_slug ): bool {
    $access_expiry = $this->get_access_date( $product_slug );
    
    if ( !$access_expiry ) {
      return false;
    }

    $today = date( 'Y-m-d' );
    $has_access = $today < $access_expiry;
    return $has_access;
  }


  public function get_access_list(): array {
    $products = Product::get_all();
    $access_list = [];
    foreach ( $products as $product ) {
      if ( $this->has_access( $product->slug ) ) {
        $access_list[] = $product->slug;
      }
    }
    if ( $this->exists() ) {
      $access_list[] = '_free';
    }
    return $access_list;
  }


  public function get_access_date( string $product_slug ): string {
    $product = Product::from_slug( $product_slug );
    $meta_key = $product->access_meta_key ?? '';
    $access_expiry = (string) $this->get( $meta_key );
    return $access_expiry;
  }


  public function is_partner(): bool {
    $partner_access_levels = [
      'monthly_partner',
      'yearly_partner',
      'lifetime_partner',
    ];

    foreach ( $partner_access_levels as $access_level ) {
      if ( $this->has_access( $access_level ) ) {
        return true;
      }
    }

    return false;
  }


  public function get_referral_ids(): array {
    global $wpdb;
    $table = $wpdb->prefix . "uap_referrals";
    $affid = $this->get_affid();
    $query = "SELECT refferal_wp_uid FROM $table WHERE affiliate_id = $affid";
    $results = $wpdb->get_results( $query, \ARRAY_N );
    $results = empty( $results ) ? [] : $results;
    
    $referral_ids = [];
    foreach ( $results as $row ) {
      if ( !empty( $row[0] ) ) {
        $referral_ids[] = (int) $row[0];
      }
    }

    return $referral_ids;
  }


  public function get_referrals(): array {
    $referral_ids = $this->get_referral_ids();
    $referrals = [];
    foreach ( $referral_ids as $id ) {
      $referrals[] = User::from_id( $id );
    }
    return $referrals;
  }


  public function get_course_progress( int $course_id ): array {
    // Default values
    $course_progress = [
      'completed' => 0,
      'total' => 0,
      'percentage' => 0.0,
      'percentage_str' => '0%',
      'formatted' => '-',
    ];
  
    // Extract course progress from usermeta
    $course_progress_meta = \get_user_meta( (int) $this->ID, '_sfwd-course_progress', true );

    if ( !empty( $course_progress_meta[$course_id]['completed'] ) ) {
      $course_progress['completed'] = (int) $course_progress_meta[$course_id]['completed'];
    }

    if ( !empty( $course_progress_meta[$course_id]['total'] ) ) {
      $course_progress['total'] = (int) $course_progress_meta[$course_id]['total'];
    }

    if ( $course_progress['total'] != 0 ) {
      $course_progress['formatted'] = "$course_progress[completed]/$course_progress[total]";
      $course_progress['percentage'] = $course_progress['completed']/$course_progress['total'];
      $course_progress['percentage_str'] = (string) ceil( $course_progress['percentage'] * 100 ) . '%';
    }
  
    return $course_progress;
  }


  public function get_free_course_progress(): array {
    $free_course_id = (int) get_field( 'free_course_id', 'options' );
    $free_course_progress = $this->get_course_progress( $free_course_id );
    return $free_course_progress;
  }


  public function db_insert(): void {
    if ( $this->exists() ) {
      return;
    }

    if ( empty( $this->user_login ) ) {
      return;
    }

    if ( empty( $this->user_pass ) ) {
      return;
    }

    $new_user_id = \wp_insert_user( $this );
    if ( is_int( $new_user_id ) && !empty( $new_user_id ) ) {
      $this->ID = $new_user_id;
    }
  }


  public function db_delete(): void {
    if ( self::id_exists( $this->ID ) ) {
      \wp_delete_user( $this->ID );
    }

    $this->db_remove_sponsor();
    $this->db_remove_downlines();
    $this->db_unregister_affiliate();
    $this->db_remove_clicks();
  }


  public function db_add_sponsor( User $sponsor ): void {
    $already_has_sponsor = $this->sponsor()->exists();
    if ( $already_has_sponsor ) {
      return;
    }

    $sponsor_affid = $sponsor->get_affid();
    if ( empty( $sponsor_affid ) ) {
      return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'uap_referrals';
    $columns = [
      'affiliate_id' => $sponsor_affid,
      'refferal_wp_uid' => $this->ID,
    ];
    $formats = [
      'affiliate_id' => '%d',
      'refferal_wp_uid' => '%d',
    ];
    $wpdb->insert( $table, $columns, $formats );
  }


  private function db_remove_sponsor(): void {
    if ( ! $this->sponsor()->exists() ) {
      return;
    }
    
    // Remove self from sponsor's referral stats
    global $wpdb;
    $table = $wpdb->prefix . 'uap_referrals';
    $where = ['refferal_wp_uid' => $this->ID];
    $formats = ['refferal_wp_uid' => '%d'];
    $wpdb->delete( $table, $where, $formats );
  }


  private function db_unregister_affiliate(): void {
    if ( empty( $this->get_affid() ) ) {
      return;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'uap_affiliates';
    $where = ['uid' => $this->ID];
    $formats = ['uid' => '%d'];
    $wpdb->delete( $table, $where, $formats );
  }


  private function db_remove_downlines(): void {
    $affid = $this->get_affid();
    if ( empty( $affid ) ) {
      return;
    }

    // Remove referral stats
    global $wpdb;
    $table = $wpdb->prefix . 'uap_referrals';
    $where = ['affiliate_id' => $affid];
    $formats = ['affiliate_id' => '%d'];
    $wpdb->delete( $table, $where, $formats );
  }


  private function db_remove_clicks(): void {
    $affid = $this->get_affid();
    if ( empty( $affid ) ) {
      return;
    }

    global $wpdb;

    $table = $wpdb->prefix . 'uap_visits';
    $where = ['affiliate_id' => $affid];
    $formats = ['affiliate_id' => '%d'];
    $wpdb->delete( $table, $where, $formats );
  }


}