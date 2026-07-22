/**
 * Client-Side Authentication Guard.
 * Ensures local session validity before rendering dashboard layouts.
 * 
 * INTERVIEW LEVEL CONCEPTS:
 * 1. Token Verification: Checks token existence in LocalStorage. If missing, intercepts 
 *    rendering lifecycle and redirects to login, implementing dynamic access locks.
 */
(function () {
    const token = localStorage.getItem('auth_token');
    const currentPath = window.location.pathname;

    // 1. Skip checks on public auth pages
    if (currentPath.endsWith('login.html') || currentPath.endsWith('register.html')) {
        return;
    }

    // 2. Enforce active JWT token check
    if (!token) {
        alert('Authentication required. Redirecting to login page...');
        window.location.href = 'login.html';
        return;
    }

    // 3. Bind events when DOM loads
    document.addEventListener('DOMContentLoaded', function () {
        // Update user email references in top bar profile layouts if present
        const userEmail = localStorage.getItem('user_email');
        if (userEmail) {
            const profileLabels = document.querySelectorAll('.user-name, .user-email, .profile-name');
            profileLabels.forEach(el => {
                if (el.tagName === 'INPUT') {
                    el.value = userEmail;
                } else {
                    el.textContent = userEmail;
                }
            });
        }

        // Intercept logout actions to clear tokens
        const logoutButtons = document.querySelectorAll('a[href="login.html"], .logout-btn, a[href*="logout"]');
        logoutButtons.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (confirm('Are you sure you want to sign out?')) {
                    localStorage.clear(); // Wipe JWT token & tenantSlug contexts
                    window.location.href = 'login.html';
                }
            });
        });
    });
})();
