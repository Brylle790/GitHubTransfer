document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById('loginForm');

    form.addEventListener('submit', function (event) {
        // Prevent the default form submission
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Add Bootstrap validation styles
        form.classList.add('was-validated');
    }, false);
});