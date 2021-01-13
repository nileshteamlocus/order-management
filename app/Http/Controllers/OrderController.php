<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;


class OrderController extends Controller {

    public function __construct( ) {
		$this->date_format  = 'd-M-Y h:i A';
        $this->single_val   = 'single';
        $this->multiple_val = 'multiple';
	}

    public function newOrderAjax( Request $request ) {

        $search_order_no = $request->input('search_order_no');
        $website         = $request->input('website');
        $products        = $request->input('products');
        $order_type      = $request->input('order_type');
        $search_order    = $request->input('search_order');
        $start_date      = $request->input('start_date');
        $end_date        = $request->input('end_date');
        $page            = $request->input('page');
        $page_code       = $request->input('page_code');

        $where_am = array();
        $where_gb = array();

        $am_order_no = '';
        if(!empty($search_order_no))
        {
            $where_am [] = "MarketOrderID LIKE '%$search_order_no%'";
            $where_gb[] = "Client_Order_ID LIKE '%$search_order_no%'";
        }

        if(!empty($products))
        {
            $product_val = ($products == 'Dry') ? 0 : 1 ;
            $where_gb[] = "IsFrozen = $product_val";
        }

        if(!empty($order_type))
        {
            if( $order_type == 'Pickup' )
            {
                $where_gb [] = "IsPickupFromStore=1";
                $where_am [] = "1=2";
            }
            if( $order_type == 'Shipping' )
            {
                $where_gb [] = "IsPickupFromStore=0";
                $where_am [] = "1=1";
            }
            if( $order_type == 'Delivery' )
            {
                $where_gb [] = "1=2";
                $where_am [] = "1=2";
            }
        }

        if(!empty($search_order))
        {
            switch ($search_order) {

                case 'last_7':
                    $where_gb[] = " Order_Date >= DATEADD(day,-7, CAST( GETDATE() AS Date ) ) ";
                    $where_am[] = " OrderDate >= DATEADD(day,-7, CAST( GETDATE() AS Date )) ";
                    break;

                case 'last_month':
                    $where_gb[] = " DATEPART(m, Order_Date) = DATEPART(m, DATEADD(m, -1, getdate())) AND DATEPART(yyyy, Order_Date) = DATEPART(yyyy, DATEADD(m, -1, getdate())) ";
                    $where_am[] = " DATEPART(m, OrderDate) = DATEPART(m, DATEADD(m, -1, getdate())) AND DATEPART(yyyy, OrderDate) = DATEPART(yyyy, DATEADD(m, -1, getdate())) ";
                    break;

                case 'month_date':
                    $where_gb[] = " Order_Date >= CAST( GETDATE()-DAY(GETDATE())+1  as Date ) ";
                    $where_am[] = " OrderDate >= CAST( GETDATE()-DAY(GETDATE())+1  as Date ) ";
                    break;

                case 'year_date':
                    $where_gb[] = " Order_Date >= DATEADD(yy, DATEDIFF(yy, 0, GETDATE()), 0) ";
                    $where_am[] = " OrderDate >= DATEADD(yy, DATEDIFF(yy, 0, GETDATE()), 0) ";
                    break;

                case 'date_range':
                    if( !empty($start_date) )
                    {
                        $where_gb[] = " Order_Date >= '$start_date' ";
                        $where_am[] = " OrderDate >= '$start_date' ";
                    }
                    if( !empty($end_date) )
                    {
                        $where_gb[] = " Order_Date <= '$end_date' ";
                        $where_am[] = " OrderDate <= '$end_date' ";
                    }
                    break;
                
                default: 
                    break;
            }
        }

        $where_am_text = " WHERE OrderState='INITIATED' ";
        $where_gb_text = " WHERE Order_State='INITIATED' ";

        if( $page_code == 'picking' )
        {
            $where_am_text = " WHERE OrderState='INPROCESS' ";
            $where_gb_text = " WHERE Order_State='INPROCESS' ";
        }

        $where_am_text = " WHERE 1=1 ";
        $where_gb_text = " WHERE 1=1 ";

        if( !empty($where_am) )
        {
            $where_am_text_ar = implode(" AND ", $where_am);
            $where_am_text = " AND $where_am_text_ar ";
        }
        if( !empty($where_gb) )
        {
            $where_gb_text_ar = implode(" AND ", $where_gb);
            $where_gb_text .= " AND $where_gb_text_ar ";
        } 

        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $perpage   = 20;
        $total_row = 0;

        if( !$page )
        {
            $page = 1;
        } 
        $start = ($page-1) * $perpage;

        $newOrder = array();
        $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
        if (isset($timezoneId[0])) {

            $sql = array();

            $order_total = 0 ;

            if( (empty($website) || $website == 'Amazon') && $products != 'Frozen' )
            {
                #         SELECT TOP $perpage
                $sql[] = "SELECT 

                        OrderDate AS RealOrderDate, 
                        MarketOrderID AS RealOrderID, 
                        OrderTotal AS RealOrderTotal, 
                        'AMAZON' AS MarketPlace ,
                        0 AS is_frozen,
                        0 AS is_pickup_from_store,
                        ShippingCost as RealShippingCost,
                        ShipTaxCost as RealShipTaxCost,
                        ClientBillName as RealBillName,
                        ClientShipAddress1 as RealShipAddress1,
                        ClientBillAddress2 as RealBillAddress2,
                        ClientBillPhone as RealBillPhone,
                        ClientBillEmail as RealBillEmail,
                        ClientShipName as RealShipName,
                        ClientShipAddress2 as RealShipAddress2,
                        ClientShipName as RealShipPhone,
                        ClientShipEmail as RealShipEmail,
                        ClientShipCity as RealShipCity,
                        ClientShipState as RealShipState,
                        ClientShipZipCode as RealShipZipCode,
                        TrackingNumber as RealTrackingNumber,
                        '' as RealPickupBy,
                        '' as RealPickupDateTimeFrom,
                        '' as RealPickedupBy,
                        '' as RealPickedupDateTime,
                        ClientBillCity as RealClientBillCity,
                        ClientBillState as RealClientBillState,
                        ClientBillZipCode as RealClientBillZipCode,
                        '' as RealPickupDateTimeTo
                        FROM Grocery.MarketOrderMaster
                        $where_am_text
                        ";

                        #ORDER BY OrderDate DESC
                $am_sql = "SELECT SUM(OrderTotal) as order_total, COUNT(MarketOrderID) as total_row
                        FROM Grocery.MarketOrderMaster
                        $where_am_text"; 

                $am_result = sqlsrv_query($conn, $am_sql);
                $am_raw = sqlsrv_fetch_object($am_result); #print_r($am_raw);
                $order_total = $order_total + $am_raw->order_total; 
                $total_row = $total_row + $am_raw->total_row; 
            }

            if( empty($website) || $website == 'GroceryBabu' )
            {
                #         SELECT TOP $perpage
                $sql[] = "SELECT 

                        Order_Date AS RealOrderDate, 
                        Client_Order_ID AS RealOrderID, 
                        Order_Total AS RealOrderTotal, 
                        'GBA' AS MarketPlace,
                        IsFrozen AS is_frozen,
                        IsPickupFromStore AS is_pickup_from_store,
                        Shipping_Cost as RealShippingCost,
                        ShipTax_Cost as RealShipTaxCost,
                        Client_Bill_Name as RealBillName,
                        Client_Ship_Address1 as RealShipAddress1,
                        Client_Bill_Address2 as RealBillAddress2,
                        Client_Bill_Phone as RealBillPhone,
                        Client_Bill_Email as RealBillEmail,
                        Client_Ship_Name as RealShipName,
                        Client_Ship_Address2 as RealShipAddress2,
                        Client_Ship_Phone as RealShipPhone,
                        Client_Ship_Email as RealShipEmail,
                        Client_Ship_City as RealShipCity,
                        Client_Ship_State as RealShipState,
                        Client_Ship_ZipCode as RealShipZipCode,
                        '' as RealTrackingNumber,
                        PickupBy as RealPickupBy,
                        PickupDateTimeFrom as RealPickupDateTimeFrom,
                        PickedupBy as RealPickedupBy,
                        PickedupDateTime as RealPickedupDateTime,
                        Client_Bill_City as RealClientBillCity,
                        Client_Bill_State as RealClientBillState,
                        Client_Bill_ZipCode as RealClientBillZipCode,
                        PickupDateTimeTo as RealPickupDateTimeTo
                        FROM Grocery.Client_Order_Master
                        $where_gb_text
                        ";
                        #ORDER BY Order_Date DESC
                         
                $gb_sql = "SELECT SUM(Order_Total) as order_total, COUNT(Client_Order_ID) as total_row
                        FROM Grocery.Client_Order_Master
                        $where_gb_text"; 

                $gb_result = sqlsrv_query($conn, $gb_sql);
                $gb_raw = sqlsrv_fetch_object($gb_result); #print_r($gb_raw);
                $order_total = $order_total + $gb_raw->order_total; 
                $total_row = $total_row + $gb_raw->total_row;
            }
            #die;

            #echo '<pre>'; print($sql); die;

            $sql_string = implode(" UNION ", $sql);
            $html = '';

            if( !empty($sql_string) )
            {
                $orderData = "SELECT RealOrderDate, RealOrderID, RealOrderTotal, MarketPlace, is_frozen, is_pickup_from_store, RealShippingCost, RealShipTaxCost, RealBillName, RealShipAddress1, RealBillAddress2, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress2, RealShipPhone, RealShipEmail, RealShipCity, RealShipState, RealShipZipCode, RealTrackingNumber, RealPickupBy, RealPickupDateTimeFrom, RealPickedupBy, RealPickedupDateTime, RealClientBillCity, RealClientBillState, RealClientBillZipCode, RealPickupDateTimeTo FROM (  $sql_string ) results ORDER BY RealOrderDate DESC 
                    OFFSET $start ROWS 
                    FETCH NEXT $perpage ROWS ONLY";
                #echo $orderData; die;

                $result = sqlsrv_query($conn, $orderData);
                $grandTotal = 0;
                $count = 0;

                while ($raw = sqlsrv_fetch_object($result)) {

                    $count++;

                    if( $count > $perpage )
                    {
                        break;
                    }

					$frozen_text = $this->frozenText( $raw->is_frozen );

                    if( $raw->is_pickup_from_store == '1' )
                    {
                        $pickup_text = 'Pickup';
                    } 
                    else if( $raw->is_pickup_from_store == '0' )
                    {
                        $pickup_text = 'Shipping';
                    }
                    else
                    {
                        $pickup_text = '';
                    }

                    if( $raw->RealOrderDate )
                    {
                        $raw->RealOrderDate = $this->dateTimeFormat( $raw->RealOrderDate );
                    }  
                    $pick_dt = $this->dateTimeFormat( $raw->RealPickedupDateTime );

                    $realPickedupDateTime = ''; 
                    if( $raw->RealPickedupDateTime &&  $pick_dt != '01-Jan-1900 00:00 AM' )
                    {
                        $realPickedupDateTime = $pick_dt; 
                    } 
                    $grandTotal = $grandTotal + $raw->RealOrderTotal;

                    $html .= "<tr>
                    <td><div class=form-check style=margin-bottom:0px;margin-top:0px;><input class='order-row' type=checkbox value=".$raw->RealOrderID." id='order-".$raw->RealOrderID."' data-site='".$raw->MarketPlace."' data-clientOrderIDText='" . $raw->RealOrderID . " ".$frozen_text."'></div></td>
                    <td style=text-align:center;>".$raw->RealOrderDate."</td>";

                    if($raw->MarketPlace == 'AMAZON')
                    {
                        $html .= "<td style=text-align:center;>
                        <img src='".url('/images/AmazonMarket.png')."' style='display:none;'>
                        <div class='am_label'>Amazon</div>
                        </td>"; 
                        $website = 'Amazon'; // Do not change this text, this is using in show desc popup if condition
                    }
                    else if ($raw->MarketPlace == 'GBA')
                    {
                        $html .= "<td style=text-align:center;>
                        <img src='".url('/images/BabuMarket.gif')."' style='display:none;'>
                        <div class='gb_label'>GroceryBabu</div>
                        </td>"; 
                        $website = 'GroceryBabu';  // Do not change this text, this is using in show desc popup if condition
                    }
                    else 
                    {
                        $html .= "<td></td>";
                        $website = ''; 
                    }

                    /*$html .= '<td>' . $raw->RealOrderID . " ".$frozen_text."</td>
                    <td style='text-align:right;'>" . $raw->RealOrderTotal . "</td>
                    <td><button class='btn btn-outline-primary showDesc' data-id='" . $raw->RealOrderID . "' data-clientOrderIDText='" . $raw->RealOrderID . " ".$frozen_text."' data-orderDate='" . $raw->RealOrderDate . "' data-orderFrom='" . $website . "' data-itemTotal='" . $raw->RealItemTotal . "' data-tax='" . $raw->RealShipTaxCost . "' data-shipping='" . $raw->RealShippingCost . "' data-orderTotal='" . $raw->RealOrderTotal . "' data-billName='" . $raw->RealBillName . "' data-billAddress='" . $raw->RealShipAddress1 . ' ' . $raw->RealBillAddress2 . "' data-billPhone='" . $raw->RealBillPhone . "' data-billEmail='" . $raw->RealBillEmail . "' data-shipName='" . $raw->RealShipName . "' data-shipAddress='" . $raw->RealShipAddress1 . ' ' . $raw->RealShipAddress2 . "' data-shipPhone='" . $raw->RealShipPhone . "' data-shipEmail='" . $raw->RealShipEmail . "'>Show Desc</button></td>
                    <td class='checkbox-orderId' style='display: none;'>" . $raw->RealOrderID . '</td>
                    </tr>';*/

                    #echo '<pre>'; print_r($raw); die;

                    $raw->RealPickupDateTimeFrom = $raw->RealPickupDateTimeFrom . ' To ' . $raw->RealPickupDateTimeTo;

                    $html .= '<td style=text-align:right;>' . $raw->RealOrderID ."</td>";
                    $html .= '<td style=text-align:center;>' . $frozen_text."</td>
                    <td style='text-align:right;'>" . $raw->RealOrderTotal . "</td>
                    <td style=text-align:center;>" . $pickup_text . "</td>"; //$page_code

                    if( $page_code == 'picking' )
                    {
                        $html .= '<td style=text-align:center;><button class="btn btn-outline-primary" data-id="' . $raw->RealOrderID . '" onclick="openVoidOrder(' . $raw->RealOrderID . ', '."'$frozen_text'".',  '."'$raw->MarketPlace'".')">Refund</button></td>
                        <td style=text-align:center;><button class="btn btn-outline-primary showDesc">Modify</button></td>
                        <td style=text-align:center;><button class="btn btn-outline-primary showDesc">Make Order</button></td>';
                    }

                    $html .= "<td style='text-align:center;'>
                    <button title='Show Description' class='btn btn-outline-primary showDesc' 
                    id='div_" .                 $raw->RealOrderID . "'  
                    data-site='" .              $raw->MarketPlace."'
                    data-id='" .                $raw->RealOrderID . "' 
                    data-clientOrderIDText='" . $raw->RealOrderID . " ".$frozen_text."' 
                    data-orderDate='" .         $raw->RealOrderDate . "' 
                    data-orderFrom='" .         $website . "' 
                    data-itemTotal='' 
                    data-tax='" .               $raw->RealShipTaxCost . "' 
                    data-shipping='" .          $raw->RealShippingCost . "' 
                    data-orderTotal='" .        $raw->RealOrderTotal . "' 
                    data-billName='" .          $raw->RealBillName . "' 
                    data-billAddress='" .       $raw->RealShipAddress1 . " " . $raw->RealBillAddress2 . "'
                    data-billPhone='" .         $raw->RealBillPhone . "' 
                    data-billEmail='" .         $raw->RealBillEmail . "' 
                    data-billingCity='" .       $raw->RealClientBillCity . "' 
                    data-billingState='" .      $raw->RealClientBillState . "' 
                    data-billingZipCode='" .    $raw->RealClientBillZipCode . "' 
                    data-shipName='" .          $raw->RealShipName . "' 
                    data-shipAddress='" .       $raw->RealShipAddress1 . " " . $raw->RealShipAddress2 . "'
                    data-shipPhone='" .         $raw->RealShipPhone . "' 
                    data-shipEmail='" .         $raw->RealShipEmail . "' 
                    data-shippingCity='" .      $raw->RealShipCity . "' 
                    data-shippingState='" .     $raw->RealShipState . "' 
                    data-shippingZipCode='" .   $raw->RealShipZipCode . "' 
                    data-shippingTrackingNumber='" . $raw->RealTrackingNumber . "' 
                    data-isPickupStore='" .     $raw->is_pickup_from_store . "'
                    data-RealPickupBy='" .      $raw->RealPickupBy . "'
                    data-RealPickupDateTimeFrom='" . $raw->RealPickupDateTimeFrom . "'
                    data-RealPickedupBy='" .    $raw->RealPickedupBy . "'
                    data-RealPickedupDateTime='" . $realPickedupDateTime . "'>
                    <i class='fa fa-info-circle'></i></button></td>
                    <td class='checkbox-orderId' style='display: none;'>" . $raw->RealOrderID . '</td>
                    </tr>';
                }
            }
        }
 
        $order_total = $this->currencyFormat( $order_total, 1 );
        $paging_html = '<ul class="pagination d-flex justify-content-center  pagination-danger">';
        if( $total_row > $perpage )
        {
            $number_of_page = ceil ($total_row / $perpage);  
            if( $page != 1 )
            {
                $prev = $page - 1;
                $paging_html .= '<li class="page-item"><a class="page-link" href="javascript:load_data('.$prev.');"><i class="mdi mdi-chevron-left"></i></a></li>';
                $paging_html .= '<li class="page-item"><a class="page-link" href="javascript:load_data(1);">1</a></li>';
                #$paging_html .= '<label style="float: left;margin-right: 5px;">..</label>';
            }
            $paging_html .= '<li class="page-item active"><a class="page-link" href="javascript:load_data('.$page.');">'.$page.'</a></li>';
            if( $page != $number_of_page )
            {
                $next = $page + 1;
                #$paging_html .= '<label style="float: left;margin-right: 5px;">..</label>';
                $paging_html .= '<li class="page-item"><a class="page-link" href="javascript:load_data('.$number_of_page.');">'.$number_of_page.'</a></li>';
                $paging_html .= '<li class="page-item"><a class="page-link" href="javascript:load_data('.$next.');"><i class="mdi mdi-chevron-right"></i></a></li>';
            }
        }
        $paging_html .= '</ul>';

        $response = array('html' => $html, 'order_total' => $order_total, 'paging_html' => $paging_html);
        echo json_encode($response);
    }
	
