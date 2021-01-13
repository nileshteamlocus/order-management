@extends('layouts.app')
@section('title', 'New Order')
@section('content')
<div class="container-scroller">
    <!-- partial:partials/_horizontal-navbar.html -->
    <div class="horizontal-menu">
        @include('layouts.header')
        @include('layouts.menu')
    </div>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="card">
                    <div class="card-body"> 

                        <span class="float-right ml-2" style="padding-top: 12px;">Grand Total : <span class="grand-total">$0</span></span>    
                        <div class="row" style="height: 61px;">
                          <div class="col-sm-12" style="padding-left: 0;"> 
                            <button type="button" class="btn btn-primary" onclick="print_orders()"><i class="mdi mdi-printer mr-1"></i></button>
                            <button onclick="load_data(1)" class="btn btn-primary" id="refresh_button"><i class="fa fa-refresh"></i></button>
                            <button onclick="toggle_filter()" class="btn btn-primary"><i class="fa fa-filter"></i></button>
                            <button onclick="clearFilter()" class="btn btn-primary" style="padding:15px;padding-top:16px;">Reset</button>
                          </div>  
                      </div>      
                        <div class="row">
                            <div class="col-12">

                                <div class="paging_html" style="margin-top: 10px; margin-bottom: 20px;"></div>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="new-order-table">
                                        <thead>
                                            <tr>
                                                <th class="checkbox-th" style="text-align:left;padding-left: 10px;"><div class="form-check" style="margin-bottom: 0px;margin-top: 0px;"><input type="checkbox" id="select-all"></div></th>
                                                <th>Order Date</th>
                                                <th>Website</th>
                                                <th style="text-align:right;">Order#</th>
                                                <th>Product Type</th>
                                                <th style="text-align:right;">Ordered($)</th>
                                                <th>Order Type</th>
                                                <th>Description</th>
                                                <th style="display: none;"></th>
                                            </tr>
                                            <tr class="filter-row" style="display: none;">
                                                <td></td>
                                                <td>

<div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width:194px;font-size: 13px;">
    <i class="fa fa-calendar"></i>&nbsp;
    <span></span> <i class="fa fa-caret-down"></i>
</div>
<div class="date-close"><i class="icon-close"></i></div>

<input type="hidden" name="search_order" id="search_order" value="">
<select class="form-control" onchange="selectDate(this.value)" id="search_order1" style="display: none;">
<option value="">Search Order by Date</option>
<option value="last_7">Last 7 Days</option>
<option value="last_month">Last Month</option>
<option value="month_date">Month to Date</option>
<option value="year_date">Year to Date</option>
<option value="date_range">Date Range</option>
</select>

<div class="input-group input-daterange d-flex align-items-center" style="display: none!important;">
<input type="text" class="form-control" value="" id="start_date">
<div class="input-group-addon mx-4">To</div>
<input type="text" class="form-control" value="" id="end_date">
<button type="button" class="btn btn-secondary date-to-div" onclick="range_search()"><i class="ti-search"></i></button>
</div></td>
                                                <td>
