import App from "./app.js";
import Login from "./login.js";

const init = () => {
    App.init();
    Login.init();
}

$(function () {
    init();
});
