<?php
/*
   bitcoin.conf:
     walletnotify=/usr/bin/php -f /srv/app/bin/walletnotify.php %s

SQLLite
=========
CREATE TABLE "walletnotify"
(

 `rowid` integer PRIMARY KEY NOT NULL,
 "txid" varchar(100) NOT NULL  UNIQUE ,
 "tot_amt" NUMERIC, "tot_fee" NUMERIC,
 "confirmations" INTEGER,
 "comment" varchar(50),
 "blocktime" varchar(20),
 "account" varchar(50),
 "address" varchar(50),
 "category" varchar(20),
 "amount" NUMERIC,
 "fee" NUMERIC,
 "last_update" VARCHAR DEFAULT CURRENT_TIMESTAMP
);

CREATE  INDEX "main"."idx_walletnotify_txid" ON "walletnotify" ("txid" ASC);

CREATE TRIGGER walletnotify_trigger_ai AFTER INSERT ON walletnotify
 BEGIN
   UPDATE walletnotify SET last_update = DATETIME('NOW', 'localtime')  WHERE rowid = new.rowid;
 END;

CREATE TRIGGER walletnotify_trigger_au AFTER UPDATE ON walletnotify
 BEGIN
   UPDATE walletnotify SET last_update = DATETIME('NOW', 'localtime')  WHERE rowid = new.rowid;
 END;

MySQL
======
CREATE TABLE `walletnotify` (
   `rowid` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `txid` varchar(100) DEFAULT NULL,
   `tot_amt` decimal(14,8) DEFAULT NULL,
   `tot_fee` decimal(14,8) DEFAULT NULL,
   `confirmations` int(11) DEFAULT NULL,
   `comment` varchar(100) DEFAULT NULL,
   `blocktime` int(13) DEFAULT NULL,
   `account` varchar(50) DEFAULT NULL,
   `address` varchar(50) DEFAULT NULL,
   `category` varchar(50) DEFAULT NULL,
   `amount` decimal(14,8) DEFAULT NULL,
   `fee` decimal(14,8) DEFAULT NULL,
   `last_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`rowid`),
   UNIQUE KEY `txid` (`txid`),
   KEY `confirmations` (`confirmations`),
   KEY `comment` (`comment`),
   KEY `account` (`account`),
   KEY `address` (`address`),
   KEY `last_update` (`last_update`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

 */

   error_reporting('E_ALL ^ E_NOTICE ^ E_DEPRECATED');

   define('WN_GLOBAL_TIMESTAMP', time());
   define('WN_RPC_ACCT', 'fwd');

   define('WN_RPC_USER', '');
   define('WN_RPC_PASS', '');
   define('WN_RPC_HOST', '');
   define('WN_RPC_PORT', '');
   define('WN_SMS_ADMIN', '#####@carrier.com');
   define('WN_EMAIL_ADMIN', '');
   define('WN_EMAIL_FROM', 'WN.BTC.Bot <walletnotify.bot@domain.com>');

   if(2 == $argc)    {
      $api = new CoindRPC();

      //- use one of these: PDO:sqlite or PDO:mysql
      //$db = new PDO('sqlite: ./walletnotify.sqlite3');
      //$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $dsn = 'mysql:dbname=walletnotify;host=localhost';
      $db = new PDO($dsn, 'root', '');

      $helper = new Helper($db, $api);

      $walletinfo = $api->getinfo();
      $txninfo = $api->gettransaction($argv[1]);

      error_log('=== WALLETNOTIFY ===');
      error_log('walletinfo: '. print_r($walletinfo,true));
      error_log('txninfo: '. print_r($txninfo,true));

      try {

         $sql =  "REPLACE INTO walletnotify ".
                     "(`txid`, `tot_amt`, `tot_fee`, `confirmations`, `comment`, `blocktime`, ".
                     "`address`, `account`, `category`, `amount`, `fee`, `last_update`) ".
                     "VALUES ".
                     "(:txid, :tot_amt, :tot_fee, :confirmations, :comment, :blocktime, ".
                     ":address, :account, :category, :amount, :fee, NOW())";

         $qry = $db->prepare($sql);

         foreach($txninfo['details'] as $id => $details) {
            $vars = array(
                           'txid'     => $txninfo['txid'],
                           'tot_amt'  => $txninfo['amount'],
                           'tot_fee'  => $txninfo['fee'],
                           'confirmations'=> $txninfo['confirmations'],
                           'comment'  => $txninfo['comment'],
                           'blocktime'=> $txninfo['blocktime'] ? $txninfo['blocktime']:$txninfo['time'],
                           'account'  => $details['account'],
                           'address'  => $details['address'],
                           'category' => $details['category'],
                           'amount'   => $details['amount'],
                           'fee'      => $details['fee']
                           );
            if(!$txnhead)   $txnhead = $vars;

            foreach($vars as $key => $val)  {
               $qry->bindValue(':'.$key, $val);
            }

            error_log('walletnotify.vars.'.$id.': '. print_r($vars,true));
            $qry->execute();
         }

      }  catch (PDOException $e) {
         error_log('error: '. print_r($e->getMessage(),true));
         error_log('['.__LINE__ .'] : '.__FILE__);
         error_log('vars: '. print_r($vars,true));
         error_log('sql: '. print_r($sql,true));
      }

      //- send notifications
      Helper::walletnotify_email($txnhead);
   }

   error_log('=== END WALLETNOTIFY ===');
   echo chr(27)."[01;32m"."WalletNofify Complete".chr(27)."[0m\n";