	public function frozenText( $is_frozen )
	{
		if( $is_frozen == '1' )
		{
			$frozen_text = 'Frozen';
		}
        if( $is_frozen == '2' )
        {
            $frozen_text = 'Cooler';
        }
		else 
		{
			$frozen_text = 'Dry';
		} 
		return $frozen_text;
	}


    public function order_details( Request $request ) {

        $order_id   = $request->input('order_id');
        $order_from = $request->input('order_from');

        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $item_html = "";
        if( $order_from == 'Amazon' )
        {
            $orderData = "SELECT ItemName, MarketItemID, ItemQuantity, ItemPrice, ItemPriceTotal FROM Grocery.MarketOrderDetailMaster WHERE MarketOrderID='$order_id' ORDER BY MarketOrderDetailID ASC";
        }
        else 
        {
            $orderData = "SELECT Item_Name as ItemName, Item_ID as MarketItemID, Item_Quantity as ItemQuantity, Item_Price as ItemPrice, '' as ItemPriceTotal FROM Grocery.Client_Order_Detail_Master WHERE Client_Order_ID='$order_id' ORDER BY Client_Order_Detail_ID ASC";
        }

        $result = sqlsrv_query($conn, $orderData);

        $item_total = 0;

        while ($raw = sqlsrv_fetch_object($result)) {
            if( $order_from != 'Amazon' )
            {
                $raw->ItemPriceTotal = $raw->ItemPrice * $raw->ItemQuantity;
                $raw->ItemPriceTotal = $this->currencyFormat( $raw->ItemPriceTotal );
            }
            $item_total = $item_total + $raw->ItemPriceTotal;
            $item_html .= "<tr><td>". $raw->MarketItemID ."</td><td>". $raw->ItemName ."</td><td class=ta_right>". $raw->ItemQuantity ."</td><td class=ta_right>". $raw->ItemPrice ."</td><td class=ta_right>". $raw->ItemPriceTotal ."</td></tr>";
        }

        $item_total = $this->currencyFormat($item_total, 1);

        $response = array('html' => $item_html, 'item_total' => $item_total);

        echo json_encode($response);
    }

