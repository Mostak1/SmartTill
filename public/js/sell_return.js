$(document).ready(function () {
    // For edit pos form
    var pos_form_obj;
    if ($('form#sell_return_form').length > 0) {
        pos_form_obj = $('form#sell_return_form');
    } else {
        pos_form_obj = $('form#add_pos_sell_form');
    }
    if ($('form#sell_return_form').length > 0 || $('form#add_pos_sell_form').length > 0) {
        initialize_printer();
    }
    // Date picker
    // Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    pos_form_validator = pos_form_obj.validate({
        submitHandler: function(form) {
            var cnf = true;

            if (cnf) {
                var data = $(form).serialize();
                var url = $(form).attr('action');
                $.ajax({
                    method: 'POST',
                    url: url,
                    data: data,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                            //Check if enabled or not
                            if (result.receipt.is_enabled) {
                                pos_print(result.receipt);
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
            return false;
        },
    });
    function finishWorkAndTriggerLink() {
        setTimeout(function () {
            $('#add_payment_link')[0].click();
        }, 2000); // Adjust the timeout duration as needed
    }
 
    $('#save_with_payment_button').click(function () {
        var form_data = pos_form_obj.serialize();
        $.ajax({
            method: 'POST',
            url: base_path + '/sell-return/store-with-payment',
            data: form_data,
            dataType: 'json',
            success: function (result) {
                if (result.success == 1) {
                    // toastr.success(result.msg);
                    let transactionId = result.transaction_id;
                    let paymentUrl = base_path + '/payments/add_payment/' + transactionId;
                    paymentUrl = paymentUrl.replace(':id', transactionId);
                    $('#add_payment_link').attr('href', paymentUrl);
                    // Programmatically click the payment link
                    $('#add_payment_link')[0].click();
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
});

function initialize_printer() {
    if ($('input#location_id').data('receipt_printer_type') == 'printer') {
        initializeSocket();
    }
}

function pos_print(receipt) {
    //If printer type then connect with websocket
    if (receipt.print_type == 'printer') {
        var content = receipt;
        content.type = 'print-receipt';

        //Check if ready or not, then print.
        if (socket.readyState != 1) {
            initializeSocket();
            setTimeout(function() {
                socket.send(JSON.stringify(content));
            }, 700);
        } else {
            socket.send(JSON.stringify(content));
        }
    } else if (receipt.html_content != '') {
        var title = document.title;
        if (typeof receipt.print_title != 'undefined') {
            document.title = receipt.print_title;
        }

        //If printer type browser then print content
        $('#receipt_section').html(receipt.html_content);
        __currency_convert_recursively($('#receipt_section'));
        setTimeout(function() {
            window.print();
            document.title = title;
        }, 1000);
    }
}
