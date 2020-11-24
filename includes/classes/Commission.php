<?php declare(strict_types=1);

namespace MOS\Affiliate;

class Commission {

  private $id;
  private $date;
  private $amount;
  private $description;
  private $transaction_id;
  private $campaign;
  private $actor_id;
  private $earner_id;
  private $payout_date;
  private $payout_method;
  private $payout_address;
  private $payout_transaction_id;
  private $refund_date;


  public static function get( int $id ): self {
    return new self();
  }


  public static function from_stripe_data( array $stripe_data ): self {
    return new self();
  }


  public static function from_cb_data( array $cb_data ): self {
    return new self();
  }


  public static function from_array( array $data ): self {
    $new_commission = new self();
    $new_commission->date = $data['date'];
    $new_commission->amount = $data['amount'];
    $new_commission->description = $data['description'];
    $new_commission->transaction_id = $data['transaction_id'];
    $new_commission->campaign = $data['campaign'];
    $new_commission->actor_id = $data['actor_id'];
    $new_commission->earner_id = $data['earner_id'];
    $new_commission->payout_date = $data['payout_date'];
    $new_commission->payout_method = $data['payout_method'];
    $new_commission->payout_address = $data['payout_address'];
    $new_commission->payout_transaction_id = $data['payout_transaction_id'];
    $new_commission->refund_date = $data['refund_date'];
    return $new_commission;
  }


  public static function get_all( array $filters ): array {
    return [];
  }


  public function get_date(): ?string {
    return $this->date;
  }


  public function get_amount(): ?float {
    return $this->amount;
  }


  public function get_description(): ?string {
    return $this->description;
  }


  public function get_transaction_id(): ?string {
    return $this->transaction_id;
  }


  public function get_campaign(): ?string {
    return $this->campaign;
  }


  public function get_actor_id(): ?int {
    return $this->actor_id;
  }


  public function get_earner_id(): ?int {
    return $this->earner_id;
  }


  public function get_payout_date(): ?string {
    return $this->payout_date;
  }


  public function get_payout_method(): ?string {
    return $this->payout_method;
  }


  public function get_payout_address(): ?string {
    return $this->payout_address;
  }


  public function get_payout_transaction_id(): ?string {
    return $this->payout_transaction_id;
  }


  public function get_refund_date(): ?string {
    return $this->refund_date;
  }


  public function exists(): bool {
    return !empty( $this->id );
  }


  public function db_insert(): bool {
    return false;
  }





}