<select onchange="load_data(1)" id="website" class="form-control">
<option value="">All</option>
<option value="Amazon">Amazon</option>
<option value="GroceryBabu">GroceryBabu</option>
</select></td>
<td><input type="text" class="form-control" id="search_order_no" name="search_order_no" placeholder="Search Order #" onkeyup="load_data(1)"></td>
                                                <td>
                            <select onchange="load_data(1)" id="products" class="form-control">
                                <option value="">All</option>
                                <option value="Dry">Dry</option>
                                <option value="Frozen">Frozen</option>
                            </select></td>
                                                <td></td> 
                                                <td>
                            <select onchange="load_data(1)" id="order_type" class="form-control">
                                <option value="">All</option>
                                <option value="Shipping">Shipping</option>
                                <option value="Pickup">Pickup</option>
                                <option value="Delivery">Delivery</option>
                            </select></td> 
                                                <td></td> 
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>

                                </div>
                                    <div class="paging_html"></div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

              <div class="loader-demo-box" style="visibility: hidden;">
                <div class="circle-loader"></div>
              </div> 

            <!-- content-wrapper ends -->
            <!-- partial:../../partials/_footer.html -->
            <footer class="footer">
                <div class="w-100 clearfix">
                    <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright © 2018 <a href="http://www.urbanui.com/" target="_blank">Urbanui</a>. All rights reserved.</span>
                    <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Hand-crafted & made with <i class="mdi mdi-heart-outline text-danger"></i></span>
                </div>
            </footer>
            <!-- partial -->
        </div>
        <div class="modal fade" id="showDescModal" tabindex="-1" role="dialog" aria-labelledby="showDescModal" data-backdrop="static" data-keyboard="false" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <p style="text-align: center;font-size: 1rem;color: blue;padding: 15px;">Order Details</p>
                    <div class="table-responsive">
                        <table class="table table-bordered" style="width: 49%; float: left;">
                            <tr><td>Order#</td><td class="order"></td></tr>
                            <tr><td>Order Date</td><td class="orderDate"></td></tr>
                            <tr><td>Order From</td><td class="orderFrom">:</td></tr>
                        </table>
                        <table class="table table-bordered" style="width: 49%; float: right;">
                            <tr><td>Item Total</td><td class="itemTotal ta_right"></td></tr>
                            <tr><td>Shipping</td><td class="shopping ta_right"></td></td></tr>
                            <tr><td>Total</td><td class="total ta_right"></td></tr>
                        </table>
                        </div>
                    
                        <div class="order-mid-div">
                        <div style="text-align: center;font-size: 1rem;color: blue;padding: 15px;">Billing / Shipping / Picking Information</div>

                    <div class="table-responsive order-info">
                        <table class="table table-bordered">
                            <tr><td colspan="2"><div style="text-align: center;">Billing  Information</div></td></tr>
                            <tr><td>Name</td><td class="billingName"></td></tr>
                            <tr><td>Address</td><td class="billingAddress"></td></tr>
                            <tr><td>City</td><td class="billingCity"></td></tr>
                            <tr><td>State</td><td class="billingState"></td></tr>
                            <tr><td>ZipCode</td><td class="billingZipCode"></td></tr>
                            <tr><td>Phone</td><td class="billingPhone"></td></tr>
                            <tr><td>Email</td><td class="billingEmail"></td></tr>
                        </table>
                    </div>
                        
                    <div class="table-responsive order-info picking" style="float: right;">
                        <table class="table table-bordered">
                            <tr><td colspan="2"><div style="text-align: center;">Picking  Information</div></td></tr>
                            <tr><td>Pickup By</td><td class="pickupBy"></td></tr>
                            <tr><td>Pickup Time</td><td class="pickupTime"></td></tr>
                            <tr><td>Pickedup By</td><td class="pickupedTime"></td></tr>
                            <tr><td>Pickedup Date</td><td class="pickupedDate"></td></tr>
                        </table>  
                    </div>
                        
                    <div class="table-responsive order-info shipping" style="float: right;">
                        <table class="table table-bordered">
                            <tr><td colspan="2"><div style="text-align: center;">Shipping  Information</div></td></tr>
                            <tr><td>Name</td><td class="shippingName"></td></tr>
                            <tr><td>Address</td><td class="shippingAddress"></td></tr>
                            <tr><td>City</td><td class="shippingCity"></td></tr>
                            <tr><td>State</td><td class="shippingState"></td></tr>
                            <tr><td>ZipCode</td><td class="shippingZipCode"></td></tr>
                            <tr><td>Phone</td><td class="shippingPhone"></td></tr>
                            <tr><td>Email</td><td class="shippingEmail"></td></tr>
                            <tr><td>Tracking Number</td><td class="shippingTrackingNumber"></td></tr>
                        </table>  
                    </div>
                    </div>


                        
                    <div class="table-responsive">
                        <div style="text-align: center;font-size: 1rem;color: blue;padding: 15px;">Item(s) in Order</div>
                        <table class="table table-bordered" id="order-details">
                            <thead>
                            <tr><th>Item#</th><th>Item Name</th><th>Order Qty</th><th>Price $</th><th>Total $</th></tr>
                            </thead>
                            <tbody>
                            <tr><td>Item#</td><td>Item Name</td><td>Order Qty</td><td>Price $</td><td>Total $</td></tr>
                            </tbody>
                        </table>  
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light desc-Close od_popup_btn" onclick="print_orders()">Print</button>
                        <button type="button" class="btn btn-light desc-Close od_popup_btn" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- main-panel ends -->
    </div>
    <!-- main-panel ends -->
