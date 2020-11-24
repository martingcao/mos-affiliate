<?php declare(strict_types=1);

namespace MOS\Affiliate\Migration;

use MOS\Affiliate\Migration;

class CommissionsMigration extends Migration {

  protected $table_name = 'mos_commissions';
  protected $columns = [
    'id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
    'date date NOT NULL',
    'amount float(10, 2) UNSIGNED NOT NULL',
    'description varchar(90) DEFAULT "" NOT NULL',
    'transaction_id varchar(255) DEFAULT "" NOT NULL',
    'campaign varchar(255) DEFAULT "" NOT NULL',
    'actor_id bigint(20) UNSIGNED NULL',
    'earner_id bigint(20) UNSIGNED NOT NULL',
    'payout_date date NULL',
    'payout_method varchar(100) NULL',
    'payout_address varchar(100) NULL',
    'payout_transaction_id varchar(100) NULL',
    'refund_date date NULL',
    'PRIMARY KEY  (id)',
    'KEY for_campaign_report (earner_id, campaign, date)',
  ];

}