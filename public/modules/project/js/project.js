/**
 * PROJECT MODULE
 * project related code
 */
// project - add form model
// Project member
function updateAssignedToFilterDropdown(project_id) {
    $.ajax({
        url:
            "{{ action([ModulesProjectHttpControllersProjectController::class, 'getProjectMembers']) }}?id=" +
            project_id,
        type: 'GET',
        success: function (data) {
            console.log(data);
            $('#assigned_to_filter_dropdown').empty();
            $('#assigned_to_filter_dropdown').append(
                '<option value="">{{ __("messages.all") }}</option>'
            );
            $.each(data, function (id, name) {
                $('#assigned_to_filter_dropdown').append(
                    '<option value="' + id + '">' + name + '</option>'
                );
            });
        },
        error: function (xhr, status, error) {
            console.error(xhr.responseText);
            // Handle error here
        },
    });
}
$(document).on('click', '#projet_member', function () {
    alert('Project Title');
    var project_id = 30;
    updateAssignedToFilterDropdown(project_id);
});

$(document).on('click', 'button.add_new_project', function () {
    var url = $(this).data('href');
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        success: function (result) {
            $('#project_model').html(result).modal({
                backdrop: 'static', // Prevent closing on click outside
                keyboard: false // Disable closing on escape key press
            }).modal('show');
            $('#project_model').find('.select2').select2();
        },
    });
});


// project - edit form model
$(document).on('click', '.edit_a_project', function () {
    var url = $(this).data('href');
    
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        success: function (result) {
            $('#project_model').html(result).modal({
                backdrop: 'static', // Prevent closing on click outside
                keyboard: false // Disable closing on escape key press
            }).modal('show');
        },
    });
    
});

//initialize ck editor, date picker and form validation when model is opened
$('#project_model').on('shown.bs.modal', function (e) {
    $('form#project_form .datepicker').datepicker({
        autoclose: true,
        format: datepicker_date_format,
    });

    tinymce.init({
        selector: 'textarea#description',

        image_class_list: [{ title: 'img-responsive', value: 'img-responsive' }],
        height: 500,
        setup: function (editor) {
            editor.on('init change', function () {
                editor.save();
            });
        },
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste',
        ],
        toolbar:
            'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code ',

        image_title: true,
        automatic_uploads: true,
        images_upload_url: '/upload',
        file_picker_types: 'image',
        // images_upload_base_path: '/some/basepath',
        file_picker_callback: function (cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.onchange = function () {
                var file = this.files[0];

                var reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function () {
                    var id = 'blobid' + new Date().getTime();
                    var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    // Instead of setting the blob URI directly, set the absolute URL
                    cb("{{ asset('storage') }}/" + file.name, { title: file.name });
                    // cb(blobInfo.blobUri(), { title: file.name });
                    // This will immediately display the uploaded image in the editor
                    var img = new Image();
                    img.src = reader.result;
                    tinymce.activeEditor.insertContent(img.outerHTML);
                };
            };
            input.click();
        },
    });

    $('.select2').select2();
    //form validation
    $('form#project_form').validate();
});

$('#project_model').on('hidden.bs.modal', function () {
    tinymce.remove('textarea#description');
});

