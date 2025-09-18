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


// Manage users page
document.addEventListener('DOMContentLoaded', function() {
    // Clear filters functionality
    const clearFilters = document.getElementById('clearFilters');
    if (clearFilters) {
        clearFilters.addEventListener('click', function() {
            window.location.href = 'manage_users.php';
        });
    }
    
    // Mobile menu functionality
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (menuBtn && sidebar && overlay) {
        menuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
    
    // User dropdown functionality
    const userToggle = document.querySelector('.user-toggle');
    const userDropdown = document.querySelector('.dropdown-menu');
    
    if (userToggle && userDropdown) {
        userToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
    }
    
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
});


// Lets make agent modal open and close
document.addEventListener('DOMContentLoaded', function() {
    const openModalBtn = document.getElementById('openModal');
    const closeModalBtn = document.getElementById('closeModal');
    const modal = document.getElementById('modal');


    if (openModalBtn && modal) {
        openModalBtn.addEventListener('click', function() {
            
            // add
            modal.style.display = 'flex';
        });
    }
    if (closeModalBtn && modal) {
        closeModalBtn.addEventListener('click', function() {
            // rem
            modal.style.display = 'none';
        });
    }


});