document.addEventListener('DOMContentLoaded', function () {
  console.log('news-toggle.js loaded');
  const toggles = document.querySelectorAll('.news-toggle');
  console.log('Found ' + toggles.length + ' news-toggle buttons');
  toggles.forEach(button => {
    button.addEventListener('click', () => {
      console.log('news-toggle button clicked');
      const container = button.closest('div.flex.flex-col');
      const preview = container.querySelector('.news-preview');
      const full = container.querySelector('.news-full');
      const card = button.closest('.sr-card-basic');
      if (full.classList.contains('hidden')) {
        full.classList.remove('hidden');
        preview.classList.add('hidden');
        card.classList.add('news-expanded');
        button.textContent = 'Lees minder';
      } else {
        full.classList.add('hidden');
        preview.classList.remove('hidden');
        card.classList.remove('news-expanded');
        button.textContent = 'Lees meer >';
      }
    });
  });
});
