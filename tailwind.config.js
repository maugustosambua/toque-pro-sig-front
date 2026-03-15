module.exports = {
  content: [
    './core/**/*.php',
    './helpers/**/*.php',
    './modules/**/*.php',
    './assets/js/**/*.js'
  ],
  theme: {
    extend: {
      colors: {
        tps: {
          50: '#f4f8fb',
          100: '#e8f0f8',
          200: '#d0dfef',
          300: '#a8c4df',
          400: '#6d96bf',
          500: '#3b74aa',
          600: '#0f5ea8',
          700: '#0d4f8f',
          800: '#123f67',
          900: '#142231'
        }
      },
      boxShadow: {
        panel: '0 10px 30px rgba(15, 23, 42, 0.08)'
      }
    }
  },
  plugins: []
};
