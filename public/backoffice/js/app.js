const sweetConfirm = (text, callback, willClose) => {
    swal(
        {
            title: translate("javascript.swal.confirm.title"),
            text: text,
            html: true,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: translate(
                "javascript.swal.confirm.confirmButtonText"
            ),
            cancelButtonText: translate(
                "javascript.swal.confirm.cancelButtonText"
            ),
            customClass: {
                confirmButton: "btn btn-success",
                cancelButton: "btn btn-outline-danger ms-1",
            },
            buttonsStyling: false,
            willClose: () => {
                if (willClose !== undefined) {
                    willClose();
                }
            },
        }).then((result) => {
        if (result) {
            swal.close();
            callback();
        } else {
            if (willClose !== undefined) {
                willClose();
            }
        }
    });
};

const sweetInput = (title, text, callback, label) => {
    swal({
            title,
            text,
            type: "input",
            inputValue: label,
            showCancelButton: true,
            closeOnConfirm: false,
            animation: "slide-from-top",
        },
        (value)=> {
            callback(value)
        }
    );
}

const ajax = (params) => {
    return new Promise((resolve, reject) => {
        $.ajax(params.path, {
            data: params.data,
            method: params.method ? params.method : "post",
            dataType: params.dataType ? params.dataType : "json",
        })
            .then((response) => resolve(response))
            .catch((errors) => reject(errors));
    });
};

const serialize = (el) => {
    let form = $(`${el}`);
    let serialize = form.serializeArray();
    $(`.container-page input.switch-input:not(:checked)`).each(function () {
        serialize.push({ name: this.name, value: '0' });
    });
    serialize = serialize.concat(
        $(`${el} input[type=radio]`)
            .map(function () {
                return { name: this.name, value: this.value };
            })
            .get()
    );
    serialize.forEach((item) => {
        if (item.value === "-1") {
            let element = serialize.find(({ name }) => name === item.name);
            element.value = "";
        }
        const classes = form.find(`[name*='${item.name}']`).attr('class');
        if (classes !== undefined && classes.indexOf('summernote') >= 0) {
            const field = form.find(`[name*='${item.name}']`);
            item.value = field.summernote('code');
        }
    });
    return { data: serializeObject(serialize), form: form };
};

const serializeObject = (obj) => {
    let jsn = {};
    $.each(obj, function () {
        if (jsn[this.name]) {
            if (!jsn[this.name].push) {
                jsn[this.name] = [jsn[this.name]];
            }
            jsn[this.name].push((this.value === "on" ? "1" : this.value) || "");
        } else {
            jsn[this.name] = this.value === "on" ? "1" : this.value || "";
        }
    });
    return jsn;
};

const clearForm = (form, disable, responseDiv) => {
    if (disable) {
        form.find(":input").prop("disabled", true);
    } else {
        form.find(":input").prop("disabled", false);
    }
    form.removeClass("was-validated");
    const invalid = form.find(".invalid-feedback");
    invalid.html("");
    invalid.parent().find("input, select").removeClass("is-invalid");
    if (responseDiv) {
        $(responseDiv.element).removeClass(function (index, className) {
            return (className.match(/(^|\s)alert-\S+/g) || []).join(" ");
        });
        $(responseDiv.element)
            .removeClass("hide")
            .addClass(`alert ${responseDiv.class}`)
            .html(responseDiv.message);
    }
};

const renderErrors = (errors, form) => {
    if (errors.responseJSON.error === undefined) {
        let items = errors.responseJSON.errors;
        if ( items === undefined && (errors.responseJSON.error || errors.responseJSON.message)) {
            form.find("input:last")
                .parent()
                .parent()
                .find(".supporting-text")
                .addClass("danger")
                .show()
                .html((errors.responseJSON.error || errors.responseJSON.message));
        } else {
            for (let item in items) {
                if (item.length > 0) {
                    let message = "";
                    if (items[item].length > 1) {
                        let rows = items[item];
                        for (let row in rows) {
                            message += `${rows[row]} <br />`;
                        }
                    } else {
                        message = items[item];
                    }
                    if (item.match(/\./)) {
                        let items = item.split(".");
                        if (items.length > 1) {
                            item = `${items[0]}[${items[1]}][${items[2]}]`;
                        } else {
                            item = `${items[0]}[${items[1]}]`;
                        }
                    }
                    const inputs = ['input', 'select', 'textarea'];
                    inputs.forEach((el) => {
                        const element = $(document).find(`${el}[name='${item}']`);
                            element
                                .closest('.text-field-container')
                                .addClass("is-invalid")
                                .parent()
                                .find(".supporting-text")
                                .show()
                                .html(message);
                            element.parent().parent().find('.supporting-text')
                                .addClass("danger")
                    })
                }
            }
        }
    } else {
        sweet(errors.responseJSON.error ?? errors.responseJSON.message);
    }
    setTimeout(() => {
        $(document).find('.supporting-text').removeClass('danger').html('')
        $(document).find('.text-field-container').parent().find("input, select, textarea").removeClass("is-invalid");
    }, 10000)
};

