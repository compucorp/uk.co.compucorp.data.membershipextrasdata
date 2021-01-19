<?php

class CRM_Membershipextrasdata_Utils_String {

  /**
   * Convert string to an ascii friendly one
   * this is a simple implementation
   *
   * @param $string
   *
   * @return string|string[]|null
   */
  public static function slugify($string) {
    $slug = preg_replace("/[^a-z0-9]+/", "_", strtolower(trim($string)));
    return $slug;
  }

}
