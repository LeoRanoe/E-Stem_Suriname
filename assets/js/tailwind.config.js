/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.php',
    './src/**/*.{js,jsx,ts,tsx}',
    './pages/**/*.{js,jsx,ts,tsx,php}',
    './include/**/*.php',
    './admin/**/*.php',
    './voter/**/*.php',
    './vote/**/*.php',
  ],
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
      backgroundImage: {
        'suriname-pattern': "url('/E-Stem_Suriname/assets/Images/placeholders/suriname-pattern.png')",
        'suriname-flag': "url('/E-Stem_Suriname/assets/Images/placeholders/suriname-flag.png')",
        'suriname-nature': "url('/E-Stem_Suriname/assets/Images/placeholders/suriname-nature.png')",
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
  plugins: [],
} 