module.exports = {
  content: [
    "./class-admin-page.php",
    "./js/aa-admin-scripts.js"],
  theme: {
    extend: {
      boxShadow: {
        'aa': '3px 5px 10px 1px rgba(0, 0, 0, 0.1)',
      },
      colors: {
        'qs-gray': '#2e363f',
      },
      zIndex: {
        '9999': '9999',
      }
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
}