</div>
<!-- page-body-wrapper ends -->
</div>
@endsection
@section('css')
<link rel="stylesheet" href="{{ URL::asset('vendors/datatables.net-bs4/dataTables.bootstrap4.css') }}" />
<link rel="stylesheet" href="{{ URL::asset('vendors/bootstrap-datepicker/bootstrap-datepicker.min.css') }}" />
<link rel="stylesheet" href="{{ URL::asset('vendors/ti-icons/css/themify-icons.css') }}" />
<link rel="stylesheet" href="{{ URL::asset('vendors/simple-line-icons/css/simple-line-icons.css') }}" />
<link rel="stylesheet" href="{{ URL::asset('css/horizontal-layout/style.css') }}" />
<link rel="stylesheet" href="{{ URL::asset('css/daterangepicker.css') }}" />
<link rel="stylesheet" href="{{ URL::asset('css/custom.css') }}" />
<link rel="stylesheet" href="{{ URL::asset('css/new-order.css') }}" />

@endsection
@section('js')
<script src="{{ URL::asset('vendors/js/vendor.bundle.base.js') }}"></script>
<script src="{{ URL::asset('vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ URL::asset('vendors/datatables.net-bs4/dataTables.bootstrap4.js') }}"></script>
<script src="{{ URL::asset('vendors/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script> 
<script src="{{ URL::asset('js/data-table.js') }}"></script>
<script src="{{ URL::asset('js/moment-with-locales.min.js') }}"></script>
<script src="{{ URL::asset('js/formpickers.js') }}"></script>
<script src="{{ URL::asset('vendors/js/moment.min.js') }}"></script>
<script src="{{ URL::asset('vendors/js/daterangepicker.js') }}"></script>
<script src="{{ URL::asset('vendors/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ URL::asset('js/alerts.js') }}"></script>

<script type="text/javascript">  
    var popup_order_id = [];
    var start = moment().subtract(29, 'days');
    var end = moment(); 
    function cb(start, end) {
        $('#reportrange span').html(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD')); 
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        $('#search_order').val('date_range');
        load_data();
    } 
    $('#reportrange').daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, cb); 
    /*cb(start, end)*/;

</script>
<script>

function print_orders() {

    if( popup_order_id.length > 0 )
    {
        var view_type = 'single';
        var orderJsonParse = JSON.stringify(popup_order_id);
    }
    else 
    { 
        var view_type = 'multiple';
        if( !$('.order-row:checkbox:checked').length )
        {
            showSwal('basic', 'Please select any order!'); return false;
        }
        var orderJsonVar = new Array();
       //var orders_data = '';
        $( ".order-row:checkbox:checked" ).each(function( index ) { 
            var order_array = {order_id:$(this).val(), site:$(this).data('site')};
            orderJsonVar[index] = order_array; 
    		/*if( index != 0) { orders_data += ' - '; } orders_data += $(this).data('clientorderidtext');*/
        });

        var orderJsonParse = JSON.stringify(orderJsonVar);
    }
        
    var post_data = {orderJson: orderJsonParse, view_type: view_type};
    
    $.ajax({
      url: "{{ url('/printOrders') }}",
      method: 'get',
      data: post_data,
      beforeSend: function() {
            $(".loader-demo-box").css('visibility', 'visible');
        },
      success: function(result){
        $(".loader-demo-box").css('visibility', 'hidden');
        $('#report').html( result );

        if(view_type == 'multiple')
        {
            $('#refresh_button').trigger('click');
        }

        window.print();
		/*var divToPrint = document.getElementById('report');
		var popupWin = window.open('', '_blank', 'width=900,height=1000');
		popupWin.document.open();
		popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
		popupWin.document.close();*/

      },
      error: function() { 
        $(".loader-demo-box").css('visibility', 'hidden');
        showSwal('basic', 'Something went wrong!!');
      }    
    });
}

function toggle_filter()
{
    if( $('.filter-row').is(":visible") )
    {
        $('.filter-row').fadeOut(); 
    }
    else
    {
        $('.filter-row').fadeIn(); 
    }
}