const sweet = (text, title, type, callback) => {
    swal({
            title: title !== undefined ? title : translate("javascript.swal.error-title"),
            text: text,
            type: type !== undefined ? type : "warning",
            showCancelButton: false,
            closeOnConfirm: true,
        }).then(() => {
            if (callback) {
                callback();
            }
        });
};

const translate = (string, args) => {
    let value = _.get(window.i18n, string);
    if (args) {
        _.forEach(args, (paramVal, paramKey) => {
            value = lodash.replace(value, `:${paramKey}`, paramVal);
        });
    }
    return value;
};

const getTag = (html, selector) => {
    let parser = new DOMParser();
    let dom = parser.parseFromString(html, "text/html");
    let elems = dom.querySelectorAll(selector);
    return Array.prototype.map.call(elems, function (e) {
        return e.outerHTML.replace(/<\/?[^>]+(>|$)/g, "");
    });
};

const removeHtml = (text) => {
    let values = getTag(text, ".hidden-value");
    if (values) {
        return values[0];
    }
    return "";
};

const actionDatatable = (dt, button, type) => {
    let tableId;
    dt.one("preXhr", function (e, s, data) {
        tableId = s.sTableId;
        $(`#${tableId}`).prepend(
            '<div class="overlay"><span>Attendi...</span></div>'
        );
        data.length = -1;
    })
        .one("draw", function (e) {
            let buttonConfig = $.fn.DataTable.ext.buttons[type];
            $.extend(true, buttonConfig, {});
            buttonConfig.action(e, dt, button, buttonConfig);
            dt.one("xhr", function (e, s, data) {
                data.length = 50;
            }).draw();
            $(`#${tableId}`).find(".overlay").remove();
        })
        .draw();
};

