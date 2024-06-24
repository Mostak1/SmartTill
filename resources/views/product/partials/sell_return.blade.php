<table style="width: 100%" class="table table-bordered table-striped ajax_view" id="sell_return_table">
    <thead>
        <tr>
            <th>@lang('sale.product')</th>
            <th>@lang('product.sku')</th>
            <th>Category</th>
            <th>Brand</th>
            <th>@lang('lang_v1.parent_sale')</th>
            <th>@lang('purchase.payment_status')</th>
            <th>Current stock</th>
            <th>Return Qty</th>
            <th>Total</th>
        </tr>
    </thead>
    <tfoot>
        <tr class="bg-gray font-17 text-center footer-total">
            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
            <td id="footer_payment_status_count_sr"></td>
            <td colspan="2"></td>
            <td><span class="display_currency" id="footer_sell_return_total" data-currency_symbol ="true"></span></td>
        </tr>
    </tfoot>
</table>