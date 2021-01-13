@extends('layouts.app')
@section('title', 'Picking Order')
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
                                                <th>Refund</th>
                                                <th>Modify</th>
                                                <th>Make Order</th>
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
                            <tr><td>Shipping</td><td class="shopping ta_right"></td></tr>
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
        </div>


        <!--  void order popup start -->
        <div class="modal fade" id="void_order_p" tabindex="-1" role="dialog" aria-labelledby="void_order_p" data-backdrop="static" data-keyboard="false" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content"> 
                 <form action="" onsubmit="return voidOrderForm()">

                    <input type="hidden" name="order_id" id="order_id">
                    <input type="hidden" name="order_from" id="order_id">

                    <div class="modal-header">
                      <h5 class="modal-title" id="exampleModalLabel">Void Order</h5>
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                      </button> 
                    </div>
                    <div class="modal-body">
                            <div class="table-responsive"> 
                                <div class="fitter">
                                    <div class="fitter">
                                        <div class="first"> Order#: </div> <div class="c-data" id="order_id_text">258296 </div> <div class="c-data"> Client Orders#: </div> <div class="c-data" id="order_desc_text"> 258296 - Cooler </div>
                                    </div>
                                    <div class="fitter">
                                        <div class="first"> Void Reason : </div> <div class="c-input"><textarea class="form-control" id="void_reason" name="void_reason" required></textarea> </div>
                                    </div>
                                </div> 
                            </div>  
                    </div>
                    <div class="modal-footer">
                      <button type="Submit" class="btn btn-success">Submit</button>
                      <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        </div>
        <!--  void order popup end -->
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
<link rel="stylesheet" href="{{ URL::asset('css/picking-order.css') }}" />

@endsection
@section('js')
<script src="{{ URL::asset('vendors/js/vendor.bundle.base.js') }}"></script>
<script src="{{ URL::asset('vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ URL::asset('vendors/datatables.net-bs4/dataTables.bootstrap4.js') }}"></script>
<script src="{{ URL::asset('vendors/jquery-validation/jquery.validate.min.js') }}"></script> 
<script src="{{ URL::asset('vendors/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script> 
<script src="{{ URL::asset('js/data-table.js') }}"></script>
<script src="{{ URL::asset('js/moment-with-locales.min.js') }}"></script>
<script src="{{ URL::asset('js/formpickers.js') }}"></script>
<script src="{{ URL::asset('vendors/js/moment.min.js') }}"></script>
<script src="{{ URL::asset('vendors/js/daterangepicker.js') }}"></script>
<script src="{{ URL::asset('vendors/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ URL::asset('js/alerts.js') }}"></script>
<script src="{{ URL::asset('js/picking-order.js') }}"></script> 
@endsection