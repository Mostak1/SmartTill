<table style="width: 100%" class="table table-bordered table-striped ajax_view hide-footer" id="product_sell_table">
    <thead>
        <tr>
            <th>@lang('sale.product')</th>
            <th>@lang('product.sku')</th>
            <th>Category</th>
            <th>Brand</th>
            <th>Date</th>
            <th>@lang('report.current_stock')</th>
            <th>@lang('report.total_unit_sold')</th>
            <th>@lang('sale.total')</th>
        </tr>
    </thead>
    <tfoot>
        <tr class="bg-gray font-17 footer-total text-center">
            <td colspan="6"><strong>@lang('sale.total'):</strong></td>
            <td id="footer_total_today_sold"></td>
            <td><span class="display_currency" id="footer_today_subtotal" data-currency_symbol="true"></span></td>
        </tr>
    </tfoot>
</table>