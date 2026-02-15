/**
 * InfinityFree Platform - Main JavaScript
 */

(function() {
    'use strict';
    
    // ============================================
    // DOM Ready
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        initMobileMenu();
        initFormValidation();
        initTooltips();
    });
    
    // ============================================
    // Mobile Menu Toggle
    // ============================================
    function initMobileMenu() {
        const menuToggle = document.querySelector('.menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        
        if (menuToggle && navMenu) {
            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('active');
            });
        }
    }
    
    // ============================================
    // Form Validation
    // ============================================
    function initFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }
    
    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        
        inputs.forEach(function(input) {
            if (!input.value.trim()) {
                showError(input, 'Ce champ est requis');
                isValid = false;
            } else {
                clearError(input);
            }
            
            // Email validation
            if (input.type === 'email' && input.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(input.value)) {
                    showError(input, 'Email invalide');
                    isValid = false;
                }
            }
            
            // Password confirmation
            if (input.name === 'password_confirm') {
                const password = form.querySelector('input[name="password"]');
                if (password && input.value !== password.value) {
                    showError(input, 'Les mots de passe ne correspondent pas');
                    isValid = false;
                }
            }
        });
        
        return isValid;
    }
    
    function showError(input, message) {
        clearError(input);
        
        input.classList.add('error');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }
    
    function clearError(input) {
        input.classList.remove('error');
        const errorMessage = input.parentNode.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    }
    
    // ============================================
    // Tooltips
    // ============================================
    function initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(function(element) {
            element.addEventListener('mouseenter', showTooltip);
            element.addEventListener('mouseleave', hideTooltip);
        });
    }
    
    function showTooltip(e) {
        const text = this.getAttribute('data-tooltip');
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        document.body.appendChild(tooltip);
        
        const rect = this.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    }
    
    function hideTooltip() {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }
    
    // ============================================
    // AJAX Helper
    // ============================================
    window.ajax = function(url, options) {
        return fetch(url, {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: options.data ? JSON.stringify(options.data) : null
        })
        .then(response => response.json())
        .catch(error => {
            console.error('AJAX Error:', error);
            throw error;
        });
    };
    
    // ============================================
    // Show Notification
    // ============================================
    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    };
    
    // ============================================
    // Confirm Dialog
    // ============================================
    window.confirmAction = function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    };
    
})();