//project form submit
$(document).on('submit', 'form#project_form', function (e) {
    e.preventDefault();
    var lead = $('#lead_id').val().length;
    var ladda = Ladda.create(document.querySelector('.ladda-button'));
    if ($('#lead_id').val().length > 1) {
        $('#lead_id_error').show();
        ladda.start();
    } else {
        $('#lead_id_error').hide();
        var url = $('form#project_form').attr('action');
        var method = $('form#project_form').attr('method');
        var data = $('form#project_form').serialize();
        ladda.start();
        $.ajax({
            method: method,
            dataType: 'json',
            url: url,
            data: data,
            success: function (result) {
                ladda.stop();
                if (result.success) {
                    $('#project_model').modal('hide');

                    toastr.success(result.msg);

                    var project_view = urlSearchParam('project_view');

                    if (project_view == 'kanban') {
                        initializeProjectKanbanBoard();
                    } else if (project_view == 'list_view') {
                        location.reload();
                    }
                    else{
                        location.reload();
                    }
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    }
    ladda.stop();
});

//project delete
$(document).on('click', '.delete_a_project', function (e) {
    e.preventDefault();
    var url = $(this).data('href');
    var redirect = $(this).data('redirect');
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then((confirmed) => {
        if (confirmed) {
            $.ajax({
                method: 'DELETE',
                dataType: 'json',
                url: url,
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);

                        var project_view = urlSearchParam('project_view');

                        if (project_view == 'kanban') {
                            initializeProjectKanbanBoard();
                        } else if (project_view == 'list_view') {
                            location.reload();
                        } else if (project_view == 'archive') {
                            location.reload();
                        }
                        else {
                            window.location.href = redirect;
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});

//project restore
$(document).on('click', '.restore_a_project', function (e) {
    e.preventDefault();
    var url = $(this).data('href');
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then((confirmed) => {
        if (confirmed) {
            $.ajax({
                method: 'GET',
                dataType: 'json',
                url: url,
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);

                        var project_view = urlSearchParam('project_view');

                        if (project_view == 'kanban') {
                            initializeProjectKanbanBoard();
                        } else if (project_view == 'list_view') {
                            location.reload();
                        }
                        else if (project_view == 'archive') {
                            location.reload();
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});


//project restore
$(document).on('click', '.permanentDelete_a_project', function (e) {
    e.preventDefault();
    var url = $(this).data('href');
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then((confirmed) => {
        if (confirmed) {
            $.ajax({
                method: 'GET',
                dataType: 'json',
                url: url,
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);

                        var project_view = urlSearchParam('project_view');

                        if (project_view == 'kanban') {
                            initializeProjectKanbanBoard();
                        } else if (project_view == 'list_view') {
                            location.reload();
                        }
                        else if (project_view == 'archive') {
                            location.reload();
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});

/**
 * PROJECT MODULE
 * project task related code
 */
// project task - add form model
$(document).on('click', '.task_btn', function () {
    var url = $(this).data('href');
    $.ajax({
        methods: 'GET',
        dataType: 'html',
        url: url,
        success: function (result) {
            $('.project_task_model').html(result).modal({
                backdrop: 'static', // Prevent closing on click outside
                keyboard: false // Disable closing on escape key press
            }).modal('show');
        },
    });
});

// project task - edit form model
$(document).on('click', '.edit_a_project_task', function () {
    var url = $(this).data('href');
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        success: function (result) {
            $('.project_task_model').html(result).modal({
                backdrop: 'static', // Prevent closing on click outside
                keyboard: false // Disable closing on escape key press
            }).modal('show');
        },
    });
});

// project task - archive
$(document).on('click', '.archive_project_task', function () {
    var url = $(this).data('href');
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then((confirmed) => {
        if (confirmed) {
            $.ajax({
                method: 'GET',
                dataType: 'json', // Change dataType to 'json' to receive JSON response
                url: url,
                success: function (response) {
                    if (response.success) {
                        // Update table content (if needed)
                        // For example, reload the datatable
                        $('#project_task_table').DataTable().ajax.reload();
                        if (typeof my_task_datatable != 'undefined') {
                            my_task_datatable.ajax.reload();
                        }
                        initializeTaskKanbanBoard();
                        // Show success toaster message
                        toastr.success(response.msg);
                    } else {
                        // Show error toaster message (if needed)
                        toastr.error(response.msg);
                    }
                },
                error: function (xhr, status, error) {
                    // Handle error (if needed)
                    console.error('Error archiving task:', error);
                },
            });
        }
    });
});

$(document).on('click', '.edit_a_task_from_view_task', function () {
    var url = $(this).data('href');
    $('.project_task_model').html('');

    if ($('.view_project_task_model').hasClass('in')) {
        $('.view_project_task_model').modal('hide');
    }
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        async: false,
        success: function (result) {
            $('.project_task_model').html(result).modal({
                backdrop: 'static', // Prevent closing on click outside
                keyboard: false // Disable closing on escape key press
            }).modal('show');
        },
    });
});

//initialize ck editor, date picker and form validation when model is opened textarea#add_description
$('.project_task_model').on('shown.bs.modal', function (e) {
    tinymce.init({
        selector: 'textarea#description',

        image_class_list: [{ title: 'img-responsive', value: 'img-responsive' }],
        height: 500,
        setup: function (editor) {
            editor.on('init change', function () {
                editor.save();
            });
        },
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste',
        ],
        toolbar:
            'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code ',

        image_title: true,
        automatic_uploads: true,
        images_upload_url: '/Lacuna_main_only/public/project/upload',
        file_picker_types: 'image',
        // images_upload_base_path: '/some/basepath',
        file_picker_callback: function (cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.onchange = function () {
                var file = this.files[0];

                var reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function () {
                    var id = 'blobid' + new Date().getTime();
                    var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    // Instead of setting the blob URI directly, set the absolute URL
                    cb("{{ asset('storage') }}/" + file.name, { title: file.name });
                    // cb(blobInfo.blobUri(), { title: file.name });
                    // This will immediately display the uploaded image in the editor
                    var img = new Image();
                    img.src = reader.result;
                    tinymce.activeEditor.insertContent(img.outerHTML);
                };
            };
            input.click();
        },
    });

    $('form#project_task_form .datepicker').datepicker({
        autoclose: true,
        format: datepicker_date_format,
    });

    $('.select2').select2();
    //form validation
    $('form#project_task_form').validate();

    //check if modal opened then make it scrollable
    if ($('.modal.in').length > 0) {
        $('body').addClass('modal-open');
    }
});

$('.project_task_model').on('hidden.bs.modal', function () {
    tinymce.remove('textarea#description');
});

//project task form submit
$(document).on('submit', 'form#project_task_form', function (e) {
    e.preventDefault();
    var url = $('form#project_task_form').attr('action');
    var method = $('form#project_task_form').attr('method');
    var data = $('form#project_task_form').serialize();
    var ladda = Ladda.create(document.querySelector('.ladda-button'));
    ladda.start();
    $.ajax({
        method: method,
        dataType: 'json',
        url: url,
        data: data,
        success: function (result) {
            ladda.stop();
            if (result.success) {
                $('.project_task_model').modal('hide');
                toastr.success(result.msg);
                if (typeof project_task_datatable != 'undefined') {
                    project_task_datatable.ajax.reload();
                }
                if (typeof archive_datatable != 'undefined') {
                    archive_datatable.ajax.reload();
                }
                if (typeof my_task_datatable != 'undefined') {
                    my_task_datatable.ajax.reload();
                }

                if (!$('.custom-kanban-board').hasClass('hide')) {
                    initializeTaskKanbanBoard();
                }
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

//data table in project view related codes
$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    var target = $(e.target).attr('href');
    if (target == '#project_task') {
        initializeProjectTaskDatatable();
    } else if (target == '#time_log') {
        initializeTimeLogDatatable();
    } else if (target == '#documents_and_notes') {
        initializeNotesDataTable();
    } else if (target == '#activities') {
        initializeActivities();
    } else if (target == '#project_overview') {
        window.location.href = $(this).data('url');
    } else if (target == '#project_invoices') {
        initializeInvoiceDatatable();
    }
});

//project task delete
$(document).on('click', '.delete_a_project_task', function (e) {
    e.preventDefault();
    var url = $(this).data('href');
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then((confirmed) => {
        if (confirmed) {
            $.ajax({
                method: 'DELETE',
                dataType: 'json',
                url: url,
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);

                        if (typeof project_task_datatable != 'undefined') {
                            project_task_datatable.ajax.reload();
                        }
                        if (typeof archive_datatable != 'undefined') {
                            archive_datatable.ajax.reload();
                        }
                        if (typeof my_task_datatable != 'undefined') {
                            my_task_datatable.ajax.reload();
                        }

                        if (!$('.custom-kanban-board').hasClass('hide')) {
                            initializeTaskKanbanBoard();
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});

// update project task status
$(document).on('click', '.change_status_of_project_task', function () {
    var url = $(this).data('href');
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        success: function (result) {
            $('.view_modal').html(result).modal({
                backdrop: 'static', // Prevent closing on click outside
                keyboard: false // Disable closing on escape key press
            }).modal('show');
        },
    });
});

//update task status form submission
$(document).on('submit', 'form#change_status', function (e) {
    e.preventDefault();
    var url = $('form#change_status').attr('action');
    var method = $('form#change_status').attr('method');
    var data = $('form#change_status').serialize();
    $.ajax({
        method: method,
        dataType: 'json',
        url: url,
        data: data,
        success: function (result) {
            if (result.success) {
                $('.view_modal').modal('hide');
                toastr.success(result.msg);

                if (typeof project_task_datatable != 'undefined') {
                    project_task_datatable.ajax.reload();
                }
                if (typeof archive_datatable != 'undefined') {
                    archive_datatable.ajax.reload();
                }
                if (typeof my_task_datatable != 'undefined') {
                    my_task_datatable.ajax.reload();
                }
            } else {
                toastr.error(result.msg);
            }
        },
    });
});
// update project task for change project
$(document).on('click', '.change_project_of_project_task', function () {
    var url = $(this).data('href');
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        success: function (result) {
            $('.view_modal').html(result).modal('show');
        },
    });
});

//update task project chenge form submission
$(document).on('submit', 'form#change_project', function (e) {
    e.preventDefault();
    var url = $('form#change_project').attr('action');
    var method = $('form#change_project').attr('method');
    var data = $('form#change_project').serialize();
    $.ajax({
        method: method,
        dataType: 'json',
        url: url,
        data: data,
        success: function (result) {
            if (result.success) {
                $('.view_modal').modal('hide');
                toastr.success(result.msg);

                if (typeof project_task_datatable != 'undefined') {
                    project_task_datatable.ajax.reload();
                }
                if (typeof archive_datatable != 'undefined') {
                    archive_datatable.ajax.reload();
                }
                if (typeof my_task_datatable != 'undefined') {
                    my_task_datatable.ajax.reload();
                }
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

// view a task
$(document).on('click', '.view_a_project_task', function () {
    var url = $(this).data('href');
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        success: function (result) {
            $('.view_project_task_model').html(result).modal({
                backdrop: 'static', // Prevent closing on click outside
                keyboard: false // Disable closing on escape key press
            }).modal('show');
        },
    });
});

// codes for  editing project task description
$('.view_project_task_model').on('shown.bs.modal', function (e) {
    $('form#update_task_description').hide();
    $('.toggleMedia').hide();
    $('form#add_comment_form').validate();
});

//toggle description edit btn
$(document).on('click', '.edit_task_description', function () {
    tinymce.init({
        selector: 'textarea#description',

        image_class_list: [{ title: 'img-responsive', value: 'img-responsive' }],
        height: 500,
        setup: function (editor) {
            editor.on('init change', function () {
                editor.save();
            });
        },
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste',
        ],
        toolbar:
            'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code ',

        image_title: true,
        automatic_uploads: true,
        images_upload_url: '/upload',
        file_picker_types: 'image',
        // images_upload_base_path: '/some/basepath',
        file_picker_callback: function (cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.onchange = function () {
                var file = this.files[0];

                var reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function () {
                    var id = 'blobid' + new Date().getTime();
                    var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    // Instead of setting the blob URI directly, set the absolute URL
                    cb("{{ asset('storage') }}/" + file.name, { title: file.name });
                    // cb(blobInfo.blobUri(), { title: file.name });
                    // This will immediately display the uploaded image in the editor
                    var img = new Image();
                    img.src = reader.result;
                    tinymce.activeEditor.insertContent(img.outerHTML);
                };
            };
            input.click();
        },
    });
    $('.toggle_description_fields').hide();
    $('form#update_task_description').show();
});

$(document).on('click', '.close_update_task_description_form', function () {
    toggleTaskForm();
    tinymce.remove('textarea#edit_description_of_task');
});

//project task description form submit
$(document).on('submit', 'form#update_task_description', function (e) {
    e.preventDefault();
    var url = $('form#update_task_description').attr('action');
    var method = $('form#update_task_description').attr('method');
    var data = $('form#update_task_description').serialize();
    var ladda = Ladda.create(document.querySelector('.save-description-btn'));
    ladda.start();
    $.ajax({
        method: method,
        dataType: 'json',
        url: url,
        data: data,
        success: function (result) {
            ladda.stop();
            if (result.success) {
                $('div.form_n_description').html(result.task_description_html);
                toastr.success(result.msg);
                toggleTaskForm();
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

//toggling task description form
function toggleTaskForm() {
    tinymce.remove('textarea#edit_description_of_task');
    $('.toggle_description_fields').show();
    $('form#update_task_description').hide();
}

//dropzone related code
var dropzoneInstance = {};
$(document).on('click', '.upload_doc', function () {
    $('.upload_doc').hide();
    $('.toggleMedia').show();
    initialize_dropzone();
});

//toggle dropzone
$(document).on('click', '.hide_upload_btn', function () {
    $('.toggleMedia').hide();
    $('.upload_doc').show();
});

//on close model destroy dropzone
$('.view_project_task_model').on('hide.bs.modal', function () {
    if (dropzoneInstance.length > 0) {
        Dropzone.forElement('div#fileupload').destroy();
        dropzoneInstance = {};
    }
});

//initialize dropzone
function initialize_dropzone() {
    var file_names = [];

    if (dropzoneInstance.length > 0) {
        Dropzone.forElement('div#fileupload').destroy();
    }

    dropzoneInstance = $('div#fileupload').dropzone({
        url: base_path + '/project/post-media-dropzone-upload',
        paramName: 'file',
        uploadMultiple: true,
        autoProcessQueue: true,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        success: function (file, response) {
            if (response.success) {
                toastr.success(response.msg);
                file_names.push(response.file_name);
                $('input#comment_media').val(file_names);
            } else {
                toastr.error(response.msg);
            }
        },
    });
}

//project task comment form submit and change for comment count reload automatically
$(document).on('submit', 'form#add_comment_form', function (e) {
    e.preventDefault();
    var url = $('form#add_comment_form').attr('action');
    var method = $('form#add_comment_form').attr('method');
    var data = $('form#add_comment_form').serialize();
    var ladda = Ladda.create(document.querySelector('.comment_btn'));
    ladda.start();
    $.ajax({
        method: method,
        dataType: 'json',
        url: url,
        data: data,
        success: function (result) {
            ladda.stop();
            if (result.success) {
                $('input#comment_media').val('');
                initialize_dropzone();
                $('form#add_comment_form')[0].reset();
                $('.direct-chat-messages').prepend(result.comment_html);
                toastr.success(result.msg);
                initializeTaskKanbanBoard();
                if (typeof project_task_datatable !== 'undefined') {
                    project_task_datatable.ajax.reload();
                }
                if (typeof archive_datatable !== 'undefined') {
                    archive_datatable.ajax.reload();
                }
                if (typeof project_task_datatable !== 'undefined') {
                    my_task_datatable.ajax.reload();
                }
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

/**
 * PROJECT MODULE
 * project time log related code
 */
// open a model for form
$(document).on('click', '.time_log_btn', function () {
    var url = $(this).data('href');
    var data = { added_from: 'timelog' };
    $('.view_modal').html('');
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        data: data,
        success: function (result) {
            $('#time_log_model').html(result).modal('show');
        },
    });
});

//initialize datetime picker for timelog form on model open
$('#time_log_model').on('shown.bs.modal', function (e) {
    $('form#time_log_form .datetimepicker').datetimepicker({
        ignoreReadonly: true,
        format: moment_date_format + ' ' + moment_time_format,
    });
    $('.select2').select2();
    $('form#time_log_form').validate();
});

//project task time log form submit
$(document).on('submit', 'form#time_log_form', function (e) {
    e.preventDefault();
    var url = $('form#time_log_form').attr('action');
    var method = $('form#time_log_form').attr('method');
    var data = $('form#time_log_form').serialize();
    $.ajax({
        method: method,
        dataType: 'json',
        url: url,
        data: data,
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                if (result.added_from !== 'task') {
                    time_logs_data_table.ajax.reload();
                    $('#time_log_model').modal('hide');
                } else {
                    $('#task-timelog').html(result.task_timelog_html);
                    $('.view_modal').modal('hide');
                }
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

// delete a time log
$(document).on('click', '#delete_a_time_log', function (e) {
    e.preventDefault();
    var url = $(this).data('href');
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then((confirmed) => {
        if (confirmed) {
            $.ajax({
                method: 'DELETE',
                dataType: 'json',
                url: url,
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        time_logs_data_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});

// project task filter related code
$(document).on(
    'change',
    '#assigned_to_filter, #status_filter, #due_date_filter, #priority_filter',
    function () {
        if ($('.custom-kanban-board').hasClass('hide')) {
            if (typeof project_task_datatable !== 'undefined') {
                project_task_datatable.ajax.reload();
            }
        }
    }
);

// project activities related code
$(document).on('click', '.load_more_activities', function () {
    var url = $(this).data('href');
    var data = { project_id: $('#project_id').val() };
    $.ajax({
        method: 'GET',
        dataType: 'json',
        url: url,
        data: data,
        success: function (result) {
            if (result.success) {
                $('.load_more_activities').hide();
                $('.timeline').append(result.activities);
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

// my task data table related code
function initializeMyTaskDataTable() {
    var task_view = $("[name='task_view']:checked").val();
    if (typeof my_task_datatable == 'undefined' && task_view == 'list_view') {
        my_task_datatable = $('#my_task_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/project/project-task',
                data: function (d) {
                    d.project_id = $('#project_id').val();
                    d.user_id = $('#assigned_to_filter').val();
                    d.status = $('#status_filter').val();
                    d.due_date = $('#due_date_filter').val();
                    d.priority = $('#priority_filter').val();
                    d.task_view = $("[name='task_view']:checked").val();
                },
            },
            columnDefs: [
                {
                    targets: [0, 1, 3, 7, 8],
                    orderable: false,
                    searchable: false,
                },
            ],
            aaSorting: [[7, 'asc']],
            columns: [
                { data: 'action', name: 'action' },
                { data: 'created_at', name: 'created_at' },
                { data: 'project' },
                { data: 'subject', name: 'subject' },
                { data: 'members' },
                { data: 'priority', name: 'priority' },
                {
                    data: 'custom_field_2', 
                    name: 'custom_field_2',
                    render: function(data, type, row) {
                        if (data) {
                            return data + ' hours';
                        } else {
                            return '';
                        }
                    }
                },
                { data: 'start_date', name: 'start_date' },
                { data: 'due_date', name: 'due_date' },
                { data: 'status', name: 'status' },
                { data: 'createdBy' },
                { data: 'updated_at', name: 'updated_at' },
                {
                    data: 'custom_field_1',
                    name: 'custom_field_1',
                    render: function (data, type, row) {
                        // Create a temporary element to parse the HTML content
                        var tempElement = $('<div>').html(data);
                        // Extract the text content from the temporary element
                        var textContent = tempElement.text();
                        // Return the extracted text content
                        return textContent;
                    }
                },
            ],
        });
    } else if (typeof my_task_datatable != 'undefined' && task_view == 'list_view') {
        my_task_datatable.ajax.reload();
    } else if (task_view == 'kanban') {
        initializeTaskKanbanBoard();
    }
}
//reload my task data table on change of filter
$(document).on(
    'change',
    '#project_id, #assigned_to_filter, #status_filter, #due_date_filter, #priority_filter',
    function () {
        initializeMyTaskDataTable();
    }
);

$(document).on('change', '.my_task_view', function () {
    if ($(this).val() == 'kanban' && $('.custom-kanban-board').hasClass('hide')) {
        $('.table-responsive, .status_filter').addClass('hide');
        $('.custom-kanban-board').removeClass('hide');
        initializeTaskKanbanBoard();
    } else if ($(this).val() == 'list_view') {
        $('.custom-kanban-board').addClass('hide');
        $('.table-responsive, .status_filter').removeClass('hide');
        initializeMyTaskDataTable();
    }
});

// on change project filter get project
$(document).on(
    'change',
    '#project_status_filter, #project_end_date_filter, #project_categories_filter',
    function () {
        var project_view = urlSearchParam('project_view');

        if (project_view == 'kanban') {
            initializeProjectKanbanBoard();
        } else if (project_view == 'list_view') {
            $('.project_html').html('');
            getProjectList();
        }
        else if (project_view == 'list_view') {
            $('.project_html').html('');
            getProjectArchive();
        }
    }
);

function getProjectList(url = '') {
    var project_view = urlSearchParam('project_view');
    var data = {
        status: $('#project_status_filter').val(),
        end_date: $('#project_end_date_filter').val(),
        category_id: $('#project_categories_filter').val(),
        project_view: project_view,
    };

    if (url.length == 0) {
        url = '/project/project';
    }

    $.ajax({
        method: 'GET',
        dataType: 'json',
        url: url,
        data: data,
        success: function (result) {
            if (result.success) {
                $('.load_more_project').hide();
                $('.project_html').append(result.projects_html);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

function getProjectArchive(url = '') {
    var project_view = urlSearchParam('project_view');
    var data = {
        status: $('#project_status_filter').val(),
        end_date: $('#project_end_date_filter').val(),
        category_id: $('#project_categories_filter').val(),
        project_view: project_view,
    };

    if (url.length == 0) {
        url = '/project/project';
    }

    $.ajax({
        method: 'GET',
        dataType: 'json',
        url: url,
        data: data,
        success: function (result) {
            if (result.success) {
                $('.load_more_project').hide();
                $('.project_html').append(result.projects_html);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

// load more project if any
$(document).on('click', '.load_more_project', function () {
    var url = $(this).data('href');
    getProjectList(url);
});

/**
 * PROJECT MODULE
 * project invoice related code
 */
// add a new row
$(document).on('click', '.add_invoice_line', function () {
    $('div.invoice_lines').append($('div.invoice_line_row').html());
});

// remove a row
$(document).on('click', '.remove_this_row', function () {
    $(this).closest('div.invoice_line').remove();
    calculateSubtotal();
});

// toggle invoice task description
$(document).on('click', '.toggle_description', function () {
    $(this).closest('div.invoice_line').find('div.description').toggle();
});

//initialize date picker for invoice form
$('form#invoice_form .date-picker').datepicker({
    autoclose: true,
    format: datepicker_date_format,
});

// initialize select2 for invoice form
$('form#invoice_form .select2').select2();

//form validation for invoice form
$('form#invoice_form').validate();

//calculate row total price
$(document).on(
    'change',
    'form#invoice_form .rate,form#invoice_form .quantity, form#invoice_form .tax',
    function () {
        var row = $(this).closest('.invoice_line');
        var price = __read_number(row.find('input.rate'));
        var qty = __read_number(row.find('input.quantity'));
        var tax = row.find('select.tax').find(':selected').data('rate');
        // add tax to price
        var price_inc_tax = __add_percent(price, tax);
        // calculate total inc. tax
        var total = price_inc_tax * qty;
        __write_number(row.find('input.total'), total, false);
        // on change of rate/qty/total recalculate subtotal/totaltax
        calculateSubtotal();
    }
);

// on change of discount type/amount recalculate invoice total
$(document).on(
    'change',
    'form#invoice_form #discount_type, form#invoice_form #discount_amount',
    function () {
        calculateInvoiceTotal();
    }
);

function calculateSubtotal() {
    var rows = $('.invoice_lines');
    var lines_total = 0;
    rows.find('.invoice_line').each(function (row) {
        lines_total += __read_number($(this).find('input.total'));
    });

    $('span.subtotal').text(__currency_trans_from_en(lines_total));
    __write_number($('input#subtotal'), lines_total, false);
    calculateInvoiceTotal();
}

function calculateInvoiceTotal() {
    var discount_type = $('select#discount_type').val();
    var discount_amount = __read_number($('input#discount_amount'));
    var subtotal = __read_number($('input#subtotal'));
    var discounted_amount = __calculate_amount(discount_type, discount_amount, subtotal);
    var invoice_total = subtotal - discounted_amount;
    $('span.invoice_total').text(__currency_trans_from_en(invoice_total));
    __write_number($('input#invoice_total'), invoice_total, false);
}

// delete a invoice
$(document).on('click', '.delete_a_invoice', function () {
    var url = $(this).data('href');
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then((confirmed) => {
        if (confirmed) {
            $.ajax({
                method: 'DELETE',
                dataType: 'json',
                url: url,
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        project_invoice_datatable.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});

// view invoice
$(document).on('click', '.view_a_project_invoice', function () {
    var url = $(this).data('href');
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        success: function (result) {
            $('.view_modal').html(result).modal('show');
        },
    });
});

$(document).on('change', '.task_view', function () {
    if ($(this).val() == 'kanban' && $('.custom-kanban-board').hasClass('hide')) {
        $('.table-responsive, .status_filter').addClass('hide');
        $('.archive-table').addClass('hide');
        $('.custom-kanban-board, #taskFilter').removeClass('hide');
        initializeTaskKanbanBoard();
    } else if ($(this).val() == 'list_view') {
        $('.custom-kanban-board').addClass('hide');
        $('.archive-table').addClass('hide');
        $('.table-responsive, .status_filter, #taskFilter').removeClass('hide');
        initializeProjectTaskDatatable();
    } else if ($(this).val() == 'archive') {
        $('.custom-kanban-board, .table-responsive, #taskFilter').addClass('hide');
        $('.archive-table').removeClass('hide');
        initializeArchiveDatatable();
    }
});

function getTaskListForKanban() {
    var data = {
        project_id: $('#project_id').val(),
        user_id: $('#assigned_to_filter').val(),
        due_date: $('#due_date_filter').val(),
        priority: $('#priority_filter').val(),
        task_view: $("[name='task_view']:checked").val(),
    };

    var kanbanDataSet = [];
    $.ajax({
        method: 'GET',
        dataType: 'json',
        async: false,
        url: '/project/project-task',
        data: data,
        success: function (result) {
            if (result.success) {
                kanbanDataSet = result.project_tasks;
            } else {
                toastr.error(result.msg);
            }
        },
    });

    return kanbanDataSet;
}

function updateProjectTaskStatusForKanban(data, el) {
    $.ajax({
        method: 'PUT',
        dataType: 'json',
        url: '/project/project-task/' + data.task_id + '/post-status',
        data: data,
        success: function (result) {
            if (result.success) {
                $(el).attr('data-parentid', data.status);
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

function initializeProjectTaskDatatable() {
    var task_view = $("[name='task_view']:checked").val();
    if (typeof project_task_datatable == 'undefined' && task_view == 'list_view') {
        project_task_datatable = $('#project_task_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/project/project-task',
                data: function (d) {
                    d.project_id = $('#project_id').val();
                    d.user_id = $('#assigned_to_filter').val();
                    d.status = $('#status_filter').val();
                    d.due_date = $('#due_date_filter').val();
                    d.priority = $('#priority_filter').val();
                    d.task_view = $("[name='task_view']:checked").val();
                },
            },
            columnDefs: [
                {
                    targets: [0, 2, 6, 7],
                    orderable: false,
                    searchable: false,
                },
            ],
            aaSorting: [[6, 'asc']],
            columns: [
                { data: 'action', name: 'action' },
                { data: 'created_at', name: 'created_at' },
                { data: 'subject', name: 'subject' },
                { data: 'members' },
                { data: 'priority', name: 'priority' },
                {
                    data: 'custom_field_2', 
                    name: 'custom_field_2',
                    render: function(data, type, row) {
                        if (data) {
                            return data + ' hours';
                        } else {
                            return '';
                        }
                    }
                },
                { data: 'start_date', name: 'start_date' },
                { data: 'due_date', name: 'due_date' },
                { data: 'status', name: 'status' },
                { data: 'createdBy' },
                { data: 'updated_at', name: 'updated_at' },
                {
                    data: 'custom_field_1',
                    name: 'custom_field_1',
                    render: function (data, type, row) {
                        // Create a temporary element to parse the HTML content
                        var tempElement = $('<div>').html(data);
                        // Extract the text content from the temporary element
                        var textContent = tempElement.text();
                        // Return the extracted text content
                        return textContent;
                    }
                },
            ],
        });
    } else if (task_view == 'list_view') {
        project_task_datatable.ajax.reload();
    } else if (task_view == 'kanban') {
        initializeTaskKanbanBoard();
    }
}
function initializeArchiveDatatable() {
    var task_view = $("[name='task_view']:checked").val();
    if (typeof archive_datatable == 'undefined' && task_view == 'archive') {
        archive_datatable = $('#archive_project_task_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/project/project-task', // Replace with your archive tasks URL
                data: function (d) {
                    d.project_id = $('#project_id').val();
                    d.user_id = $('#assigned_to_filter').val();
                    d.status = $('#status_filter').val();
                    d.due_date = $('#due_date_filter').val();
                    d.priority = $('#priority_filter').val();
                    d.task_view = $("[name='task_view']:checked").val();
                },
            },
            columnDefs: [
                {
                    targets: [0, 2, 6, 7],
                    orderable: false,
                    searchable: false,
                },
            ],
            aaSorting: [[6, 'asc']],
            columns: [
                { data: 'action', name: 'action' },
                { data: 'created_at', name: 'created_at' },
                { data: 'subject', name: 'subject' },
                { data: 'members' },
                { data: 'priority', name: 'priority' },
                {
                    data: 'custom_field_2', 
                    name: 'custom_field_2',
                    render: function(data, type, row) {
                        if (data) {
                            return data + ' hours';
                        } else {
                            return '';
                        }
                    }
                },
                { data: 'start_date', name: 'start_date' },
                { data: 'due_date', name: 'due_date' },
                { data: 'status', name: 'status' },
                { data: 'createdBy' },
                { data: 'updated_at', name: 'updated_at' },
                {
                    data: 'custom_field_1',
                    name: 'custom_field_1',
                    render: function (data, type, row) {
                        // Create a temporary element to parse the HTML content
                        var tempElement = $('<div>').html(data);
                        // Extract the text content from the temporary element
                        var textContent = tempElement.text();
                        // Return the extracted text content
                        return textContent;
                    }
                },
            ],
        });
    } else if (task_view == 'archive') {
        archive_datatable.ajax.reload();
    }
}
function initializeTimeLogDatatable() {
    if (typeof time_logs_data_table == 'undefined') {
        time_logs_data_table = $('#time_logs_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/project/project-task-time-logs',
                data: function (d) {
                    d.project_id = $('#project_id').val();
                },
            },
            columnDefs: [
                {
                    targets: [0, 1, 3, 4, 5, 6],
                    orderable: false,
                    searchable: false,
                },
            ],
            aaSorting: [[2, 'desc']],
            columns: [
                { data: 'action', name: 'action' },
                { data: 'task' },
                { data: 'start_datetime', name: 'start_datetime' },
                { data: 'end_datetime', name: 'end_datetime' },
                { data: 'work_hour' },
                { data: 'user' },
                { data: 'note', name: 'note' },
            ],
        });
    } else {
        time_logs_data_table.ajax.reload();
    }
}

function initializeActivities() {
    var data = { project_id: $('#project_id').val() };
    $.ajax({
        method: 'GET',
        dataType: 'json',
        url: '/project/activities',
        data: data,
        success: function (result) {
            if (result.success) {
                $('.timeline').html(result.activities);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

function initializeInvoiceDatatable() {
    if (typeof project_invoice_datatable == 'undefined') {
        project_invoice_datatable = $('#project_invoice_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/project/invoice',
                data: function (d) {
                    d.project_id = $('#project_id').val();
                },
            },
            columnDefs: [
                {
                    targets: [0, 2, 7],
                    orderable: false,
                    searchable: false,
                },
            ],
            aaSorting: [[2, 'asc']],
            columns: [
                { data: 'action', name: 'action' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'contact_id', name: 'contact_id' },
                { data: 'pjt_title', name: 'pjt_title' },
                { data: 'payment_status', name: 'payment_status' },
                { data: 'final_total', name: 'final_total' },
                { data: 'status', name: 'status' },
            ],
            fnDrawCallback: function (oSettings) {
                __currency_convert_recursively($('#project_invoice_table'));
            },
        });
    } else {
        project_invoice_datatable.ajax.reload();
    }
}

function initializeNotesDataTable() {
    if (typeof documents_and_notes_data_table == 'undefined') {
        getDocAndNoteIndexPage();
        initializeDocumentAndNoteDataTable();
    } else {
        documents_and_notes_data_table.ajax.reload();
    }
}

/*
kanban code for project
*/
$(document).on('change', '.project_view', function () {
    window.location.href = $(this).data('href');
});

function getProjectListForKanban() {
    var project_view = urlSearchParam('project_view');
    var data = {
        end_date: $('#project_end_date_filter').val(),
        category_id: $('#project_categories_filter').val(),
        project_view: project_view,
    };

    var kanbanDataSet = [];
    $.ajax({
        method: 'GET',
        dataType: 'json',
        async: false,
        url: '/project/project',
        data: data,
        success: function (result) {
            if (result.success) {
                kanbanDataSet = result.projects_html;
            } else {
                toastr.error(result.msg);
            }
        },
    });

    return kanbanDataSet;
}

function updateProjectStatusForKanban(data, el) {
    $.ajax({
        method: 'PUT',
        dataType: 'json',
        url: '/project/project/' + data.project_id + '/post-status',
        data: data,
        success: function (result) {
            if (result.success) {
                $(el).attr('data-parentid', data.status);
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

function initializeProjectKanbanBoard() {
    //before creating kanban board, set div to empty.
    $('div#myKanban').html('');
    lists = getProjectListForKanban();

    KanbanBoard.prototype.run = function () {
        var _this = this;
        _this.lists = lists;
        var boards = lists
            .map(function (l) {
                return _this.listToKanbanBoard(l);
            })
            .map(function (b) {
                return _this.processBoard(b);
            });
        var kanbanTest = _this.initProjectKanban(boards, '#myKanban');
        $('.meta-tasks').each(function (i, el) {
            return new PerfectScrollbar(el, { useBothWheelAxes: true });
        });

        // _this.setupUI(kanbanTest);
    };

    new KanbanBoard().run();
}

KanbanBoard.prototype.initProjectKanban = function (boards, jKanbanElemSelector) {
    var _this = this;
    var kanban = new jKanban({
        element: jKanbanElemSelector,
        gutter: '5px',
        widthBoard: '320px',
        dragBoards: false,
        click: function (el) {
            //TODO: implement card clickable
            // _this.listApi.
            // getCard(el.dataset.eid).
            // then(_this.openCardModal.bind(_this));
        },
        dragEl: function (el, source) {
            $(el).addClass('dragging');
            isDraggingCard = true;
        },
        dragendEl: function (el) {
            $(el).removeClass('dragging');
            isDraggingCard = false;
        },
        dropEl: function (el, target, source, sibling) {
            var $el = $(el);

            $el.closest('.kanban-drag')[0]._ps.update();

            var $newParentStatus = $(target).parent('div.kanban-board').data('id');
            var $status = $(el).attr('data-parentid');

            //PROJECT MODULE:update status of project in db
            if (!$('div.project-kanban-board').hasClass('hide')) {
                if ($newParentStatus !== $status) {
                    var data = {
                        status: $newParentStatus,
                        project_id: $(el).data('eid'),
                    };

                    updateProjectStatusForKanban(data, $el);
                }
            }
        },

        addItemButton: false,
        boards: boards,
    });

    initializeAutoScrollOnKanbanWhileCardDragging(kanban);

    return kanban;
};

function initializeTaskKanbanBoard(argument) {
    //before creating kanban board, set div to empty.
    $('div#myKanban').html('');
    lists = getTaskListForKanban();

    KanbanBoard.prototype.run = function () {
        var _this = this;
        _this.lists = lists;
        var boards = lists
            .map(function (l) {
                return _this.listToKanbanBoard(l);
            })
            .map(function (b) {
                return _this.processBoard(b);
            });
        var kanbanTest = _this.initTaskKanban(boards, '#myKanban');
        $('.meta-tasks').each(function (i, el) {
            return new PerfectScrollbar(el, { useBothWheelAxes: true });
        });

        // _this.setupUI(kanbanTest);
    };

    new KanbanBoard().run();
}

KanbanBoard.prototype.initTaskKanban = function (boards, jKanbanElemSelector) {
    var _this = this;
    var kanban = new jKanban({
        element: jKanbanElemSelector,
        gutter: '5px',
        widthBoard: '320px',
        dragBoards: false,
        click: function (el) {
            //TODO: implement card clickable
            // _this.listApi.
            // getCard(el.dataset.eid).
            // then(_this.openCardModal.bind(_this));
        },
        dragEl: function (el, source) {
            $(el).addClass('dragging');
            isDraggingCard = true;
        },
        dragendEl: function (el) {
            $(el).removeClass('dragging');
            isDraggingCard = false;
        },
        dropEl: function (el, target, source, sibling) {
            var $el = $(el);

            $el.closest('.kanban-drag')[0]._ps.update();

            var $newParentStatus = $(target).parent('div.kanban-board').data('id');
            var $status = $(el).attr('data-parentid');

            //PROJECT MODULE:update status of task in db
            if (!$('.custom-kanban-board').hasClass('hide')) {
                if ($newParentStatus !== $status) {
                    var data = {
                        project_id: $(el).data('project_id'),
                        status: $newParentStatus,
                        task_id: $(el).data('eid'),
                    };

                    updateProjectTaskStatusForKanban(data, $el);
                }
            }
        },

        addItemButton: false,
        boards: boards,
    });

    initializeAutoScrollOnKanbanWhileCardDragging(kanban);

    return kanban;
};

/**
 * PROJECT MODULE
 * project report
 */

//Employee time log report
$('#employee_timelog_report_daterange').daterangepicker(dateRangeSettings, function (start, end) {
    $('#employee_timelog_report_daterange').val(
        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
    );
});

$('#employee_timelog_report_daterange').on('cancel.daterangepicker', function (ev, picker) {
    $('#employee_timelog_report_daterange').val('');
});

$(document).on(
    'change',
    '#employee_timelog_report_user_id, #employee_timelog_report_project_id, #employee_timelog_report_daterange',
    function () {
        getEmployeeTimeLogReport();
    }
);

function getEmployeeTimeLogReport() {
    var data = {
        start_date: $('input#employee_timelog_report_daterange')
            .data('daterangepicker')
            .startDate.format('YYYY-MM-DD'),
        end_date: $('input#employee_timelog_report_daterange')
            .data('daterangepicker')
            .endDate.format('YYYY-MM-DD'),
        project_id: $('#employee_timelog_report_project_id').val(),
        user_id: $('#employee_timelog_report_user_id').val(),
    };

    $.ajax({
        method: 'GET',
        dataType: 'json',
        url: '/project/project-employee-timelog-reports',
        data: data,
        success: function (result) {
            if (result.success) {
                $('div.employee_time_logs_report').html(result.timelog_report);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

//project time log report
$('#project_timelog_report_daterange').daterangepicker(dateRangeSettings, function (start, end) {
    $('#project_timelog_report_daterange').val(
        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
    );
});

$('#project_timelog_report_daterange').on('cancel.daterangepicker', function (ev, picker) {
    $('#project_timelog_report_daterange').val('');
});

$(document).on(
    'change',
    '#project_timelog_report_project_id, #project_timelog_report_daterange',
    function () {
        getProjectTimeLogReport();
    }
);

function getProjectTimeLogReport() {
    var data = {
        start_date: $('input#project_timelog_report_daterange')
            .data('daterangepicker')
            .startDate.format('YYYY-MM-DD'),
        end_date: $('input#project_timelog_report_daterange')
            .data('daterangepicker')
            .endDate.format('YYYY-MM-DD'),
        project_id: $('#project_timelog_report_project_id').val(),
    };

    $.ajax({
        method: 'GET',
        dataType: 'json',
        url: '/project/project-timelog-reports',
        data: data,
        success: function (result) {
            if (result.success) {
                $('div.project_timelog_report').html(result.timelog_report);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

//delete a comment on task
$(document).on('click', '.delete-task-comment', function (e) {
    e.preventDefault();
    var element = $(this);
    var comment_id = $(this).data('comment_id');
    var task_id = $(this).data('task_id');
    swal({
        title: LANG.sure,
        icon: 'warning',
        buttons: true,
        dangerMode: true,
    }).then((confirmed) => {
        if (confirmed) {
            $.ajax({
                method: 'DELETE',
                dataType: 'json',
                url: '/project/project-task-comment/' + comment_id + '?task_id=' + task_id,
                success: function (result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        element.closest('.direct-chat-msg').remove();
                        initializeTaskKanbanBoard();
                        if (typeof project_task_datatable !== 'undefined') {
                            project_task_datatable.ajax.reload();
                        }
                        if (typeof archive_datatable !== 'undefined') {
                            archive_datatable.ajax.reload();
                        }
                        if (typeof my_task_datatable != 'undefined') {
                            my_task_datatable.ajax.reload();
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });
});

//add time log from task view
$(document).on('click', '.add_time_log', function () {
    var url = $(this).data('href');
    var data = { added_from: 'task' };
    $('#time_log_model').html('');
    $.ajax({
        method: 'GET',
        dataType: 'html',
        url: url,
        data: data,
        success: function (result) {
            $('.view_modal').html(result).modal('show');
            $('form#time_log_form .datetimepicker').datetimepicker({
                ignoreReadonly: true,
                format: moment_date_format + ' ' + moment_time_format,
            });
            $('.select2').select2();
            $('form#time_log_form').validate();
        },
    });
});
// settings blank field handaling
$(document).on('submit', '#settings_form', function (event) {
    event.preventDefault();
    var ladda = Ladda.create(document.querySelector('#settings_form button'));
    ladda.start();
    var error = false;

    // Check if any status is checked and its input field is empty
    if ($('#not_started_id').is(':checked') && $('#not_started').val() == '') {
        $('#not_started').css('border-color', 'red');
        error = true;
    } else {
        $('#not_started').css('border-color', 'gray');
    }

    if ($('#in_progress_id').is(':checked') && $('#in_progress').val() == '') {
        $('#in_progress').css('border-color', 'red');
        error = true;
    } else {
        $('#in_progress').css('border-color', 'gray');
    }

    if ($('#on_hold_id').is(':checked') && $('#on_hold').val() == '') {
        $('#on_hold').css('border-color', 'red');
        error = true;
    } else {
        $('#on_hold').css('border-color', 'gray');
    }

    if ($('#cancelled_id').is(':checked') && $('#cancelled').val() == '') {
        $('#cancelled').css('border-color', 'red');
        error = true;
    } else {
        $('#cancelled').css('border-color', 'gray');
    }

    if ($('#completed_id').is(':checked') && $('#completed').val() == '') {
        $('#completed').css('border-color', 'red');
        error = true;
    } else {
        $('#completed').css('border-color', 'gray');
    }

    // If no errors, submit the form
    if (!error) {
        $('#settings_form')[0].submit();
    } else {
        ladda.stop();
    }
});
$(document).ready(function () {

    $('.c-close').on('click', function() {
        $('#c-popup').hide();
    });

    $(document).on('click', function(event) {
        if ($(event.target).is('#c-popup')) {
            $('#c-popup').hide();
        }
    });

    // Function to initialize Spectrum color picker
    function initializeSpectrum(element) {
        element.spectrum({
            showPalette: true,
            palette: [
                ['#000000', '#4C4C4C', '#999999', '#B3B3B3', '#CCCCCC', '#E6E6E6', '#F2F2F2', '#FFFFFF'],
                ['#FF0000', '#FF9900', '#FFFF00', '#00FF00', '#00FFFF', '#0000FF', '#9900FF', '#FF00FF'],
                ['#FF3333', '#FFCC33', '#FFFF33', '#33FF33', '#33FFFF', '#3333FF', '#CC33FF', '#FF33FF'],
                ['#FF6666', '#FFCC66', '#FFFF66', '#66FF66', '#66FFFF', '#6666FF', '#CC66FF', '#FF66FF'],
                ['#FF9999', '#FFCC99', '#FFFF99', '#99FF99', '#99FFFF', '#9999FF', '#CC99FF', '#FF99FF'],
                ['#CC0000', '#CC6600', '#CCCC00', '#66CC00', '#00CC99', '#0066CC', '#6600CC', '#CC00CC'],
                ['#990000', '#994C00', '#999900', '#4C9900', '#009966', '#004C99', '#4C0099', '#990099'],
                ['#660000', '#663300', '#666600', '#336600', '#006633', '#003366', '#330066', '#660066']
            ],
            showInput: true,
            allowEmpty: true,
            showInitial: true,
            preferredFormat: "hex"
        });
    }

    // Attach click event handler to status cards
    $(document).on('click', '.project_stats', function () {
        // Get the status from the data attribute of the clicked card
        var status = $(this).data('status');

        // Set the selected status in the status filter dropdown and trigger change event
        $('#project_status_filter').val(status).trigger('change');
    });

    // Prevent form submission when the plus icon button is clicked
    $('#entries').on('click', '.add-entry', function (e) {
        e.preventDefault();
        addEntry();
    });


    // Add new entry function
    function addEntry() {
        var newEntry = `<div class="entry">
        <div class="row">
            <div class="col-md-4">
                <input class="form-control" type="text" name="level_name[]" placeholder="Label Name">
            </div>
            <div hidden class="col-md-2">
                <input class="form-control color-picker" type="text" name="color[]" value="#000000">
            </div>
            <div style="padding-right: 0px; width: 12%;" class="col-md-2">
                <input class="form-control bg-picker" type="text" name="bg[]" value="#FFFFFF">
            </div>
            <div style="padding-left: 0px;" class="col-md-2">
                <span style="font-size: 20px" class="remove-entry bg-white text-red"><i class="fas fa-minus-circle"></i></span>
            </div>
        </div>
    </div>`;
        $('#entries').append(newEntry);

        // Initialize Spectrum on the new color picker
        initializeSpectrum($('.color-picker').last());

        // Initialize Spectrum on the new color picker
        initializeSpectrum($('.bg-picker').last());
    }

    // Remove entry
    $('#entries').on('click', '.remove-entry', function () {
        $(this).closest('.entry').remove();
    });

    // Get values as array
    function getEntriesArray() {
        var entriesArray = [];
        $('#entries .entry').each(function () {
            var levelName = $(this).find('input[name="level_name[]"]').val();
            var color = $(this).find('input[name="color[]"]').val();
            var bg = $(this).find('input[name="bg[]"]').val();
            entriesArray.push({ levelName: levelName, color: color, bg: bg });
        });
        return entriesArray;
    }

    // Example usage: print array to console
    $('#save-button').on('click', function () {
        var entriesArray = getEntriesArray();
        console.log(entriesArray);
    });

    // Handle form submission when the update button is clicked
    $('#update-button').on('click', function () {
        // Submit the form
        $('#settings_form').submit();
    });
});
// Subject limit set
$(document).on('keyup', 'input[name="subject"]', function () {
    var maxLength = 180; // Maximum length allowed
    var length = $(this).val().length; // Current length of the input value

    // Show or hide the message based on the input length
    if (length > maxLength) {
        $('#subject-limit-message').show();
    } else {
        $('#subject-limit-message').hide();
    }
});

$(document).ready(function () {
    // Function to determine if a color is light or dark
    function isLightColor(hexColor) {
        var c = hexColor.substring(1); // strip #
        var rgb = parseInt(c, 16); // convert rrggbb to decimal
        var r = (rgb >> 16) & 0xff; // extract red
        var g = (rgb >> 8) & 0xff; // extract green
        var b = (rgb >> 0) & 0xff; // extract blue

        var luma = 0.2126 * r + 0.7152 * g + 0.0722 * b; // per ITU-R BT.709
        return luma > 128; // returns true if light color
    }

    // Initialize Spectrum for color input fields
    $(".color-picker").spectrum({
        showPalette: true,
        palette: [
            ['#000000', '#4C4C4C', '#999999', '#B3B3B3', '#CCCCCC', '#E6E6E6', '#F2F2F2', '#FFFFFF'],
            ['#FF0000', '#FF9900', '#FFFF00', '#00FF00', '#00FFFF', '#0000FF', '#9900FF', '#FF00FF'],
            ['#FF3333', '#FFCC33', '#FFFF33', '#33FF33', '#33FFFF', '#3333FF', '#CC33FF', '#FF33FF'],
            ['#FF6666', '#FFCC66', '#FFFF66', '#66FF66', '#66FFFF', '#6666FF', '#CC66FF', '#FF66FF'],
            ['#FF9999', '#FFCC99', '#FFFF99', '#99FF99', '#99FFFF', '#9999FF', '#CC99FF', '#FF99FF'],
            ['#CC0000', '#CC6600', '#CCCC00', '#66CC00', '#00CC99', '#0066CC', '#6600CC', '#CC00CC'],
            ['#990000', '#994C00', '#999900', '#4C9900', '#009966', '#004C99', '#4C0099', '#990099'],
            ['#660000', '#663300', '#666600', '#336600', '#006633', '#003366', '#330066', '#660066']
        ],
        showInput: true,
        allowEmpty: true,
        showInitial: true,
        preferredFormat: "hex"
    });

    // Initialize Spectrum for background color input fields
    $(".bg-picker").spectrum({
        showPalette: true,
        palette: [
            ['#000000', '#4C4C4C', '#999999', '#B3B3B3', '#CCCCCC', '#E6E6E6', '#F2F2F2', '#FFFFFF'],
            ['#FF0000', '#FF9900', '#FFFF00', '#00FF00', '#00FFFF', '#0000FF', '#9900FF', '#FF00FF'],
            ['#FF3333', '#FFCC33', '#FFFF33', '#33FF33', '#33FFFF', '#3333FF', '#CC33FF', '#FF33FF'],
            ['#FF6666', '#FFCC66', '#FFFF66', '#66FF66', '#66FFFF', '#6666FF', '#CC66FF', '#FF66FF'],
            ['#FF9999', '#FFCC99', '#FFFF99', '#99FF99', '#99FFFF', '#9999FF', '#CC99FF', '#FF99FF'],
            ['#CC0000', '#CC6600', '#CCCC00', '#66CC00', '#00CC99', '#0066CC', '#6600CC', '#CC00CC'],
            ['#990000', '#994C00', '#999900', '#4C9900', '#009966', '#004C99', '#4C0099', '#990099'],
            ['#660000', '#663300', '#666600', '#336600', '#006633', '#003366', '#330066', '#660066']
        ],
        showInput: true,
        allowEmpty: true,
        showInitial: true,
        preferredFormat: "hex",
        change: function (color) {
            var bgColor = color.toHexString();
            var textColor = isLightColor(bgColor) ? '#000000' : '#FFFFFF';
            $(this).closest('.row').find('.color-picker').spectrum('set', textColor);
        }
    });

    // Manually trigger change on load to set initial text color based on initial background color
    $('.bg-picker').each(function () {
        var bgColor = $(this).spectrum('get').toHexString();
        var textColor = isLightColor(bgColor) ? '#000000' : '#FFFFFF';
        $(this).closest('.row').find('.color-picker').spectrum('set', textColor);
    });

    // Add event listener to the dynamically added thumbnails
    $(document).on('click', '.c-thumbnail', function() {
        var src = $(this).attr('src');
        var alt = $(this).attr('alt');
        $('#c-popup-img').attr('src', src);
        $('#c-caption').text(alt);
        $('#c-popup').show();
    });

    // Add event listener to close button
    $('.c-close').on('click', function() {
        $('#c-popup').hide();
    });

    // Close the popup when clicking outside of the image
    $(document).on('click', function(event) {
        if ($(event.target).is('#c-popup')) {
            $('#c-popup').hide();
        }
    });
});