/*
      =   =   =   =   =   =   =   =   =   =   =   =   =   =   =   =   =   =   =
 */


/*
EasyBitcoin-PHP

A simple class for making calls to Bitcoin's API using PHP.
https://github.com/aceat64/EasyBitcoin-PHP

====================

The MIT License (MIT)

Copyright (c) 2013 Andrew LeCody

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

====================

// Initialize Bitcoin connection/object
$bitcoin = new Bitcoin('username','password');

// Optionally, you can specify a host and port.
$bitcoin = new Bitcoin('username','password','host','port');
// Defaults are:
//  host = localhost
//  port = 8332
//  proto = http

// If you wish to make an SSL connection you can set an optional CA certificate or leave blank
// This will set the protocol to HTTPS and some CURL flags
$bitcoin->setSSL('/full/path/to/mycertificate.cert');

// Make calls to bitcoind as methods for your object. Responses are returned as an array.
// Examples:
$bitcoin->getinfo();
$bitcoin->getrawtransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098',1);
$bitcoin->getblock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');

// The full response (not usually needed) is stored in $this->response while the raw JSON is stored in $this->raw_response

// When a call fails for any reason, it will return FALSE and put the error message in $this->error
// Example:
echo $bitcoin->error;

// The HTTP status code can be found in $this->status and will either be a valid HTTP status code or will be 0 if cURL was unable to connect.
// Example:
echo $bitcoin->status;

*/

class Bitcoin
{
   // Configuration options
   private $username;
   private $password;
   private $proto;
   private $host;
   private $port;
   private $url;
   private $CACertificate;

   // Information and debugging
   public $status;
   public $error;
   public $raw_response;
   public $response;

   private $id = 0;

   /**
    * @param string $username
    * @param string $password
    * @param string $host
    * @param int $port
    * @param string $proto
    * @param string $url
    */
   function __construct($username, $password, $host = 'localhost', $port = 8332, $url = null) {
         $this->username      = $username;
         $this->password      = $password;
         $this->host          = $host;
         $this->port          = $port;
         $this->url           = $url;

         // Set some defaults
         $this->proto         = $host == 'localhost' ? 'http':'https';
         $this->CACertificate = null;
   }

   /**
    * @param string|null $certificate
    */
   function setSSL($certificate = null) {
         $this->proto         = 'https'; // force HTTPS
         $this->CACertificate = $certificate;
   }

