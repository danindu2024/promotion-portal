/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          500: '#0056b3',
          600: '#004494',
          DEFAULT: '#0056b3',
        },
        secondary: '#6c757d',
        success: '#28a745',
        danger: '#dc3545',
        background: '#f8f9fa',
      },
      fontFamily: {
        sans: ['Inter', 'Roboto', 'sans-serif'],
        mono: ['Roboto Mono', 'monospace'],
      },
    },
  },
  plugins: [],
}
