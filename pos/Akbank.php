<?php

namespace Pos;

class Akbank {
  private static $config = [];
  private static $test = false;
  private static $url = [
    'akbank' => [
      'post3d'  => 'https://www.sanalakpos.com/fim/est3Dgate',
      'check' => 'https://www.sanalakpos.com/fim/api'
    ],
    'test' => [
        'post3d'  => 'https://entegrasyon.asseco-see.com.tr/fim/est3Dgate',
        'check' => 'https://entegrasyon.asseco-see.com.tr/fim/api',
    ]
  ];

  public static function config($arr){
    self::$config = $arr;
    return new static();
  }

  public static function test($mode = false){
    self::$test = $mode;
    return new static();
  }

  public static function form($order){
    $order = self::pay3d($order);
    echo '<form id="form_payment" method="post" action="'.$order->url.'" style="position: fixed;left: 0;right: 0;">
          <input type="hidden" name="pan" size="20" value="'.$order->pan.'" placeholder="cardnumber"/>
          <input type="hidden" name="cv2" size="4" value="'.$order->cv2.'" placeholder="Güvenlik Kodu" />
          <input type="hidden" name="Ecom_Payment_Card_ExpDate_Year" value="'.$order->Ecom_Payment_Card_ExpDate_Year.'" placeholder="Son Kullanma Yili"/>
          <input type="hidden" name="Ecom_Payment_Card_ExpDate_Month" value="'.$order->Ecom_Payment_Card_ExpDate_Month.'" placeholder="Son Kullanma Ayi"/>
          <input type="hidden" name="cardType" value="'.$order->cardType.'" />
          <input type="hidden" name="clientid" value="'.$order->clientid.'"/>
          <input type="hidden" name="amount" value="'.$order->amount.'"/>
          <input type="hidden" name="oid" value="'.$order->oid.'"/>
          <input type="hidden" name="okUrl" value="'.$order->okUrl.'"/>
          <input type="hidden" name="failUrl" value="'.$order->failUrl.'"/>
          <input type="hidden" name="rnd" value="'.$order->rnd.'" />
          <input type="hidden" name="hash" value="'.$order->hash.'" />
          <input type="hidden" name="islemtipi" value="'.$order->islemtipi.'" />
          <input type="hidden" name="taksit" value="'.$order->taksit.'"/>
          <input type="hidden" name="storetype" value="'.$order->storetype.'" />
          <input type="hidden" name="lang" value="'.$order->lang.'" />
          <input type="hidden" name="currency" value="'.$order->currency.'" />
          <button id="btn_payment_form" class="btn btn-success" type="submit" style="display:none;">Ödemeyi Onayla</button>
        </form>';
  }

  public static function pay3d($order){
    $config = self::$config;
    $url = self::$url['akbank'];
    if(self::$test){
      $config = [
        'clientid' => "100200000",
        'name' => "test",
        'password' => "123456",
        'key' => "123456"
      ];
      $url = self::$url['test'];
    }
    $form = [
      'url' => $url['post3d'],
      'pan' => $order['card']['number'],
      'cv2' => $order['card']['cvv'],
      'Ecom_Payment_Card_ExpDate_Year' => $order['card']['year'],
      'Ecom_Payment_Card_ExpDate_Month' => $order['card']['month'],
      'cardType' => (substr($order['card']['number'],0,1)=='4' ? 1 : 2),
      'clientid' => $config['clientid'],
      'amount' => $order['amount'],
      'oid' => $order['id'],
      'okUrl' => $order['url']['ok'],
      'failUrl' => $order['url']['fail'],
      'rnd' => time(),
      'islemtipi' => 'Auth',
      'taksit' => ($order['installment'] ?? 1),
      'storetype' => '3d',
      'lang' => ($order['language'] ?? 'tr'),
      'currency' => ($order['currency'] ?? '949')
    ];
    $form['hash'] = base64_encode(pack('H*',sha1($config['clientid'].$form['oid'].$form['amount'].$form['okUrl'].$form['failUrl']."Auth".$form['taksit'].$form['rnd'].$config['key'])));
    return (object)$form;
  }

  public static function verify(){

  }
}
