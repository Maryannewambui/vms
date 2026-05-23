<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Visitor Management System - Pipe Manufacturing Company">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?>PipeVMS - Visitor Management System</title>
    <base href="<?= APP_URL ?>/">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        },
                        industrial: {
                            light: '#f1f5f9',
                            DEFAULT: '#e2e8f0',
                            dark: '#cbd5e1',
                        },
                        safety: {
                            low: '#22c55e',
                            medium: '#eab308',
                            high: '#f97316',
                            critical: '#ef4444',
                        }
                    }
                }
            }
        }
    </script>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>

    <!-- Signature Pad Library -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Base Styles -->
    <style type="text/tailwindcss">
        @layer utilities {
            .animate-fade-in {
                animation: fadeIn 0.3s ease-in-out;
            }
            .animate-slide-up {
                animation: slideUp 0.3s ease-out;
            }
            .animate-slide-down {
                animation: slideDown 0.3s ease-out;
            }
            .animate-pulse-border {
                animation: pulseBorder 2s infinite;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes pulseBorder {
            0%, 100% { border-color: theme('colors.primary.500'); }
            50% { border-color: theme('colors.primary.300'); }
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Toast animations */
        .toast-enter {
            animation: toastEnter 0.3s ease-out;
        }
        .toast-exit {
            animation: toastExit 0.3s ease-in forwards;
        }
        @keyframes toastEnter {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes toastExit {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Badge print styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .print-badge, .print-badge * {
                visibility: visible;
            }
            .print-badge {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }

        /* Modal backdrop */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        /* Table hover effect */
        .table-row-hover:hover {
            background-color: #f8fafc;
        }

        /* Card hover effect */
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        /* Sidebar transition */
        .sidebar-transition {
            transition: width 0.3s ease, transform 0.3s ease;
        }

        /* Loading spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #f97316;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-white bg-opacity-80 z-50 hidden flex items-center justify-center">
        <div class="text-center">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-slate-600">Loading...</p>
        </div>
    </div>

    <?php
    // Auto-include navigation when user is logged in so module pages that only include header show nav
    if (function_exists('isLoggedIn') && isLoggedIn()) {
        include_once __DIR__ . '/sidebar.php';
        include_once __DIR__ . '/topnav.php';
    }
    ?>
