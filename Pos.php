<?php

class Pos {
  public static function bank($bank){
    switch ($bank) {
      case 'akbank':
        include __DIR__.'/pos/Akbank.php';
        return new \pos\Akbank();
        break;
    }
    throw new Exception("$bank Not Supported");
  }
}
