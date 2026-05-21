function togglePw() {

    const input = document.getElementById('password');
    const icon = document.querySelector('.toggle-pw');

    if (input.type === 'password') {

        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');

    } else {

        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');

    }
}

document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector("form");

    form.addEventListener("submit", function (e) {

        e.preventDefault();

        const email =
        document.getElementById("email").value.trim();

        const password =
        document.getElementById("password").value.trim();

        if (email === "" || password === "") {

            alert("Please fill in all fields.");

            return;
        }

        const btn =
        document.querySelector(".btn-login");

        btn.innerText = "Signing In...";
        btn.disabled = true;

        setTimeout(() => {

            form.submit();

        }, 650);

    });

});