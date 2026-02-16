const sweetConfirm = (text, callback, willClose, title) => {
    swal(
        {
            title: title ?? translate("javascript.swal.confirm.title"),
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
            buttonsStyling: false
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
    setTimeout(() => {
        const confirmButton = document.querySelector('.swal-button--confirm');
        if (confirmButton) {
            confirmButton.setAttribute('data-mode', 'Primary Medium');
        }
    }, 100);
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

const reloadTable = (name = '.datatable') => {
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

const removeDropzone = () => {
    if (Dropzone.instances.length > 0) {
        Dropzone.instances.forEach((e) => {
            e.off();
            e.destroy();
        });
    }
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

const datatable = (parameters) => {
    const filters = function(d) {
        const filterData = {};
        $('.filters-miticko input, .filters-miticko select').each(function() {
            if ($(this).attr('name')) {
                filterData[$(this).attr('name')] = $(this).val();
            }
        });
        d.filters = filterData;
        return d;
    };
    let table = new DataTable('.datatable', {
        responsive: true,
        searching: false,
        ordering:  false,
        processing: true,
        serverSide: true,
        pagingType: 'simple',
        layout: {
            topStart: '',
            bottomEnd: 'pageLength',
            bottomStart: '',
            topEnd: {
                info: {},
                paging: {}
            }
        },
        language: {
            lengthMenu: "ELEMENTI PER PAGINA _MENU_",
            paginate: {
                previous: '<',
                next: '>'
            },
            info: '_START_-_END_ di _TOTAL_',
            infoEmpty: '0-0 di 0'
        },
        columns: parameters.columns,
        ajax: {
            url: parameters.path,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data:  filters
        },
        drawCallback: function (api) {
            if (parameters.drawCallback !== undefined) {
                parameters.drawCallback(api);
            }
        }
    });
}

const fill_filters = (type, input, value, name) => {
    if (type === 'daterange') {
        input.val(`${value.start.toLocaleDateString('it-IT').split('T')[0]}|${value.end.toLocaleDateString('it-IT').split('T')[0]}`);
        const filter = input.parent();
        const container = filter.find(`.label-container`);
        container.html(`<span class="fa-regular fa-xmark remove-filter" data-name="${name}"></span><span class="filter-filled">${date_long_ita(value.start)} - ${date_long_ita(value.end)}</span>`);
        filter.addClass('filled')
    }
}

const date_long_ita = (date) => {
    const mesi = ['GEN', 'FEB', 'MAR', 'APR', 'MAG', 'GIU', 'LUG', 'AGO', 'SET', 'OTT', 'NOV', 'DIC'];
    const day = date.getDate();
    const month = mesi[date.getMonth()];
    const year = date.getFullYear();
    return `${day} ${month} ${year}`;
}

const filter_date_range = (type, modal, name) => {
    let output;
    const btn_success = modal.find(`.btn-success`);
    const btn_cancel = modal.find(`.btn-cancel`);
    const filter = $("#calendar-container").data('filter');
    const input = $(`.filters-miticko input[name='${filter}']`)
    flatpickr("#calendar-container", {
        inline: true,
        mode: "range",
        locale: "it",
        monthSelectorType: "static",
        dateFormat: "d/m/Y",
        onChange: function(dates) {
            if (input && dates.length === 2) {
                const [startDate, endDate] = dates;
                const json_date = {
                    start: startDate,
                    end: endDate
                };
                output = {
                    type,
                    input,
                    json_date
                }
            }
        },
        onReady: function(selectedDates, dateStr, instance) {
            const yearInput = instance.calendarContainer.querySelector(".numInputWrapper");
            if (yearInput) {
                const currentYear = instance.currentYear;
                const yearSpan = document.createElement("span");
                yearSpan.className = "cur-year";
                yearSpan.textContent = ` ${currentYear}`;
                yearInput.parentNode.replaceChild(yearSpan, yearInput);
            }
        },
        onMonthChange: function(selectedDates, dateStr, instance) {
            const yearSpan = instance.calendarContainer.querySelector(".cur-year");
            if (yearSpan) {
                yearSpan.textContent =  ` ${instance.currentYear}`;
            }
        }

    });
    modal.modal('show');
    btn_success.on('click', function () {
        fill_filters(output.type, output.input, output.json_date, name);
        modal.modal('hide');
        App.reloadTable()
    })
    btn_cancel.on('click', function () {
        modal.modal('hide');
    })
}

const change_status_element = (btn) => {
    const is_active = btn.data("is-active");
    App.sweetConfirm(`Confermi?`, () => {
        const id = btn.data("id");
        const route = btn.data("route");
        ajax({ path: `/backoffice/${route}/${id}/status`, method: "post" }).then(() => {
            reloadTable();
        });
    }, null,  `Stai per ${is_active === 1 ? 'disattivare' : 'attivare'} questo elemento`)
}

const init = () => {

    $(document).on("loadSwitchTrigger", function (e, parameters) {
        loadSwitch(parameters.container)
    });

    $(document).on("reloadDatatable", function (e, parameters) {
        reloadTable()
    });

    $(document).on("datatable", function (e, parameters) {
        datatable(parameters)
    });

    $(document).on("click", ".btn-find", function () {
        reloadTable();
    });

    $(document).on("click", ".btn-status", function () {
        change_status_element($(this));
    });

    $(document).on("blur keyup", ".filters-miticko input",
        debounce(function () {
            if ($(this).val().length === 0 || $(this).val().length > 2) {
                reloadTable();
            }
        }, 500)
    );

    $(document).on("change", ".filters-miticko select, .filters-miticko input", function () {
            reloadTable();
        }
    );

    $(document).on("keyup blur", ".is_number", function () {
        const text = $(this);
        text.val(text.val().toString().replace(/,/g, "."));
    });

    $(document).on("upload",  function (e, parameters) {
        upload(parameters)
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

    $(document).on("selectChoice", function (e, parameters) {
        selectChoice(parameters)
    });

    $(document).on("fetch", function (e, parameters) {
        ajax(parameters).then((response) => {
            parameters.then(response);
        }).catch((response) => {
            parameters.catch(response);
        })
    });

    $(document).on("sweetConfirmTrigger", function (e, parameters) {
        sweetConfirm(parameters.text, parameters.callback ?? null, parameters.willClose ?? null, parameters.title ?? null);
    });

    $(document).on('click', '.filters-miticko .filter', function () {
        const filter = $(this);
        const filter_type = filter.data('type');
        const modal = $(`#filter-${filter_type}`);
        const name = filter.data('name');
        const label = filter.data('label');
        const close = filter.data('close');
        if (filter_type === 'daterange') {
            if (!close) {
                filter_date_range(filter_type, modal, name, label)
            }
        }
        if (filter_type === 'status') {
            modal.modal('show');
            const btn_success = modal.find(`.btn-success`);
            const btn_cancel = modal.find(`.btn-cancel`);
            btn_success.on('click', function () {
                const inputs = JSON.stringify(modal.find('input').filter((i, el) => $(el).val() !== '0')
                    .map(function() {
                        return {
                            name: $(this).attr('name'),
                            value: $(this).val()
                        };
                    }).get());
                filter.find('input').val(inputs)

                modal.modal('hide');
                App.reloadTable()
            });
            btn_cancel.on('click', function () {
                modal.modal('hide');
                filter.find('input').val('')

                modal.modal('hide');
                App.reloadTable()
            })
        }
    });

    $(document).on('click', '.remove-filter', function () {
        const name = $(this).data('name');
        const input = $(`.filters-miticko input[name='${name}']`);
        const filter = input.parent();
        const label = filter.data('label');
        const container = filter.find(`.label-container`);
        container.html(`<span class="fa fa-square-plus"></span><span class="label">${label}</span>`);
        filter.removeClass('filled')
        filter.data('close', true)
        setTimeout(() => {
            filter.data('close', false)
        }, 250)
        input.val('');
        App.reloadTable()
    });

    $(document).on('click', '.checkbox-miticko', function () {
        const input = $(this).find('input');
        const icon = $(this).find('.fa-regular');
        if (input.val() === '0') {
            icon.removeClass('fa-square');
            icon.addClass('fa-square-check');
            input.val('1')
        } else {
            icon.removeClass('fa-square-check');
            icon.addClass('fa-square');
            input.val('0')
        }
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
    debounce,
    clearForm,
    update_or_create,
    sweetInput,
};

export default App;
