import App from "./app.js";

const login = () => {
    const serialized = App.serialize('.form-login');
    App.ajax({path: `/login`, method: 'post', data: serialized.data}).then(response => {
        const supporting_text = $('.form-login')
            .find("input:last")
            .parent()
            .parent()
            .find(".supporting-text");
        const container = supporting_text.parent();
        container.attr('data-mode', 'SupptextAppearance-Success');
        supporting_text
            .show()
            .html('Login effettuato con successo');
        setTimeout(() => {
            location.href = response.url
        }, 1000)
    }).catch(errors => {
        console.error(errors);
        if (errors.status === 419 || errors.status === 401) {
            const supporting_text = $('.form-login')
                .find("input:last")
                .parent()
                .parent()
                .find(".supporting-text");
            supporting_text
                .addClass("danger")
                .show()
                .html('Ricarica la pagina o cambia connessione');
            return;
        }
        App.renderErrors(errors, $('.form-login'))
    })
}
const logout = () => {
    App.ajax({path: `/logout`, method: 'post'}).then(() => {
        location.href = '/login'
    })
}
const resetPassword = () => {
    const serialized = App.serialize('.reset-password');
    App.ajax({path: `/reset-password`, method: 'post', data: serialized.data}).then(response => {
        App.sweet('La password è stata aggiornata con successo', 'Perfetto!', 'success', () => {
            location.href = response.url;
        })

    }).catch(errors => {
        App.sweet(errors.responseJSON.message)
    })
}

const init = () => {

    $(document).on('click', '.btn-login', function () {
        login()
    })

    $(document).on('click', '.btn-logout', function () {
        logout()
    })

    $(document).on('click', '.btn-reset-password', function () {
        resetPassword()
    })

    $(".form-login input").each(function () {
        $(this).keypress((e) => {
            if (e.keyCode === 13) {
                login();
            }
        });
    });
}

const Login = {
    init,
}

export default Login