    public function currencyFormat( $amount, $curSymbol = 0 )
    {
        $text = number_format($amount, 2);
        if( $curSymbol == 1 )
        {
            return "$ $text";
        }
        return $text;
    }

    public function dateTimeFormat( $dateTime )
    {
        #echo '<pre>';
        #print_r($dateTime);
        #print_r($dateTime->format("Y-m-d H:i:s"));

        if( !$dateTime )
        {
            return '';
        }

        $formated_date = date( $this->date_format , strtotime( $dateTime->format("Y-m-d H:i:s") . ' -5 hours'));
        #print_r($formated_date); die;

        return $formated_date;
    }


    public function printOrders( Request $request ) {

        $orderJson   = $request->input('orderJson');
        $view_type   = $request->input('view_type');
        $orderJson   = json_decode($orderJson);

        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
			
		$item_html        = '';
		$total_weight     = 0;
		$total_qty        = 0;
		$order_title      = array();
		$order_table      = '';
		$item_final_total = 0;
        $am_order_ids     = array();
        $gb_order_ids     = array();

        foreach ($orderJson as $orderJsonData) {
			
			$total_order_qty = 0;
			$order_items     = '';
		
            if( $orderJsonData->site == 'AMAZON' )
            {
                $am_order_ids[] = $orderJsonData->order_id;
				$a_order = "SELECT 
				OrderDate, PaymentDate, OrderState, PaymentStatus, ClientBillName, ClientBillPhone, ClientBillEmail, ClientBillAddress1, ClientShipName, ClientShipPhone, ClientShipEmail, ClientShipAddress1, 0 as IsPickupFromStore, ShipTaxCost, ShippingCost, OrderTotal
				FROM Grocery.MarketOrderMaster
				WHERE MarketOrderID='$orderJsonData->order_id'";

                $a_order_result = sqlsrv_query($conn, $a_order);
				$g_order_raw = sqlsrv_fetch_object($a_order_result);
				
				$frozen_text = $this->frozenText( 0 );
				
                $a_orderData = "SELECT 
				modm.ItemName, modm.MarketItemID, modm.ItemQuantity, modm.ItemPrice, modm.ItemPriceTotal, IM.Brand_name, modm.ItemWeight, modm.ItemPriceTotal
				FROM Grocery.MarketOrderDetailMaster modm
				LEFT JOIN Grocery.Item_Master IM ON IM.Item_#=modm.MarketItemID
				WHERE modm.MarketOrderID='$orderJsonData->order_id' 
				ORDER BY modm.MarketOrderDetailID ASC";

                $a_result = sqlsrv_query($conn, $a_orderData);

                while ($a_raw = sqlsrv_fetch_object($a_result)) {
					
					$total_weight     = $total_weight + $a_raw->ItemWeight;
					$total_order_qty  = $total_order_qty + $a_raw->ItemQuantity;
					$total_qty        = $total_qty + $total_order_qty;
					$item_final_total = $item_final_total + $a_raw->ItemPriceTotal;
					
                    $item_html .= '<tr>
					<td align="left">' . $a_raw->MarketItemID . '</td>
					<td align="left">' . $a_raw->ItemName . '</td>
					<td align="left">' . $a_raw->Brand_name . '</td>
					<td align="right">' . $a_raw->ItemQuantity . '</td>
					<td align="right">' . $a_raw->ItemWeight . '</td>
					</tr>';
					
					$order_items .= '<tr>
						<td align="left">' . $a_raw->MarketItemID . '</td>
						<td align="left">' . $a_raw->ItemName . '</td>
						<td align="left">' . $a_raw->Brand_name . '</td>
						<td align="left">' . $a_raw->ItemWeight . '</td>
						<td align="left">' . $a_raw->ItemQuantity . '</td>
						<td align="left">' . $a_raw->ItemPrice . '</td>
						<td align="left">' . $a_raw->ItemPriceTotal . '</td>
					</tr>';
                } 
            }
			
            else 
            {
                $gb_order_ids[] = $orderJsonData->order_id;
				$g_order = "SELECT 
				Order_Date as OrderDate, Payment_Date as PaymentDate,Order_State as OrderState, Payment_Status as PaymentStatus, Client_Bill_Name as ClientBillName, PickupBy as PickupBy, Client_Bill_Phone as ClientBillPhone, PickupDateTimeTo as PickupDateTimeTo, PickupDateTimeFrom as PickupDateTimeFrom, Client_Bill_Email as ClientBillEmail, PickedupBy as PickedupBy, Client_Bill_Address1 as ClientBillAddress1, PickedupDateTime as PickedupDateTime, Client_Ship_Name as ClientShipName, Client_Ship_Phone as ClientShipPhone, Client_Ship_Email as ClientShipEmail, Client_Ship_Address1	 as ClientShipAddress1, IsPickupFromStore, ShipTax_Cost as ShipTaxCost, Shipping_Cost as ShippingCost, Order_Total as OrderTotal, IsFrozen
				FROM Grocery.Client_Order_Master
				WHERE Client_Order_ID='$orderJsonData->order_id'";

                $g_order_result = sqlsrv_query($conn, $g_order);
				$g_order_raw    = sqlsrv_fetch_object($g_order_result);
				
				$frozen_text = $this->frozenText( $g_order_raw->IsFrozen );
				
                $a_orderData = "SELECT 
				codm.Item_Name as ItemName, codm.Item_ID as MarketItemID, codm.Item_Quantity as ItemQuantity, codm.Item_Price as ItemPrice, IM.Brand_name, codm.Item_Weight as ItemWeight
				FROM Grocery.Client_Order_Detail_Master codm
				LEFT JOIN Grocery.Item_Master IM ON IM.Item_#=codm.Item_ID
				WHERE codm.Client_Order_ID='$orderJsonData->order_id' 
				ORDER BY codm.Client_Order_Detail_ID ASC";

                $a_result = sqlsrv_query($conn, $a_orderData);

                while ($g_raw = sqlsrv_fetch_object($a_result)) {
					
					$total_weight    = $total_weight + $g_raw->ItemWeight;
					$total_order_qty = $total_order_qty + $g_raw->ItemQuantity;
					$total_qty       = $total_qty + $total_order_qty;
					$ItemPriceTotal  = $g_raw->ItemPrice * $total_qty;
					$item_final_total = $item_final_total + $ItemPriceTotal;
					
                    $item_html .= '
					<tr>
						<td align="left">' . $g_raw->MarketItemID . '</td>
						<td align="left">' . $g_raw->ItemName . '</td>
						<td align="left">' . $g_raw->Brand_name . '</td>
						<td align="right">' . $g_raw->ItemQuantity . '</td>
						<td align="right">' . $g_raw->ItemWeight . '</td>
					</tr>';
					
					$order_items .= '<tr>
						<td align="left">' . $g_raw->MarketItemID . '</td>
						<td align="left">' . $g_raw->ItemName . '</td>
						<td align="left">' . $g_raw->Brand_name . '</td>
						<td align="left">' . $g_raw->ItemWeight . '</td>
						<td align="left">' . $g_raw->ItemQuantity . '</td>
						<td align="left">' . $g_raw->ItemPrice . '</td>
						<td align="left">' . $ItemPriceTotal . '</td>
					</tr>';
                } 
            }
			
			$order_title_string = "$orderJsonData->order_id - $frozen_text";
			$order_title[]      = $order_title_string;
			
			if( $g_order_raw->IsPickupFromStore == '1')
			{
				$shipping_html    = '<table width="100%" class="print-table">
<tr>
<th align="left">Order Date:</th><td align="left">' .  $this->dateTimeFormat( $g_order_raw->OrderDate ) . '</td>
<th align="left">Payment Date:</th><td align="left">' . $this->dateTimeFormat( $g_order_raw->PaymentDate ) . '</td></tr>
<tr>
<th align="left">Order State:</th><td align="left">' . $g_order_raw->OrderState . '</td>
<th align="left">Payment Status:</th><td align="left">' . $g_order_raw->PaymentStatus . '</td>
</tr>
<tr>
<th align="left">Billing Name:</th><td align="left">' . $g_order_raw->ClientBillName . '</td>
<th align="left">Pickup By:</th><td align="left">' . $g_order_raw->PickupBy . '</td>
</tr>
<tr>
<th align="left">Billing Phone:</th><td align="left">' . $g_order_raw->ClientBillPhone . '</td>
<th align="left">Pickup From Date:</th><td align="left">' . $g_order_raw->PickupDateTimeTo . ' To ' . $g_order_raw->PickupDateTimeFrom . '</td>
</tr>
<tr>
<th align="left">Billing Email:</th><td align="left">' . $g_order_raw->ClientBillEmail . '</td>
<th align="left">Pickped By:</th><td align="left">' . $g_order_raw->PickedupBy . '</td>
</tr>
<tr>
<th align="left">Billing Address:</th><td align="left">' . $g_order_raw->ClientBillAddress1 . '</td>
<th align="left">Pickped Date:</th><td align="left">' . $this->dateTimeFormat( $g_order_raw->PickedupDateTime ) . '</td>
</tr>
</table>';
			}	
			else 
			{
				$shipping_html    = '<table width="100%" class="print-table">
<tr>
<th align="left">Order Date:</th><td align="left">' . $this->dateTimeFormat( $g_order_raw->OrderDate ) . '</td>
<th align="left">Payment Date:</th><td align="left">' . $this->dateTimeFormat( $g_order_raw->PaymentDate ) . '</td></tr>
<tr>
<th align="left">Order State:</th><td align="left">' . $g_order_raw->OrderState . '</td>
<th align="left">Payment Status:</th><td align="left">' . $g_order_raw->PaymentStatus . '</td>
</tr>
<tr>
<th align="left">Billing Name:</th><td align="left">' . $g_order_raw->ClientBillName . '</td>
<th align="left">Shipping Name:</th><td align="left">' . $g_order_raw->ClientShipName . '</td>
</tr>
<tr>
<th align="left">Billing Phone:</th><td align="left">' . $g_order_raw->ClientBillPhone . '</td>
<th align="left">Shipping Phone:</th><td align="left">' . $g_order_raw->ClientShipPhone . '</td>
</tr>
<tr>
<th align="left">Billing Email:</th><td align="left">' . $g_order_raw->ClientBillEmail . '</td>
<th align="left">Shipping Email:</th><td align="left">' . $g_order_raw->ClientShipEmail . '</td>
</tr>
<tr>
<th align="left">Billing Address:</th><td align="left">' . $g_order_raw->ClientBillAddress1 . '</td>
<th align="left">Shipping Address:</th><td align="left">' . $g_order_raw->ClientShipAddress1 . '</td>
</tr>
</table>';
			}			
			
			$order_table .= '<div class="page-break"></div>
		<table align="center" class="order-aggregate-report print-table"><tr><td align="center">GroceryBabu.Com Order # ' . $order_title_string . '</td></tr></table> 
		' . $shipping_html . '
		<table border="1" cellspacing="0" cellpadding="9" width="100%" class="print-table">
			<tr>
				<th align="left">Item#</th>
				<th align="left">Item Name</th>
				<th align="left">Brand</th>
				<th align="left">Total Weight(lb)</th>
				<th align="left">Quantity</th>
				<th align="left">Unit Price($)</th>
				<th align="left">Unit Total($)</th>
			</tr>
			' . $order_items . '
		</table> 
		<table align="center" width="100%">
			<tr><td>
		<table align="right" class="print-table">
			<tr><th align="left">Total Quantity:</th><td align="center"></td><td align="left">' . $total_qty . '</td></tr>
			<tr><th align="left">Item Total:</th><td align="center">$</td><td align="left">' . $item_final_total . '</td></tr>
			<tr><th align="left">Tax:</th><td align="center">$</td><td align="left">' . $g_order_raw->ShipTaxCost . '</td></tr>
			<tr><th align="left">Shipping:</th><td align="center">$</td><td align="left">' . $g_order_raw->ShippingCost . '</td></tr>
			<tr><th align="left">Order Total:</th><td align="center">$</td><td align="left">' . $g_order_raw->OrderTotal . '</td></tr>
		</table>
		</td></tr></table>
		</div>';
			
        }
		
		$order_title_str = implode(", ", $order_title);
		$report_date = date('d-M-Y h:i: a');

        $order_aggregate_report = '';
        if( $view_type == $this->multiple_val )  
        {
            if( !empty($gb_order_ids) )
            {
                $gb_order_string = implode(',', $gb_order_ids);
                $gb_update_sql    = "UPDATE Grocery.Client_Order_Master SET Order_State='' WHERE Client_Order_ID IN ($gb_order_string)"; 
                sqlsrv_query($conn, $gb_update_sql);
            }
            if( !empty($am_order_ids) )
            {
                $am_order_string = implode(',', $am_order_ids);
                $am_update_sql    = "UPDATE Grocery.Client_Order_Master SET Order_State='' WHERE Client_Order_ID IN ($am_order_string)"; 
                sqlsrv_query($conn, $am_update_sql);
            }

            $order_aggregate_report = '<table align="center" class="order-aggregate-report print-table"><tr><td align="center">Order Aggregate Report</td></tr></table> 
        <table>
            <tr><th align="left">Report Date:</th><td align="left" id="report_date">'.$report_date.'</td></tr>
            <tr><th align="left">Order #:</th><td align="left" id="orders_data">'.$order_title_str.'</td></tr>
        </table> 
        <table border="1" cellspacing="0" cellpadding="9" width="100%" id="combine_products" class=" print-table">
        <thead>
            <tr><th align="left">Item#</th><th align="left">Item Name</th><th align="left">Brand</th><th align="center">Quantity</th><th align="center">Total Weight (lb)</th></tr>
            </thead>
            <tbody>
            ' . $item_html . '
            </tbody>
        </table> 
        <table align="center" width="100%">
            <tr><td>
        <table align="right" class="print-table">
            <tr><th align="left">Total Quantity:</th><td align="left" id="total_qty">'.$total_qty.'</td></tr>
            <tr><th align="left">Total Weight (lb):</th><td align="left" id="total_weight">'.$total_weight.'</td></tr>
        </table>
        </td></tr></table>';
        }
		
		echo '
		<style>
		@media print { 
            .page-break { display: block; page-break-before: always; } 
            .order-aggregate-report { background:#c0c0c0;width:400px; margin-bottom:20px; }
            .print-table td, .print-table th { 
                padding-top:13px;
                padding-bottom:13px; 
                font-size:20px;
            }
        }
        </style>' . $order_aggregate_report . $order_table ; exit;
    }


    public function updatePickingOrders( Request $request ) {

        $order_ids   = $request->input('order_ids');
        $orderJson   = json_decode($orderJson);

        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    }

    public function newOrder() {
        /*$connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $newOrder = array();
        $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
        if (isset($timezoneId[0])) {
            $orderData = "Select 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,
                RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,
                OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from (Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost,
                COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone,
                COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  ,
                (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock),
                Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('INITIATED')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal,
                ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,
                MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,
                '' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('INITIATED')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,
                MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,
                MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,
                0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('INITIATED','SHIPPED')  ) as GBA Order By GBA.RealOrderDate Asc";
            $result = sqlsrv_query($conn, $orderData);
            $grandTotal = 0;
            while ($raw = sqlsrv_fetch_object($result)) {
                $raw->RealOrderDate = $raw->RealOrderDate->format($this->date_format);
                $raw->RealPaymentDate = !empty($raw->RealPaymentDate) ? $raw->RealPaymentDate->format($this->date_format) : '' ;
                $raw->RealShipDate = !empty($raw->RealShipDate) ? $raw->RealShipDate->format($this->date_format) : '' ;
                $grandTotal = $grandTotal + $raw->RealOrderTotal;
                array_push($newOrder, $raw);
            }
        }*/
        #return view('order.neworder', compact('newOrder', 'grandTotal'));

        $isFirstTime = \Session::get('isFirstTime');
        if( $isFirstTime != 1 )
        {
            \Session::put('isFirstTime', 1);
        }

        return view('order.neworder', compact('isFirstTime') );
    }

    public function pickingOrder() { 

        /*die;
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $packingOrder = array();
        $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
        if (isset($timezoneId[0])) {
            $orderData = "Select 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity,"
                    . " RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,"
                    . "OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,"
                    . "  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,"
                    . "  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid ,"
                    . " IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('INPROCESS')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate,"
                    . " MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService,"
                    . " MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity,"
                    . " MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,"
                    . "'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('INPROCESS')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal,"
                    . " ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState,"
                    . " MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,"
                    . "CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('INPROCESS')  ) as GBA Order By GBA.RealOrderDate Asc";
            $result = sqlsrv_query($conn, $orderData);
            $grandTotal = 0;
            while ($raw = sqlsrv_fetch_object($result)) {
                $raw->RealOrderDate = $this->dateTimeFormat( $raw->RealOrderDate );
                $raw->RealPaymentDate = !empty($raw->RealPaymentDate) ? $this->dateTimeFormat( $raw->RealPaymentDate ) : '' ;
                $raw->RealShipDate = !empty($raw->RealShipDate) ? $this->dateTimeFormat( $raw->RealShipDate ) : '' ;
                $grandTotal = $grandTotal + $raw->RealOrderTotal;
                array_push($packingOrder, $raw);
            }
        }*/
        $order_data = '';
        return view('order.pickingorder', compact('order_data'));
    }


    public function void_order( Request $request ) {

        $order_id    = $request->input('order_id');
        $void_reason = $request->input('void_reason');
        $order_from  = $request->input('order_from');

        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $item_html = "";
        if( $order_from == 'Amazon' )
        {
            $orderData = "SELECT ItemName, MarketItemID, ItemQuantity, ItemPrice, ItemPriceTotal FROM Grocery.MarketOrderDetailMaster WHERE MarketOrderID='$order_id' ORDER BY MarketOrderDetailID ASC";
        }
        else 
        {
            $orderData = "SELECT Item_Name as ItemName, Item_ID as MarketItemID, Item_Quantity as ItemQuantity, Item_Price as ItemPrice, '' as ItemPriceTotal FROM Grocery.Client_Order_Detail_Master WHERE Client_Order_ID='$order_id' ORDER BY Client_Order_Detail_ID ASC";
        }

        $result = sqlsrv_query($conn, $orderData);

        $item_total = 0;

       /* while ($raw = sqlsrv_fetch_object($result)) {
            if( $order_from != 'Amazon' )
            {*/

        echo '<pre>'; print_r($_REQUEST); die;

    }

    public function verifyOrder() {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $verifyOrder = array();
        $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
        if (isset($timezoneId[0])) {
            $orderData = "Select 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('MAKEORDER','PROCESSED')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('MAKEORDER','PROCESSED')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('MAKEORDER','PROCESSED')  ) as GBA Order By GBA.RealOrderDate Asc";
            $result = sqlsrv_query($conn, $orderData);
            $grandTotal = 0;
            while ($raw = sqlsrv_fetch_object($result)) {
                $raw->RealOrderDate = $this->dateTimeFormat( $raw->RealOrderDate );
                $raw->RealPaymentDate = !empty($raw->RealPaymentDate) ? $this->dateTimeFormat( $raw->RealPaymentDate ) : '' ;
                $raw->RealShipDate = !empty($raw->RealShipDate) ? $this->dateTimeFormat( $raw->RealShipDate ) : '' ;
                $grandTotal = $grandTotal + $raw->RealOrderTotal;
                array_push($verifyOrder, $raw);
            }
        }
        return view('order.verifyorder', compact('verifyOrder', 'grandTotal'));
    }

    public function finishOrder() {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $finishOrder = array();
        $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
        if (isset($timezoneId[0])) {
            $orderData = "Select Top 1000 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('SHIPPED')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('SHIPPED')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('SHIPPED')  ) as GBA Order By GBA.RealOrderDate Desc";
            $result = sqlsrv_query($conn, $orderData);
            $grandTotal = 0;
            while ($raw = sqlsrv_fetch_object($result)) {
                $raw->RealOrderDate = $raw->RealOrderDate->format($this->date_format);
                $raw->RealPaymentDate = !empty($raw->RealPaymentDate) ? $raw->RealPaymentDate->format($this->date_format) : '' ;
                $raw->RealShipDate = !empty($raw->RealShipDate) ? $raw->RealShipDate->format($this->date_format) : '' ;
                $grandTotal = $grandTotal + $raw->RealOrderTotal;
                array_push($finishOrder, $raw);
            }
        }
        return view('order.finishorder', compact('finishOrder', 'grandTotal'));
    }

    public function voidOrder() {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $voidOrder = array();
        $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
        if (isset($timezoneId[0])) {
            $orderData = "Select 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('VOID')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('VOID')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('VOID')  ) as GBA Order By GBA.RealOrderDate Desc";
            $result = sqlsrv_query($conn, $orderData);
            $grandTotal = 0;
            while ($raw = sqlsrv_fetch_object($result)) {
                $raw->RealOrderDate = $raw->RealOrderDate->format($this->date_format);
                $raw->RealPaymentDate = !empty($raw->RealPaymentDate) ? $raw->RealPaymentDate->format($this->date_format) : '' ;
                $raw->RealShipDate = !empty($raw->RealShipDate) ? $raw->RealShipDate->format($this->date_format) : '' ;
                $grandTotal = $grandTotal + $raw->RealOrderTotal;
                array_push($voidOrder, $raw);
            }
        }
        return view('order.voidorder', compact('voidOrder', 'grandTotal'));
    }

    public function allOrder() {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $allOrder = array();
        $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
        if (isset($timezoneId[0])) {
            $orderData = "Select Top 1000 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  ) as GBA Order By GBA.RealOrderDate Desc";
            $result = sqlsrv_query($conn, $orderData);
            $grandTotal = 0;
            while ($raw = sqlsrv_fetch_object($result)) {
                $raw->RealOrderDate = $raw->RealOrderDate->format($this->date_format);
                $raw->RealPaymentDate = !empty($raw->RealPaymentDate) ? $raw->RealPaymentDate->format($this->date_format) : '' ;
                $raw->RealShipDate = !empty($raw->RealShipDate) ? $raw->RealShipDate->format($this->date_format) : '' ;
                $grandTotal = $grandTotal + $raw->RealOrderTotal;
                array_push($allOrder, $raw);
            }
        }
        return view('order.allorder', compact('allOrder', 'grandTotal'));
    }
    
    public function neworderDesc(Request $request) {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $orderDetails = array();
        if ($request->ajax()) {
            $post = $request->all();
            $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
            if (isset($timezoneId[0])) {
                $orderDesc = "Select Top 1 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,
                RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,
                OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from (Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost,
                COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone,
                COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  ,
                (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock),
                Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('INITIATED')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal,
                ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,
                MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,
                '' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('INITIATED')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,
                MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,
                MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,
                0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('INITIATED')  ) as GBA WHERE GBA.RealOrderID = '" . $post['orderId'] . "' Order By GBA.RealOrderDate Asc";
                $result = sqlsrv_query($conn, $orderDesc);
                $data = sqlsrv_fetch_object($result);
                $data->RealOrderDate = $data->RealOrderDate->format($this->date_format);
                $data->RealPaymentDate = !empty($data->RealPaymentDate) ? $data->RealPaymentDate->format($this->date_format) : '';
                $data->RealShipDate = !empty($data->RealShipDate) ? $data->RealShipDate->format($this->date_format) : '';
                array_push($orderDetails, $data);
            }
            return $orderDetails;
        }
    }
    
    public function pickingorderdesc(Request $request) {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $orderDetails = array();
        if ($request->ajax()) {
            $post = $request->all();
            $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
            if (isset($timezoneId[0])) {
                $orderDesc = "Select Top 1 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity,"
                    . " RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,"
                    . "OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,"
                    . "  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,"
                    . "  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid ,"
                    . " IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('INPROCESS')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate,"
                    . " MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService,"
                    . " MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity,"
                    . " MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,"
                    . "'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('INPROCESS')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal,"
                    . " ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState,"
                    . " MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,"
                    . "CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('INPROCESS')  ) as GBA WHERE GBA.RealOrderID = '" . $post['orderId'] . "' Order By GBA.RealOrderDate Asc";
                $result = sqlsrv_query($conn, $orderDesc);
                $data = sqlsrv_fetch_object($result);
                $data->RealOrderDate = $data->RealOrderDate->format($this->date_format);
                $data->RealPaymentDate = !empty($data->RealPaymentDate) ? $data->RealPaymentDate->format($this->date_format) : '';
                $data->RealShipDate = !empty($data->RealShipDate) ? $data->RealShipDate->format($this->date_format) : '';
                array_push($orderDetails, $data);
            }
            return $orderDetails;
        }
    }
    public function verifyorderdesc(Request $request) {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $orderDetails = array();
        if ($request->ajax()) {
            $post = $request->all();
            $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
            if (isset($timezoneId[0])) {
                $orderDesc = "Select Top 1 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('MAKEORDER','PROCESSED')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('MAKEORDER','PROCESSED')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('MAKEORDER','PROCESSED')  ) as GBA WHERE GBA.RealOrderID = '" . $post['orderId'] . "' Order By GBA.RealOrderDate Asc";
                $result = sqlsrv_query($conn, $orderDesc);
                $data = sqlsrv_fetch_object($result);
                $data->RealOrderDate = $data->RealOrderDate->format($this->date_format);
                $data->RealPaymentDate = !empty($data->RealPaymentDate) ? $data->RealPaymentDate->format($this->date_format) : '';
                $data->RealShipDate = !empty($data->RealShipDate) ? $data->RealShipDate->format($this->date_format) : '';
                array_push($orderDetails, $data);
            }
            return $orderDetails;
        }
    }
    public function finishorderdesc(Request $request) {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $orderDetails = array();
        if ($request->ajax()) {
            $post = $request->all();
            $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
            if (isset($timezoneId[0])) {
                $orderDesc = "Select Top 1 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('SHIPPED')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('SHIPPED')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('SHIPPED')  ) as GBA WHERE GBA.RealOrderID = '" . $post['orderId'] . "' Order By GBA.RealOrderDate Desc";
                $result = sqlsrv_query($conn, $orderDesc);
                $data = sqlsrv_fetch_object($result);
                $data->RealOrderDate = $data->RealOrderDate->format($this->date_format);
                $data->RealPaymentDate = !empty($data->RealPaymentDate) ? $data->RealPaymentDate->format($this->date_format) : '';
                $data->RealShipDate = !empty($data->RealShipDate) ? $data->RealShipDate->format($this->date_format) : '';
                array_push($orderDetails, $data);
            }
            return $orderDetails;
        }
    }
    public function voidorderdesc(Request $request) {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $orderDetails = array();
        if ($request->ajax()) {
            $post = $request->all();
            $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
            if (isset($timezoneId[0])) {
                $orderDesc = "Select Top 1 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  and COM.Order_State in ('VOID')  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  and MOM.OrderState in ('VOID')  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  and MOM.OrderState in ('VOID')  ) as GBA WHERE GBA.RealOrderID = '" . $post['orderId'] . "' Order By GBA.RealOrderDate Desc";
                $result = sqlsrv_query($conn, $orderDesc);
                $data = sqlsrv_fetch_object($result);
                $data->RealOrderDate = $data->RealOrderDate->format($this->date_format);
                $data->RealPaymentDate = !empty($data->RealPaymentDate) ? $data->RealPaymentDate->format($this->date_format) : '';
                $data->RealShipDate = !empty($data->RealShipDate) ? $data->RealShipDate->format($this->date_format) : '';
                array_push($orderDetails, $data);
            }
            return $orderDetails;
        }
    }
    
    public function allorderdesc(Request $request) {
        $connectionInfo = array("Database" => env('DB_DATABASE'), "UID" => env('DB_USERNAME'), "PWD" => env('DB_PASSWORD'));
        $conn = sqlsrv_connect(env('DB_HOST'), $connectionInfo);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        $orderDetails = array();
        if ($request->ajax()) {
            $post = $request->all();
            $timezoneId = DB::select("SELECT TimeZoneId FROM sites.entity WITH(NOLOCK) WHERE seid = 1");
            if (isset($timezoneId[0])) {
                $orderDesc = DB::select("Select Top 1 0 as chkOrderPrint,MarketPlace, RealOrderID, [dbo].[GetUTCToLocal](RealOrderDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealOrderDate,OrderState, RealOrderState, [dbo].[GetUTCToLocal](RealPaymentDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealPaymentDate, RealPaymentStatus, RealBillName, RealBillAddress1, RealBillAddress2,  RealBillCity, RealBillState, RealBillZipCode, RealBillPhone, RealBillEmail, RealShipName, RealShipAddress1, RealShipAddress2,  RealShipCity, RealShipState, RealShipZipCode, RealShipPhone, RealShipEmail, RealItemTotal, RealShipTaxCost, RealSalesTaxCost, RealShippingCost, CONVERT(VARCHAR(10), RealOrderTotal) AS RealOrderTotal,  [dbo].[GetUTCToLocal](RealShipDate, '" . $timezoneId[0]->TimeZoneId . "') AS RealShipDate, RealTrackNumber, RealShipService, RealShipShipment,isFrozen ,(CASE WHEN ISNULL(IsFrozen,0) = 0 THEN '' ELSE 'Frozen' END) AS IsFrozenText,  Client_Order_ID_Text,MainOrderID,OrgOrderTotal,OrderDiffAmt,IsDiffAmtPaid,IsPickupFromStore,WebSiteName from ( Select 'GBA' as MarketPlace, Convert(varchar(25), COM.Client_Order_ID) RealOrderID, COM.Order_Date RealOrderDate, COM.Order_State OrderState, COM.Order_State RealOrderState,  COM.Payment_Date RealPaymentDate, COM.Payment_Status RealPaymentStatus,  COM.Item_Total RealItemTotal, COM.ShipTax_Cost RealShipTaxCost, COM.SalesTax_Cost RealSalesTaxCost, COM.Shipping_Cost RealShippingCost, COM.Order_Total RealOrderTotal,  COM.Order_Shipping_Date RealShipDate, COM.Order_Shipping_Info RealTrackNumber, COM.Order_Shipping_Service RealShipService, COM.Order_Shipping_Shipment RealShipShipment,  COM.Client_Bill_Name RealBillName, COM.Client_Bill_Address1 RealBillAddress1, COM.Client_Bill_Address2 RealBillAddress2,  COM.Client_Bill_City RealBillCity, COM.Client_Bill_State RealBillState, COM.Client_Bill_ZipCode RealBillZipCode,  COM.Client_Bill_Phone RealBillPhone, COM.Client_Bill_Email RealBillEmail,  COM.Client_Ship_Name RealShipName, COM.Client_Ship_Address1 RealShipAddress1, COM.Client_Ship_Address2 RealShipAddress2,  COM.Client_Ship_City RealShipCity, COM.Client_Ship_State RealShipState, COM.Client_Ship_ZipCode RealShipZipCode,  COM.Client_Ship_Phone RealShipPhone, COM.Client_Ship_Email RealShipEmail,COM.IsFrozen,COM.Client_Order_ID_Text,COM.MainOrderID,CONVERT(VARCHAR(10),COM.OrgOrderTotal) AS OrgOrderTotal  , (CASE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) WHEN '0' THEN '' ELSE (CASE WHEN ISNULL(IsDiffAmtPaid,0) = 0 AND ISNULL(IsPaymentReceived,0) = 1 THEN CAST(ABS((Order_Total - ISNULL(OrgOrderTotal,0))) AS VARCHAR(10)) ELSE '' END) END)  AS OrderDiffAmt,ISNULL(IsDiffAmtPaid,0) AS IsDiffAmtPaid , IsPickupFromStore,WebSiteName from Grocery.Client_Order_Master COM With(NoLock), Grocery.Client_Order_Detail_Master CODM With(NoLock), Grocery.Client_Order_Trade_Master COTM with(nolock)  where COM.Client_Order_ID = CODM.Client_Order_ID and COM.Client_Order_ID = COTM.Client_Order_ID  Union  Select 'EBAY' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.EbayOrderID is not null  Union  Select 'AMAZON' as MarketPlace, MOM.OrderID RealOrderID, MOM.OrderDate RealOrderDate,MOM.OrderState OrderState, MOM.OrderState RealOrderState,  MOM.PaymentDate RealPaymentDate, MOM.PaymentStatus RealPaymentStatus,  MOM.ItemTotal RealitemTotal, ShipTaxCost RealShipTaxCost, MOM.SalesTaxCost RealSalesTaxCost, MOM.ShippingCost RealShippingCost, MOM.OrderTotal RealOrderTotal,  MOM.OrderShippingDate RealShipDate, MOM.TrackingNumber RealTrackNumber, MOM.OrderShippingService RealShipService, MOM.OrderShippingShipment RealShipShipment,  MOM.ClientBillName RealBillName, MOM.ClientBillAddress1 RealBillAddress1, MOM.ClientBillAddress2 RealBillAddress2,  MOM.ClientBillCity RealBillCity, MOM.ClientBillState RealBillState, MOM.ClientBillZipCode RealBillZipCode,  MOM.ClientBillPhone RealBillPhone, MOM.ClientBillEmail RealBillEmail,  MOM.ClientShipName RealShipName, MOM.ClientShipAddress1 RealShipAddress1, MOM.ClientShipAddress2 RealShipAddress2,  MOM.ClientShipCity RealShipCity, MOM.ClientShipState RealShipState, MOM.ClientShipZipCode RealShipZipCode,  MOM.ClientShipPhone RealShipPhone, MOM.ClientShipEmail RealShipEmail,0 AS IsFrozen,MOM.OrderID AS Client_Order_ID_Text,MOM.OrderID AS MainOrderID,CAST(MOM.OrderTotal AS VARCHAR(10)) AS OrgOrderTotal ,'' AS OrderDiffAmt,0 AS IsDiffAmtPaid,0 as IsPickupFromStore,'Amazon' As WebSiteName from Grocery.MarketOrderMaster MOM With(NoLock), Grocery.MarketOrderDetailMaster MODM with(nolock)  where MOM.MarketOrderID = MODM.MarketOrderID and MOM.AmazonOrderID is not null  ) as GBA WHERE GBA.RealOrderID = '" . $post['orderId'] . "' Order By GBA.RealOrderDate Desc");
                $result = sqlsrv_query($conn, $orderDesc);
                $data = sqlsrv_fetch_object($result);
                $data->RealOrderDate = $data->RealOrderDate->format($this->date_format);
                $data->RealPaymentDate = !empty($data->RealPaymentDate) ? $data->RealPaymentDate->format($this->date_format) : '';
                $data->RealShipDate = !empty($data->RealShipDate) ? $data->RealShipDate->format($this->date_format) : '';
                array_push($orderDetails, $data);
            }
            return $orderDetails;
        }
    }

}
