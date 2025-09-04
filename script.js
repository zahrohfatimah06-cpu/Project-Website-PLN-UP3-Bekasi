// Validasi form login
function validateLoginForm(event) {
    event.preventDefault();

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();

    if (username === "" || password === "") {
        Swal.fire({
            icon: 'warning',
            title: 'Form Tidak Lengkap',
            text: 'Username dan Password harus diisi!',
            confirmButtonColor: '#007bff'
        });
        return false;
    }

    document.getElementById("loginForm").submit();
}
// Validasi form registrasi
function validateRegisterForm(event) {
    event.preventDefault();

    const username = document.getElementById("reg-username").value.trim();
    const password = document.getElementById("reg-password").value.trim();
    const confirm = document.getElementById("reg-confirm").value.trim();

    if (username === "" || password === "" || confirm === "") {
        Swal.fire({
            icon: 'warning',
            title: 'Lengkapi Form',
            text: 'Semua kolom harus diisi.',
        });
        return false;
    }

    if (password !== confirm) {
        Swal.fire({
            icon: 'error',
            title: 'Password Tidak Sama',
            text: 'Password dan konfirmasi tidak cocok!',
        });
        return false;
    }

    document.getElementById("registerForm").submit();
}