const datatable = (params) => {
    let datatable_table = [];
    let datatables = [];
    let name =
        typeof params.name !== "undefined" ? params.name : "datatable_table";
    datatable_table[name] = $(`.${name}`);
    if (datatable_table[name].length) {
        let exportRules = {
            exportOptions: {
                columns: ":not(:last-child)",
                format: {
                    body: function (text, column) {
                        return column >= 8 ? removeHtml(text) : text;
                    },
                },
                modifier: {
                    order: "current",
                    page: "all",
                },
            },
        };
        let exportExtend = [];
        if (params.export) {
            params.export.forEach((item) => {
                if (item === "csv") {
                    exportExtend.push(
                        $.extend(true, {}, exportRules, {
                            extend: "csvHtml5",
                            action: function (e, dt, button, config) {
                                actionDatatable(dt, button, "csvHtml5");
                            },
                        })
                    );
                }
                if (item === "excel") {
                    exportExtend.push(
                        $.extend(true, {}, exportRules, {
                            extend: "excelHtml5",
                            text: '<span class="fa fa-file-excel-o"></span> Excel Export',
                            action: function (e, dt, button) {
                                actionDatatable(dt, button, "excelHtml5");
                            },
                        })
                    );
                }
            });
        }
        datatables[name] = datatable_table[name].DataTable({
            ajax: {
                url: params.url,
                data: function (d) {
                    let filters = {};
                    if (typeof params.dataForm !== "undefined") {
                        if (params.dataForm.length > 0) {
                            params.dataForm.forEach((item) => {
                                let element = $(`${params.search_class === undefined ? '.advanced-search' : params.search_class } .${item}`);
                                let val = element.val();
                                if (element.attr("type") === "checkbox") {
                                    if (element.is(":checked")) {
                                        val = "1";
                                    } else {
                                        val = "";
                                    }
                                }
                                filters[item] = val;
                            });
                            if (params.saveFilters !== 'undefined' && params.saveFilters) {
                                Cookies.set('filters', filters);
                            }
                        }
                    }
                    d.filters = filters;
                },
            },
            processing: true,
            serverSide: true,
            columns: params.columns,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"],
            ],
            iDisplayLength:
                typeof params.iDisplayLength !== "undefined"
                    ? params.iDisplayLength
                    : 50,
            order:
                typeof params.order !== "undefined"
                    ? params.order
                    : [[0, "desc"]],
            bStateSave:
                typeof params.stateSave !== "undefined"
                    ? params.stateSave
                    : false,
            dom:
                '<"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"' + ">>t" +
                (typeof params.export !== "undefined" ? "B" : "") +
                '<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            buttons: [exportExtend],
            rowGroup:
                typeof params.grouping !== "undefined"
                    ? { dataSrc: params.grouping }
                    : null,
            orderCellsTop: true,
            responsive: true,
            language: {
                loadingRecords: "&nbsp;",
                processing:
                    '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span> ',
                search: translate("javascript.datatable.search"),
                emptyTable: translate("javascript.datatable.emptyTable"),
                info: translate("javascript.datatable.info"),
                infoEmpty: translate("javascript.datatable.infoEmpty"),
                lengthMenu: translate("javascript.datatable.lengthMenu"),
                infoFiltered: translate("javascript.datatable.search"),
                paginate: {
                    // remove previous & next text from pagination
                    previous: "&nbsp;",
                    next: "&nbsp;",
                },
            }
        });
    }

    $("input.dt-input").on("keyup", function () {
        filterColumn($(this).attr("data-column"), $(this).val());
    });
};

const filterColumn = (i, val) => {
    if (i === 5) {
        var startDate = $(".start_date").val(),
            endDate = $(".end_date").val();
        if (startDate !== "" && endDate !== "") {
            filterByDate(i, startDate, endDate); // We call our filter function
        }
        $(".dt-advanced-search").dataTable().fnDraw();
    } else {
        $(".dt-advanced-search")
            .DataTable()
            .column(i)
            .search(val, false, true)
            .draw();
    }
};

const filterByDate = (column, startDate, endDate) => {
    $.fn.dataTableExt.afnFiltering.push(function (
        oSettings,
        aData,
        iDataIndex
    ) {
        var rowDate = normalizeDate(aData[column]),
            start = normalizeDate(startDate),
            end = normalizeDate(endDate);

        // If our date from the row is between the start and end
        if (start <= rowDate && rowDate <= end) {
            return true;
        } else if (rowDate >= start && end === "" && start !== "") {
            return true;
        } else if (rowDate <= end && start === "" && end !== "") {
            return true;
        } else {
            return false;
        }
    });
};

const normalizeDate = function (dateString) {
    let date = new Date(dateString);
    return (
        date.getFullYear() +
        "" +
        ("0" + (date.getMonth() + 1)).slice(-2) +
        "" +
        ("0" + date.getDate()).slice(-2)
    );
};

const reloadTable = (name = '.datatable_table') => {
    const table = $(name).DataTable();
    table.clear();
    table.ajax.reload();
};

