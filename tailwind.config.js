/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './*.php',
    './views/**/*.php',
    './assets/js/**/*.js'
  ],
  theme: {
    extend: {
      colors: {
        accent: 'var(--accent)',
        'accent-2': 'var(--accent-2)',
        danger: 'var(--danger)',
        'status-green': 'var(--status-green)',
        'status-amber': 'var(--status-amber)',
        'status-red': 'var(--status-red)',
        'muted-ui': 'var(--muted)',
        'bg-raised': 'var(--bg-raised)',
        panel: 'var(--panel)',
        border: 'var(--border)'
      }
    }
  },
  plugins: []
};
