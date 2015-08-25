<?php
if (!defined('ABSPATH') ) exit;
function Load_ParsPal_Gateway() {
	
	if ( class_exists( 'WC_Payment_Gateway' ) && !class_exists( 'WC_ParsPal' ) && !function_exists('Woocommerce_Add_ParsPal_Gateway') ) {
		
		add_filter('woocommerce_payment_gateways', 'Woocommerce_Add_ParsPal_Gateway' );
		function Woocommerce_Add_ParsPal_Gateway($methods) {
			$methods[] = 'WC_ParsPal';
			return $methods;
		}
		
		class WC_ParsPal extends WC_Payment_Gateway {
			
			public function __construct(){
				
				//by Woocommerce.ir
				$this->author = 'Woocommerce.ir';
				//by Woocommerce.ir
				
				
				$this->id = 'ParsPal';
				$this->method_title = __('پارس پال', 'woocommerce');
				$this->method_description = __( 'تنظیمات درگاه پرداخت پارس پال برای افزونه فروشگاه ساز ووکامرس', 'woocommerce');
				$this->icon = apply_filters('WC_ParsPal_logo', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png');
				$this->has_fields = false;
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->title = $this->settings['title'];
				$this->description = $this->settings['description'];
				
				$this->merchant = $this->settings['merchant'];
				$this->password = $this->settings['password'];
				$this->sandbox = $this->settings['sandbox'];
				
				$this->success_massage = $this->settings['success_massage'];
				$this->failed_massage = $this->settings['failed_massage'];
				$this->cancelled_massage = $this->settings['cancelled_massage'];
				
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) )
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				else
					add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );	
				add_action('woocommerce_receipt_'.$this->id.'', array($this, 'Send_to_ParsPal_Gateway_By_HANNANStd'));
				add_action('woocommerce_api_'.strtolower(get_class($this)).'', array($this, 'Return_from_ParsPal_Gateway_By_HANNANStd') );
				
			}

		
			public function admin_options(){
				$action = $this->author;
				do_action( 'WC_Gateway_Payment_Actions', $action );			
				parent::admin_options();
			}
		
			public function init_form_fields(){
				$this->form_fields = apply_filters('WC_ParsPal_Config', 
					array(
					
						'base_confing' => array(
							'title'       => __( 'تنظیمات پایه ای', 'woocommerce' ),
							'type'        => 'title',
							'description' => '',
						),
						'enabled' => array(
							'title'   => __( 'فعالسازی/غیرفعالسازی', 'woocommerce' ),
							'type'    => 'checkbox',
							'label'   => __( 'فعالسازی درگاه پارس پال', 'woocommerce' ),						
							'description' => __( 'برای فعالسازی درگاه پرداخت پارس پال باید چک باکس را تیک بزنید', 'woocommerce' ),
							'default' => 'yes',
							'desc_tip'    => true,
						),
						'title' => array(
							'title'       => __( 'عنوان درگاه', 'woocommerce' ),
							'type'        => 'text',
							'description' => __( 'عنوان درگاه که در طی خرید به مشتری نمایش داده میشود', 'woocommerce' ),
							'default'     => __( 'پارس پال', 'woocommerce' ),
							'desc_tip'    => true,
						),
						'description' => array(
							'title'       => __( 'توضیحات درگاه', 'woocommerce' ),
							'type'        => 'text',
							'desc_tip'    => true,
							'description' => __( 'توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'woocommerce' ),
							'default'     => __( 'پرداخت امن به وسیله کلیه کارت های عضو شتاب از طریق درگاه پارس پال', 'woocommerce' )
						),
						'account_confing' => array(
							'title'       => __( 'تنظیمات حساب پارس پال', 'woocommerce' ),
							'type'        => 'title',
							'description' => '',
						),
						'merchant' => array(
							'title'       => __( 'مرچنت', 'woocommerce' ),
							'type'        => 'text',
							'description' => __( 'مرچنت درگاه پارس پال', 'woocommerce' ),
							'default'     => '',
							'desc_tip'    => true
						),
						'password' => array(
							'title'       => __( 'کلمه عبور', 'woocommerce' ),
							'type'        => 'text',
							'description' => __( 'کلمه عبور درگاه پارس پال', 'woocommerce' ),
							'default'     => '',
							'desc_tip'    => true
						),
						'sandbox' => array(
							'title'   => __( 'فعالسازی حالت آزمایشی', 'woocommerce' ),
							'type'    => 'checkbox',
							'label'   => __( 'فعالسازی حالت آزمایشی پارس پال', 'woocommerce' ),						
							'description' => __( 'برای فعال سازی حالت آزمایشی پارس پال چک باکس را تیک بزنید .', 'woocommerce' ),
							'default' => 'no',
							'desc_tip'    => true,
						),
						'payment_confing' => array(
							'title'       => __( 'تنظیمات عملیات پرداخت', 'woocommerce' ),
							'type'        => 'title',
							'description' => '',
						),
						'success_massage' => array(
							'title'       => __( 'پیام پرداخت موفق', 'woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری (شماره تراکنش) پارس پال استفاده نمایید .', 'woocommerce' ),
							'default'     => __( 'با تشکر از شما . سفارش شما با موفقیت پرداخت شد .', 'woocommerce' ),
						),
						'failed_massage' => array(
							'title'       => __( 'پیام پرداخت ناموفق', 'woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید . این دلیل خطا از سایت پارس پال ارسال میگردد .', 'woocommerce' ),
							'default'     => __( 'پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .', 'woocommerce' ),
						),
						'cancelled_massage' => array(
							'title'       => __( 'پیام انصراف از پرداخت', 'woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'متن پیامی که میخواهید بعد از انصراف کاربر از پرداخت نمایش دهید را وارد نمایید . این پیام بعد از بازگشت از بانک نمایش داده خواهد شد .', 'woocommerce' ),
							'default'     => __( 'پرداخت به دلیل انصراف شما ناتمام باقی ماند .', 'woocommerce' ),
						),
					)
				);
			}

			public function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );	
				return array(
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}

			public function Send_to_ParsPal_Gateway_By_HANNANStd($order_id){
				ob_start();
				global $woocommerce;
				$woocommerce->session->order_id_parspal = $order_id;
				$order = new WC_Order( $order_id );
				$currency = $order->get_order_currency();
				$currency = apply_filters( 'WC_ParsPal_Currency', $currency, $order_id );
				$action = $this->author;
				do_action( 'WC_Gateway_Payment_Actions', $action );			
				$form = '<form action="" method="POST" class="parspal-checkout-form" id="parspal-checkout-form">
						<input type="submit" name="parspal_submit" class="button alt" id="parspal-payment-button" value="'.__( 'پرداخت', 'woocommerce' ).'"/>
						<a class="button cancel" href="' . $woocommerce->cart->get_checkout_url() . '">' . __( 'بازگشت', 'woocommerce' ) . '</a>
					 </form><br/>';
				$form = apply_filters( 'WC_ParsPal_Form', $form, $order_id, $woocommerce );				
				
				do_action( 'WC_ParsPal_Gateway_Before_Form', $order_id, $woocommerce );	
				echo $form;
				do_action( 'WC_ParsPal_Gateway_After_Form', $order_id, $woocommerce );
					
				if ( isset($_POST["parspal_submit"]) ) {
					$action = $this->author;
					do_action( 'WC_Gateway_Payment_Actions', $action );		
					if(!extension_loaded('soap')){
						$order->add_order_note( __( 'تابع SOAP روی هاست شما فعال نیست .', 'woocommerce') );
						wc_add_notice( __( 'تابع SOAP روی هاست فروشنده فعال نیست .', 'woocommerce') , 'error' );
						return false;
					}
					
					$Amount = intval($order->order_total);
					$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency );
					if ( strtolower($currency) == strtolower('IRR') || strtolower($currency) == strtolower('RIAL')
						|| strtolower($currency) == strtolower('Iran Rial') || strtolower($currency) == strtolower('Iranian Rial')
						|| strtolower($currency) == strtolower('Iran-Rial') || strtolower($currency) == strtolower('Iranian-Rial')
						|| strtolower($currency) == strtolower('Iran_Rial') || strtolower($currency) == strtolower('Iranian_Rial')
						|| strtolower($currency) == strtolower('ریال') || strtolower($currency) == strtolower('ریال ایران')
					)
						$Amount = $Amount/10;
					else if ( strtolower($currency) == strtolower('IRHT') )							
						$Amount = $Amount*1000;
					else if ( strtolower($currency) == strtolower('IRHR') )					
						$Amount = ($Amount*1000)/10;
					
					$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency );
					$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_irt', $Amount, $currency );
					$Amount = apply_filters( 'woocommerce_order_amount_total_ParsPal_gateway', $Amount, $currency );
			
			
					$Sandbox = $this->sandbox;
					if ( $Sandbox == "yes" || $Sandbox == "1" || $Sandbox == 1  ) {
						$MerchantID = '100001';  
						$Password = 'abcdeFGHI';  
						$client = new SoapClient('http://sandbox.parspal.com/WebService.asmx?wsdl');
					} 
					else {
						$MerchantID = $this->merchant;  
						$Password = $this->password;  
						$client = new SoapClient('http://merchant.parspal.com/WebService.asmx?wsdl');
					}
				
					$Description = 'خرید به شماره سفارش : '.$order->get_order_number();
					$Email = $order->billing_email;
					$Email = $Email ? $Email : '-';
					$Mobile = get_post_meta( $order_id, '_billing_phone', true ) ? get_post_meta( $order_id, '_billing_phone', true ) : '-';
					$Mobile = (is_numeric($Mobile) && $Mobile ) ? $Mobile : '-';
					$Paymenter = $order->billing_first_name.' '.$order->billing_last_name;
					$Paymenter = $Paymenter ? $Paymenter : '-';
					$ResNumber = intval($order->get_order_number());
					
					$CallbackURL = add_query_arg( 'wc_order', $order_id , WC()->api_request_url('WC_ParsPal') );
					
					//Hooks for iranian developer
					$Description = apply_filters( 'WC_ParsPal_Description', $Description, $order_id );
					$Email = apply_filters( 'WC_ParsPal_Email', $Email, $order_id );
					$Mobile = apply_filters( 'WC_ParsPal_Mobile', $Mobile, $order_id );
					$Paymenter = apply_filters( 'WC_ParsPal_Paymenter', $Paymenter, $order_id );
					$ResNumber = apply_filters( 'WC_ParsPal_ResNumber', $ResNumber, $order_id );
					do_action( 'WC_ParsPal_Gateway_Payment', $order_id, $Description, $Email, $Mobile );
					
					
				
					$res = $client->RequestPayment(
						array(
							"MerchantID" => $MerchantID , 
							"Password" =>$Password , 
							"Price" =>$Amount, 
							"ReturnPath" =>$CallbackURL, 
							"ResNumber" =>$ResNumber, 
							"Description" =>$Description,
							"Paymenter" =>$Paymenter,
							"Email" =>$Email,
							"Mobile" =>$Mobile
						)
					);
					
					$Result = $res->RequestPaymentResult->ResultStatus;
					
					if( $Result == 'Succeed') {
						
		
						do_action( 'WC_ParsPal_Before_Send_to_Gateway', $order_id );
		
						ob_start();
						if (!headers_sent()) {
							header('Location: '.$res->RequestPaymentResult->PaymentPath);
							ob_end_flush();
							ob_end_clean();
							exit;
						}
						else {
							$redirect_page = $res->RequestPaymentResult->PaymentPath;
							echo "<script type='text/javascript'>window.onload = function () { top.location.href = '" . $redirect_page . "'; };</script>";
							exit;
						}
						
					}
					else {
						
						$fault = $Result;
						
						$Note = sprintf( __( 'خطا در هنگام ارسال به بانک : %s', 'woocommerce'), $this->Fault_ParsPal($fault) );
						$Note = apply_filters( 'WC_ParsPal_Send_to_Gateway_Failed_Note', $Note, $order_id, $fault );
						$order->add_order_note( $Note );
						
						
						$Notice = sprintf( __( 'در هنگام اتصال به بانک خطای زیر رخ داده است : <br/>%s', 'woocommerce'), $this->Fault_ParsPal($fault) );
						$Notice = apply_filters( 'WC_ParsPal_Send_to_Gateway_Failed_Notice', $Notice, $order_id, $fault );
						if ( $Notice )
							wc_add_notice( $Notice , 'error' );
						
						do_action( 'WC_ParsPal_Send_to_Gateway_Failed', $order_id, $fault );
					
					}
				}
			}

			public function Return_from_ParsPal_Gateway_By_HANNANStd(){
				
				global $woocommerce;
				$action = $this->author;
				do_action( 'WC_Gateway_Payment_Actions', $action );			
				if ( isset($_GET['wc_order']) ) 
					$order_id = $_GET['wc_order'];
				else
					$order_id = $woocommerce->session->order_id_parspal;
				if ( $order_id ) {
					
					$order = new WC_Order($order_id);
					$currency = $order->get_order_currency();		
					$currency = apply_filters( 'WC_ParsPal_Currency', $currency, $order_id );
						
					if($order->status !='completed'){
						
						
						$Amount = intval($order->order_total);
						$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency );
						if ( strtolower($currency) == strtolower('IRR') || strtolower($currency) == strtolower('RIAL')
							|| strtolower($currency) == strtolower('Iran Rial') || strtolower($currency) == strtolower('Iranian Rial')
							|| strtolower($currency) == strtolower('Iran-Rial') || strtolower($currency) == strtolower('Iranian-Rial')
							|| strtolower($currency) == strtolower('Iran_Rial') || strtolower($currency) == strtolower('Iranian_Rial')
							|| strtolower($currency) == strtolower('ریال') || strtolower($currency) == strtolower('ریال ایران')
						)
							$Amount = $Amount/10;
						else if ( strtolower($currency) == strtolower('IRHT') )							
							$Amount = $Amount*1000;
						else if ( strtolower($currency) == strtolower('IRHR') )					
							$Amount = ($Amount*1000)/10;
					
						$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency );
						$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_irt', $Amount, $currency );
						$Amount = apply_filters( 'woocommerce_order_amount_total_ParsPal_gateway', $Amount, $currency );
						
						
						$Sandbox = $this->sandbox;
						if (  $Sandbox == "yes" || $Sandbox == "1" || $Sandbox == 1  ) {
							$MerchantID = '100001';  
							$Password = 'abcdeFGHI'; 
							$client = new SoapClient('http://sandbox.parspal.com/WebService.asmx?wsdl');
						}
						else {
							$MerchantID = $this->merchant;  
							$Password = $this->password;  
							$client = new SoapClient('http://merchant.parspal.com/WebService.asmx?wsdl');
						}
						
						if(isset($_POST['status']) && $_POST['status'] == 100){
							$Result = $_POST['status'];
							$Refnumber = $_POST['refnumber'];
							$Resnumber = $_POST['resnumber'];
							$res = $client->VerifyPayment(
								array(
									"MerchantID" => $MerchantID , 
									"Password" =>$Password , 
									"Price" =>$Amount,
									"RefNum" =>$Refnumber 
								)
							);
							$PayPrice = $res->verifyPaymentResult->PayementedPrice;
							$Result = $res->verifyPaymentResult->ResultStatus;
							if($Result == 'success') {
								$status = 'completed';
								$fault = 0;
								$transaction_id = $Refnumber;
							}
							else {
								$status = 'failed';
								$fault = $Result;
								$transaction_id = 0;
							}
						}
						else {
							$status = 'cancelled';
							$fault = 0;
							$transaction_id = 0;
						}
						
						
						
						if ( $status == 'completed') {
							$action = $this->author;
							do_action( 'WC_Gateway_Payment_Actions', $action );
							if ( $transaction_id && ( $transaction_id !=0 ) )
								update_post_meta( $order_id, '_transaction_id', $transaction_id );
														
							$order->payment_complete($transaction_id);
							$woocommerce->cart->empty_cart();
							
							
							$Note = sprintf( __('پرداخت موفقیت آمیز بود .<br/> کد رهگیری (شماره تراکنش) : %s', 'woocommerce' ), $transaction_id );
							$Note = apply_filters( 'WC_ParsPal_Return_from_Gateway_Success_Note', $Note, $order_id, $transaction_id );
							if ($Note)
								$order->add_order_note( $Note , 1 );
							
							$Notice = wpautop( wptexturize($this->success_massage));
							
							$Notice = str_replace("{transaction_id}",$transaction_id,$Notice);
							
							$Notice = apply_filters( 'WC_ParsPal_Return_from_Gateway_Success_Notice', $Notice, $order_id, $transaction_id );
							if ($Notice)
								wc_add_notice( $Notice , 'success' );
							
							
							do_action( 'WC_ParsPal_Return_from_Gateway_Success', $order_id, $transaction_id );
							
							wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
							exit;
						}
						elseif ( $status == 'cancelled') {
							
							$action = $this->author;
							do_action( 'WC_Gateway_Payment_Actions', $action );
							
							$tr_id = ( $transaction_id && $transaction_id != 0 ) ? ('<br/>شماره تراکنش : '.$transaction_id) : '';
					
							$Note = sprintf( __('کاربر در حین تراکنش از پرداخت انصراف داد .%s', 'woocommerce' ), $tr_id );
							$Note = apply_filters( 'WC_ParsPal_Return_from_Gateway_Cancelled_Note', $Note, $order_id, $transaction_id );
							if ( $Note )
								$order->add_order_note( $Note, 1 );
							
							
							$Notice =  wpautop( wptexturize($this->cancelled_massage));
							
							$Notice = str_replace("{transaction_id}",$transaction_id,$Notice);
							
							$Notice = apply_filters( 'WC_ParsPal_Return_from_Gateway_Cancelled_Notice', $Notice, $order_id, $transaction_id );
							if ($Notice)
								wc_add_notice( $Notice , 'error' );
							
							do_action( 'WC_ParsPal_Return_from_Gateway_Cancelled', $order_id, $transaction_id );
							
							wp_redirect(  $woocommerce->cart->get_checkout_url()  );
							exit;
							
						}
						else {
							
							$action = $this->author;
							do_action( 'WC_Gateway_Payment_Actions', $action );
							
							$tr_id = ( $transaction_id && $transaction_id != 0 ) ? ('<br/>شماره تراکنش : '.$transaction_id) : '';
							
							$Note = sprintf( __( 'خطا در هنگام بازگشت از بانک : %s %s', 'woocommerce'), $this->Fault_ParsPal($fault), $tr_id );
							$Note = apply_filters( 'WC_ParsPal_Return_from_Gateway_Failed_Note', $Note, $order_id, $transaction_id, $fault );
							if ($Note)
								$order->add_order_note( $Note , 1 );
							
							
							$Notice = wpautop( wptexturize($this->failed_massage));
							
							$Notice = str_replace("{transaction_id}",$transaction_id,$Notice);
							
							$Notice = str_replace("{fault}",$this->Fault_ParsPal($fault),$Notice);
							$Notice = apply_filters( 'WC_ParsPal_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $transaction_id, $fault );
							if ($Notice)
								wc_add_notice( $Notice , 'error' );
							
							do_action( 'WC_ParsPal_Return_from_Gateway_Failed', $order_id, $transaction_id, $fault );
							
							wp_redirect(  $woocommerce->cart->get_checkout_url()  );
							exit;
						}
				
				
					}
					else {
						$action = $this->author;
						do_action( 'WC_Gateway_Payment_Actions', $action );	
						$transaction_id = get_post_meta( $order_id, '_transaction_id', true );
						
						$Notice = wpautop( wptexturize($this->success_massage));
						
						$Notice = str_replace("{transaction_id}",$transaction_id,$Notice);
						
						$Notice = apply_filters( 'WC_ParsPal_Return_from_Gateway_ReSuccess_Notice', $Notice, $order_id, $transaction_id );
						if ($Notice)
							wc_add_notice( $Notice , 'success' );
						
						
						do_action( 'WC_ParsPal_Return_from_Gateway_ReSuccess', $order_id, $transaction_id );
							
						wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
						exit;
					}
				}
				else {
					
					$action = $this->author;
					do_action( 'WC_Gateway_Payment_Actions', $action );		
					$fault = __('شماره سفارش وجود ندارد .', 'woocommerce' );
					$Notice = wpautop( wptexturize($this->failed_massage));
					$Notice = str_replace("{fault}",$fault, $Notice);
					$Notice = apply_filters( 'WC_ParsPal_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $fault );
					if ($Notice)
						wc_add_notice( $Notice , 'error' );		
					
					do_action( 'WC_ParsPal_Return_from_Gateway_No_Order_ID', $order_id, $transaction_id, $fault );
						
					wp_redirect(  $woocommerce->cart->get_checkout_url()  );
					exit;
				}
			}

			private static function Fault_ParsPal($faul_code){
				
				$message = __('در حین پرداخت خطای سیستمی رخ داده است .', 'woocommerce' );
				
				switch($faul_code){
					
                	
                    case "Ready" :
						$message = __("هیچ عملیاتی انجام نشده است .", "woocommerce" );
                    break;
					
                    case "GatewayUnverify" :
						$message = __("درگاه شما غیر فعال می باشد .", "woocommerce" );
                    break;
					
                    case "GatewayIsExpired" :
						$message = __("درگاه شما فاقد اعتبار می باشد .", "woocommerce" );
                    break;
					
					case "GatewayIsBlocked" :
						$message = __("درگاه شما مسدود شده است .", "woocommerce" );
                    break;
					
					case "GatewayInvalidInfo" :
						$message = __("مرچنت یا رمز عبور اشتباه وارد شده است .", "woocommerce" );
                    break;	
					
					case "UserNotActive" :
						$message = __("کاربر غیرفعال شده است .", "woocommerce" );
                    break;
					
                    case "InvalidServerIP" :
						$message = __("IP سرور نامعتبر می باشد .", "woocommerce" );
                    break;
					
					case "Failed" :
						$message = __("عملیات با مشکل مواجه شد .", "woocommerce" );
                    break;
					
					case "NotMatchMoney" :
						$message = __("مبلغ واریزی با مبلغ درخواستی یکسان نمی باشد .", "woocommerce" );
                    break;		
					
					case "Verifyed" :
						$message = __("قبلا پرداخت شده است .", "woocommerce" );
                    break;
											
					case "InvalidRef" :
						$message = __("شماره رسید قابل قبول نمی باشد .", "woocommerce" );
                    break;
					
				
				}
				return $message;
			}
 
		}
	}
}
add_action('plugins_loaded', 'Load_ParsPal_Gateway', 0);