const debounce = (func, wait, immediate) => {
    let timeout;
    return function () {
        let context = this,
            args = arguments;
        let later = function () {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        let callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};

const loadSelect2 = (parameters) => {
    setTimeout(() => {
        $(`${parameters.element}`).select2({
            placeholder: "Cerca",
            closeOnSelect: parameters.closeOnSelect !== undefined ? parameters.closeOnSelect : false,
            ajax: {
                url: `/${parameters.route}`,
                dataType: "json",
                delay: 250,
                method:
                    parameters.method !== undefined ? parameters.method : "get",
                data: (params) => {
                    return {
                        search: params.term,
                        role: (parameters.role !== undefined) ? parameters.role : '',
                        fields: (parameters.fields !== undefined) ? parameters.fields : '',
                        is_active: (parameters.is_active !== undefined) ? parameters.is_active : '',
                        brand_id: (parameters.brand_id !== undefined) ? parameters.brand_id : '',
                    };
                },
                processResults: function (data) {
                    return {
                        results: $.map(data.results, function (item) {
                            return {
                                text: item.text,
                                id: item.id,
                            };
                        }),
                    };
                },
                cache: false,
            },
        });
    }, 350);
};

const removeDropzone = () => {
    if (Dropzone.instances.length > 0) {
        Dropzone.instances.forEach((e) => {
            e.off();
            e.destroy();
        });
    }
};

const logs = () => {
    ajax({ path: "logs/get", method: "get" }).then((response) => {
        $(".logs").html(response);
    });
};

const loadSwitch = (container) => {
    let elems = Array.prototype.slice.call(
        document.querySelectorAll(`${container} .js-switch`)
    );
    elems.forEach(function (html, number) {
        let el = $(`${container} .js-switch`).eq(number);
        if (el.parent().find("span.switchery").length === 0) {
            Switchery(html);
        }
    });
};

const changeStatusObject = (el) => {
    const elementId = el.data("id");
    const model = el.data("model");
    ajax({ path: `/backoffice/${model}/${elementId}/status`, method: "post" }).then(
        () => {
            reloadTable();
        }
    );
};

const dashboard = () => {
    const data = App.serialize('.load-dashboard');
    console.log(data)
    App.ajax({path: `/dashboard_ajax`, method: 'post', data: { ...data.data }}).then(response => {
        $('.dashboard-report').html(response.html)
    });
}

const success = (clean = false) => {
    const div = $(`.btn-execute`).parent().parent();
    if (clean) {
        div.find(`.alert`).remove();
    } else {
        div.append(`<div class="mt-2 alert alert-arrow-right alert-icon-right alert-light-success alert-dismissible fade show mb-4" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-circle"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12" y2="16"></line></svg>
            <strong>${translate('javascript.operation-done')}</strong>
        </div>`)
    }
}

const update_or_create = (method, form_name, endpoint, redirect, callback) => {
    const data = App.serialize(form_name);
    $(form_name).append('<div class="overlay"><span>Attendi...</span></div>')
    const form = $(form_name);
    App.ajax({ path: `${endpoint}`, method, data: { ...data.data }}).then(response => {
        App.success();
        $(form_name).find('.overlay').remove()
        if (redirect !== null && redirect !== false) {
            if (response.url !== undefined) {
                redirect = response.url;
            }
            setTimeout(() => {
                window.location.href = redirect;
            }, 1500)
        }
        if (callback !== undefined) {
            callback(response);

        }
        setTimeout(() => {
            App.success(true);
        }, 1500)
    }).catch(errors => {
        console.log(errors)
        $(form_name).find('.overlay-div').remove();
        App.renderErrors(errors, form)
        App.sweet(errors.responseJSON.message);
    })
}

const initDropzone = (class_name, callback, acceptedFiles = '.pdf', uploadMultiple = false, maxFiles = 1) => {
    $(`.${class_name}`).dropzone({
        uploadMultiple,
        maxFiles,
        acceptedFiles,
        init: function () {
            this.on("error", function (file, errorMessage) {
                App.sweet(errorMessage.file ?? errorMessage.message);
                this.removeFile(file);
            }).on("complete", function (file, message) {
                callback(file, message, this);
            });
        },
    });
};

const upload = (parameters) => {
    const callback = parameters.callback;
    App.initDropzone(parameters.class, (file, message) => {
        callback(file, message)
    }, parameters.acceptedFiles, parameters.multiple, parameters.maxFiles)
}

const preview_image = (parameters) => {
    $(parameters.container).parent().parent().append(`<div class="col-xs-12 upload-preview"><img src="${parameters.file}" /></div>`);
}

const preview_images = (parameters) => {
    const object = parameters.images;
    let execute = 0;
    let preview = '<div class="media-library">';
    for (const k in object) {
        const image = object[k]
        if ($(`.form-element`).find(`input.media_${image.media_id}`).length === 0) {
            $(`.form-element`).append(`<input type="hidden" class="media_${image.media_id}" name="image[]" value="${image.media_id}" />`);
            preview += `<div class="media-item"><img src="${image.url}"></div>`;
            execute++;
        }
    }
    preview += "</div>";
    if (execute > 0) {
        $(parameters.container).append(preview);
    }
}

const append_form = (parameters) => {
    $(`.form-element`).append(`<input type="hidden" name="image" value="${parameters.file}" />`);
}

const filter_elements = (query, container, element) => {
    $(container).find(element).each(function () {
        if ($(this).text().toLowerCase().trim().indexOf(query.toLowerCase()) === -1) {
            $(this).hide();
        } else {
            $(this).show();
        }
    });
}

const delete_model_image = (e) => {
    App.sweetConfirm("Sei sicuro di voler rimuovere l'immagine?", () => {
        const model = e.data('model');
        const id = e.data('id');
        App.ajax({ path: `/backoffice/${model}/${id}/image`, method: 'delete'}).then(() => {
            $(`.model_image`).remove();
        })
    })
}

const store_category = (e) => {
    const data = App.serialize(`.store-category`);

    App.sweetConfirm("Sei sicuro di voler creare la categoria?", () => {
        App.ajax({ path: `/backoffice/categories/create`, method: 'post', data: { ...data.data }}).then(response => {
            list_categories(type, response.id);
        }).catch(errors => {
            App.sweet('Il nome della categoria è obbligatoria!')
        })
    }, undefined)
}

const list_categories = (category_type, category_id) => {
    App.ajax({ path: `/backoffice/categories`, method: 'get', data: {category_type, category_id}}).then(response => {
        $(`.list-categories`).html(response.html)
    })
}

const date = (parameters) => {
    const currentDate = moment().format("DD-MM-YYYY");
    const input = $(`#${parameters.date}`);
    input.daterangepicker({
        singleDatePicker:true,
        locale: {
            format: 'DD/MM/YYYY'
        },
        showDropdowns: true,
        minDate: currentDate,
        autoApply: true,
        autoUpdateInput: true,
    });
}

const date_range = (parameters) => {
    const currentDate = moment().format("DD-MM-YYYY");
    const inputs = $(`#${parameters.from}, #${parameters.to}`);
    inputs.daterangepicker({
        locale: {
            format: 'DD/MM/YYYY'
        },
        alwaysShowCalendars: true,
        minDate: currentDate,
        autoApply: true,
        autoUpdateInput: false,
    });
    inputs.on('apply.daterangepicker', function(ev, picker) {
        const selectedStartDate = picker.startDate.format('DD/MM/YYYY');
        const selectedEndDate = picker.endDate.format('DD/MM/YYYY');
        const from = $(`#${parameters.from}`);
        const to = $(`#${parameters.to}`);
        from.val(selectedStartDate);
        to.val(selectedEndDate);
        to.data('daterangepicker').setStartDate(selectedStartDate)
        to.data('daterangepicker').setEndDate(selectedEndDate)
        from.data('daterangepicker').setStartDate(selectedStartDate)
        from.data('daterangepicker').setEndDate(selectedEndDate)
    });
}

const plus_minus = (e) =>  {
    const $button = e;
    const $input = $button.parent().find("input.quantity-input");
    $input.val((i, v) => {
        const newVal = (+v || 0) + (+$button.data('multi') || 0);
        const maxVal = +$button.data('max');
        return Math.max(0, maxVal ? Math.min(newVal, maxVal) : newVal);
    });
}

const delete_element = (e) =>  {
    const url = e.data('url');
    App.sweetConfirm('Sei sicuro di voler cancellare questo elemento?', () => {
        App.ajax({ path: url, method: 'delete'}).then(() => {
            toastr.success('Ottimo!', 'Elemento cancellato con successo!');
            App.reloadTable()
        })
    })
}

const selectChoice = (parameters) => {
    const select = document.getElementById(parameters.id);
    const choices = new Choices(select, {
        placeholder: true,
        searchEnabled: true,
        shouldSort: false,
        searchChoices: false, // Disattiva la ricerca locale
        loadingText: 'Caricamento...',
        noResultsText: 'Nessun risultato',
        itemSelectText: 'Seleziona',
        allowHTML: true
    });

// Intercetta il termine digitato
    select.addEventListener('search', function (event) {
        const searchTerm = event.detail.value;

        // Mostra "loading" mentre cerca
        choices.clearChoices();
        choices.setChoices([{ label: 'Caricamento...', value: '', disabled: true }], 'value', 'label', false);
        const body = parameters.body;
        body.value = searchTerm;
        // Chiamata AJAX
        fetch(`/backoffice/${parameters.path}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf_token
            },
            body:  JSON.stringify(body)
        })
            .then(response => response.json())
            .then(data => {
                const searchTerm = event.detail.value.toLowerCase();
                const highlight = (text) => {
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    return text.replace(regex, '<mark>$1</mark>'); // evidenziamo con <mark>
                };
                // Prepara i dati per Choices
                const results = data.results.map(item => {
                    return {
                        value: item.id, // cambia secondo la tua struttura
                        label: `${highlight(item.text)}`
                    };
                });

                choices.setChoices(results, 'value', 'label', true);
            })
            .catch(() => {
                choices.setChoices([], 'value', 'label', true);
            });
    });
    if (parameters.first !== undefined) {
        choices.setChoices([{
            value: parameters.first,
            label: parameters.first_label,
            selected: false
        }], 'value', 'label', false);
    }
}

const init = () => {

    $(document).on("click", ".btn-status", function () {
        changeStatusObject($(this));
    });

    $(document).on("datatable", function (e, parameters) {
        datatable(parameters);
    });

    $(document).on("startSelect2", function (e, parameters) {
        loadSelect2(parameters);
    });

    $(document).on("loadSwitchTrigger", function (e, parameters) {
        loadSwitch(parameters.container)
    });

    $(document).on("reloadDatatable", function (e, parameters) {
        reloadTable()
    });

    $(document).on("click", ".btn-find", function () {
        reloadTable();
    });

    $(document).on("blur keyup", ".advanced-search input",
        debounce(function () {
            if ($(this).val().length === 0 || $(this).val().length > 2) {
                reloadTable();
            }
        }, 500)
    );

    $(document).on(
        "change",
        ".advanced-search select, .advanced-search input",
        function () {
            reloadTable();
        }
    );

    if ($(".logs").length > 0) {
        logs();
        $(".reload-logs").on("click", function () {
            logs();
        });
    }

    if ($(".dashboard-report").length > 0) {
        dashboard();
        $(".load-dashboard-ajax").on("click", function () {
            dashboard();
        });
    }

    $(document).on("keyup blur", ".is_number", function () {
        const text = $(this);
        text.val(text.val().toString().replace(/,/g, "."));
    });

    $(document).on('click', '.quantity-right-plus', function() {
        const input = $(this).parent().parent().find('.input-number');
        const max = input.data('max');
        const quantity = parseInt(input.val());
        if (quantity < max) {
            $(this).parent().parent().find('.input-number').val(quantity + 1);
        }
    });

    $(document).on('click', '.quantity-left-minus', function(e){
        const input = $(this).parent().parent().find('.input-number');
        const quantity = parseInt(input.val());
        if(quantity > 0){
            $(this).parent().parent().find('.input-number').val(quantity - 1);
        }
    });

    $(document).on("filter_elements",  function (e, parameters) {
        filter_elements(parameters.query, parameters.container, parameters.element)
    });

    $(document).on("upload",  function (e, parameters) {
        upload(parameters)
    });

    $(document).on("preview_image",  function (e, parameters) {
        preview_image(parameters)
    });
    $(document).on("preview_images",  function (e, parameters) {
        preview_images(parameters)
    });

    $(document).on("append_form",  function (e, parameters) {
        append_form(parameters)
    });

    $(document).on("click", ".btn-delete-model-image", function () {
        delete_model_image($(this))
    });

    $(document).on("click", ".btn-store-category", function () {
        store_category($(this))
    });

    $(document).on("date_range",  function (e, parameters) {
        date_range(parameters)
    });

    $(document).on("date",  function (e, parameters) {
        date(parameters)
    });

    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    $(document).on('click', '.plus-minus-button', function () {
        plus_minus($(this))
    })

    $(document).on('click', '.btn-delete-element', function () {
        delete_element($(this))
    })

    $(document).on("selectChoice", function (e, parameters) {
        selectChoice(parameters)
    });
};


const App = {
    init,
    translate,
    sweetConfirm,
    sweet,
    ajax,
    serialize,
    serializeObject,
    renderErrors,
    removeDropzone,
    reloadTable,
    loadSwitch,
    initDropzone,
    success,
    debounce,
    clearForm,
    update_or_create,
    sweetInput,
};

export default App;
