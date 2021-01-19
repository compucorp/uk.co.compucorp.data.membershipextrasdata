<?php

class CRM_Membershipextrasdata_Utils {

  public static function slugify($string) {
    $slug = preg_replace("/[^a-z0-9]+/", "_", strtolower(trim($string)));
    return $slug;
  }

}
