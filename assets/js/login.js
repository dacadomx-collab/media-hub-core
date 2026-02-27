(function () {
    const form = document.getElementById("loginForm");
    const email = document.getElementById("email");
    const password = document.getElementById("password");
    const systemMessage = document.getElementById("systemMessage");

    if (!form || !email || !password || !systemMessage) {
        return;
    }

    const suspiciousChars = /[<>"'`{}[\]\\;$]/g;

    const messages = {
        email: document.getElementById("emailMessage"),
        password: document.getElementById("passwordMessage")
    };

    const setFieldState = (field, messageEl, ok, message) => {
        field.classList.remove("input-error", "input-valid");
        messageEl.textContent = message || "";

        if (ok === true) {
            field.classList.add("input-valid");
        }

        if (ok === false) {
            field.classList.add("input-error");
        }
    };

    const stripSuspicious = (field, messageEl) => {
        const clean = field.value.replace(suspiciousChars, "");
        if (clean !== field.value) {
            field.value = clean;
            setFieldState(field, messageEl, false, "Se removieron caracteres no permitidos.");
            return false;
        }
        return true;
    };

    const validateEmail = () => {
        const cleaned = stripSuspicious(email, messages.email);
        const value = email.value.trim();

        if (!value) {
            setFieldState(email, messages.email, false, "Ingresa tu correo.");
            return false;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        if (!emailPattern.test(value)) {
            setFieldState(email, messages.email, false, "Formato de correo inválido.");
            return false;
        }

        setFieldState(email, messages.email, cleaned ? true : false, "Correo verificado.");
        return cleaned;
    };

    const validatePassword = () => {
        const cleaned = stripSuspicious(password, messages.password);
        const value = password.value;

        if (!value) {
            setFieldState(password, messages.password, false, "Ingresa tu clave.");
            return false;
        }

        if (value.length < 8) {
            setFieldState(password, messages.password, false, "Mínimo 8 caracteres.");
            return false;
        }

        setFieldState(password, messages.password, cleaned ? true : false, "Clave válida.");
        return cleaned;
    };

    [
        { field: email, validate: validateEmail, messageEl: messages.email },
        { field: password, validate: validatePassword, messageEl: messages.password }
    ].forEach(({ field, validate, messageEl }) => {
        field.addEventListener("input", validate);
        field.addEventListener("blur", validate);
        field.addEventListener("paste", () => {
            setTimeout(() => stripSuspicious(field, messageEl), 0);
        });
    });

    form.addEventListener("submit", (event) => {
        const emailOk = validateEmail();
        const passOk = validatePassword();

        if (!emailOk || !passOk) {
            event.preventDefault();
            systemMessage.textContent = "Credenciales con formato no seguro. Revisa los campos.";
            systemMessage.classList.add("error");
            systemMessage.classList.remove("ok");
            return;
        }

        systemMessage.textContent = "Validacion local completada. Preparando autenticacion segura...";
        systemMessage.classList.add("ok");
        systemMessage.classList.remove("error");
    });
})();
