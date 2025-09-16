// change adminLogin button text to loading when clicked for 500ms
document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.getElementById('adminLoginBtn');
    loginBtn.addEventListener('click', function() {
        loginBtn.textContent = 'Loading...';
        setTimeout(function() {
            loginBtn.textContent = 'Login';
        }, 1000);
    });
});