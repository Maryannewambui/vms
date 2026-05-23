    <!-- Footer Scripts -->
    <script>
        // Utility functions
        const VMS = {
            // Show loading overlay
            showLoading: function() {
                document.getElementById('loading-overlay').classList.remove('hidden');
            },

            // Hide loading overlay
            hideLoading: function() {
                document.getElementById('loading-overlay').classList.add('hidden');
            },

            // Show toast notification
            showToast: function(message, type = 'info', duration = 5000) {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');

                const colors = {
                    success: 'bg-green-500',
                    error: 'bg-red-500',
                    warning: 'bg-yellow-500',
                    info: 'bg-blue-500'
                };

                const icons = {
                    success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
                    error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                    warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                    info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                };

                toast.className = `toast-enter flex items-center px-4 py-3 rounded-lg shadow-lg text-white ${colors[type]}`;
                toast.innerHTML = `
                    <span class="mr-2">${icons[type]}</span>
                    <span>${message}</span>
                    <button onclick="this.parentElement.remove()" class="ml-4 hover:opacity-70">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                `;

                container.appendChild(toast);

                // Auto remove after duration
                setTimeout(() => {
                    toast.classList.add('toast-exit');
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            },

            // Confirm dialog
            confirm: function(message, callback) {
                if (confirm(message)) {
                    callback();
                }
            },

            // Format date
            formatDate: function(date, format = 'medium') {
                const options = {
                    short: { month: 'numeric', day: 'numeric' },
                    medium: { month: 'short', day: 'numeric', year: 'numeric' },
                    long: { month: 'long', day: 'numeric', year: 'numeric' },
                    datetime: { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }
                };
                return new Date(date).toLocaleDateString('en-US', options[format]);
            },

            // Format time
            formatTime: function(time) {
                return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            // Escape HTML
            escapeHtml: function(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },

            // Open modal
            openModal: function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('hidden');
                    setTimeout(() => modal.querySelector('.modal-content').classList.add('animate-slide-up'), 10);
                }
            },

            // Close modal
            closeModal: function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.querySelector('.modal-content').classList.remove('animate-slide-up');
                    setTimeout(() => modal.classList.add('hidden'), 300);
                }
            },

            // Print element
            printElement: function(elementId) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.classList.add('print-badge');
                    window.print();
                    element.classList.remove('print-badge');
                }
            }
        };

        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return true;

            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            return isValid;
        }

        // Close sidebar on mobile
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.add('-translate-x-full');
            }
        }

        // Handle session timeout warning (30 min)
        let sessionTimeout;
        function resetSessionTimer() {
            clearTimeout(sessionTimeout);
            sessionTimeout = setTimeout(() => {
                VMS.showToast('Your session will expire soon. Please save your work.', 'warning', 10000);
            }, 25 * 60 * 1000); // 25 minutes
        }

        // Reset timer on user activity
        document.addEventListener('mousemove', resetSessionTimer);
        document.addEventListener('keypress', resetSessionTimer);
        resetSessionTimer();

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape to close modals
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal:not(.hidden)');
                if (openModal) {
                    VMS.closeModal(openModal.id);
                }
            }
        });

        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert-auto-hide').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>
