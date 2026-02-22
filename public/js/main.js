/**
 * AaoSikheSystemApp - Complete JavaScript Application
 * Combines SystemCore security features with AaoSikheSystem UI functionality
 */

class AaoSikheSystemApp {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        this.animationManager = null;
        this.init();
    }

    async init() {
        await this.setupAnimationManager();
        this.setupCSRF();
        this.setupAjax();
        this.setupForms();
        this.initAnimations();
        this.initCounters();
        this.initScrollEffects();
        this.setupGlobalEventHandlers();
        
        console.log('AaoSikheSystemApp initialized successfully');
    }

    /**
     * Setup Animation Manager
     */
    async setupAnimationManager() {
        try {
            // Dynamically import animation manager
            const { AnimationManager } = await import('./animation.js');
            this.animationManager = new AnimationManager();
        } catch (error) {
            console.warn('AnimationManager not available, using fallback animations');
            this.animationManager = null;
        }
    }

    /**
     * CSRF Token Setup
     */
    setupCSRF() {
        // Add CSRF token to all AJAX requests (jQuery)
        if (window.$ && $.ajaxSetup) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });
        }

        // For native fetch
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                if (args[1]) {
                    args[1].headers = {
                        ...args[1].headers,
                        'X-CSRF-Token': this.csrfToken
                    };
                }
                return originalFetch.apply(this, args);
            }.bind(this);
        }
    }

    /**
     * AJAX Setup with Error Handling
     */
    setupAjax() {
        // Global AJAX error handling (jQuery)
        if (window.$) {
            $(document).ajaxError(function(event, jqXHR, settings, error) {
                console.error('AJAX Error:', error);
                
                if (jqXHR.status === 403) {
                    this.showNotification('Session expired. Please refresh the page.', 'error');
                } else if (jqXHR.status >= 500) {
                    this.showNotification('Server error. Please try again later.', 'error');
                }
            }.bind(this));
        }

        // Native fetch error handling
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            try {
                const response = await originalFetch(...args);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response;
            } catch (error) {
                console.error('Fetch Error:', error);
                this.showNotification('Network error. Please check your connection.', 'error');
                throw error;
            }
        };
    }

    /**
     * Form Setup and Validation
     */
    setupForms() {
        // Auto-validate forms with data-validate attribute
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Contact form handling
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Newsletter subscription
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => this.handleNewsletterSubmit(e));
        }

        // Login form handling
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
    }

    /**
     * Form Validation
     */
    validateForm(form) {
        let isValid = true;
        const errors = [];
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            const value = field.value.trim();
            const fieldName = field.getAttribute('name') || field.getAttribute('id') || 'This field';

            if (!value) {
                isValid = false;
                errors.push(`${fieldName} is required`);
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }

            // Email validation
            if (field.type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errors.push('Please enter a valid email address');
                    field.classList.add('is-invalid');
                }
            }

            // Password strength validation
            if (field.type === 'password' && value) {
                if (value.length < 8) {
                    isValid = false;
                    errors.push('Password must be at least 8 characters long');
                    field.classList.add('is-invalid');
                }
            }
        });

        if (!isValid && errors.length > 0) {
            this.showNotification(errors.join('<br>'), 'error');
        }

        return isValid;
    }

    /**
     * Handle Form Submission
     */
    handleFormSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Show loading state
        submitBtn.innerHTML = '<span class="loading"></span> Processing...';
        submitBtn.disabled = true;

        // Simulate API call
        setTimeout(() => {
            // Show success message
            this.showNotification('Thank you for your message! We\'ll get back to you soon.', 'success');

            // Reset form and button
            form.reset();
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 2000);
    }

    /**
     * Handle Newsletter Subscription
     */
    handleNewsletterSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const email = form.querySelector('input[type="email"]').value;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        if (!this.validateForm(form)) {
            return false;
        }

        // Show loading state
        submitBtn.innerHTML = '<span class="loading"></span> Subscribing...';
        submitBtn.disabled = true;

        // Simulate API call
        setTimeout(() => {
            // Show success message
            this.showNotification(`Thank you for subscribing with ${email}! You'll hear from us soon.`, 'success');

            // Reset form and button
            form.reset();
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 1500);
    }

    /**
     * Handle Login
     */
    handleLogin(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        if (!this.validateForm(form)) {
            return false;
        }

        const formData = new FormData(form);
        
        // Show loading state
        submitBtn.innerHTML = '<span class="loading"></span> Signing in...';
        submitBtn.disabled = true;

        // AJAX login
        fetch('/login', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-Token': this.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                this.showNotification('Login successful!', 'success');
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            this.showNotification('Login failed. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    /**
     * Animation System
     */
    initAnimations() {
        if (this.animationManager) {
            this.animationManager.initializeAnimations();
        } else {
            this.initBasicAnimations();
        }
    }

    initBasicAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.card, .feature-icon, .course-card, .testimonial-card, .hero-section').forEach(el => {
            observer.observe(el);
        });
    }

    /**
     * Counter Animations
     */
    initCounters() {
        const counters = document.querySelectorAll('.counter');
        
        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target') || +counter.textContent;
            const increment = target / 100;
            let current = 0;

            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    counter.innerText = Math.ceil(current);
                    setTimeout(updateCounter, 20);
                } else {
                    counter.innerText = target;
                }
            };

            // Start counter when in viewport
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            });

            observer.observe(counter);
        });
    }

    /**
     * Scroll Effects
     */
    initScrollEffects() {
        // Parallax effect for hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = document.querySelector('.hero-section');
            
            if (parallax) {
                parallax.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });

        // Active navigation highlighting
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

        window.addEventListener('scroll', () => {
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (window.pageYOffset >= sectionTop - 100) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active', 'fw-bold');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active', 'fw-bold');
                }
            });
        });

        // Back to top button
        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTop.style.display = 'block';
                } else {
                    backToTop.style.display = 'none';
                }
            });

            backToTop.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    }

    /**
     * Global Event Handlers
     */
    setupGlobalEventHandlers() {
        // Smooth scrolling for anchor links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (link) {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });

        // Image lazy loading
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * DataTables Integration
     */
    initDataTable(selector, ajaxUrl, columns, options = {}) {
        if (window.$ && $.fn.DataTable) {
            return $(selector).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: ajaxUrl,
                    type: 'GET',
                    data: function(d) {
                        // Add custom parameters if needed
                        return d;
                    }
                },
                columns: columns,
                language: {
                    processing: '<i class="fa fa-spinner fa-spin fa-fw"></i> Processing...'
                },
                ...options
            });
        } else {
            console.warn('DataTables not available');
            return null;
        }
    }

    /**
     * Chart.js Integration
     */
    initChart(canvasId, type, data, options = {}) {
        if (window.Chart) {
            const ctx = document.getElementById(canvasId)?.getContext('2d');
            if (ctx) {
                return new Chart(ctx, {
                    type: type,
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        ...options
                    }
                });
            }
        } else {
            console.warn('Chart.js not available');
            return null;
        }
    }

    /**
     * Confirmation Dialog
     */
    confirm(message, callback, options = {}) {
        if (window.Swal) {
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed!',
                ...options
            }).then((result) => {
                if (result.isConfirmed) {
                    callback();
                }
            });
        } else {
            // Fallback to native confirm
            if (confirm(message)) {
                callback();
            }
        }
    }

    /**
     * Notification System
     */
    showNotification(message, type = 'success', duration = 5000) {
        // Try SweetAlert2 first
        if (window.Swal && type !== 'info') {
            Swal.fire({
                icon: type,
                title: type.charAt(0).toUpperCase() + type.slice(1),
                text: message,
                timer: duration,
                showConfirmButton: false
            });
            return;
        }

        // Fallback to custom notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${this.getNotificationIcon(type)} me-2"></i>
                <div>${message}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto remove after duration
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, duration);

        // Close button handler
        notification.querySelector('.btn-close').addEventListener('click', () => {
            notification.remove();
        });
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    /**
     * Utility Methods
     */
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Format number with commas
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Get query parameters
    getQueryParams() {
        const params = new URLSearchParams(window.location.search);
        const result = {};
        for (const [key, value] of params) {
            result[key] = value;
        }
        return result;
    }

    // Set query parameters
    setQueryParams(params) {
        const url = new URL(window.location);
        Object.keys(params).forEach(key => {
            url.searchParams.set(key, params[key]);
        });
        window.history.pushState({}, '', url);
    }

    /**
     * API Methods
     */
    
    // Generic API call
    async apiCall(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'X-CSRF-Token': this.csrfToken,
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('API Call Error:', error);
            throw error;
        }
    }

    // GET request
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        return this.apiCall(fullUrl, { method: 'GET' });
    }

    // POST request
    async post(url, data = {}) {
        return this.apiCall(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    // PUT request
    async put(url, data = {}) {
        return this.apiCall(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    // DELETE request
    async delete(url) {
        return this.apiCall(url, { method: 'DELETE' });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.AaoSikheSystemApp = new AaoSikheSystemApp();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AaoSikheSystemApp;
}