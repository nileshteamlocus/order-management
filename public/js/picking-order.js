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
      url: "/printOrders",
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
      url: "/neworderajax",
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
         page_code      : 'picking',
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

    //$(document).on("click", ".refund-btn" , function() {
        //order_id
    //});

    $(document).on("click", ".showDesc" , function() {

        var order_id = $(this).attr("data-id"); 
        popup_order_id[0] = {order_id: order_id, site:$(this).data('site')}; 

        $.ajax({
              url: "/order_details",
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

function openVoidOrder(order_id, order_type, order_from)
{
    $('#order_id').val( order_id );
    $('#order_id_text').html( order_id );
    $('#order_desc_text').html( order_id + ' - ' + order_type );
    $('#order_from').val( order_from );
    $("#void_order_p").modal("show");
}

function voidOrderForm()
{
    var void_reason = $("#void_reason").val();
    var order_id    = $("#order_id").val();
    if( void_reason == '' )
    {
        $("#void_reason").addClass('form-control-danger');
    }
    else 
    {
        $.ajax({
              url: "/void_order",
              method: 'post',
              data: {void_reason: void_reason, order_id: order_id},
              beforeSend: function() {
                    $(".loader-demo-box").css('visibility', 'visible');
                },
              success: function(result_vo){
                $(".loader-demo-box").css('visibility', 'hidden');
                console.log(result_vo);
              },
              error: function() { 
                $(".loader-demo-box").css('visibility', 'hidden');
                showSwal('basic', 'Something went wrong!!');
              }    
        }); 
    }
    return false;
}