var load_data_ajax = '';
function lazy_load_data()
{
    if( load_data_ajax )
    {
        load_data_ajax.abort(); 
    }
    setTimeout(function () {
        load_data(1);
    }, 1000);
}


function load_data(page_index, type)
{
    $('#select-all').prop('checked', false);
    var search_order_no = $('#search_order_no').val();
    var website         = $('#website').val();
    var products        = $('#products').val();
    var order_type      = $('#order_type').val();
    var search_order    = $('#search_order').val();
    var start_date      = $('#start_date').val();
    var end_date        = $('#end_date').val();

    if( type )
    {
        search_order = type;
    }

    var load_data_ajax = $.ajax({
      url: "{{ url('/neworderajax') }}",
      method: 'get',
      data: {
         search_order_no: search_order_no,
         website        : website,
         products       : products,
         order_type     : order_type,
         search_order   : search_order,
         start_date     : start_date,
         end_date       : end_date,
         page           : page_index,
      },
      beforeSend: function() {
            $(".loader-demo-box").css('visibility', 'visible');
        },
      success: function(result){
        var result_js = JSON.parse(result);
        $('.grand-total').html(result_js.order_total);
        $('#new-order-table tbody').html(result_js.html);
        $('.paging_html').html(result_js.paging_html); 
        $(".loader-demo-box").css('visibility', 'hidden');
        $('html,body').animate({ scrollTop: 0 }, 'slow');
      },
      error: function() { 
        $(".loader-demo-box").css('visibility', 'hidden');
        showSwal('basic', 'Something went wrong!!');
      }    
    }); 
}

load_data(1);

function clearFilter()
{
    $('#reportrange span').html(''); 
    $('#start_date').val('');
    $('#end_date').val('');
    $('#search_order').val('');
    $('#search_order_no').val('');
    $("#website").val($("#website option:first").val());
    $("#order_type").val($("#order_type option:first").val());
    $("#products").val($("#products option:first").val());

    load_data(1);
}

function selectDate( date_val )
{
    if( date_val != 'date_range' )
    {
        $('.input-daterange').css('visibility', 'hidden');
        load_data(1);
    }
    else 
    {
        $('.input-daterange').css('visibility', 'visible');
    }
}

function range_search()
{
    var start_date = $('#start_date').val();
    var end_date = $('#end_date').val();

    var is_valid = 1;
    if( !start_date )
    {
        $('#start_date').addClass('input-error');
        is_valid = 0;
    } 
    else 
    {
        $('#start_date').removeClass('input-error');
    }

    if( !end_date )
    {
        $('#end_date').addClass('input-error');
        is_valid = 0;
    }
    else 
    {
        $('#end_date').removeClass('input-error');
    }

    if( is_valid )
    {
        load_data(1);
    }
}