   function __call($method, $params) {

         $this->status       = null;
         $this->error        = null;
         $this->raw_response = null;
         $this->response     = null;

         // If no parameters are passed, this will be an empty array
         $params = array_values($params);

         // The ID should be unique for each call
         $this->id++;

         // Build the request, it's ok that params might have any empty array
         $request = json_encode(array(
               'method' => $method,
               'params' => $params,
               'id'     => $this->id
         ));

         // Build the cURL session
         $curl    = curl_init("{$this->proto}://{$this->username}:{$this->password}@{$this->host}:{$this->port}/{$this->url}");
         $options = array(
               CURLOPT_RETURNTRANSFER => TRUE,
               CURLOPT_FOLLOWLOCATION => TRUE,
               CURLOPT_MAXREDIRS      => 10,
               CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
               CURLOPT_POST           => TRUE,
               CURLOPT_POSTFIELDS     => $request
         );

         if ($this->proto == 'https') {
               // If the CA Certificate was specified we change CURL to look for it
               if ($this->CACertificate != null) {
                     $options[CURLOPT_CAINFO] = $this->CACertificate;
                     $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
               }
               else {
                     // If not we need to assume the SSL cannot be verified so we set this flag to FALSE to allow the connection
                     $options[CURLOPT_SSL_VERIFYPEER] = FALSE;
               }
         }

         curl_setopt_array($curl, $options);

         // Execute the request and decode to an array
         $this->raw_response = curl_exec($curl);
         $this->response     = json_decode($this->raw_response, TRUE);
         //error_log('this->response: '. print_r($this->response,true));

         // If the status is not 200, something is wrong
         $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

         // If there was no error, this will be an empty string
         $curl_error = curl_error($curl);

         curl_close($curl);

         if (!empty($curl_error)) {
               $this->error = $curl_error;
         }

         if ($this->response['error']) {
               // If bitcoind returned an error, put that in $this->error
               $this->error = $this->response['error']['message'];
         }
         elseif ($this->status != 200) {
               // If bitcoind didn't return a nice error message, we need to make our own
               switch ($this->status) {
                     case 400:
                           $this->error = 'HTTP_BAD_REQUEST';
                           break;
                     case 401:
                           $this->error = 'HTTP_UNAUTHORIZED';
                           break;
                     case 403:
                           $this->error = 'HTTP_FORBIDDEN';
                           break;
                     case 404:
                           $this->error = 'HTTP_NOT_FOUND';
                           break;
               }
         }

         if ($this->error) {
               return FALSE;
         }

         return $this->response['result'];
   }
}

class AddressHistory
{

   public $address          = null;
   public $n_tx             = 0;
   public $total_sent       = 0;
   public $total_received   = 0;
   public $balance          = 0;
   public $final_balance    = 0;
   public $txns = array();

   public function __construct($txn=null)
   {
      if(! is_array($txn))    return null;

      if(array_key_exists('address', $txn))       $this->address = $txn['address'];
      if(array_key_exists('n_tx', $txn))          $this->n_tx = $txn['n_tx'];
      if(array_key_exists('total_sent', $txn))    $this->total_sent = $txn['total_sent'];
      if(array_key_exists('total_received', $txn))$this->total_received = $txn['total_received'];
      if(array_key_exists('balance', $txn))       $this->balance = $txn['balance'];
      if(array_key_exists('final_balance', $txn)) $this->final_balance = $txn['final_balance'];

      if(is_array($txn['txns']))  {
         foreach($txn['txns'] as $key => $this_txn)  {
            $new_txn = array(
                           'hash'         => $this_txn['hash'],
                           'block_height' => $this_txn['block_height'],
                           'value'        => $this_txn['value'],
                           'spent'        => $this_txn['spent'],
                           'spent_by'     => $this_txn['spent_by'],
                           'confirmations'=> $this_txn['confirmations']
                           );
            $this->txns[$key] = new TransRef($new_txn);
         }
      }   else    {
         $this->txns = null;
      }

      return $this;
   }
}

class TransRef
{

   public $hash;
   public $block_height;
   public $value;
   public $spent;
   public $spent_by;
   public $confirmations;

   public function __construct($txnref=null)
   {
      if(! is_array($txnref))    return null;

      if(array_key_exists('hash', $txnref))           $this->hash = $txnref['hash'];
      if(array_key_exists('block_height', $txnref))   $this->block_height = $txnref['block_height'];
      if(array_key_exists('value', $txnref))          $this->value = $txnref['value'];
      if(array_key_exists('spent', $txnref))          $this->spent = $txnref['spent'];
      if(array_key_exists('spent_by', $txnref))       $this->spent_by = $txnref['spent_by'];
      if(array_key_exists('confirmations', $txnref))  $this->confirmations = $txnref['confirmations'];

      return $this;
   }
}


class CoindRPC extends Bitcoin
{

   public function __construct()
   {

      return parent::__construct(WN_RPC_USER, WN_RPC_PASS, WN_RPC_HOST, WN_RPC_PORT);

   }

   public function __call($method, $params)
   {
      return parent::__call($method, $params);
   }

   public function get_address_balance($address, $confirmations=0)
   {
      try {

         $address_info = $this->validateaddress($address);

         if($address_info['isvalid'] == 1 && $address_info['ismine'] == 1)   {
            $balance = $this->getreceivedbyaddress($address, $confirmations);
         }

         if($balance != '') {
            return floatval($balance);
         }   else    {
            return 0;
         }

      } catch (Exception $e) {
         error_log('error: '. print_r($e->getMessage(),true));
         error_log('['.__LINE__.'] : '.__FILE__);
      }
   }

