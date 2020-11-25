<?php declare(strict_types=1);

namespace MOS\Affiliate\Test;

use \MOS\Affiliate\Test;
use \MOS\Affiliate\Database;

class DatabaseClassTest extends Test {

  
  public function test_get_row() {
    $db = new Database();
    $result = $db->get_row( 'users', ['ID=1'] );
    $this->assert_equal( $result['ID'], 1, $result );
  }


}