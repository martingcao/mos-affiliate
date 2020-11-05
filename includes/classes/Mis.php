<?php

namespace MOS\Affiliate;

class Mis {

  public $meta_key = '';
  public $name = '';
  public $default = '';
  public $link_template = '';
  public $ability = '';


  public static function get( string $slug ): self {
    $new_mis = new self;
    
    if ( ! in_array( $slug, array_keys(MIS_NETWORKS) )) {
      return $new_mis;
    }

    $new_mis->meta_key = MIS_META_KEY_PREFIX . MIS_NETWORKS[$slug]['meta_key'];
    $new_mis->name = MIS_NETWORKS[$slug]['name'];
    $new_mis->default = MIS_NETWORKS[$slug]['default'];
    $new_mis->link_template = MIS_NETWORKS[$slug]['link_template'];
    $new_mis->ability = MIS_NETWORKS[$slug]['ability'];

    return $new_mis;
  }


  public function exists(): bool {
    $exists = ! empty( $this->meta_key );
    return $exists;
  }


  public function generate_link( string $mis_value ): string {
    $link = str_replace( MIS_LINK_PLACEHOLDER, $mis_value, $this->link_template );
    return $link;
  }


}