   public function get_address_history($address)
   {

         try {

            $address_info = $this->validateaddress($address);

            if($address_info['isvalid'] == 1 && $address_info['ismine'] == 1)   {
               $history = $this->listtransactions(WN_RPC_ACCT);

               $txns = array();
               $final_balance = $balance = 0;
               foreach($history as $txn) {
                  if($txn['address'] != $address)    continue;
                  $n_tx = $total_received = $total_sent = 0;

                  $n_tx = intval($addr_hist['n_tx']) + 1;
                  switch($txn['category'])  {
                     case('receive'):
                        $total_received = $addr_hist['total_received'] += $txn['amount'];
                        $balance = $balance + $txn['amount'];

                        //- can we trust final balance here?  do we need more history
                        $final_balance = $final_balance + $txn['amount'];
                     break;
                     case('send'):
                        $total_sent = $addr_hist['total_sent'] += $txn['amount'];
                        $balance = $balance + $txn['amount'];

                        //- can we trust final balance here?  do we need more history
                        $final_balance = $final_balance + $txn['amount'];
                     break;
                  }

                  $txns[] = array(
                                  'hash'   => $txn['txid'],
                                  'value'  => $txn['amount'],
                                  'spent'  => $txn['spent'],
                                  'spent_by'  => $txn['spent_by'],
                                  'confirmations'   => $txn['confirmations'],
                                  );
               }

               $addr_hist = array(
                                  'address'    => $address,
                                  'n_tx'       => $n_tx,
                                  'total_sent' => $total_sent,
                                  'total_received' => $total_received,
                                  'balance'        => $balance,
                                  'final_balance'  => $final_balance,
                                  'txns'           => $txns
                                  );

               $addr_hist = new AddressHistory($addr_hist);
            }   else {
               $addr_hist = false;
               error_log('Address invalid: '.$address);
               error_log('['.__LINE__.'] : '.__FILE__);
            }

            return $addr_hist;
      } catch (Exception $e) {
         error_log('error: '. print_r($e->getMessage(),true));
         error_log('['.__LINE__.'] : '.__FILE__);
      }
   }

   public function get_transaction($hash)
   {
      try {
         return $this->gettransaction($hash);
      } catch (Exception $e) {
         error_log('error: '. print_r($e->getMessage(),true));
         error_log('['.__LINE__.'] : '.__FILE__);
      }
   }
}



class Helper
{

   public static $api = null;
   public static $db = null;

   public function __construct($db, $api)
   {
      Helper::$api = $api;
      Helper::$db = $db;
   }

   public static function walletnotify_email($txnhead)
   {
      //- bitcoind calls walletnotify on 0 confirmations and 1.
      //- We only want email to go out on the first call. Otherwise
      //- if we want only one 1 confrime, change this to 
      //- confirmations == 0) return;
      if($txnhead['confirmations'] > 0)   return;

      $tmpl = file_get_contents('email.notify.tmpl.html');

      foreach($txnhead as $key => $val)   {
            $map['{'.$key.'}'] = $val;
      }

      $map['{timestamp}'] = date('Y-m-d H:i:s', WN_GLOBAL_TIMESTAMP);
      $map['{hostname}'] = php_uname('n');

      $html = str_replace(array_keys($map), array_values($map), $tmpl);

      $txid_short = substr($txnhead['txid'], 0, 4).' .. '.substr($txnhead['txid'], -4);
      $msg = "=WNotify=".
               "\ntxid: ".$txid_short.
               "\nAmt : ".$txnhead['amount'].
               "\nCmnt: ".$txnhead['comment'].
               "\nAcct: ".$txnhead['account'].
               "\nConf: ".$txnhead['confirmations'].
               "\nCat : ".$txnhead['category'].
               "\nAddr: ".$txnhead['address'].
               "";

      //- send to carrier's email to SMS gateway if configured
      if(defined('WN_SMS_ADMIN') && filter_var(WN_SMS_ADMIN, FILTER_VALIDATE_EMAIL))  {
            Helper::send_email_sms($msg, WN_SMS_ADMIN);
      }

      return Helper::send_email($html, 'WN:WalletNotify', WN_EMAIL_ADMIN);;
   }

   public static function send_email($msg, $subj, $to)
   {

      $headers = 'From: '.WN_EMAIL_FROM."\r\n";
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

      if(trim($msg) == '')    return false;

      return mail($to, $subj, $msg, $headers);
   }

   public static function send_email_sms($msg, $to)
   {
      if(trim($msg) == '')    return false;
      if($to == '')   return false;

      $headers = 'From: '.WN_EMAIL_FROM."\r\n";

      return mail($to, null, $msg."\n.", $headers);
   }
}
