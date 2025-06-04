<?php
/**
 * Centralized Styles for E-Stem Suriname
 * This file contains all the necessary CSS and Tailwind configurations
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Include Tailwind configuration -->
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        'suriname': {
          'green': '#007847',
          'green-dark': '#006241',
          'red': '#C8102E',
          'red-dark': '#a50d26',
          'yellow': '#FFD100',
          'yellow-dark': '#E6BC00',
        },
      },
      fontFamily: {
        'sans': ['Poppins', 'ui-sans-serif', 'system-ui'],
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-out',
        'slide-in': 'slideIn 0.3s ease-out',
        'bounce-subtle': 'bounce 2s infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(-10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideIn: {
          '0%': { transform: 'translateX(-20px)', opacity: '0' },
          '100%': { transform: 'translateX(0)', opacity: '1' },
        },
        bounce: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-5px)' },
        },
      },
      boxShadow: {
        'suriname': '0 4px 6px rgba(0, 120, 71, 0.1), 0 2px 4px rgba(0, 120, 71, 0.06)',
        'suriname-lg': '0 10px 15px rgba(0, 120, 71, 0.1), 0 4px 6px rgba(0, 120, 71, 0.05)',
      },
    },
  },
}
</script>

<!-- Common CSS styles -->
<style>
  /* Custom Scrollbar */
  ::-webkit-scrollbar {
    width: 8px;
  }
  ::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
  }
  ::-webkit-scrollbar-thumb {
    background: #007847;
    border-radius: 4px;
  }
  ::-webkit-scrollbar-thumb:hover {
    background: #006241;
  }

  /* Table Hover Effect */
  .hover-row:hover td {
    background-color: rgba(0, 120, 71, 0.05);
    transform: scale(1.01);
    transition: all 0.2s ease;
  }

  /* Button Hover Effect */
  .btn-hover {
    transition: all 0.3s ease;
  }
  .btn-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  /* Suriname-styled components */
  .sr-card {
    @apply bg-white rounded-lg shadow-md border-l-4 border-suriname-green p-4;
  }

  .sr-btn-primary {
    @apply bg-suriname-green hover:bg-suriname-green-dark text-white font-medium py-2 px-4 rounded-md transition-all duration-300 shadow hover:shadow-md transform hover:-translate-y-1;
  }

  .sr-btn-secondary {
    @apply bg-white border border-suriname-green text-suriname-green hover:bg-suriname-green hover:text-white font-medium py-2 px-4 rounded-md transition-all duration-300 shadow hover:shadow-md transform hover:-translate-y-1;
  }

  .sr-btn-danger {
    @apply bg-suriname-red hover:bg-suriname-red-dark text-white font-medium py-2 px-4 rounded-md transition-all duration-300 shadow hover:shadow-md transform hover:-translate-y-1;
  }

  .sr-input {
    @apply border-gray-300 focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-20 rounded-md shadow-sm;
  }

  .sr-pattern-dots {
    background-image: radial-gradient(rgba(0, 120, 71, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
  }

  .sr-pattern-stripes {
    background: repeating-linear-gradient(
      45deg,
      rgba(0, 120, 71, 0.03),
      rgba(0, 120, 71, 0.03) 10px,
      rgba(0, 120, 71, 0.06) 10px,
      rgba(0, 120, 71, 0.06) 20px
    );
  }

  .sr-hero-bg {
    background: linear-gradient(135deg, rgba(0, 120, 71, 0.95), rgba(0, 98, 65, 0.95));
    background-size: cover;
    background-position: center;
  }
</style> 