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

// Auto-hide alert messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alertBox = document.getElementById('alertBox');
    const alertBox2 = document.getElementById('alertBox2');
   if (alertBox) {
       setTimeout(function() {
           alertBox.style.display = 'none';
       }, 5000);
   }
   if (alertBox2) {
       setTimeout(function() {
           alertBox2.style.display = 'none';
       }, 5000);
   }
});

// reset advanced search filters when clear button is clicked

document.addEventListener('DOMContentLoaded', function() {
    const clearButton = document.getElementById('clearFilters');
    
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            // Simply redirect to the dashboard page without any query parameters
            window.location.href = 'dashboard.php';
        });
    }
});

// Dashboard Responsive JavaScript

// Lets make the sidebar toggleable on small screens
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.querySelector('.sidebar'); // Select the sidebar element
    const filterSection = document.getElementById('filterSection');
    const tog = document.querySelector('.tog');

    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function() {
            // rem
            sidebar.classList.toggle('sm-none');
        });
    }

    if (tog && filterSection) {
        tog.addEventListener('click', function() {
            filterSection.classList.toggle('sm-none');
            if (filterSection.classList.contains('sm-none')) {
                tog.textContent = '+';
            } else {
                tog.textContent = '-';
            }
        });
    }
});