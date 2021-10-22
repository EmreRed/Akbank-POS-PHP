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

  public static function test($mode = true){
    self::$test = $mode;
    return new static();
  }

  public static function form($order){
    $order = self::pay3d($order);
    return '<form id="pos_form" method="post" action="'.$order->url.'" style="position: fixed;left: 0;right: 0;">
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
      'taksit' => ($order['installment'] ?? ''),
      'storetype' => '3d',
      'lang' => ($order['language'] ?? 'tr'),
      'currency' => ($order['currency'] ?? '949')
    ];
    $form['hash'] = base64_encode(pack('H*',sha1($config['clientid'].$form['oid'].$form['amount'].$form['okUrl'].$form['failUrl']."Auth".$form['taksit'].$form['rnd'].$config['key'])));
    return (object)$form;
  }

  public static function verify($order){
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
    $order = self::pay3d($order);
    $name = $config['name'];
    $password = $config['password'];
    $clientid = $config['clientid'];
    $storekey = $config['key'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $hashparams = $_POST["HASHPARAMS"];
    $hashparamsval = $_POST["HASHPARAMSVAL"];
    $hashparam = $_POST["HASH"];
    $paramsval="";
    $index1=0;
    $index2=0;
    while($index1 < strlen($hashparams)){
      $index2 = strpos($hashparams,":",$index1);
      $vl = $_POST[substr($hashparams,$index1,$index2- $index1)];
      if($vl == null) $vl = "";
      $paramsval = $paramsval . $vl;
      $index1 = $index2 + 1;
    }
    $hashval = $paramsval.$storekey;
    $hash = base64_encode(pack('H*',sha1($hashval)));
    if($paramsval != $hashparamsval || $hashparam != $hash) return false;
    $mode = self::$test ? 'T' : 'P';
    $type = "Auth";
    $oid = $_POST['oid'];
    $mdStatus = $_POST['mdStatus'];
    $xid = $_POST['xid'];
    $eci = $_POST['eci'];
    $cavv = $_POST['cavv'];
    $md = $_POST['md'];

    if($mdStatus != "1" && $mdStatus != "2" && $mdStatus != "3" && $mdStatus != "4") return false;

    $url = $url['check'];
    $request= "DATA=<?xml version=\"1.0\" encoding=\"ISO-8859-9\"?>".
    "<CC5Request>".
    "<Name>$name</Name>".
    "<Password>$password</Password>".
    "<ClientId>$clientid</ClientId>".
    "<IPAddress>$ip</IPAddress>".
    "<Email></Email>".
    "<Mode>$mode</Mode>".
    "<OrderId>$oid</OrderId>".
    "<GroupId></GroupId>".
    "<TransId></TransId>".
    "<UserId></UserId>".
    "<Type>Auth</Type>".
    "<Number>$md</Number>".
    "<Expires></Expires>".
    "<Cvv2Val></Cvv2Val>".
    "<Total>$order->amount</Total>".
    "<Currency>949</Currency>".
    "<Taksit>$order->taksit</Taksit>".
    "<PayerTxnId>$xid</PayerTxnId>".
    "<PayerSecurityLevel>$eci</PayerSecurityLevel>".
    "<PayerAuthenticationCode>$cavv</PayerAuthenticationCode>".
    "<CardholderPresentCode>13</CardholderPresentCode>".
    "<BillTo>".
    "<Name></Name>".
    "<Street1></Street1>".
    "<Street2></Street2>".
    "<Street3></Street3>".
    "<City></City>".
    "<StateProv></StateProv>".
    "<PostalCode></PostalCode>".
    "<Country></Country>".
    "<Company></Company>".
    "<TelVoice></TelVoice>".
    "</BillTo>".
    "<ShipTo>".
    "<Name></Name>".
    "<Street1></Street1>".
    "<Street2></Street2>".
    "<Street3></Street3>".
    "<City></City>".
    "<StateProv></StateProv>".
    "<PostalCode></PostalCode>".
    "<Country></Country>".
    "</ShipTo>".
    "<Extra></Extra>".
    "</CC5Request>";
    $result = self::call($url,$request);
    if(empty($result)) return false;
    $Response = "";
    $OrderId = "";
    $AuthCode  = "";
    $ProcReturnCode = "";
    $ErrMsg  = "";
    $HOSTMSG  = "";
    $HostRefNum = "";
    $TransId = "";
    $response_tag="Response";
    $posf = strpos($result, ("<" . $response_tag . ">"));
    $posl = strpos($result, ("</" . $response_tag . ">"));
    $posf = $posf+ strlen($response_tag) +2 ;
    $Response = substr ( $result, $posf, $posl - $posf);
    $response_tag="OrderId";
    $posf = strpos($result, ("<" . $response_tag . ">"));
    $posl = strpos($result, ("</" . $response_tag . ">"));
    $posf = $posf+ strlen($response_tag) +2 ;
    $OrderId = substr($result, $posf , $posl - $posf);
    $response_tag="AuthCode";
    $posf = strpos($result, "<" . $response_tag . ">");
    $posl = strpos($result, "</" . $response_tag . ">");
    $posf = $posf+ strlen($response_tag) +2 ;
    $AuthCode = substr($result, $posf , $posl - $posf);
    $response_tag="ProcReturnCode";
    $posf = strpos ($result, "<" . $response_tag . ">" );
    $posl = strpos ($result, "</" . $response_tag . ">" );
    $posf = $posf+ strlen($response_tag) +2 ;
    $ProcReturnCode = substr (  $result, $posf , $posl - $posf);
    $response_tag="ErrMsg";
    $posf = strpos ($result, "<" . $response_tag . ">" );
    $posl = strpos ($result, "</" . $response_tag . ">");
    $posf = $posf+ strlen($response_tag) +2 ;
    $ErrMsg = substr (  $result, $posf , $posl - $posf);
    $response_tag="HostRefNum";
    $posf = strpos ($result, "<" . $response_tag . ">" );
    $posl = strpos ($result, "</" . $response_tag . ">");
    $posf = $posf+ strlen($response_tag) +2 ;
    $HostRefNum = substr (  $result, $posf , $posl - $posf);
    $response_tag="TransId";
    $posf = strpos($result, "<" . $response_tag . ">" );
    $posl = strpos($result, "</" . $response_tag . ">" );
    $posf = $posf+ strlen($response_tag) + 2;
    $TransId = substr($result, $posf , $posl - $posf);
    $paymentCeckCode = $TransId;
    if($Response != "Approved") return false;
    return $HostRefNum;
  }

  private static function call($url, $data){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
    $result = curl_exec($ch);
    curl_close ($ch);
    return $result;
  }
}
