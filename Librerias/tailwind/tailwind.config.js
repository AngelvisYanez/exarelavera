/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    '../../administrador/**/*.{php,js,html}',
    '../../auditoria/**/*.{php,js,html}',
    '../../mascaras/**/*.{php,js,html}',
    '../../skins/**/*.{php,js,html}',
    '../../relavera/**/*.{php,js,html}'
  ],
  theme: {
    extend: {}
  },
  plugins: [],
  corePlugins: {
    preflight: false
  }
};
