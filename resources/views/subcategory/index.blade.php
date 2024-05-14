@extends('layouts.app')
@section('title', 'Subcategories')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('subcategory.subcategories')
            <small>@lang('subcategory.manage_your_subcategories')</small>
        </h1>
        <!-- <ol class="breadcrumb">
                                <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                                  Sub-Categories
                                                                                                                                                                <li class="active">Here</li>
                                                                                                                                                            </ol> -->
    </section>

    <!-- Main content -->
    <section class="content">
        @php
            $cat_code_enabled =
                isset($module_category_data['enable_taxonomy_code']) && !$module_category_data['enable_taxonomy_code']
                    ? false
                    : true;
        @endphp
        @component('components.widget', ['class' => 'box-primary', 'title' => __('subcategory.all_your_subcategories')])
            @can('subcategory.create')
                @slot('tool')
                    <div class="box-tools">
                        <button type="button" class="btn btn-block btn-primary btn-modal"
                            data-href="{{ action([\App\Http\Controllers\SubCategoryController::class, 'create']) }}"
                            data-container=".subcategories_modal">
                            <i class="fa fa-plus"></i> @lang('messages.add')</button>
                    </div>
                @endslot
            @endcan
            @can('subcategory.view')
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="subcategory_table">
                        <thead>
                            <tr>
                                <th>@lang('subcategory.subcategories')</th>
                                <th>@lang('subcategory.category')</th>
                                <th>Created By</th>
                                <th>@lang('messages.action')



                                </th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            @endcan
        @endcomponent

        <div class="modal fade subcategories_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

    </section>
    <!-- /.content 'action','created_by','name','category'-->

@endsection
@section('javascript')
    <script>
        $(document).ready(function() {
            initializeSubCategoryDataTable();
            function initializeSubCategoryDataTable() {
                my_task_datatable = $("#subcategory_table").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('subcategories.index') }}', // Assuming you have a route named "subcategories.index"
                    },
                    columnDefs: [{
                        targets: [0], // This is the index of the action column
                        orderable: false,
                        searchable: false,
                    }],
                    order: [
                        [2, 'asc'] // Sort the table based on the category column in ascending order
                    ],
                    columns: [{
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'category', // This should match the key returned from your controller
                            name: 'category'
                        },
                        {
                            data: 'created_by',
                            name: 'created_by'
                        },
                        {
                            data: 'action',
                            name: 'action'
                        },
                    ]
                });
            }
            $(document).on('click', 'button.delete_category_button', function() {
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        var data = $(this).serialize();

                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success === true) {
                                    initializeSubCategoryDataTable();
                                    toastr.success(result.msg);
                                    category_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });
        });
    </script>

@endsection
{{-- var subcategoryTable;

function initializeTaxonomyDataTable() {
    $.ajax({
        url: '{{ route('subcategory.indexjson') }}',
        method: 'GET',
        success: function(response) {
            console.log(response.subcategories);
            var data = response.subcategories;
            processDataTable(data);
        },
        error: function(xhr, status, error) {
            console.error(error);
        }
    });
}

function processDataTable(data) {
    // Iterate through each data
    var filterdataArray = data.map(function(item) {
        return {
            id: item.id,
            name: item.name,
            category: item.category ? item.category.name : 'N/A',
            creator: item.creator ? item.creator.first_name : 'N/A',
        };
    });
    console.log(filterdataArray);
    if (!subcategoryTable) {
        subcategoryTable = $('#subcategory_table').DataTable({
            data: filterdataArray,
            columns: [{
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'category',
                    name: 'category'
                },
                {
                    data: 'creator',
                    name: 'creator'
                },
                {
                    data: null,
                    name: 'id',
                    orderable: true,
                    searchable: true,
                    render: function(data, type, full, meta) {
                        var editButton =
                            '<button type="button" class="btn btn-xs btn-primary btn-modal" data-href="{{ url('subcategories') }}/' +
                            data.id +
                            '/edit" data-container=".subcategories_modal"><i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')</button>';

                        var deleteButton =
                            '<button data-href="{{ url('subcategories') }}/' + data.id +
                            '" class="btn btn-xs btn-danger delete_category_button"><i class="glyphicon glyphicon-trash"></i> Delete</button>';
                        return editButton + '&nbsp;' + deleteButton;
                    },
                },
            ],
        });
    } else {
        subcategoryTable.clear().rows.add(filterdataArray).draw();
    }
}

initializeTaxonomyDataTable();
// Example: Simulate adding new data to the table
$(document).on('click', '.close', function() {
    initializeTaxonomyDataTable();
});
$(document).on('click', 'button.delete_category_button', function() {
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then(willDelete => {
        if (willDelete) {
            var href = $(this).data('href');
            var data = $(this).serialize();

            $.ajax({
                method: 'DELETE',
                url: href,
                dataType: 'json',
                data: data,
                success: function(result) {
                    if (result.success === true) {
                        initializeTaxonomyDataTable();
                        toastr.success(result.msg);
                        category_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
}); --}}
