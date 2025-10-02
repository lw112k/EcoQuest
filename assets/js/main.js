// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {

    // Helper function to display temporary messages on the form
    const displayMessage = (containerId, message, isError = true) => {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = message;
            container.className = isError ? 'error-message' : 'success-message';
        }
    };

    // --- 1. Registration Form Validation ---
    const registerForm = document.getElementById('registerForm');

    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {

            // Get fields
            const fullName = document.getElementById('full_name').value.trim();
            const studentId = document.getElementById('student_id').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            let isValid = true;
            let errorMessage = '';

            // Check 1: Empty Fields Check
            if (!fullName || !studentId || !email || !password || !confirmPassword) {
                isValid = false;
                errorMessage = 'Walao eh, don\'t leave any field empty!';
            }

            // Check 2: Password Match Check
            else if (password !== confirmPassword) {
                isValid = false;
                errorMessage = 'Your password and confirmation password do not match. Fix it, cepat!';
                // Clear password fields on mismatch for security
                document.getElementById('password').value = '';
                document.getElementById('confirm_password').value = '';
            }

            // Check 3: Minimum Password Length (Good Practice)
            else if (password.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long.';
            }

            // If validation failed, prevent form submission
            if (!isValid) {
                e.preventDefault();
                // Display the error message
                // Note: You need a message placeholder in register.php for this to work.
                // For now, we will use a simple alert if you don't have a placeholder.

                // If you added a div like <div id="validationMessage"></div> above the submit button:
                // displayMessage('validationMessage', errorMessage, true);

                // For simplicity now, let's use an alert as a fallback, but a dedicated div is better UX.
                alert('Validation failed: ' + errorMessage);

                // To properly display messages, add a placeholder div in register.php,
                // e.g., right before the submit button:
                // <div id="validationMessage" class="error-message"></div>
            }
            // If isValid is true, the form submits normally to register_process.php
        });
    }


    // --- 2. Mobile Menu Toggle (from previous discussion) ---
    // Implement this for your responsive design requirement!
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-links');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('is-open');
            navToggle.classList.toggle('is-active');
        });
    }

});