$(document).ready(function () {

    var isFirstTime = '{!! $isFirstTime !!}';

    if( isFirstTime != 1)
    {
        showSuccessToast('Login Successfully.');
    }

    let runningTotal = 0;
    var rows_selected = [];
    /*let table = $('.table').DataTable({
        "ordering": false,
    });*/
    $("#select-all").click(function () {

        if( $(this).prop('checked') )
        {
            $('#new-order-table input[type="checkbox"]').prop('checked', true);    
        }
        else 
        {
            $('#new-order-table input[type="checkbox"]').prop('checked', false);    
        }
        

        /*if (runningTotal == table.rows().count()) {
            table.rows().every(function (rowIdx, tableLoop, rowLoop) {
                let clone = table.row(rowIdx).data().slice(0);
                clone[[0]] = '<input type="checkbox" class="checkOrder">'
                rows_selected = [];
                table.row(rowIdx).data(clone);
            });
            console.log(rows_selected);
        } else {
            table.rows().every(function (rowIdx, tableLoop, rowLoop) {
                let clone = table.row(rowIdx).data().slice(0);
                clone[[0]] = '<input type="checkbox" class="checkOrder" value="'+ clone[6] +'" checked="checked">'
                rows_selected.push(clone[6]);
                table.row(rowIdx).data(clone);
            });
            console.log(rows_selected);
        }
        runningTotal = 0;
        table.rows().every(function (rowIdx, tableLoop, rowLoop) {
            var data = this.data();
            if ($(data[0]).prop("checked")) {
                runningTotal++
            }
        });*/
    });
    $('.table tbody').on('click', 'input[type="checkbox"]', function(e){
        if ($(this).is(':checked')){
            rows_selected.push($(this).val());
        } else {
            var index = $.inArray($(this).val(), rows_selected);
            rows_selected.splice(index, 1);
        }
    });

    $(document).on("click", ".showDesc" , function() {

        var order_id = $(this).attr("data-id"); 
        popup_order_id[0] = {order_id: order_id, site:$(this).data('site')}; 

        $.ajax({
              url: "{{ url('/order_details') }}",
              method: 'get',
              data: {
                 order_id: order_id,
                 order_from : $('#div_'+order_id).attr("data-orderfrom")
              },
              beforeSend: function() {
                    $(".loader-demo-box").css('visibility', 'visible');
                },
              success: function(result_od){

                    $(".loader-demo-box").css('visibility', 'hidden');

                    if( $('#div_'+order_id).attr("data-isPickupStore") == '1' )
                    {
                        $('.picking').show();
                        $('.shipping').hide();

                        $('.pickupBy').text($('#div_'+order_id).attr("data-RealPickupBy"));
                        $('.pickupTime').text($('#div_'+order_id).attr("data-RealPickupDateTimeFrom"));
                        $('.pickupedTime').text($('#div_'+order_id).attr("data-RealPickedupBy"));
                        $('.pickupedDate').text($('#div_'+order_id).attr("data-RealPickedupDateTime"));
                    }

                    if( $('#div_'+order_id).attr("data-isPickupStore") == '0' )
                    {
                        $('.picking').hide();
                        $('.shipping').show();

                        $('.shippingName').text($('#div_'+order_id).attr("data-shipname"));
                        $('.shippingAddress').text($('#div_'+order_id).attr("data-shipaddress"));
                        $('.shippingPhone').text($('#div_'+order_id).attr("data-shipphone"));
                        $('.shippingEmail').text($('#div_'+order_id).attr("data-shipemail"));
                        $('.shippingCity').text($('#div_'+order_id).attr("data-shipcity"));
                        $('.shippingState').text($('#div_'+order_id).attr("data-shipstate"));
                        $('.shippingZipCode').text($('#div_'+order_id).attr("data-shipzipcode"));
                        $('.shippingTrackingNumber').text($('#div_'+order_id).attr("data-shiptrackingnumber"));
                    }

                    var result_od_js = JSON.parse(result_od);
                    $('#order-details tbody').html(result_od_js.html);

                    $('.order').text($('#div_'+order_id).attr("data-clientorderidtext"));
                    $('.orderDate').text($('#div_'+order_id).attr("data-orderdate"));
                    $('.orderFrom').text($('#div_'+order_id).attr("data-orderfrom"));
                    $('.itemTotal').text( result_od_js.item_total );
                    $('.tax').text('$ ' + $('#div_'+order_id).attr("data-tax"));
                    $('.shopping').text('$ ' + $('#div_'+order_id).attr("data-shipping"));
                    $('.total').text('$ ' + $('#div_'+order_id).attr("data-ordertotal"));
                    $('.billingName').text($('#div_'+order_id).attr("data-billname"));
                    $('.billingAddress').text($('#div_'+order_id).attr("data-billaddress"));
                    $('.billingPhone').text($('#div_'+order_id).attr("data-billphone"));
                    $('.billingEmail').text($('#div_'+order_id).attr("data-billemail"));
                    $('.billingCity').text($('#div_'+order_id).attr("data-billingcity"));
                    $('.billingState').text($('#div_'+order_id).attr("data-billingstate"));
                    $('.billingZipCode').text($('#div_'+order_id).attr("data-billingzipcode"));
                    $("#showDescModal").modal("show");
              },
              error: function() { 
                $(".loader-demo-box").css('visibility', 'hidden');
                showSwal('basic', 'Something went wrong!!');
              } 
    });

    });


    $(".date-close").click(function () {
        $('#reportrange span').html(''); 
        $('#start_date').val('');
        $('#end_date').val('');
        load_data(1);
    });
});
</script>
@endsection