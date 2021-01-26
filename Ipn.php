<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Ipn
{
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function createTxt($error_msg)
    {
        $archivo = fopen("datosPago.txt", "w+b");
        $data = $error_msg;
        fwrite($archivo,  $data);
        fclose($archivo);
    }

    //contrucción del mensaje de error
    public function errorAndDie($error_msg)
    {
        global $cp_debug_email;
        #$cp_debug_email = 'darwin@igniweb.com';
        if (!empty($cp_debug_email)) {
            $report = 'Error: ' . $error_msg . "\n\n";
            $report .= "POST Data\n\n";
            foreach ($_POST as $k => $v) {
                $report .= "|$k| = |$v|\n";
            }
            mail($cp_debug_email, 'CoinPayments IPN Error', $report);
        }
        die('IPN Error: ' . $error_msg);
    }

    //Negociación para recibir datos de coinpayment 
    public function getData($ipn_mode,$http_hmac,$request,$merchant,$cp_merchant_id,$cp_ipn_secret,$cp_debug_email)
    { 
             
        if(!isset($ipn_mode) || $ipn_mode != 'hmac') {
        $this->createTxt('IPN Mode is not HMAC fff');      
        errorAndDie('IPN Mode is not HMAC....');             
        }

        if (!isset($http_hmac) || empty($http_hmac)) {
        $this->createTxt('No HMAC signature sent.');
        errorAndDie('No HMAC signature sent.');
        
        }

        
        
        file_put_contents("coinpayments.log", $request,FILE_APPEND);
        if ($request === FALSE || empty($request)) {
        $this->createTxt('Error reading POST data');
        errorAndDie('Error reading POST data');      
        }

        if (!isset($merchant) || $merchant != trim($cp_merchant_id)) {
        $this->createTxt('No or incorrect Merchant ID passed');
        errorAndDie('No or incorrect Merchant ID passed');
        
        }

        $hmac = hash_hmac("sha512", $request, trim($cp_ipn_secret));
        if (!hash_equals($hmac, $http_hmac)) {
        //if ($hmac != $_SERVER['HTTP_HMAC']) { <-- Use this if you are running a version of PHP below 5.6.0 without the hash_equals function
        $this->createTxt('HMAC signature does not match');
        errorAndDie('HMAC signature does not match');
        
        }

       
    }
    //Validación de la información traida del post contra información de la bd
    public function validData($data=array(), $order_currency, $order_total)
    {
            
            // Check the original currency to make sure the buyer didn't change it.
            if ($data['currency1'] != $order_currency) {
                createTxt('Original currency mismatch!');
                errorAndDie('Original currency mismatch!');
            }

            // Check amount against order total
            if ($data['amount1'] < $order_total) {
                createTxt('Amount is less than order total!');
                errorAndDie('Amount is less than order total!');
            }

            if ($data['status'] >= 100 || $data['status'] == 2) {
                
                return $data;
                
            } else if ($data['status'] < 0) {
                #$message='payment error, this is usually final but payments will sometimes be reopened if there was no exchange rate conversion or with seller consent!';
                #return array('status'=>$data['status'], 'status_text'=>$data['status_text'], 'message'=>$message);
                return $data;
                //payment error, this is usually final but payments will sometimes be reopened if there was no exchange rate conversion or with seller consent
            } else {
                #$message = 'payment is pending, you can optionally add a note to the order page!';
                #return array('status' => $data['status'], 'status_text' => $data['status_text'], 'message' => $message);
                return $data;
                //payment is pending, you can optionally add a note to the order page

            }
            die('IPN OK');
        
    }
        
    
        
}
