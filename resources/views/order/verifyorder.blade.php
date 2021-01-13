@extends('layouts.app')
@section('title', 'Verify Order')
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
                        <button type="button" class="btn btn-primary"><i class="mdi mdi-printer mr-1"></i>Print Order</button>
                        <button type="button" class="btn btn-success"></i>Print Invoice Copy</button>
                        <button type="button" class="btn btn-success"></i>Print Packaging Slip</button>
                        <span class="float-right ml-2" style="padding-top: 12px;">Grand Total:${{$grandTotal}}</span>
                        <h4 class="card-title"></h4>
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th><div class="form-check" style="margin-bottom: 0px;margin-top: 0px;"><input type="checkbox" id="select-all"></div></th>
                                                <th>Order Date</th>
                                                <th></th>
                                                <th>Order#</th>
                                                <th>Payment Status</th>
                                                <th>Ordered($)</th>
                                                <th>Refund</th>
                                                <th>Payment Date</th>
                                                <th>Payment</th>
                                                <th>Shipping</th>
                                                <th>Description</th>
                                                <th style="display: none;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($verifyOrder as $value)
                                                <tr>
                                                    <td><div class="form-check" style="margin-bottom: 0px;margin-top: 0px;"><input type="checkbox" value="{{$value->RealOrderID}}" id="order-{{$value->RealOrderID}}"></div></td>
                                                    <td>{{$value->RealOrderDate}}</td>
                                                    @if($value->MarketPlace == 'AMAZON')
                                                        <td><img src="{{asset('images/AmazonMarket.png')}}"</td>
                                                    @elseif ($value->MarketPlace == 'GBA')
                                                        <td><img src="{{asset('images/BabuMarket.gif')}}"</td>
                                                    @else
                                                        <td></td>
                                                    @endif
                                                    <td>{{$value->Client_Order_ID_Text}}</td>
                                                    <td>{{$value->RealPaymentStatus}}</td>
                                                    <td style="text-align: right;">${{$value->RealOrderTotal}}</td>
                                                    <td><button class="btn btn-outline-primary voidDetails" data-id="{{$value->RealOrderID}}" data-orderid="{{$value->Client_Order_ID_Text}}" data-toggle="modal" data-target="#voidOrder-Modal">Void Order</button></td>
                                                    <td>{{$value->RealPaymentDate}}</td>
                                                    <td><button class="btn btn-outline-primary paymentData" data-toggle="modal" data-target="#payment-Modal" data-id="{{$value->RealOrderID}}" data-orderid="{{$value->Client_Order_ID_Text}}" data-itemTotal="{{$value->RealItemTotal}}" data-tax="{{$value->RealShipTaxCost}}" data-shipping="{{$value->RealShippingCost}}" data-refundAmt="{{$value->IsDiffAmtPaid}}" data-orderTotal="{{$value->RealOrderTotal}}">Payment</button></td>
                                                    <td><button class="btn btn-outline-primary shippingDetails" data-toggle="modal" data-target="#shippingOrder-Modal" data-id="{{$value->RealOrderID}}" data-orderid="{{$value->Client_Order_ID_Text}}">Shipping</button></td>
                                                    <td><button class="btn btn-outline-primary showDesc" data-id="{{$value->RealOrderID}}" data-clientOrderIDText="{{$value->Client_Order_ID_Text}}" data-orderDate="{{$value->RealOrderDate}}" data-orderFrom="{{$value->WebSiteName}}" data-itemTotal="{{$value->RealItemTotal}}" data-tax="{{$value->RealShipTaxCost}}" data-shipping="{{$value->RealShippingCost}}" data-orderTotal="{{$value->RealOrderTotal}}" data-billName="{{$value->RealBillName}}" data-billAddress="{{$value->RealShipAddress1}} {{$value->RealBillAddress2}}" data-billPhone="{{$value->RealBillPhone}}" data-billEmail="{{$value->RealBillEmail}}" data-shipName="{{$value->RealShipName}}" data-shipAddress="{{$value->RealShipAddress1}} {{$value->RealShipAddress2}}" data-shipPhone="{{$value->RealShipPhone}}" data-shipEmail="{{$value->RealShipEmail}}">Show Desc</button></td>
                                                    <td class="checkbox-orderId" style="display: none;">{{$value->RealOrderID}}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel-2">Order Description</h5>
                        <button type="button" class="close desc-Close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div></div>
                    <div class="modal-body">
                        <p style="text-align: center;font-size: 1rem;color: blue;">Order Description</p>
                        <table>
                            <tr><td >Order#</td><td>:</td><td><td class="order"></td></tr>
                            <tr><td >Order Date</td><td>:</td><td><td class="orderDate"></td></tr>
                            <tr><td style="padding-right: 65px;">Order From</td><td>:</td><td><td class="orderFrom"></td></tr>
                        </table><br>
                        <p style="text-align: center;font-size: 1rem;color: blue;">Order Total</p>
                        <table>
                            <tr><td  style="padding-right: 75px;">Item Total</td><td>:</td><td><td class="itemTotal"></td></tr>
                            <tr><td>Tax</td><td>:</td><td><td class="tax"></td></tr>
                            <tr><td>Shopping</td><td>:</td><td><td class="shopping"></td></tr>
                            <tr><td>Total</td><td>:</td><td><td class="total"></td></tr>
                        </table><br>
                        <p style="text-align: center;font-size: 1rem;color: blue;">Billing Information</p>
                        <table>
                            <tr><td style="padding-right: 85px;">Name</td><td>:</td><td><td class="billingName"></td></tr>
                            <tr><td style="padding-right: 85px;">Address</td><td>:</td><td><td class="billingAddress" style="word-break: break-word;"></td></tr>
                            <tr><td style="padding-right: 85px;">Phone</td><td>:</td><td><td class="billingPhone"></td></tr>
                            <tr><td style="padding-right: 85px;">Email</td><td>:</td><td><td class="billingEmail" style="word-break: break-word;"></td></tr>
                        </table><br>
                        <p style="text-align: center;font-size: 1rem;color: blue;">Shipping Information</p>
                        <table>
                            <tr><td style="padding-right: 85px;">Name</td><td>:</td><td><td class="shippingName"></td></tr>
                            <tr><td style="padding-right: 85px;">Address</td><td>:</td><td><td class="shippingAddress" style="word-break: break-word;"></td></tr>
                            <tr><td style="padding-right: 85px;">Phone</td><td>:</td><td><td class="shippingPhone"></td></tr>
                            <tr><td style="padding-right: 85px;">Email</td><td>:</td><td><td class="shippingEmail" style="word-break: break-word;"></td></tr>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light desc-Close" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="voidOrder-Modal" tabindex="-1" role="dialog" aria-labelledby="voidOrder-Modal" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel-2">Void Order</h5>
                        <button type="button" class="close desc-Close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div></div>
                    <div class="modal-body">
                        <table>
                            <tr>
                                <td>Order</td>
                                <td>:&nbsp;</td>
                                <td class="orderID"></td>
                                <td>&nbsp;Client Order#</td>
                                <td>:&nbsp;</td>
                                <td class="client-order"></td>
                            </tr>
                        </table>
                        </br>
                        <div class="form-group">
                            <label for="message-text" class="col-form-label" style="font-size: 1rem;">Void Reason:</label>
                            <textarea class="form-control" style="height: 100px;" id="message-text"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success">Void</button>
                        <button type="button" class="btn btn-light desc-Close" data-dismiss="modal">Exit</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="payment-Modal" tabindex="-1" role="dialog" aria-labelledby="payment-Modal" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel-2">Order Payment</h5>
                        <button type="button" class="close desc-Close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div></div>
                    <div class="modal-body">
                        <table>
                            <tr>
                                <td>Order#</td>
                                <td>:&nbsp;</td>
                                <td class="order-id" style="word-wrap: break-word;">233869</td>
                                <td>&nbsp;&nbsp;</td>
                                <td>&nbsp;Client Order#</td>
                                <td>:&nbsp;</td>
                                <td class="clientOrder" style="word-wrap: break-word;"></td>
                            </tr>
                            <tr>
                                <td>Item Total</td>
                                <td>:&nbsp;</td>
                                <td class="itemTotal">$23.38</td>
                            </tr>
                            <tr>
                                <td>Tax</td>
                                <td>:&nbsp;</td>
                                <td class="tax">$0.00</td>
                            </tr>
                            <tr>
                                <td>Shipping</td>
                                <td>:&nbsp;</td>
                                <td class="shipping">$19.99</td>
                            </tr>
                            <tr>
                                <td>Refund Amt</td>
                                <td>:&nbsp;</td>
                                <td class="refundAmt">$0.00</td>
                            </tr>
                            <tr>
                                <td>Order Total</td>
                                <td>:&nbsp;</td>
                                <td class="orderTotal">$48.88</td>
                            </tr>
                        </table>
                        </br>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success">Change Payment</button>
                        <button type="button" class="btn btn-light desc-Close" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="shippingOrder-Modal" tabindex="-1" role="dialog" aria-labelledby="shippingOrder-Modal" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel-2">Shipping Order</h5>
                        <button type="button" class="close desc-Close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-footer" style="display: block;text-align: center;">
                        <button type="button" class="btn btn-success">Submit</button>
                        <button type="button" class="btn btn-light desc-Close" data-dismiss="modal">Exit</button>
                    </div>
                    <div class="modal-body">
                        <table>
                            <tr>
                                <td>Order</td>
                                <td>:&nbsp;</td>
                                <td class="shippingorderID"></td>
                                <td>&nbsp;Client Order#</td>
                                <td>:&nbsp;</td>
                                <td class="shippingClient-order"></td>
                            </tr>
                        </table>
                        <div class="form-group">
                            <label for="message-text" class="col-form-label" style="font-size: 1rem;">Shipping Service</label>
                            <select class="form-control">
                              <option>Please select shipping service</option>
                            </select>
                            <label for="message-text" class="col-form-label" style="font-size: 1rem;">Label Type</label>
                            <div class="form-check">
                              <label class="form-check-label">
                                <input type="radio" class="form-check-input" name="labelType" id="labelType" value="">Thermal
                                <i class="input-helper"></i></label>
                            </div>
                            <label for="message-text" class="col-form-label" style="font-size: 1rem;">Enter No. of Box</label>
                            <input type="text" class="typeahead tt-input" style="width: 50%;" name="no_box"></br>
                            <input type="button" class="form-control btn btn-secondary btn-rounded btn-fw" style="width:20%;" name="createBtn" value="Create">
                        </div>
                        <table style="width: 100%;" border="1">
                            <tr>
                                <th style="text-align: center">Box Number</th>
                                <th style="text-align: center">Box Weight(lb)</th>
                                <th style="text-align: center">Box Size</th>
                            </tr>
                            <tr>
                                <td style="text-align: center">Box 1</td>
                                <td style="text-align: center">10</td>
                                <td style="text-align: center">Fro-M(12.1W,10.25H.9.8L)</td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer" style="display: block;text-align: center;">
                        <button type="button" class="btn btn-primary">Select Shipping Method</button>
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
@endsection
@section('js')
<script src="{{ URL::asset('vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ URL::asset('vendors/datatables.net-bs4/dataTables.bootstrap4.js') }}"></script>
<script src="{{ URL::asset('js/data-table.js') }}"></script>
<script>
$(document).ready(function () {
    let runningTotal = 0;
    var rows_selected = [];
    let table = $('.table').DataTable({
        "ordering": false,
    });
    $("#select-all").click(function () {
        if (runningTotal == table.rows().count()) {
            table.rows().every(function (rowIdx, tableLoop, rowLoop) {
                let clone = table.row(rowIdx).data().slice(0);
                clone[[0]] = '<input type="checkbox" class="checkOrder">'
                rows_selected = [];
                table.row(rowIdx).data(clone);
            });
        } else {
            table.rows().every(function (rowIdx, tableLoop, rowLoop) {
                let clone = table.row(rowIdx).data().slice(0);
                clone[[0]] = '<input type="checkbox" class="checkOrder" value="'+ clone[6] +'" checked="checked">'
                rows_selected.push(clone[6]);
                table.row(rowIdx).data(clone);
            });
        }
        runningTotal = 0;
        table.rows().every(function (rowIdx, tableLoop, rowLoop) {
            var data = this.data();
            if ($(data[0]).prop("checked")) {
                runningTotal++
            }
        });
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
        $("#showDescModal").modal("show");
        $('.order').text($(this).attr("data-clientorderidtext"));
        $('.orderDate').text($(this).attr("data-orderdate"));
        $('.orderFrom').text($(this).attr("data-orderfrom"));
        $('.itemTotal').text('$' + $(this).attr("data-itemtotal"));
        $('.tax').text('$' + $(this).attr("data-tax"));
        $('.shopping').text('$' + $(this).attr("data-shipping"));
        $('.total').text('$' + $(this).attr("data-ordertotal"));
        $('.billingName').text($(this).attr("data-billname"));
        $('.billingAddress').text($(this).attr("data-billaddress"));
        $('.billingPhone').text($(this).attr("data-billphone"));
        $('.billingEmail').text($(this).attr("data-billemail"));
        $('.shippingName').text($(this).attr("data-shipname"));
        $('.shippingAddress').text($(this).attr("data-shipaddress"));
        $('.shippingPhone').text($(this).attr("data-shipphone"));
        $('.shippingEmail').text($(this).attr("data-shipemail"));
    });
    $(document).on("click", ".desc-Close" , function() {
        $('.order').text('');
        $('.orderDate').text('');
        $('.orderFrom').text('');
        $('.itemTotal').text('');
        $('.tax').text('');
        $('.shopping').text('');
        $('.total').text('');
        $('.billingName').text('');
        $('.billingAddress').text('');
        $('.billingPhone').text('');
        $('.billingEmail').text('');
        $('.shippingName').text('');
        $('.shippingAddress').text('');
        $('.shippingPhone').text('');
        $('.shippingEmail').text('');
        $('.orderID').text('');
        $('.client-order').text('');
        $('.order-id').text('');
        $('.clientOrder').text('');
        $('.itemTotal').text('');
        $('.tax').text('');
        $('.shipping').text('');
        $('.refundAmt').text('');
        $('.orderTotal').text('');
    });
    $(document).on("click", ".voidDetails" , function() {
        $('.orderID').text($(this).attr("data-id"));
        $('.client-order').text($(this).attr("data-orderid"));
    });
    $(document).on("click", ".shippingDetails" , function() {
        $('.shippingorderID').text($(this).attr("data-id"));
        $('.shippingClient-order').text($(this).attr("data-orderid"));
    });
    $(document).on("click", ".paymentData" , function() {
        $('.order-id').text($(this).attr("data-id"));
        $('.clientOrder').text($(this).attr("data-orderid"));
        $('.itemTotal').text('$' + $(this).attr("data-itemtotal"));
        $('.tax').text('$' + $(this).attr("data-tax"));
        $('.shipping').text('$' + $(this).attr("data-shipping"));
        $('.refundAmt').text('$' + $(this).attr("data-refundamt"));
        $('.orderTotal').text('$' + $(this).attr("data-ordertotal"));
    });
});
</script>
@endsection